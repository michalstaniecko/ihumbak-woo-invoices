<?php
/**
 * Document Repository.
 *
 * @package IHumbak\Invoices\Infrastructure\Database
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Infrastructure\Database;

use IHumbak\Invoices\Models\Document;
use IHumbak\Invoices\Models\Invoice;
use IHumbak\Invoices\Models\Receipt;
use IHumbak\Invoices\Models\CreditNote;
use IHumbak\Invoices\Core\Installer;

/**
 * Repository for document CRUD operations.
 */
class DocumentRepository {

	/**
	 * WordPress database instance.
	 *
	 * @var \wpdb
	 */
	private \wpdb $wpdb;

	/**
	 * Documents table name.
	 *
	 * @var string
	 */
	private string $table;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb  = $wpdb;
		$this->table = Installer::get_documents_table();
	}

	/**
	 * Find document by ID.
	 *
	 * @param int $id Document ID.
	 * @return Document|null
	 */
	public function find( int $id ): ?Document {
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE id = %d",
				$id
			),
			ARRAY_A
		);

		if ( ! $row ) {
			return null;
		}

		return $this->hydrate( $row );
	}

	/**
	 * Find document by document number.
	 *
	 * @param string $number Document number.
	 * @return Document|null
	 */
	public function findByNumber( string $number ): ?Document {
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE document_number = %s",
				$number
			),
			ARRAY_A
		);

		if ( ! $row ) {
			return null;
		}

		return $this->hydrate( $row );
	}

	/**
	 * Find documents by order ID.
	 *
	 * @param int $order_id WooCommerce order ID.
	 * @return Document[]
	 */
	public function findByOrderId( int $order_id ): array {
		$rows = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE order_id = %d ORDER BY created_at DESC",
				$order_id
			),
			ARRAY_A
		);

		return array_map( array( $this, 'hydrate' ), $rows ?: array() );
	}

	/**
	 * Find all documents with filters.
	 *
	 * @param array<string, mixed> $filters Filter parameters.
	 * @param int                  $limit   Limit.
	 * @param int                  $offset  Offset.
	 * @return Document[]
	 */
	public function findAll( array $filters = array(), int $limit = 20, int $offset = 0 ): array {
		$where = $this->buildWhereClause( $filters );
		$sql   = "SELECT * FROM {$this->table} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d";

		$rows = $this->wpdb->get_results(
			$this->wpdb->prepare( $sql, $limit, $offset ),
			ARRAY_A
		);

		return array_map( array( $this, 'hydrate' ), $rows ?: array() );
	}

	/**
	 * Count documents with filters.
	 *
	 * @param array<string, mixed> $filters Filter parameters.
	 * @return int
	 */
	public function count( array $filters = array() ): int {
		$where = $this->buildWhereClause( $filters );
		$sql   = "SELECT COUNT(*) FROM {$this->table} {$where}";

		return (int) $this->wpdb->get_var( $sql );
	}

	/**
	 * Save document (insert or update).
	 *
	 * @param Document $document Document to save.
	 * @return int Document ID.
	 * @throws \RuntimeException When save operation fails.
	 */
	public function save( Document $document ): int {
		$data = $this->prepareData( $document );

		if ( $document->getId() ) {
			// Update existing.
			$result = $this->wpdb->update(
				$this->table,
				$data,
				array( 'id' => $document->getId() ),
				$this->getDataFormats( $data ),
				array( '%d' )
			);

			if ( false === $result ) {
				throw new \RuntimeException(
					sprintf(
						/* translators: 1: Document ID, 2: Database error message */
						esc_html__( 'Failed to update document ID %1$d: %2$s', 'ihumbak-invoices' ),
						absint( $document->getId() ),
						esc_html( $this->wpdb->last_error )
					)
				);
			}

			return $document->getId();
		}

		// Insert new.
		$result = $this->wpdb->insert(
			$this->table,
			$data,
			$this->getDataFormats( $data )
		);

		if ( false === $result ) {
			throw new \RuntimeException(
				sprintf(
					/* translators: %s: Database error message */
					esc_html__( 'Failed to insert document: %s', 'ihumbak-invoices' ),
					esc_html( $this->wpdb->last_error )
				)
			);
		}

		$id = (int) $this->wpdb->insert_id;

		if ( 0 === $id ) {
			throw new \RuntimeException( esc_html__( 'Failed to get inserted document ID.', 'ihumbak-invoices' ) );
		}

		$document->setId( $id );

		return $id;
	}

	/**
	 * Delete document.
	 *
	 * @param int $id Document ID.
	 * @return bool
	 */
	public function delete( int $id ): bool {
		$result = $this->wpdb->delete(
			$this->table,
			array( 'id' => $id ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Update document status.
	 *
	 * @param int    $id     Document ID.
	 * @param string $status New status.
	 * @return bool
	 */
	public function updateStatus( int $id, string $status ): bool {
		$result = $this->wpdb->update(
			$this->table,
			array( 'status' => $status ),
			array( 'id' => $id ),
			array( '%s' ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Find credit notes by corrected document ID.
	 *
	 * @param int $corrected_document_id Original document ID.
	 * @return CreditNote[]
	 */
	public function findByCorrectedDocumentId( int $corrected_document_id ): array {
		$rows = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE corrected_document_id = %d AND document_type = %s ORDER BY created_at DESC",
				$corrected_document_id,
				'credit_note'
			),
			ARRAY_A
		);

		$documents = array_map( array( $this, 'hydrate' ), $rows ?: array() );

		// Filter to ensure only CreditNote instances are returned.
		return array_values(
			array_filter(
				$documents,
				static fn( Document $doc ): bool => $doc instanceof CreditNote
			)
		);
	}

	/**
	 * Hydrate document from database row.
	 *
	 * @param array<string, mixed> $row Database row.
	 * @return Document
	 */
	private function hydrate( array $row ): Document {
		$type = $row['document_type'] ?? 'invoice';

		return match ( $type ) {
			'receipt'     => Receipt::fromArray( $row ),
			'credit_note' => CreditNote::fromArray( $row ),
			default       => Invoice::fromArray( $row ),
		};
	}

	/**
	 * Prepare data for database.
	 *
	 * @param Document $document Document.
	 * @return array<string, mixed>
	 */
	private function prepareData( Document $document ): array {
		$data = array(
			'order_id'              => $document->getOrderId(),
			'document_type'         => $document->getDocumentType(),
			'document_number'       => $document->getDocumentNumber(),
			'issue_date'            => $document->getIssueDate()?->format( 'Y-m-d' ),
			'sale_date'             => $document->getSaleDate()?->format( 'Y-m-d' ),
			'due_date'              => $document->getDueDate()?->format( 'Y-m-d' ),
			'corrected_document_id' => $document->getCorrectedDocumentId(),
			'buyer_data'            => $document->getBuyer()?->toJson() ?? '{}',
			'seller_data'           => $document->getSeller()?->toJson() ?? '{}',
			'subtotal'              => $document->getSubtotal(),
			'tax_total'             => $document->getTaxTotal(),
			'total'                 => $document->getTotal(),
			'currency'              => $document->getCurrency(),
			'status'                => $document->getStatus(),
			'pdf_path'              => $document->getPdfPath(),
			'notes'                 => $document->getNotes(),
		);

		// Add invoice specific fields.
		if ( $document instanceof Invoice ) {
			$data['payment_method']       = $document->getPaymentMethod();
			$data['payment_method_id']    = $document->getPaymentMethodId();
			$data['payment_method_title'] = $document->getPaymentMethodTitle();
		}

		// Add credit note specific fields.
		if ( $document instanceof CreditNote ) {
			$data['correction_reason'] = $document->getCorrectionReason();
			$data['correction_type']   = $document->getCorrectionType();
			$data['refund_id']         = $document->getRefundId();
		}

		return $data;
	}

	/**
	 * Get data formats for wpdb.
	 *
	 * @param array<string, mixed> $data Data array.
	 * @return array<string>
	 */
	private function getDataFormats( array $data ): array {
		$formats = array();
		foreach ( $data as $key => $value ) {
			if ( is_int( $value ) ) {
				$formats[] = '%d';
			} elseif ( is_float( $value ) ) {
				$formats[] = '%f';
			} else {
				$formats[] = '%s';
			}
		}
		return $formats;
	}

	/**
	 * Build WHERE clause from filters.
	 *
	 * @param array<string, mixed> $filters Filters.
	 * @return string
	 */
	private function buildWhereClause( array $filters ): string {
		$conditions = array();

		if ( ! empty( $filters['document_type'] ) ) {
			$conditions[] = $this->wpdb->prepare( 'document_type = %s', $filters['document_type'] );
		}

		if ( ! empty( $filters['status'] ) ) {
			$conditions[] = $this->wpdb->prepare( 'status = %s', $filters['status'] );
		}

		if ( ! empty( $filters['order_id'] ) ) {
			$conditions[] = $this->wpdb->prepare( 'order_id = %d', $filters['order_id'] );
		}

		if ( ! empty( $filters['date_from'] ) ) {
			$conditions[] = $this->wpdb->prepare( 'issue_date >= %s', $filters['date_from'] );
		}

		if ( ! empty( $filters['date_to'] ) ) {
			$conditions[] = $this->wpdb->prepare( 'issue_date <= %s', $filters['date_to'] );
		}

		if ( ! empty( $filters['search'] ) ) {
			$search       = '%' . $this->wpdb->esc_like( $filters['search'] ) . '%';
			$conditions[] = $this->wpdb->prepare(
				'(document_number LIKE %s OR buyer_data LIKE %s)',
				$search,
				$search
			);
		}

		if ( empty( $conditions ) ) {
			return '';
		}

		return 'WHERE ' . implode( ' AND ', $conditions );
	}
}
