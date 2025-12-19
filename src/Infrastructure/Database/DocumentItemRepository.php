<?php
/**
 * Document Item Repository.
 *
 * @package IHumbak\Invoices\Infrastructure\Database
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Infrastructure\Database;

use IHumbak\Invoices\Models\DocumentItem;
use IHumbak\Invoices\Core\Installer;

/**
 * Repository for document item CRUD operations.
 */
class DocumentItemRepository {

	/**
	 * WordPress database instance.
	 *
	 * @var \wpdb
	 */
	private \wpdb $wpdb;

	/**
	 * Document items table name.
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
		$this->table = Installer::get_document_items_table();
	}

	/**
	 * Find item by ID.
	 *
	 * @param int $id Item ID.
	 * @return DocumentItem|null
	 */
	public function find( int $id ): ?DocumentItem {
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

		return DocumentItem::fromArray( $row );
	}

	/**
	 * Find all items for a document.
	 *
	 * @param int $document_id Document ID.
	 * @return DocumentItem[]
	 */
	public function findByDocumentId( int $document_id ): array {
		$rows = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE document_id = %d ORDER BY id ASC",
				$document_id
			),
			ARRAY_A
		);

		return array_map(
			fn( $row ) => DocumentItem::fromArray( $row ),
			$rows ?: array()
		);
	}

	/**
	 * Save item (insert or update).
	 *
	 * @param DocumentItem $item Item to save.
	 * @return int Item ID.
	 */
	public function save( DocumentItem $item ): int {
		$data = array(
			'document_id'      => $item->getDocumentId(),
			'product_id'       => $item->getProductId(),
			'name'             => $item->getName(),
			'quantity'         => $item->getQuantity(),
			'unit'             => $item->getUnit(),
			'unit_price_net'   => $item->getUnitPriceNet(),
			'unit_price_gross' => $item->getUnitPriceGross(),
			'tax_rate'         => $item->getTaxRate(),
			'tax_amount'       => $item->getTaxAmount(),
			'line_total_net'   => $item->getLineTotalNet(),
			'line_total_gross' => $item->getLineTotalGross(),
		);

		$formats = array( '%d', '%d', '%s', '%f', '%s', '%f', '%f', '%f', '%f', '%f', '%f' );

		if ( $item->getId() ) {
			// Update existing.
			$this->wpdb->update(
				$this->table,
				$data,
				array( 'id' => $item->getId() ),
				$formats,
				array( '%d' )
			);
			return $item->getId();
		}

		// Insert new.
		$this->wpdb->insert( $this->table, $data, $formats );

		$id = (int) $this->wpdb->insert_id;
		$item->setId( $id );

		return $id;
	}

	/**
	 * Save multiple items for a document.
	 *
	 * Uses database transaction to ensure atomicity of delete + insert operations.
	 *
	 * @param int            $document_id Document ID.
	 * @param DocumentItem[] $items       Items to save.
	 * @return void
	 * @throws \Exception If transaction fails.
	 */
	public function saveItems( int $document_id, array $items ): void {
		// Start transaction.
		$this->wpdb->query( 'START TRANSACTION' );

		try {
			// Delete existing items.
			$this->deleteByDocumentId( $document_id );

			// Insert new items.
			foreach ( $items as $item ) {
				$item->setDocumentId( $document_id );
				$this->save( $item );
			}

			// Commit transaction.
			$this->wpdb->query( 'COMMIT' );
		} catch ( \Exception $e ) {
			// Rollback on error.
			$this->wpdb->query( 'ROLLBACK' );
			throw $e;
		}
	}

	/**
	 * Delete item.
	 *
	 * @param int $id Item ID.
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
	 * Delete all items for a document.
	 *
	 * @param int $document_id Document ID.
	 * @return bool
	 */
	public function deleteByDocumentId( int $document_id ): bool {
		$result = $this->wpdb->delete(
			$this->table,
			array( 'document_id' => $document_id ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Count items for a document.
	 *
	 * @param int $document_id Document ID.
	 * @return int
	 */
	public function countByDocumentId( int $document_id ): int {
		return (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table} WHERE document_id = %d",
				$document_id
			)
		);
	}
}
