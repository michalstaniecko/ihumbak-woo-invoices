<?php
/**
 * Order Status Service.
 *
 * @package IHumbak\Invoices\Modules\Invoice
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Modules\Invoice;

use IHumbak\Invoices\Core\Plugin;
use IHumbak\Invoices\Models\Document;

/**
 * Service for handling automatic order status changes when documents are issued.
 */
class OrderStatusService {

	/**
	 * Document types that support automatic order status change.
	 *
	 * @var string[]
	 */
	private const SUPPORTED_DOCUMENT_TYPES = array(
		'invoice',
		'receipt',
	);

	/**
	 * Cached order status settings.
	 *
	 * @var array<string, mixed>|null
	 */
	private ?array $order_status_settings = null;

	/**
	 * Get order status settings from plugin configuration.
	 *
	 * Settings are cached for performance optimization.
	 *
	 * @return array<string, mixed> Order status settings array.
	 */
	private function getOrderStatusSettings(): array {
		if ( null === $this->order_status_settings ) {
			$settings                    = Plugin::get_instance()->get_settings();
			$this->order_status_settings = $settings['display']['order_status'] ?? array();
		}

		return $this->order_status_settings;
	}

	/**
	 * Check if automatic order status change feature is enabled.
	 *
	 * @return bool True if enabled.
	 */
	public function isEnabled(): bool {
		$order_status_settings = $this->getOrderStatusSettings();

		return ! empty( $order_status_settings['enabled'] );
	}

	/**
	 * Get the configured target status.
	 *
	 * @return string Target status (without 'wc-' prefix).
	 */
	public function getTargetStatus(): string {
		$order_status_settings = $this->getOrderStatusSettings();

		return $order_status_settings['target'] ?? 'completed';
	}

	/**
	 * Get available WooCommerce order statuses for settings dropdown.
	 *
	 * @return array<string, string> Array of status slug => label.
	 */
	public function getOrderStatuses(): array {
		if ( ! function_exists( 'wc_get_order_statuses' ) ) {
			return array();
		}

		$statuses  = wc_get_order_statuses();
		$formatted = array();

		foreach ( $statuses as $status => $label ) {
			// Remove 'wc-' prefix for storage.
			$slug               = str_replace( 'wc-', '', $status );
			$formatted[ $slug ] = $label;
		}

		return $formatted;
	}

	/**
	 * Check if order status should be changed for this document.
	 *
	 * @param Document $document The document being issued.
	 * @return bool True if status should be changed.
	 */
	public function shouldChangeStatus( Document $document ): bool {
		// Only supported document types.
		if ( ! in_array( $document->getDocumentType(), self::SUPPORTED_DOCUMENT_TYPES, true ) ) {
			return false;
		}

		// Must have an order ID.
		if ( ! $document->getOrderId() ) {
			return false;
		}

		// Must be enabled in settings.
		if ( ! $this->isEnabled() ) {
			return false;
		}

		$order_id = $document->getOrderId();

		/**
		 * Filter whether automatic order status change should happen.
		 *
		 * @since 0.5.0
		 *
		 * @param bool     $enabled   Whether status change is enabled.
		 * @param int      $order_id  WooCommerce order ID.
		 * @param Document $document  The document being issued.
		 */
		return apply_filters( 'ihumbak_order_status_change_enabled', true, $order_id, $document );
	}

	/**
	 * Get target status for a specific document.
	 *
	 * @param Document $document The document being issued.
	 * @return string Target status.
	 */
	public function getTargetStatusForDocument( Document $document ): string {
		$target_status = $this->getTargetStatus();
		$order_id      = $document->getOrderId() ?? 0;

		/**
		 * Filter the target order status.
		 *
		 * @since 0.5.0
		 *
		 * @param string   $status    Target order status.
		 * @param int      $order_id  WooCommerce order ID.
		 * @param Document $document  The document being issued.
		 */
		return apply_filters( 'ihumbak_order_status_change_target', $target_status, $order_id, $document );
	}

	/**
	 * Maybe change order status after document is issued.
	 *
	 * @param Document $document      The issued document.
	 * @param bool     $user_confirmed Whether user confirmed status change via checkbox.
	 * @return bool True if status was changed, false otherwise.
	 */
	public function maybeChangeOrderStatus( Document $document, bool $user_confirmed ): bool {
		// User must confirm via checkbox.
		if ( ! $user_confirmed ) {
			return false;
		}

		// Check if change should happen (includes order_id validation).
		if ( ! $this->shouldChangeStatus( $document ) ) {
			return false;
		}

		$order_id = $document->getOrderId();

		// Get the order.
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return false;
		}

		$new_status = $this->getTargetStatusForDocument( $document );
		$old_status = $order->get_status();

		// Don't change if already at target status.
		if ( $old_status === $new_status ) {
			return false;
		}

		/**
		 * Fires before order status is changed.
		 *
		 * @since 0.5.0
		 *
		 * @param int      $order_id   WooCommerce order ID.
		 * @param string   $new_status New status being set.
		 * @param Document $document   The issued document.
		 */
		do_action( 'ihumbak_before_order_status_change', $order_id, $new_status, $document );

		// Change the status.
		$order->update_status(
			$new_status,
			sprintf(
				/* translators: %s: document number */
				__( 'Order status changed after issuing document: %s', 'ihumbak-invoices' ),
				$document->getDocumentNumber()
			)
		);

		/**
		 * Fires after order status is changed.
		 *
		 * @since 0.5.0
		 *
		 * @param int      $order_id   WooCommerce order ID.
		 * @param string   $new_status New status that was set.
		 * @param string   $old_status Previous order status.
		 * @param Document $document   The issued document.
		 */
		do_action( 'ihumbak_after_order_status_change', $order_id, $new_status, $old_status, $document );

		return true;
	}

	/**
	 * Get default checkbox state for order status change.
	 *
	 * @param int|null $order_id WooCommerce order ID.
	 * @return bool Default checked state.
	 */
	public function getCheckboxDefault( ?int $order_id ): bool {
		// Default to checked if feature is enabled.
		$default = $this->isEnabled();

		/**
		 * Filter the default checkbox state for order status change.
		 *
		 * @since 0.5.0
		 *
		 * @param bool     $checked   Default checked state.
		 * @param int|null $order_id  WooCommerce order ID or null for new documents.
		 */
		return apply_filters( 'ihumbak_order_status_change_checkbox_default', $default, $order_id );
	}

	/**
	 * Get target status label for display.
	 *
	 * @return string Status label.
	 */
	public function getTargetStatusLabel(): string {
		$statuses      = $this->getOrderStatuses();
		$target_status = $this->getTargetStatus();

		return $statuses[ $target_status ] ?? $target_status;
	}
}
