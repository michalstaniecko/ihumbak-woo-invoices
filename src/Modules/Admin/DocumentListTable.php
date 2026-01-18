<?php
/**
 * Document List Table.
 *
 * @package IHumbak\Invoices\Modules\Admin
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Modules\Admin;

use IHumbak\Invoices\Infrastructure\Database\DocumentRepository;
use IHumbak\Invoices\Models\CreditNote;
use IHumbak\Invoices\Models\Document;
use IHumbak\Invoices\Models\ReceiptReturn;

// Load WP_List_Table if not loaded.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * WP_List_Table for displaying documents.
 */
class DocumentListTable extends \WP_List_Table {

	/**
	 * Document repository.
	 *
	 * @var DocumentRepository
	 */
	private DocumentRepository $repository;

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'Document', 'ihumbak-invoices' ),
				'plural'   => __( 'Documents', 'ihumbak-invoices' ),
				'ajax'     => false,
			)
		);

		$this->repository = new DocumentRepository();
	}

	/**
	 * Get table columns.
	 *
	 * @return array<string, string>
	 */
	public function get_columns(): array {
		return array(
			'cb'              => '<input type="checkbox" />',
			'document_number' => __( 'Number', 'ihumbak-invoices' ),
			'document_type'   => __( 'Type', 'ihumbak-invoices' ),
			'buyer'           => __( 'Buyer', 'ihumbak-invoices' ),
			'total'           => __( 'Total', 'ihumbak-invoices' ),
			'issue_date'      => __( 'Issue Date', 'ihumbak-invoices' ),
			'status'          => __( 'Status', 'ihumbak-invoices' ),
			'order_id'        => __( 'Order', 'ihumbak-invoices' ),
		);
	}

	/**
	 * Get sortable columns.
	 *
	 * @return array<string, array<int, string|bool>>
	 */
	public function get_sortable_columns(): array {
		return array(
			'document_number' => array( 'document_number', false ),
			'issue_date'      => array( 'issue_date', true ),
			'total'           => array( 'total', false ),
			'status'          => array( 'status', false ),
		);
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array<string, string>
	 */
	public function get_bulk_actions(): array {
		return array(
			'delete' => __( 'Delete', 'ihumbak-invoices' ),
		);
	}

	/**
	 * Prepare items for display.
	 *
	 * @return void
	 */
	public function prepare_items(): void {
		$per_page     = 20;
		$current_page = $this->get_pagenum();

		$filters = $this->get_filters();

		$total_items = $this->repository->count( $filters );
		$items       = $this->repository->findAll(
			$filters,
			$per_page,
			( $current_page - 1 ) * $per_page
		);

		$this->items = $items;

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => (int) ceil( $total_items / $per_page ),
			)
		);

		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns(),
		);
	}

	/**
	 * Get filters from request.
	 *
	 * @return array<string, mixed>
	 */
	private function get_filters(): array {
		$filters = array();

        // phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['document_type'] ) ) {
			$filters['document_type'] = sanitize_text_field( wp_unslash( $_GET['document_type'] ) );
		}

		if ( ! empty( $_GET['status'] ) ) {
			$filters['status'] = sanitize_text_field( wp_unslash( $_GET['status'] ) );
		}

		if ( ! empty( $_GET['s'] ) ) {
			$filters['search'] = sanitize_text_field( wp_unslash( $_GET['s'] ) );
		}
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

		return $filters;
	}

	/**
	 * Render checkbox column.
	 *
	 * @param Document $item Document.
	 * @return string
	 */
	public function column_cb( $item ): string {
		return sprintf(
			'<input type="checkbox" name="document_ids[]" value="%d" />',
			$item->getId()
		);
	}

	/**
	 * Render document number column.
	 *
	 * @param Document $item Document.
	 * @return string
	 */
	public function column_document_number( $item ): string {
		$edit_url = add_query_arg(
			array(
				'page'   => 'ihumbak-invoices',
				'action' => 'edit',
				'type'   => $item->getDocumentType(),
				'id'     => $item->getId(),
			),
			admin_url( 'admin.php' )
		);

		$actions = array(
			'edit' => sprintf(
				'<a href="%s">%s</a>',
				esc_url( $edit_url ),
				esc_html__( 'Edit', 'ihumbak-invoices' )
			),
		);

		// Show PDF action for issued documents (not drafts).
		if ( ! $item->isDraft() ) {
			$pdf_url = add_query_arg(
				array(
					'page'   => 'ihumbak-invoices',
					'action' => 'pdf',
					'id'     => $item->getId(),
					'nonce'  => wp_create_nonce( 'pdf_document_' . $item->getId() ),
				),
				admin_url( 'admin.php' )
			);

			$actions['pdf'] = sprintf(
				'<a href="%s" target="_blank">%s</a>',
				esc_url( $pdf_url ),
				esc_html__( 'Download PDF', 'ihumbak-invoices' )
			);

			// Add regenerate PDF action (force regeneration, ignores cache).
			$regenerate_url = add_query_arg(
				array(
					'page'   => 'ihumbak-invoices',
					'action' => 'pdf',
					'id'     => $item->getId(),
					'force'  => '1',
					'nonce'  => wp_create_nonce( 'pdf_document_' . $item->getId() ),
				),
				admin_url( 'admin.php' )
			);

			$actions['regenerate'] = sprintf(
				'<a href="%s" target="_blank">%s</a>',
				esc_url( $regenerate_url ),
				esc_html__( 'Regenerate PDF', 'ihumbak-invoices' )
			);

			// Add send email action for issued documents with email recipient available.
			if ( $item->canSendEmail() && ! $item->wasSent() ) {
				$email_url = add_query_arg(
					array(
						'page'   => 'ihumbak-invoices',
						'action' => 'send_email',
						'id'     => $item->getId(),
						'nonce'  => wp_create_nonce( 'send_email_' . $item->getId() ),
					),
					admin_url( 'admin.php' )
				);

				$actions['email'] = sprintf(
					'<a href="%s">%s</a>',
					esc_url( $email_url ),
					esc_html__( 'Send Email', 'ihumbak-invoices' )
				);
			}
		}

		if ( $item->isDraft() ) {
			$delete_url = add_query_arg(
				array(
					'page'   => 'ihumbak-invoices',
					'action' => 'delete',
					'id'     => $item->getId(),
					'nonce'  => wp_create_nonce( 'delete_document_' . $item->getId() ),
				),
				admin_url( 'admin.php' )
			);

			$actions['delete'] = sprintf(
				'<a href="%s" class="delete" onclick="return confirm(\'%s\');">%s</a>',
				esc_url( $delete_url ),
				esc_attr__( 'Are you sure you want to delete this document?', 'ihumbak-invoices' ),
				esc_html__( 'Delete', 'ihumbak-invoices' )
			);
		}

		return sprintf(
			'<strong><a href="%s">%s</a></strong>%s',
			esc_url( $edit_url ),
			esc_html( $item->getDocumentNumber() ?: __( '(Draft)', 'ihumbak-invoices' ) ),
			$this->row_actions( $actions )
		);
	}

	/**
	 * Render document type column.
	 *
	 * @param Document $item Document.
	 * @return string
	 */
	public function column_document_type( $item ): string {
		$output = esc_html( $item->getDocumentTypeLabel() );

		// For credit notes, show which invoice is being corrected.
		if ( $item instanceof CreditNote ) {
			if ( $item->isManualEntry() && $item->getOriginalDocumentNumber() ) {
				// Manual entry mode - show the manually entered invoice number.
				$output .= '<br><small style="color: #666;">' .
					sprintf(
						/* translators: %s: Original invoice number */
						esc_html__( 'Corrects: %s', 'ihumbak-invoices' ),
						esc_html( $item->getOriginalDocumentNumber() )
					) .
					' <em>(' . esc_html__( 'external', 'ihumbak-invoices' ) . ')</em></small>';
			} elseif ( $item->getCorrectedDocumentId() ) {
				// System mode - fetch and show the invoice number with link.
				$original = $this->repository->find( $item->getCorrectedDocumentId() );
				if ( $original ) {
					$edit_url = add_query_arg(
						array(
							'page'   => 'ihumbak-invoices',
							'action' => 'edit',
							'type'   => 'invoice',
							'id'     => $original->getId(),
						),
						admin_url( 'admin.php' )
					);
					$output  .= '<br><small style="color: #666;">' .
						sprintf(
							/* translators: %s: Original invoice number (with link) */
							esc_html__( 'Corrects: %s', 'ihumbak-invoices' ),
							'<a href="' . esc_url( $edit_url ) . '">' . esc_html( $original->getDocumentNumber() ) . '</a>'
						) .
						'</small>';
				}
			}
		}

		// For receipt returns, show which receipt is being returned.
		if ( $item instanceof ReceiptReturn ) {
			if ( $item->isManualEntry() && $item->getOriginalDocumentNumber() ) {
				// Manual entry mode - show the manually entered receipt number.
				$output .= '<br><small style="color: #666;">' .
					sprintf(
						/* translators: %s: Original receipt number */
						esc_html__( 'Returns: %s', 'ihumbak-invoices' ),
						esc_html( $item->getOriginalDocumentNumber() )
					) .
					' <em>(' . esc_html__( 'external', 'ihumbak-invoices' ) . ')</em></small>';
			} elseif ( $item->getCorrectedDocumentId() ) {
				// System mode - fetch and show the receipt number with link.
				$original = $this->repository->find( $item->getCorrectedDocumentId() );
				if ( $original ) {
					$edit_url = add_query_arg(
						array(
							'page'   => 'ihumbak-invoices',
							'action' => 'edit',
							'type'   => 'receipt',
							'id'     => $original->getId(),
						),
						admin_url( 'admin.php' )
					);
					$output  .= '<br><small style="color: #666;">' .
						sprintf(
							/* translators: %s: Original receipt number (with link) */
							esc_html__( 'Returns: %s', 'ihumbak-invoices' ),
							'<a href="' . esc_url( $edit_url ) . '">' . esc_html( $original->getDocumentNumber() ) . '</a>'
						) .
						'</small>';
				}
			}
		}

		return $output;
	}

	/**
	 * Render buyer column.
	 *
	 * @param Document $item Document.
	 * @return string
	 */
	public function column_buyer( $item ): string {
		$buyer = $item->getBuyer();
		if ( ! $buyer ) {
			return '—';
		}

		$output = esc_html( $buyer->getName() );
		if ( $buyer->hasNip() ) {
			$output .= '<br><small>NIP: ' . esc_html( $buyer->getNip() ) . '</small>';
		}

		return $output;
	}

	/**
	 * Render total column.
	 *
	 * @param Document $item Document.
	 * @return string
	 */
	public function column_total( $item ): string {
		return sprintf(
			'<strong>%s</strong><br><small>%s: %s</small>',
			esc_html( number_format( $item->getTotal(), 2, ',', ' ' ) . ' ' . $item->getCurrency() ),
			esc_html__( 'Net', 'ihumbak-invoices' ),
			esc_html( number_format( $item->getSubtotal(), 2, ',', ' ' ) . ' ' . $item->getCurrency() )
		);
	}

	/**
	 * Render issue date column.
	 *
	 * @param Document $item Document.
	 * @return string
	 */
	public function column_issue_date( $item ): string {
		$issue_date = $item->getIssueDate();
		return $issue_date ? esc_html( $issue_date->format( 'd.m.Y' ) ) : '—';
	}

	/**
	 * Render status column.
	 *
	 * @param Document $item Document.
	 * @return string
	 */
	public function column_status( $item ): string {
		$status_class = 'ihumbak-status ihumbak-status-' . $item->getStatus();
		return sprintf(
			'<span class="%s">%s</span>',
			esc_attr( $status_class ),
			esc_html( $item->getStatusLabel() )
		);
	}

	/**
	 * Render order column.
	 *
	 * @param Document $item Document.
	 * @return string
	 */
	public function column_order_id( $item ): string {
		$order_id = $item->getOrderId();
		if ( ! $order_id ) {
			return '—';
		}

		$order_url = admin_url( 'post.php?post=' . $order_id . '&action=edit' );

		// Check if HPOS is enabled.
		if ( class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) ) {
			if ( \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
				$order_url = admin_url( 'admin.php?page=wc-orders&action=edit&id=' . $order_id );
			}
		}

		return sprintf(
			'<a href="%s">#%d</a>',
			esc_url( $order_url ),
			$order_id
		);
	}

	/**
	 * Message when no items found.
	 *
	 * @return void
	 */
	public function no_items(): void {
		esc_html_e( 'No documents found.', 'ihumbak-invoices' );
	}

	/**
	 * Extra table navigation (filters).
	 *
	 * @param string $which Top or bottom.
	 * @return void
	 */
	protected function extra_tablenav( $which ): void {
		if ( 'top' !== $which ) {
			return;
		}

        // phpcs:disable WordPress.Security.NonceVerification.Recommended
		$current_type   = isset( $_GET['document_type'] ) ? sanitize_text_field( wp_unslash( $_GET['document_type'] ) ) : '';
		$current_status = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

		?>
		<div class="alignleft actions">
			<select name="document_type">
				<option value=""><?php esc_html_e( 'All types', 'ihumbak-invoices' ); ?></option>
				<option value="invoice" <?php selected( $current_type, 'invoice' ); ?>><?php esc_html_e( 'Invoices', 'ihumbak-invoices' ); ?></option>
				<option value="receipt" <?php selected( $current_type, 'receipt' ); ?>><?php esc_html_e( 'Receipts', 'ihumbak-invoices' ); ?></option>
				<option value="credit_note" <?php selected( $current_type, 'credit_note' ); ?>><?php esc_html_e( 'Credit Notes', 'ihumbak-invoices' ); ?></option>
				<option value="receipt_return" <?php selected( $current_type, 'receipt_return' ); ?>><?php esc_html_e( 'Receipt Returns', 'ihumbak-invoices' ); ?></option>
			</select>

			<select name="status">
				<option value=""><?php esc_html_e( 'All statuses', 'ihumbak-invoices' ); ?></option>
				<?php foreach ( Document::getStatuses() as $status => $label ) : ?>
					<option value="<?php echo esc_attr( $status ); ?>" <?php selected( $current_status, $status ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<?php submit_button( __( 'Filter', 'ihumbak-invoices' ), '', 'filter_action', false ); ?>
		</div>
		<?php
	}
}
