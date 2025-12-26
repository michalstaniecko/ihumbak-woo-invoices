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
use IHumbak\Invoices\Models\CreditNote;
use IHumbak\Invoices\Models\DocumentItem;
use IHumbak\Invoices\Models\Buyer;
use IHumbak\Invoices\Models\Seller;
use IHumbak\Invoices\Modules\Invoice\NumberingService;
use IHumbak\Invoices\Modules\Invoice\RefundDataExtractor;
use IHumbak\Invoices\Modules\Invoice\SuperAdminService;
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
	 * Refund data extractor.
	 *
	 * @var RefundDataExtractor
	 */
	private RefundDataExtractor $refund_extractor;

	/**
	 * Super admin service.
	 *
	 * @var SuperAdminService
	 */
	private SuperAdminService $super_admin_service;

	/**
	 * Constructor.
	 *
	 * @param SuperAdminService|null $super_admin_service Optional super admin service for DI.
	 */
	public function __construct( ?SuperAdminService $super_admin_service = null ) {
		$this->document_repository = new DocumentRepository();
		$this->item_repository     = new DocumentItemRepository();
		$this->numbering_service   = new NumberingService();
		$this->refund_extractor    = new RefundDataExtractor();
		$this->super_admin_service = $super_admin_service ?? new SuperAdminService();
	}

	/**
	 * Initialize controller hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_post_ihumbak_save_invoice', array( $this, 'handle_save_invoice' ) );
		add_action( 'admin_post_ihumbak_save_receipt', array( $this, 'handle_save_receipt' ) );
		add_action( 'admin_post_ihumbak_save_credit_note', array( $this, 'handle_save_credit_note' ) );
		add_action( 'admin_post_ihumbak_revert_to_draft', array( $this, 'handle_revert_to_draft' ) );
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
		$document            = null;
		$items               = array();
		$pre_filled_order_id = null;

		if ( $id ) {
			$document = $this->document_repository->find( $id );
			if ( $document ) {
				$items = $this->item_repository->findByDocumentId( $id );
			}
		} else {
			// Check for order_id parameter for pre-filling from WC order metabox.
			// Auto-fetch is only enabled if nonce is valid (link from order metabox).
			$pre_filled_order_id = $this->get_verified_order_id_from_request();
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

		$super_admin_service = $this->super_admin_service;

		include IHUMBAK_INVOICES_PATH . 'templates/admin/invoice-edit.php';
	}

	/**
	 * Render receipt edit page.
	 *
	 * @param int|null $id Document ID (null for new).
	 * @return void
	 */
	public function render_receipt_edit( ?int $id = null ): void {
		$document            = null;
		$items               = array();
		$pre_filled_order_id = null;

		if ( $id ) {
			$document = $this->document_repository->find( $id );
			if ( $document ) {
				$items = $this->item_repository->findByDocumentId( $id );
			}
		} else {
			// Check for order_id parameter for pre-filling from WC order metabox.
			// Auto-fetch is only enabled if nonce is valid (link from order metabox).
			$pre_filled_order_id = $this->get_verified_order_id_from_request();
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

		$super_admin_service = $this->super_admin_service;

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
	 * Handle save credit note action.
	 *
	 * @return void
	 */
	public function handle_save_credit_note(): void {
		$this->handle_save( 'credit_note' );
	}

	/**
	 * Render credit note edit page.
	 *
	 * @param int|null $id Document ID (null for new).
	 * @return void
	 */
	public function render_credit_note_edit( ?int $id = null ): void {
		$document          = null;
		$items             = array();
		$original_document = null;
		$original_items    = array();
		$available_refunds = array();

		if ( $id ) {
			$document = $this->document_repository->find( $id );
			if ( $document ) {
				$items = $this->item_repository->findByDocumentId( $id );

				// Load original document if set.
				if ( $document->getCorrectedDocumentId() ) {
					$original_document = $this->document_repository->find( $document->getCorrectedDocumentId() );
					$original_items    = $this->item_repository->findByDocumentId( $document->getCorrectedDocumentId() );

					// Load refunds if order is linked.
					if ( $original_document && $original_document->getOrderId() ) {
						$available_refunds = $this->refund_extractor->extractRefundsFromOrderId( $original_document->getOrderId() );
					}
				}
			}
		}

		// Check for pre-selected invoice ID and order_id from credit note creation links.
		$pre_selected_invoice_id = null;
		$pre_filled_order_id     = null;

		if ( isset( $_GET['corrected_document_id'] ) ) {
			$corrected_id = absint( $_GET['corrected_document_id'] );

			// Verify nonce for credit note creation (from invoice-edit.php or OrderMetaBox).
			$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
			if ( wp_verify_nonce( $nonce, 'ihumbak_create_credit_note_' . $corrected_id ) ) {
				$pre_selected_invoice_id = $corrected_id;

				// Also get order_id if provided (from OrderMetaBox).
				if ( isset( $_GET['order_id'] ) ) {
					$pre_filled_order_id = absint( $_GET['order_id'] );
				}
			}
		}

		// If order_id is provided and verified, load refunds for that order.
		if ( $pre_filled_order_id && empty( $available_refunds ) ) {
			$available_refunds = $this->refund_extractor->extractRefundsFromOrderId( $pre_filled_order_id );
		}

		$settings = Plugin::get_instance()->get_settings();
		$seller   = $document ? $document->getSeller()?->toArray() : ( $settings['seller'] ?? array() );
		$buyer    = $document ? $document->getBuyer()?->toArray() : array();
		$items    = array_map( fn( $item ) => $item->toArray(), $items );

		// Get available invoices for dropdown with existing corrections info.
		$available_invoices   = $this->get_correctable_invoices();
		$existing_corrections = $this->get_existing_corrections_map( $available_invoices );

		$next_number = $this->numbering_service->previewNextNumber(
			'credit_note',
			$settings['numbering']['credit_note_pattern'] ?? 'CN/{YYYY}/{MM}/{NNNN}',
			$settings['numbering']['reset_monthly'] ?? true
		);

		$super_admin_service = $this->super_admin_service;

		include IHUMBAK_INVOICES_PATH . 'templates/admin/credit-note-edit.php';
	}

	/**
	 * Get invoices that can be corrected.
	 *
	 * @return Document[]
	 */
	private function get_correctable_invoices(): array {
		return $this->document_repository->findAll(
			array(
				'document_type' => 'invoice',
				'status'        => Document::STATUS_ISSUED,
			),
			1000,
			0
		);
	}

	/**
	 * Get map of existing corrections for invoices.
	 *
	 * @param Document[] $invoices Array of invoices.
	 * @return array<int, CreditNote[]> Map of invoice ID to array of credit notes.
	 */
	private function get_existing_corrections_map( array $invoices ): array {
		$corrections = array();

		foreach ( $invoices as $invoice ) {
			$invoice_id = $invoice->getId();
			if ( $invoice_id ) {
				$credit_notes = $this->document_repository->findByCorrectedDocumentId( $invoice_id );
				if ( ! empty( $credit_notes ) ) {
					$corrections[ $invoice_id ] = $credit_notes;
				}
			}
		}

		return $corrections;
	}

	/**
	 * Handle document save.
	 *
	 * @param string $type Document type.
	 * @return void
	 * @throws \InvalidArgumentException When validation fails (caught internally).
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
			$document = match ( $type ) {
				'receipt'     => new Receipt(),
				'credit_note' => new CreditNote(),
				default       => new Invoice(),
			};
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
					$pattern  = match ( $type ) {
						'receipt'     => $settings['numbering']['receipt_pattern'] ?? 'PAR/{YYYY}/{MM}/{NNNN}',
						'credit_note' => $settings['numbering']['credit_note_pattern'] ?? 'CN/{YYYY}/{MM}/{NNNN}',
						default       => $settings['numbering']['invoice_pattern'] ?? 'FV/{YYYY}/{MM}/{NNNN}',
					};

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

			// Get and validate items.
			$items = $this->get_items_from_request();
			if ( empty( $items ) ) {
				throw new \InvalidArgumentException(
					esc_html__( 'Please add at least one item to the document.', 'ihumbak-invoices' )
				);
			}

			// Save document.
			$document_id = $this->document_repository->save( $document );

			// Save items.
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
	 * @throws \InvalidArgumentException If corrected document ID is invalid.
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

		// Credit note specific fields.
		if ( $document instanceof CreditNote ) {
			// Corrected document ID (required).
			if ( empty( $_POST['corrected_document_id'] ) ) {
				throw new \InvalidArgumentException(
					esc_html__( 'Please select a source invoice for the credit note.', 'ihumbak-invoices' )
				);
			}

			$corrected_id = absint( $_POST['corrected_document_id'] );

			// Validate that the corrected document exists and is an invoice.
			$original_document = $this->document_repository->find( $corrected_id );
			if ( ! $original_document ) {
				throw new \InvalidArgumentException(
					esc_html__( 'The selected source invoice does not exist.', 'ihumbak-invoices' )
				);
			}
			if ( 'invoice' !== $original_document->getDocumentType() ) {
				throw new \InvalidArgumentException(
					esc_html__( 'Credit notes can only be created for invoices.', 'ihumbak-invoices' )
				);
			}

			$document->setCorrectedDocumentId( $corrected_id );

			// Propagate order_id from original invoice to credit note.
			if ( $original_document->getOrderId() ) {
				$document->setOrderId( $original_document->getOrderId() );
			}

			// Correction reason (required).
			if ( empty( $_POST['correction_reason'] ) ) {
				throw new \InvalidArgumentException(
					esc_html__( 'Please provide a correction reason.', 'ihumbak-invoices' )
				);
			}
			$document->setCorrectionReason( sanitize_textarea_field( wp_unslash( $_POST['correction_reason'] ) ) );

			// Correction type (full or partial).
			if ( ! empty( $_POST['correction_type'] ) ) {
				try {
					$document->setCorrectionType( sanitize_text_field( wp_unslash( $_POST['correction_type'] ) ) );
				} catch ( \InvalidArgumentException $e ) {
					// Use default type (partial) if invalid value provided.
					unset( $e );
				}
			}

			// Refund ID (optional).
			if ( ! empty( $_POST['refund_id'] ) ) {
				$document->setRefundId( absint( $_POST['refund_id'] ) );
			}
		}

		// Seller.
		if ( isset( $_POST['seller'] ) && is_array( $_POST['seller'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below.
			$seller_raw  = wp_unslash( $_POST['seller'] );
			$seller_data = array(
				'name'    => sanitize_text_field( $seller_raw['name'] ?? '' ),
				'details' => sanitize_textarea_field( $seller_raw['details'] ?? '' ),
			);
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
			$item->setSku( sanitize_text_field( $item_data['sku'] ?? '' ) );
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

	/**
	 * Handle revert to draft action.
	 *
	 * Only super-admins can revert issued/sent/paid documents to draft status.
	 *
	 * @return void
	 */
	public function handle_revert_to_draft(): void {
		// Verify nonce.
		if ( ! isset( $_POST['ihumbak_revert_nonce'] ) ||
			! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST['ihumbak_revert_nonce'] ) ),
				'ihumbak_revert_to_draft'
			)
		) {
			wp_die( esc_html__( 'Security check failed.', 'ihumbak-invoices' ) );
		}

		// Check basic permissions.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'ihumbak-invoices' ) );
		}

		// Check super-admin permissions.
		if ( ! $this->super_admin_service->isCurrentUserSuperAdmin() ) {
			wp_die( esc_html__( 'Only super-admins can revert document status.', 'ihumbak-invoices' ) );
		}

		// Get document ID.
		$document_id = isset( $_POST['document_id'] ) ? absint( $_POST['document_id'] ) : 0;

		if ( ! $document_id ) {
			wp_die( esc_html__( 'Invalid document ID.', 'ihumbak-invoices' ) );
		}

		// Load document.
		$document = $this->document_repository->find( $document_id );

		if ( ! $document ) {
			wp_die( esc_html__( 'Document not found.', 'ihumbak-invoices' ) );
		}

		// Verify document can be reverted (must be issued, sent, or paid - not draft or cancelled).
		$revertable_statuses = SuperAdminService::getRevertableStatuses();
		if ( ! in_array( $document->getStatus(), $revertable_statuses, true ) ) {
			wp_die(
				esc_html__( 'Only issued, sent, or paid documents can be reverted to draft.', 'ihumbak-invoices' )
			);
		}

		// Revert status to draft.
		$document->setStatus( Document::STATUS_DRAFT );
		$this->document_repository->save( $document );

		/**
		 * Fires after a document status is reverted to draft.
		 *
		 * @param Document $document The reverted document.
		 * @param int      $user_id  ID of the user who performed the action.
		 */
		do_action( 'ihumbak_document_reverted_to_draft', $document, get_current_user_id() );

		// Determine document type for redirect.
		$type = $document->getDocumentType();

		// Redirect back with success message.
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'ihumbak-invoices',
					'action'  => 'edit',
					'type'    => $type,
					'id'      => $document_id,
					'message' => 'reverted',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Get verified order ID from request.
	 *
	 * Returns order_id only if valid nonce is present (link from order metabox).
	 * This prevents auto-fetch for manually typed URLs without proper authorization.
	 *
	 * @return int|null Order ID or null if not present or nonce invalid.
	 */
	private function get_verified_order_id_from_request(): ?int {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified below.
		$order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;

		if ( $order_id < 1 ) {
			return null;
		}

		// Verify nonce - auto-fetch only if link came from order metabox.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This IS the nonce verification.
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'ihumbak_create_from_order_' . $order_id ) ) {
			// Nonce invalid or missing - no auto-fetch, user can still manually fetch.
			return null;
		}

		return $order_id;
	}
}
