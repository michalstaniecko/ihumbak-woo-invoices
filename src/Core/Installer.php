<?php
/**
 * Database Installer.
 *
 * @package IHumbak\Invoices\Core
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Core;

/**
 * Handles database table creation and updates.
 */
class Installer {

	/**
	 * Database version.
	 *
	 * @var string
	 */
	private const DB_VERSION = '1.2.0';

	/**
	 * Option name for storing database version.
	 *
	 * @var string
	 */
	private const DB_VERSION_OPTION = 'ihumbak_invoices_db_version';

	/**
	 * Install database tables.
	 *
	 * @return void
	 */
	public function install(): void {
		$installed_version = get_option( self::DB_VERSION_OPTION, '0' );

		// Force migration if schema fix marker not set.
		$schema_fixed = get_option( 'ihumbak_schema_fix_101', false );
		if ( ! $schema_fixed ) {
			$this->run_migrations( '1.0.0' );
			update_option( 'ihumbak_schema_fix_101', true );
		}

		if ( version_compare( $installed_version, self::DB_VERSION, '<' ) ) {
			$this->create_tables();
			$this->run_migrations( $installed_version );
			update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
		}
	}

	/**
	 * Run database migrations.
	 *
	 * @param string $from_version Version to migrate from.
	 * @return void
	 */
	private function run_migrations( string $from_version ): void {
		global $wpdb;

		// Migration to 1.0.1: Make columns nullable.
		if ( version_compare( $from_version, '1.0.1', '<' ) ) {
			$table = self::get_documents_table();

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "ALTER TABLE {$table} MODIFY COLUMN order_id bigint(20) unsigned DEFAULT NULL" );
			$wpdb->query( "ALTER TABLE {$table} MODIFY COLUMN document_number varchar(50) DEFAULT NULL" );
			$wpdb->query( "ALTER TABLE {$table} MODIFY COLUMN issue_date date DEFAULT NULL" );
			$wpdb->query( "ALTER TABLE {$table} MODIFY COLUMN sale_date date DEFAULT NULL" );
			$wpdb->query( "ALTER TABLE {$table} MODIFY COLUMN buyer_data longtext DEFAULT NULL" );
			$wpdb->query( "ALTER TABLE {$table} MODIFY COLUMN seller_data longtext DEFAULT NULL" );
			// phpcs:enable
		}

