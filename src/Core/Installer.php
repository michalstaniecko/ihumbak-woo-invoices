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
	private const DB_VERSION = '1.5.0';

	/**
	 * Option name for storing database version.
	 *
	 * @var string
	 */
	private const DB_VERSION_OPTION = 'ihumbak_invoices_db_version';

	/**
	 * Option name for storing completed migrations.
	 *
	 * @var string
	 */
	private const MIGRATIONS_OPTION = 'ihumbak_completed_migrations';

	/**
	 * List of migrations with their required version.
	 *
	 * @var array<string, string>
	 */
	private const MIGRATIONS = array(
		'schema_fix_101'     => '1.0.0',
		'sku_column_110'     => '1.1.0',
		'credit_note_120'    => '1.2.0',
		'payment_method_130' => '1.3.0',
		'payment_date_140'   => '1.4.0',
		'sent_at_150'        => '1.5.0',
	);

	/**
	 * Install database tables.
	 *
	 * @return void
	 */
	public function install(): void {
		$installed_version = get_option( self::DB_VERSION_OPTION, '0' );

		// Run any pending force migrations.
		$this->run_force_migrations();

		if ( version_compare( $installed_version, self::DB_VERSION, '<' ) ) {
			$this->create_tables();
			$this->run_migrations( $installed_version );
			update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
		}
	}

	/**
	 * Run force migrations that need to be applied regardless of version.
	 *
	 * This handles cases where migrations were added but the version wasn't properly tracked.
	 *
	 * @return void
	 */
	private function run_force_migrations(): void {
		$completed = get_option( self::MIGRATIONS_OPTION, array() );

		// Migrate old markers to new system.
		$completed = $this->migrate_old_markers( $completed );

		$updated = false;

		foreach ( self::MIGRATIONS as $migration_key => $from_version ) {
			if ( ! in_array( $migration_key, $completed, true ) ) {
				$this->run_migrations( $from_version );
				$completed[] = $migration_key;
				$updated     = true;
			}
		}

		if ( $updated ) {
			update_option( self::MIGRATIONS_OPTION, $completed );
		}
	}

	/**
	 * Migrate old individual option markers to new array-based system.
	 *
	 * @param array<string> $completed Already completed migrations.
	 * @return array<string> Updated completed migrations.
	 */
	private function migrate_old_markers( array $completed ): array {
		$old_markers = array(
			'ihumbak_schema_fix_101'       => 'schema_fix_101',
			'ihumbak_credit_note_migrated' => 'sku_column_110',
		);

		$updated = false;

		foreach ( $old_markers as $old_option => $new_key ) {
			if ( get_option( $old_option, false ) && ! in_array( $new_key, $completed, true ) ) {
				$completed[] = $new_key;
				delete_option( $old_option );
				$updated = true;
			}
		}

		if ( $updated ) {
			update_option( self::MIGRATIONS_OPTION, $completed );
		}

		return $completed;
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

		// Migration to 1.3.0: Add payment method columns.
		if ( version_compare( $from_version, '1.3.0', '<' ) ) {
			$table = self::get_documents_table();

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			// Add payment_method column (for old installations that never had it).
			$column_exists = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s',
					$wpdb->dbname,
					$table,
					'payment_method'
				)
			);
			if ( ! $column_exists ) {
				$wpdb->query( "ALTER TABLE {$table} ADD COLUMN payment_method varchar(20) DEFAULT '' AFTER notes" );
			}

			// Add payment_method_id column.
			$column_exists = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s',
					$wpdb->dbname,
					$table,
					'payment_method_id'
				)
			);
			if ( ! $column_exists ) {
				$wpdb->query( "ALTER TABLE {$table} ADD COLUMN payment_method_id varchar(50) DEFAULT '' AFTER payment_method" );
			}

			// Add payment_method_title column.
			$column_exists = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s',
					$wpdb->dbname,
					$table,
					'payment_method_title'
				)
			);
			if ( ! $column_exists ) {
				$wpdb->query( "ALTER TABLE {$table} ADD COLUMN payment_method_title varchar(255) DEFAULT '' AFTER payment_method_id" );
			}

			// Migrate existing data from WC orders.
			$this->migrate_payment_method_data();

			// phpcs:enable
		}

		// Migration to 1.4.0: Add payment_date column.
		if ( version_compare( $from_version, '1.4.0', '<' ) ) {
			$table = self::get_documents_table();

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			// Add payment_date column.
			$column_exists = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s',
					$wpdb->dbname,
					$table,
					'payment_date'
				)
			);
			if ( ! $column_exists ) {
				$wpdb->query( "ALTER TABLE {$table} ADD COLUMN payment_date date DEFAULT NULL AFTER due_date" );
			}

			// Migrate existing data from WC orders.
			$this->migrate_payment_date_data();

			// phpcs:enable
		}

		// Migration to 1.5.0: Add sent_at column.
		if ( version_compare( $from_version, '1.5.0', '<' ) ) {
			$table = self::get_documents_table();

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			// Add sent_at column.
			$column_exists = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s',
					$wpdb->dbname,
					$table,
					'sent_at'
				)
			);
			if ( ! $column_exists ) {
				$wpdb->query( "ALTER TABLE {$table} ADD COLUMN sent_at datetime DEFAULT NULL AFTER updated_at" );
			}

			// phpcs:enable
		}
	}

	/**
	 * Migrate payment method data from WooCommerce orders for existing invoices.
	 *
	 * @return void
	 */
	private function migrate_payment_method_data(): void {
		global $wpdb;

		// Check if WooCommerce is active.
		if ( ! function_exists( 'wc_get_order' ) ) {
			return;
		}

		$table = self::get_documents_table();

		// Get invoices with order_id that have empty payment_method_id.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$invoices = $wpdb->get_results(
			"SELECT id, order_id FROM {$table}
			WHERE document_type = 'invoice'
			AND order_id IS NOT NULL
			AND (payment_method_id = '' OR payment_method_id IS NULL)"
		);
		// phpcs:enable

		if ( empty( $invoices ) ) {
			return;
		}

		foreach ( $invoices as $invoice ) {
			$order = wc_get_order( $invoice->order_id );

			if ( ! $order ) {
				continue;
			}

			$payment_method_id    = $order->get_payment_method();
			$payment_method_title = $order->get_payment_method_title();

			if ( empty( $payment_method_id ) ) {
				continue;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$table,
				array(
					'payment_method_id'    => $payment_method_id,
					'payment_method_title' => $payment_method_title,
				),
				array( 'id' => $invoice->id ),
				array( '%s', '%s' ),
				array( '%d' )
			);
		}
	}

	/**
	 * Migrate payment date data from WooCommerce orders for existing invoices.
	 *
	 * @return void
	 */
	private function migrate_payment_date_data(): void {
		global $wpdb;

		// Check if WooCommerce is active.
		if ( ! function_exists( 'wc_get_order' ) ) {
			return;
		}

		$table = self::get_documents_table();

		// Get documents with order_id that have empty payment_date.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$documents = $wpdb->get_results(
			"SELECT id, order_id FROM {$table}
			WHERE order_id IS NOT NULL
			AND payment_date IS NULL"
		);
		// phpcs:enable

		if ( empty( $documents ) ) {
			return;
		}

		foreach ( $documents as $document ) {
			$order = wc_get_order( $document->order_id );

			if ( ! $order ) {
				continue;
			}

			$date_paid = $order->get_date_paid();

			if ( ! $date_paid ) {
				continue;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$table,
				array(
					'payment_date' => $date_paid->format( 'Y-m-d' ),
				),
				array( 'id' => $document->id ),
				array( '%s' ),
				array( '%d' )
			);
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
            payment_date date DEFAULT NULL,
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
            payment_method varchar(20) DEFAULT '',
            payment_method_id varchar(50) DEFAULT '',
            payment_method_title varchar(255) DEFAULT '',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            sent_at datetime DEFAULT NULL,
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
		delete_option( self::MIGRATIONS_OPTION );
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
