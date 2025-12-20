<?php
/**
 * Document Controller.
 *
 * @package IHumbak\Invoices\Modules\Admin
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Modules\Admin;

use IHumbak\Invoices\Infrastructure\Database\DocumentRepository;
use IHumbak\Invoices\Infrastructure\Database\DocumentItemRepository;
use IHumbak\Invoices\Models\Document;
use IHumbak\Invoices\Models\Invoice;
use IHumbak\Invoices\Models\Receipt;
use IHumbak\Invoices\Models\DocumentItem;
use IHumbak\Invoices\Models\Buyer;
use IHumbak\Invoices\Models\Seller;
use IHumbak\Invoices\Modules\Invoice\NumberingService;
use IHumbak\Invoices\Core\Plugin;

/**
 * Handles document admin actions.
 */
class DocumentController {

	/**
	 * Document repository.
	 *
	 * @var DocumentRepository
	 */
	private DocumentRepository $document_repository;

	/**
	 * Document item repository.
	 *
	 * @var DocumentItemRepository
	 */
	private DocumentItemRepository $item_repository;

	/**
	 * Numbering service.
	 *
	 * @var NumberingService
	 */
	private NumberingService $numbering_service;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->document_repository = new DocumentRepository();
		$this->item_repository     = new DocumentItemRepository();
		$this->numbering_service   = new NumberingService();
	}

	/**
	 * Initialize controller hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_post_ihumbak_save_invoice', array( $this, 'handle_save_invoice' ) );
		add_action( 'admin_post_ihumbak_save_receipt', array( $this, 'handle_save_receipt' ) );
	}

	/**
	 * Render documents list page.
	 *
	 * @return void
	 */
	public function render_list_page(): void {
		$list_table = new DocumentListTable();
		$list_table->prepare_items();

		include IHUMBAK_INVOICES_PATH . 'templates/admin/documents-list.php';
	}

	/**
	 * Render invoice edit page.
	 *
	 * @param int|null $id Document ID (null for new).
	 * @return void
	 */
	public function render_invoice_edit( ?int $id = null ): void {
		$document = null;
		$items    = array();

		if ( $id ) {
			$document = $this->document_repository->find( $id );
			if ( $document ) {
				$items = $this->item_repository->findByDocumentId( $id );
			}
		}

		$settings    = Plugin::get_instance()->get_settings();
		$seller      = $document ? $document->getSeller()?->toArray() : ( $settings['seller'] ?? array() );
		$buyer       = $document ? $document->getBuyer()?->toArray() : array();
		$items       = array_map( fn( $item ) => $item->toArray(), $items );
		$next_number = $this->numbering_service->previewNextNumber(
			'invoice',
			$settings['numbering']['invoice_pattern'] ?? 'FV/{YYYY}/{MM}/{NNNN}',
			$settings['numbering']['reset_monthly'] ?? true
		);

		include IHUMBAK_INVOICES_PATH . 'templates/admin/invoice-edit.php';
	}

	/**
	 * Render receipt edit page.
	 *
	 * @param int|null $id Document ID (null for new).
	 * @return void
	 */
	public function render_receipt_edit( ?int $id = null ): void {
		$document = null;
		$items    = array();

		if ( $id ) {
			$document = $this->document_repository->find( $id );
			if ( $document ) {
				$items = $this->item_repository->findByDocumentId( $id );
			}
		}

		$settings    = Plugin::get_instance()->get_settings();
		$seller      = $document ? $document->getSeller()?->toArray() : ( $settings['seller'] ?? array() );
		$buyer       = $document ? $document->getBuyer()?->toArray() : array();
		$items       = array_map( fn( $item ) => $item->toArray(), $items );
		$next_number = $this->numbering_service->previewNextNumber(
			'receipt',
			$settings['numbering']['receipt_pattern'] ?? 'PAR/{YYYY}/{MM}/{NNNN}',
			$settings['numbering']['reset_monthly'] ?? true
		);

		include IHUMBAK_INVOICES_PATH . 'templates/admin/receipt-edit.php';
	}

	/**
	 * Handle save invoice action.
	 *
	 * @return void
	 */
	public function handle_save_invoice(): void {
		$this->handle_save( 'invoice' );
	}

	/**
	 * Handle save receipt action.
	 *
	 * @return void
	 */
	public function handle_save_receipt(): void {
		$this->handle_save( 'receipt' );
	}

	/**
	 * Handle document save.
	 *
	 * @param string $type Document type.
	 * @return void
	 */
	private function handle_save( string $type ): void {
		// Verify nonce.
		if ( ! isset( $_POST['ihumbak_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ihumbak_nonce'] ) ), 'ihumbak_save_document' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'ihumbak-invoices' ) );
		}

		// Check permissions.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'ihumbak-invoices' ) );
		}

		// Get document ID if editing.
		$document_id = isset( $_POST['document_id'] ) ? absint( $_POST['document_id'] ) : null;

		// Create or load document model.
		if ( $document_id ) {
			$document = $this->document_repository->find( $document_id );
			if ( ! $document ) {
				wp_die( esc_html__( 'Document not found.', 'ihumbak-invoices' ) );
			}
			if ( ! $document->canBeEdited() ) {
				wp_die( esc_html__( 'This document cannot be edited.', 'ihumbak-invoices' ) );
			}
		} else {
			$document = 'invoice' === $type ? new Invoice() : new Receipt();
		}

		try {
			// Set document data.
			$this->populate_document_from_request( $document );

			// Determine status based on action.
			$save_action = isset( $_POST['save_action'] ) ? sanitize_text_field( wp_unslash( $_POST['save_action'] ) ) : 'draft';

			if ( 'issue' === $save_action ) {
				// Generate document number on issue.
				if ( ! $document->getDocumentNumber() ) {
					$settings = Plugin::get_instance()->get_settings();
					$pattern  = 'invoice' === $type
						? ( $settings['numbering']['invoice_pattern'] ?? 'FV/{YYYY}/{MM}/{NNNN}' )
						: ( $settings['numbering']['receipt_pattern'] ?? 'PAR/{YYYY}/{MM}/{NNNN}' );

					$document->setDocumentNumber(
						$this->numbering_service->generateNumber(
							$type,
							$pattern,
							$settings['numbering']['reset_monthly'] ?? true
						)
					);
				}
				$document->setStatus( Document::STATUS_ISSUED );
			} else {
				$document->setStatus( Document::STATUS_DRAFT );
			}

			// Save document.
			$document_id = $this->document_repository->save( $document );

			// Save items.
			$items = $this->get_items_from_request();
			$this->item_repository->saveItems( $document_id, $items );

			// Fire action.
			do_action( 'ihumbak_document_saved', $document );

			if ( 'issue' === $save_action ) {
				do_action( 'ihumbak_document_issued', $document );
			}

			// Redirect back with success message.
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'    => 'ihumbak-invoices',
						'action'  => 'edit',
						'type'    => $type,
						'id'      => $document_id,
						'message' => 'saved',
					),
					admin_url( 'admin.php' )
				)
			);
			exit;

		} catch ( \Exception $e ) {
			// Store error in transient for display.
			set_transient(
				'ihumbak_save_error_' . get_current_user_id(),
				$e->getMessage(),
				60
			);

			// Redirect back to form with error.
			$redirect_args = array(
				'page'    => 'ihumbak-invoices',
				'action'  => $document_id ? 'edit' : 'new',
				'type'    => $type,
				'message' => 'error',
			);

			if ( $document_id ) {
				$redirect_args['id'] = $document_id;
			}

			wp_safe_redirect(
				add_query_arg( $redirect_args, admin_url( 'admin.php' ) )
			);
			exit;
		}
	}

	/**
	 * Populate document from request data.
	 *
	 * @param Document $document Document to populate.
	 * @return void
	 */
	private function populate_document_from_request( Document $document ): void {
        // phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in calling method.

		// Dates.
		if ( ! empty( $_POST['issue_date'] ) ) {
			$document->setIssueDate( new \DateTimeImmutable( sanitize_text_field( wp_unslash( $_POST['issue_date'] ) ) ) );
		}
		if ( ! empty( $_POST['sale_date'] ) ) {
			$document->setSaleDate( new \DateTimeImmutable( sanitize_text_field( wp_unslash( $_POST['sale_date'] ) ) ) );
		}
		if ( ! empty( $_POST['due_date'] ) ) {
			$document->setDueDate( new \DateTimeImmutable( sanitize_text_field( wp_unslash( $_POST['due_date'] ) ) ) );
		}

		// Order ID (can be empty).
		$order_id = isset( $_POST['order_id'] ) && '' !== $_POST['order_id']
			? absint( $_POST['order_id'] )
			: null;
		$document->setOrderId( $order_id );

		// Notes.
		if ( isset( $_POST['notes'] ) ) {
			$document->setNotes( sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) );
		}

		// Payment method (invoice only).
		if ( $document instanceof Invoice && ! empty( $_POST['payment_method'] ) ) {
			$document->setPaymentMethod( sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) );
		}

		// Seller.
		if ( isset( $_POST['seller'] ) && is_array( $_POST['seller'] ) ) {
			$seller_data = array_map( 'sanitize_text_field', wp_unslash( $_POST['seller'] ) );
			$document->setSeller( Seller::fromArray( $seller_data ) );
		}

		// Buyer.
		if ( isset( $_POST['buyer'] ) && is_array( $_POST['buyer'] ) ) {
			$buyer_data = array_map( 'sanitize_text_field', wp_unslash( $_POST['buyer'] ) );
			$document->setBuyer( Buyer::fromArray( $buyer_data ) );
		}

		// Totals - sanitize and cast to float.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Cast to float is sufficient sanitization.
		$document->setSubtotal( (float) wp_unslash( $_POST['subtotal'] ?? 0 ) );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Cast to float is sufficient sanitization.
		$document->setTaxTotal( (float) wp_unslash( $_POST['tax_total'] ?? 0 ) );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Cast to float is sufficient sanitization.
		$document->setTotal( (float) wp_unslash( $_POST['total'] ?? 0 ) );
		$document->setCurrency( 'PLN' );

        // phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Get document items from request.
	 *
	 * @return DocumentItem[]
	 */
	private function get_items_from_request(): array {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in calling method.

		if ( ! isset( $_POST['items'] ) || ! is_array( $_POST['items'] ) ) {
			return array();
		}

		$items = array();

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized per-field below.
		$raw_items = wp_unslash( $_POST['items'] );

		foreach ( $raw_items as $item_data ) {
			if ( ! is_array( $item_data ) || empty( $item_data['name'] ) ) {
				continue;
			}

			$item = new DocumentItem();
			$item->setName( sanitize_text_field( $item_data['name'] ?? '' ) );
			$item->setQuantity( (float) ( $item_data['quantity'] ?? 1 ) );
			$item->setUnit( sanitize_text_field( $item_data['unit'] ?? 'szt.' ) );
			$item->setUnitPriceNet( (float) ( $item_data['unit_price_net'] ?? 0 ) );
			$item->setUnitPriceGross( (float) ( $item_data['unit_price_gross'] ?? 0 ) );
			$item->setTaxRate( (float) ( $item_data['tax_rate'] ?? 23 ) );
			$item->setTaxAmount( (float) ( $item_data['tax_amount'] ?? 0 ) );
			$item->setLineTotalNet( (float) ( $item_data['line_total_net'] ?? 0 ) );
			$item->setLineTotalGross( (float) ( $item_data['line_total_gross'] ?? 0 ) );

			if ( ! empty( $item_data['product_id'] ) ) {
				$item->setProductId( absint( $item_data['product_id'] ) );
			}

			$items[] = $item;
		}

        // phpcs:enable WordPress.Security.NonceVerification.Missing

		return $items;
	}

	/**
	 * Handle document deletion.
	 *
	 * @param int $id Document ID.
	 * @return void
	 */
	public function handle_delete( int $id ): void {
		$document = $this->document_repository->find( $id );

		if ( ! $document || ! $document->isDraft() ) {
			wp_die( esc_html__( 'Document cannot be deleted.', 'ihumbak-invoices' ) );
		}

		// Delete items first.
		$this->item_repository->deleteByDocumentId( $id );

		// Delete document.
		$this->document_repository->delete( $id );

		do_action( 'ihumbak_document_deleted', $id );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'ihumbak-invoices',
					'message' => 'deleted',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