		// Migration to 1.1.0: Add SKU column to document items.
		if ( version_compare( $from_version, '1.1.0', '<' ) ) {
			$items_table = self::get_document_items_table();

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$column_exists = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s',
					$wpdb->dbname,
					$items_table,
					'sku'
				)
			);

			if ( ! $column_exists ) {
				$wpdb->query( "ALTER TABLE {$items_table} ADD COLUMN sku varchar(100) DEFAULT '' AFTER name" );
			}
			// phpcs:enable
		}

		// Migration to 1.2.0: Add Credit Note columns.
		if ( version_compare( $from_version, '1.2.0', '<' ) ) {
			$table = self::get_documents_table();

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			// Add correction_reason column.
			$column_exists = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s',
					$wpdb->dbname,
					$table,
					'correction_reason'
				)
			);
			if ( ! $column_exists ) {
				$wpdb->query( "ALTER TABLE {$table} ADD COLUMN correction_reason TEXT DEFAULT NULL AFTER notes" );
			}

			// Add correction_type column.
			$column_exists = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s',
					$wpdb->dbname,
					$table,
					'correction_type'
				)
			);
			if ( ! $column_exists ) {
				$wpdb->query( "ALTER TABLE {$table} ADD COLUMN correction_type varchar(20) DEFAULT 'partial' AFTER correction_reason" );
			}

			// Add refund_id column with index.
			$column_exists = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s',
					$wpdb->dbname,
					$table,
					'refund_id'
				)
			);
			if ( ! $column_exists ) {
				$wpdb->query( "ALTER TABLE {$table} ADD COLUMN refund_id bigint(20) unsigned DEFAULT NULL AFTER correction_type" );
				$wpdb->query( "ALTER TABLE {$table} ADD INDEX refund_id (refund_id)" );
			}

			// phpcs:enable
		}
	}

	/**
	 * Create database tables.
	 *
	 * @return void
	 */
	private function create_tables(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$sql  = $this->get_documents_table_sql( $charset_collate );
		$sql .= $this->get_document_items_table_sql( $charset_collate );
		$sql .= $this->get_numbering_table_sql( $charset_collate );

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Get SQL for documents table.
	 *
	 * @param string $charset_collate Charset and collation.
	 * @return string
	 */
	private function get_documents_table_sql( string $charset_collate ): string {
		global $wpdb;

		$table_name = $wpdb->prefix . 'ihumbak_documents';

		return "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            order_id bigint(20) unsigned DEFAULT NULL,
            document_type varchar(20) NOT NULL DEFAULT 'invoice',
            document_number varchar(50) DEFAULT NULL,
            issue_date date DEFAULT NULL,
            sale_date date DEFAULT NULL,
            due_date date DEFAULT NULL,
            corrected_document_id bigint(20) unsigned DEFAULT NULL,
            buyer_data longtext DEFAULT NULL,
            seller_data longtext DEFAULT NULL,
            subtotal decimal(10,2) NOT NULL DEFAULT 0.00,
            tax_total decimal(10,2) NOT NULL DEFAULT 0.00,
            total decimal(10,2) NOT NULL DEFAULT 0.00,
            currency varchar(3) NOT NULL DEFAULT 'PLN',
            status varchar(20) NOT NULL DEFAULT 'draft',
            pdf_path varchar(255) DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY document_type (document_type),
            KEY document_number (document_number),
            KEY status (status),
            KEY issue_date (issue_date),
            KEY corrected_document_id (corrected_document_id)
        ) {$charset_collate};\n\n";
	}

	/**
	 * Get SQL for document items table.
	 *
	 * @param string $charset_collate Charset and collation.
	 * @return string
	 */
	private function get_document_items_table_sql( string $charset_collate ): string {
		global $wpdb;

		$table_name = $wpdb->prefix . 'ihumbak_document_items';

		return "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            document_id bigint(20) unsigned NOT NULL,
            product_id bigint(20) unsigned DEFAULT NULL,
            name varchar(255) NOT NULL,
            sku varchar(100) DEFAULT '',
            quantity decimal(10,3) NOT NULL DEFAULT 1.000,
            unit varchar(20) NOT NULL DEFAULT 'szt.',
            unit_price_net decimal(10,2) NOT NULL DEFAULT 0.00,
            unit_price_gross decimal(10,2) NOT NULL DEFAULT 0.00,
            tax_rate decimal(5,2) NOT NULL DEFAULT 0.00,
            tax_amount decimal(10,2) NOT NULL DEFAULT 0.00,
            line_total_net decimal(10,2) NOT NULL DEFAULT 0.00,
            line_total_gross decimal(10,2) NOT NULL DEFAULT 0.00,
            PRIMARY KEY (id),
            KEY document_id (document_id),
            KEY product_id (product_id)
        ) {$charset_collate};\n\n";
	}

	/**
	 * Get SQL for numbering table.
	 *
	 * @param string $charset_collate Charset and collation.
	 * @return string
	 */
	private function get_numbering_table_sql( string $charset_collate ): string {
		global $wpdb;

		$table_name = $wpdb->prefix . 'ihumbak_numbering';

		return "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            document_type varchar(20) NOT NULL,
            year int(4) NOT NULL,
            month int(2) DEFAULT NULL,
            last_number int(10) unsigned NOT NULL DEFAULT 0,
            pattern varchar(100) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY type_year_month (document_type, year, month)
        ) {$charset_collate};\n\n";
	}

	/**
	 * Uninstall - drop all tables.
	 *
	 * @return void
	 */
	public function uninstall(): void {
		global $wpdb;

		$tables = array(
			$wpdb->prefix . 'ihumbak_document_items',
			$wpdb->prefix . 'ihumbak_documents',
			$wpdb->prefix . 'ihumbak_numbering',
		);

		foreach ( $tables as $table ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
		}

		delete_option( self::DB_VERSION_OPTION );
		delete_option( 'ihumbak_invoices_settings' );
		delete_option( 'ihumbak_invoices_version' );
	}

	/**
	 * Get the documents table name.
	 *
	 * @return string
	 */
	public static function get_documents_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'ihumbak_documents';
	}

	/**
	 * Get the document items table name.
	 *
	 * @return string
	 */
	public static function get_document_items_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'ihumbak_document_items';
	}

	/**
	 * Get the numbering table name.
	 *
	 * @return string
	 */
	public static function get_numbering_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'ihumbak_numbering';
	}
}
