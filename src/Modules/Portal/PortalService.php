<?php
/**
 * Portal Service.
 *
 * Business logic for customer document access in My Account portal.
 *
 * @package IHumbak\Invoices\Modules\Portal
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Modules\Portal;

use IHumbak\Invoices\Infrastructure\Database\DocumentRepository;
use IHumbak\Invoices\Models\Document;

/**
 * Service for customer portal document operations.
 */
class PortalService {

	/**
	 * Visible document statuses for customers.
	 *
	 * @var string[]
	 */
	private const VISIBLE_STATUSES = array(
		Document::STATUS_ISSUED,
		Document::STATUS_SENT,
		Document::STATUS_PAID,
	);

	/**
	 * Document repository.
	 *
	 * @var DocumentRepository
	 */
	private DocumentRepository $document_repository;

	/**
	 * Constructor.
	 *
	 * @param DocumentRepository|null $document_repository Document repository instance.
	 */
	public function __construct( ?DocumentRepository $document_repository = null ) {
		$this->document_repository = $document_repository ?? new DocumentRepository();
	}

	/**
	 * Get all documents for a customer.
	 *
	 * Retrieves documents from all orders belonging to the customer.
	 *
	 * @param int $customer_id WordPress user ID.
	 * @return Document[]
	 */
	public function getDocumentsForCustomer( int $customer_id ): array {
		$order_ids = $this->getCustomerOrderIds( $customer_id );

		if ( empty( $order_ids ) ) {
			return array();
		}

		$documents = array();
		foreach ( $order_ids as $order_id ) {
			$order_documents = $this->document_repository->findByOrderId( $order_id );
			$documents       = array_merge( $documents, $order_documents );
		}

		// Filter visible documents and sort by issue date descending.
		$visible = $this->filterVisibleDocuments( $documents );
		usort(
			$visible,
			static fn( Document $a, Document $b ): int =>
				( $b->getIssueDate()?->getTimestamp() ?? 0 ) <=> ( $a->getIssueDate()?->getTimestamp() ?? 0 )
		);

		return $visible;
	}

	/**
	 * Get documents for a specific order.
	 *
	 * @param int $order_id WooCommerce order ID.
	 * @return Document[]
	 */
	public function getDocumentsForOrder( int $order_id ): array {
		$documents = $this->document_repository->findByOrderId( $order_id );

		return $this->filterVisibleDocuments( $documents );
	}

	/**
	 * Check if customer can access a specific document.
	 *
	 * @param int $customer_id WordPress user ID.
	 * @param int $document_id Document ID.
	 * @return bool
	 */
	public function canCustomerAccessDocument( int $customer_id, int $document_id ): bool {
		$document = $this->document_repository->find( $document_id );

		if ( ! $document ) {
			return false;
		}

		// Document must be visible (not draft or cancelled).
		if ( ! $this->isDocumentVisible( $document ) ) {
			return false;
		}

		// Verify customer owns the order.
		$order_id = $document->getOrderId();
		if ( ! $order_id ) {
			return false;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return false;
		}

		return (int) $order->get_customer_id() === $customer_id;
	}

	/**
	 * Get document by ID if customer has access.
	 *
	 * @param int $customer_id WordPress user ID.
	 * @param int $document_id Document ID.
	 * @return Document|null
	 */
	public function getDocumentForCustomer( int $customer_id, int $document_id ): ?Document {
		if ( ! $this->canCustomerAccessDocument( $customer_id, $document_id ) ) {
			return null;
		}

		return $this->document_repository->find( $document_id );
	}

	/**
	 * Filter documents to show only visible ones.
	 *
	 * Hides draft and cancelled documents from customers.
	 *
	 * @param Document[] $documents Documents to filter.
	 * @return Document[]
	 */
	private function filterVisibleDocuments( array $documents ): array {
		return array_values(
			array_filter(
				$documents,
				fn( Document $document ): bool => $this->isDocumentVisible( $document )
			)
		);
	}

	/**
	 * Check if document is visible to customers.
	 *
	 * @param Document $document Document to check.
	 * @return bool
	 */
	private function isDocumentVisible( Document $document ): bool {
		return in_array( $document->getStatus(), self::VISIBLE_STATUSES, true );
	}

	/**
	 * Get all order IDs for a customer.
	 *
	 * @param int $customer_id WordPress user ID.
	 * @return int[]
	 */
	private function getCustomerOrderIds( int $customer_id ): array {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return array();
		}

		$orders = wc_get_orders(
			array(
				'customer' => $customer_id,
				'return'   => 'ids',
				'limit'    => -1,
			)
		);

		// Cast to int[] - wc_get_orders returns int[] when 'return' => 'ids'.
		return array_map(
			static function ( $order ): int {
				if ( $order instanceof \WC_Order ) {
					return $order->get_id();
				}
				return (int) $order;
			},
			$orders
		);
	}
}
