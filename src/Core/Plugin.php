<?php
/**
 * Main Plugin class.
 *
 * @package IHumbak\Invoices\Core
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Core;

use IHumbak\Invoices\Modules\Admin\DocumentController;
use IHumbak\Invoices\Modules\Admin\AjaxController;
use IHumbak\Invoices\Modules\Admin\OrderMetaBox;
use IHumbak\Invoices\Modules\Admin\OrderListColumn;
use IHumbak\Invoices\Modules\Admin\ReportController;
use IHumbak\Invoices\Modules\PDF\PdfGenerator;
use IHumbak\Invoices\Modules\PDF\PdfCacheManager;
use IHumbak\Invoices\Modules\PDF\TemplateLoader;
use IHumbak\Invoices\Modules\PDF\TemplateRegistry;
use IHumbak\Invoices\Modules\Invoice\PermissionService;
use IHumbak\Invoices\Modules\Email\EmailService;
use IHumbak\Invoices\Modules\Email\InvoiceEmail;
use IHumbak\Invoices\Modules\Email\ReceiptEmail;
use IHumbak\Invoices\Modules\Email\CreditNoteEmail;
use IHumbak\Invoices\Modules\Email\ReceiptReturnEmail;
use IHumbak\Invoices\Modules\Portal\PortalController;
use IHumbak\Invoices\Modules\Updates\UpdateService;
use IHumbak\Invoices\Infrastructure\Database\DocumentRepository;
use IHumbak\Invoices\Infrastructure\Database\DocumentItemRepository;

/**
 * Plugin singleton class.
 */
final class Plugin {

	/**
	 * Document controller.
	 *
	 * @var DocumentController|null
	 */
	private ?DocumentController $document_controller = null;

	/**
	 * AJAX controller.
	 *
	 * @var AjaxController|null
	 */
	private ?AjaxController $ajax_controller = null;

	/**
	 * Order metabox.
	 *
	 * @var OrderMetaBox|null
	 */
	private ?OrderMetaBox $order_metabox = null;

	/**
	 * Report controller.
	 *
	 * @var ReportController|null
	 */
	private ?ReportController $report_controller = null;

	/**
	 * Permission service.
	 *
	 * @var PermissionService|null
	 */
	private ?PermissionService $permission_service = null;

	/**
	 * Email service.
	 *
	 * @var EmailService|null
	 */
	private ?EmailService $email_service = null;

	/**
	 * Portal controller.
	 *
	 * @var PortalController|null
	 */
	private ?PortalController $portal_controller = null;

	/**
	 * Update service.
	 *
	 * @var UpdateService|null
	 */
	private ?UpdateService $update_service = null;

	/**
	 * Plugin instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * DI Container.
	 *
	 * @var Container
	 */
	private Container $container;

	/**
	 * Get the singleton instance.
	 *
	 * @return self
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor.
	 */
	private function __construct() {
		$this->container = new Container();
		$this->init();
	}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization.
	 *
	 * @throws \Exception When attempting to unserialize.
	 */
	public function __wakeup(): void {
		throw new \Exception( 'Cannot unserialize singleton' );
	}

	/**
	 * Initialize the plugin.
	 *
	 * @return void
	 */
	private function init(): void {
		$this->load_textdomain();
		$this->register_services();
		$this->check_database_updates();
		$this->init_hooks();
	}

	/**
	 * Check and run database updates if needed.
	 *
	 * Uses admin_init hook for non-AJAX requests to avoid running on every request.
	 * For AJAX requests, skip entirely as migrations should not run during AJAX.
	 *
	 * @return void
	 */
	private function check_database_updates(): void {
		// Skip database checks during AJAX requests.
		if ( wp_doing_ajax() ) {
			return;
		}

		// Only run database checks in admin context.
		if ( ! is_admin() ) {
			return;
		}

		// Run installer to check for updates.
		$installer = new Installer();
		$installer->install();
	}

	/**
	 * Load plugin textdomain.
	 *
	 * @return void
	 */
	private function load_textdomain(): void {
		load_plugin_textdomain(
			'ihumbak-invoices',
			false,
			dirname( IHUMBAK_INVOICES_BASENAME ) . '/languages'
		);
	}

	/**
	 * Register services in the container.
	 *
	 * @return void
	 */
	private function register_services(): void {
		// Register update service first (per library requirements, must be during plugins_loaded).
		$this->update_service = new UpdateService();
		if ( $this->update_service->is_enabled() ) {
			$this->update_service->init();
		}
		$this->container->register( 'update.service', fn() => $this->update_service );

		// Register core services.
		$this->container->register( 'installer', fn() => new Installer() );

		// Register permission service.
		$this->permission_service = new PermissionService();
		$this->container->register( 'permission.service', fn() => $this->permission_service );

		// Register PDF services.
		$this->container->register(
			'pdf.cache_manager',
			fn() => new PdfCacheManager()
		);

		$this->container->register(
			'pdf.template_loader',
			fn() => new TemplateLoader()
		);

		$this->container->register(
			'pdf.template_registry',
			fn( Container $c ) => new TemplateRegistry( $c->get( 'pdf.template_loader' ) )
		);

		$this->container->register(
			'pdf.generator',
			fn( Container $c ) => new PdfGenerator(
				$c->get( 'pdf.template_loader' ),
				$c->get( 'pdf.cache_manager' ),
				$c->get( 'pdf.template_registry' )
			)
		);

		// Register email service.
		$this->email_service = new EmailService();
		$this->container->register( 'email.service', fn() => $this->email_service );

		// Initialize admin-only features.
		if ( is_admin() ) {
			// Check for database updates.
			$installer = new Installer();
			$installer->install();

			// Initialize controllers.
			$this->document_controller = new DocumentController();
			$this->document_controller->init();

			$this->ajax_controller = new AjaxController();
			$this->ajax_controller->init();

			// Initialize order metabox.
			$this->order_metabox = new OrderMetaBox( new DocumentRepository() );
			$this->order_metabox->init();

			// Initialize order list column.
			$order_list_column = new OrderListColumn( new DocumentRepository() );
			$order_list_column->init();

			// Initialize report controller.
			$this->report_controller = new ReportController();
			$this->report_controller->init();
		}

		// Initialize frontend portal (customer My Account).
		// Must run outside is_admin() to work on frontend.
		$this->portal_controller = new PortalController();
		$this->portal_controller->init();
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		// Admin hooks.
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_action( 'admin_init', array( $this, 'handle_early_pdf_download' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		}

		// Frontend hooks.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );

		// Register WooCommerce email classes.
		add_filter( 'woocommerce_email_classes', array( $this, 'register_email_classes' ) );

		// Auto-send email on document issue.
		add_action( 'ihumbak_document_issued', array( $this, 'handle_document_issued' ) );
	}

	/**
	 * Register admin menu.
	 *
	 * @return void
	 */
	public function register_admin_menu(): void {
		$documents_capability = $this->permission_service->getMinimumCapability();

		// Documents page - configurable capability.
		add_submenu_page(
			'woocommerce',
			__( 'Invoices', 'ihumbak-invoices' ),
			__( 'Invoices', 'ihumbak-invoices' ),
			$documents_capability,
			'ihumbak-invoices',
			array( $this, 'render_admin_page' )
		);

		// Settings page - always administrators only.
		add_submenu_page(
			'woocommerce',
			__( 'Invoice Settings', 'ihumbak-invoices' ),
			__( 'Invoice Settings', 'ihumbak-invoices' ),
			PermissionService::SETTINGS_CAPABILITY,
			'ihumbak-invoices-settings',
			array( $this, 'render_settings_page' )
		);

		// Reports page - configurable capability.
		add_submenu_page(
			'woocommerce',
			__( 'Invoice Reports', 'ihumbak-invoices' ),
			__( 'Invoice Reports', 'ihumbak-invoices' ),
			$documents_capability,
			'ihumbak-invoices-reports',
			array( $this, 'render_reports_page' )
		);
	}

	/**
	 * Register plugin settings.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			'ihumbak_invoices_settings',
			'ihumbak_invoices_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => $this->get_default_settings(),
			)
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook ): void {
		// Check if we're on WooCommerce order edit page (for metabox styles).
		if ( $this->is_order_edit_page( $hook ) ) {
			wp_enqueue_style( 'dashicons' );
			wp_enqueue_style(
				'ihumbak-invoices-admin',
				IHUMBAK_INVOICES_URL . 'assets/css/admin.css',
				array(),
				IHUMBAK_INVOICES_VERSION
			);
			return;
		}

		if ( ! str_contains( $hook, 'ihumbak-invoices' ) ) {
			return;
		}

		wp_enqueue_style( 'dashicons' );

		wp_enqueue_style(
			'ihumbak-invoices-admin',
			IHUMBAK_INVOICES_URL . 'assets/css/admin.css',
			array(),
			IHUMBAK_INVOICES_VERSION
		);

		wp_enqueue_script(
			'ihumbak-invoices-admin',
			IHUMBAK_INVOICES_URL . 'assets/js/admin.js',
			array( 'jquery', 'wp-util' ),
			IHUMBAK_INVOICES_VERSION,
			true
		);

		// Check if we're on edit page.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';

		if ( in_array( $action, array( 'new', 'edit' ), true ) ) {
			wp_enqueue_script(
				'ihumbak-document-edit',
				IHUMBAK_INVOICES_URL . 'assets/js/document-edit.js',
				array( 'jquery', 'ihumbak-invoices-admin' ),
				IHUMBAK_INVOICES_VERSION,
				true
			);
		}

		// Determine if document is readonly (issued, not draft).
		$is_readonly = false;
		if ( 'edit' === $action ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$document_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
			if ( $document_id > 0 ) {
				$repository = new DocumentRepository();
				$document   = $repository->find( $document_id );
				if ( $document && ! $document->isDraft() ) {
					$is_readonly = true;
				}
			}
		}

		wp_localize_script(
			'ihumbak-invoices-admin',
			'ihumbakInvoices',
			array(
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'ihumbak_invoices_nonce' ),
				'selectLogo' => __( 'Select Logo', 'ihumbak-invoices' ),
				'useLogo'    => __( 'Use this logo', 'ihumbak-invoices' ),
				'isReadonly' => $is_readonly,
				'i18n'       => array(
					'confirmDelete'          => __( 'Are you sure you want to delete this item?', 'ihumbak-invoices' ),
					'calculating'            => __( 'Calculating...', 'ihumbak-invoices' ),
					'error'                  => __( 'An error occurred. Please try again.', 'ihumbak-invoices' ),
					'orderDataLoaded'        => __( 'Order data loaded successfully.', 'ihumbak-invoices' ),
					'orderNotFound'          => __( 'Order not found.', 'ihumbak-invoices' ),
					'replaceItemsConfirm'    => __( 'The form already contains items. Do you want to replace them with order data?', 'ihumbak-invoices' ),
					'nameRequiredError'      => __( 'Please enter a product name for all items with values.', 'ihumbak-invoices' ),
					'vatRequiredError'       => __( 'Please enter a VAT rate for all items.', 'ihumbak-invoices' ),
					'selectOriginalDocument' => __( 'Please select the original document to correct.', 'ihumbak-invoices' ),
				),
			)
		);
	}

	/**
	 * Enqueue frontend assets.
	 *
	 * @return void
	 */
	public function enqueue_frontend_assets(): void {
		if ( ! is_account_page() ) {
			return;
		}

		wp_enqueue_style(
			'ihumbak-invoices-frontend',
			IHUMBAK_INVOICES_URL . 'assets/css/frontend.css',
			array(),
			IHUMBAK_INVOICES_VERSION
		);
	}

	/**
	 * Render admin page.
	 *
	 * @return void
	 */
	public function render_admin_page(): void {
		if ( ! $this->permission_service->canManageDocuments() ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'ihumbak-invoices' ) );
		}

        // phpcs:disable WordPress.Security.NonceVerification.Recommended
		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : 'list';
		$type   = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : 'invoice';
		$id     = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : null;
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

		switch ( $action ) {
			case 'new':
			case 'edit':
				if ( 'receipt' === $type ) {
					$this->document_controller->render_receipt_edit( $id );
				} elseif ( 'credit_note' === $type ) {
					$this->document_controller->render_credit_note_edit( $id );
				} elseif ( 'receipt_return' === $type ) {
					$this->document_controller->render_receipt_return_edit( $id );
				} else {
					$this->document_controller->render_invoice_edit( $id );
				}
				break;

			case 'pdf':
				// PDF download is handled early in admin_init to avoid output buffering issues.
				// This case should not be reached, but redirect to list if it is.
				wp_safe_redirect( admin_url( 'admin.php?page=ihumbak-invoices' ) );
				exit;

			case 'send_email':
				$this->handle_send_email( $id );
				break;

			case 'delete':
				// Verify nonce for delete action.
				if ( $id && isset( $_GET['nonce'] ) ) {
					$nonce = sanitize_text_field( wp_unslash( $_GET['nonce'] ) );
					if ( wp_verify_nonce( $nonce, 'delete_document_' . $id ) ) {
						$this->document_controller->handle_delete( $id );
					}
				}
				// If nonce invalid, fall through to list.
				// No break intentionally.

			default:
				$this->document_controller->render_list_page();
				break;
		}
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		if ( ! $this->permission_service->canAccessSettings() ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'ihumbak-invoices' ) );
		}

		// Prepare permission-related variables for the template.
		$permission_default_role    = PermissionService::DEFAULT_ROLE;
		$permission_available_roles = PermissionService::getAvailableRoles();

		include IHUMBAK_INVOICES_PATH . 'templates/admin/settings.php';
	}

	/**
	 * Render reports page.
	 *
	 * @return void
	 */
	public function render_reports_page(): void {
		if ( ! $this->permission_service->canManageDocuments() ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'ihumbak-invoices' ) );
		}

		$this->report_controller->render_reports_page();
	}

	/**
	 * Handle PDF download early in admin_init before any output.
	 *
	 * This must be called before any HTML is output to avoid corrupting the PDF.
	 *
	 * @return void
	 */
	public function handle_early_pdf_download(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Nonce verified below.
		if ( ! isset( $_GET['page'] ) || 'ihumbak-invoices' !== $_GET['page'] ) {
			return;
		}

		// Support both 'pdf' and legacy 'download_pdf' action for backward compatibility.
		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
		if ( ! in_array( $action, array( 'pdf', 'download_pdf' ), true ) ) {
			return;
		}

		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : null;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$this->handle_pdf_download( $id );
	}

	/**
	 * Handle PDF download request.
	 *
	 * @param int|null $id Document ID.
	 * @return void
	 */
	private function handle_pdf_download( ?int $id ): void {
		// Check user permissions.
		if ( ! $this->permission_service->canManageDocuments() ) {
			wp_die( esc_html__( 'You do not have permission to download this document.', 'ihumbak-invoices' ) );
		}

		if ( ! $id ) {
			wp_die( esc_html__( 'Invalid document ID.', 'ihumbak-invoices' ) );
		}

		// Verify nonce - support both new 'pdf_document_' and legacy 'download_pdf_' prefix.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'pdf_document_' . $id ) && ! wp_verify_nonce( $nonce, 'download_pdf_' . $id ) ) {
			wp_die( esc_html__( 'Security check failed.', 'ihumbak-invoices' ) );
		}

		// Check if force regeneration is requested.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$force = isset( $_GET['force'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['force'] ) );

		// Get document.
		$repository = new DocumentRepository();
		$document   = $repository->find( $id );

		if ( ! $document ) {
			wp_die( esc_html__( 'Document not found.', 'ihumbak-invoices' ) );
		}

		// Check if document is issued.
		if ( $document->isDraft() ) {
			wp_die( esc_html__( 'Cannot generate PDF for draft documents.', 'ihumbak-invoices' ) );
		}

		// Load document items from database.
		$item_repository = new DocumentItemRepository();
		$items           = $item_repository->findByDocumentId( $id );
		$document->setItems( $items );

		try {
			// Get PDF generator from container and download document.
			$generator = $this->container->get( 'pdf.generator' );
			$generator->download( $document, $force );
			exit;
		} catch ( \Exception $e ) {
			wp_die(
				esc_html(
					sprintf(
						/* translators: %s: Error message */
						__( 'Failed to generate PDF: %s', 'ihumbak-invoices' ),
						$e->getMessage()
					)
				)
			);
		}
	}

	/**
	 * Register WooCommerce email classes.
	 *
	 * @param array<string, \WC_Email> $emails Existing email classes.
	 * @return array<string, \WC_Email> Modified email classes.
	 */
	public function register_email_classes( array $emails ): array {
		$emails['IHumbak_Invoice_Email']        = new InvoiceEmail();
		$emails['IHumbak_Receipt_Email']        = new ReceiptEmail();
		$emails['IHumbak_Credit_Note_Email']    = new CreditNoteEmail();
		$emails['IHumbak_Receipt_Return_Email'] = new ReceiptReturnEmail();

		return $emails;
	}

	/**
	 * Handle document issued action for auto-send email.
	 *
	 * @param \IHumbak\Invoices\Models\Document $document The issued document.
	 * @return void
	 */
	public function handle_document_issued( $document ): void {
		if ( $this->email_service ) {
			$this->email_service->maybeSendOnIssue( $document );
		}
	}

	/**
	 * Handle manual send email action.
	 *
	 * @param int|null $id Document ID.
	 * @return void
	 */
	private function handle_send_email( ?int $id ): void {
		// Check permissions.
		if ( ! $this->permission_service->canManageDocuments() ) {
			wp_die( esc_html__( 'You do not have permission to send emails.', 'ihumbak-invoices' ) );
		}

		if ( ! $id ) {
			wp_die( esc_html__( 'Invalid document ID.', 'ihumbak-invoices' ) );
		}

		// Verify nonce.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'send_email_' . $id ) ) {
			wp_die( esc_html__( 'Security check failed.', 'ihumbak-invoices' ) );
		}

		// Get document.
		$repository = new DocumentRepository();
		$document   = $repository->find( $id );

		if ( ! $document ) {
			wp_die( esc_html__( 'Document not found.', 'ihumbak-invoices' ) );
		}

		// Check if document can be sent.
		if ( ! $this->email_service || ! $this->email_service->canSend( $document ) ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'    => 'ihumbak-invoices',
						'message' => 'email_error',
						'reason'  => 'cannot_send',
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}

		// Load document items.
		$item_repository = new DocumentItemRepository();
		$items           = $item_repository->findByDocumentId( $id );
		$document->setItems( $items );

		// Send email.
		$result = $this->email_service->send( $document );

		// Redirect with result.
		$message = $result ? 'email_sent' : 'email_error';

		// Check if we should return to edit page.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$return_to = isset( $_GET['return_to'] ) ? sanitize_text_field( wp_unslash( $_GET['return_to'] ) ) : '';

		if ( 'edit' === $return_to ) {
			// Return to document edit page.
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'    => 'ihumbak-invoices',
						'action'  => 'edit',
						'type'    => $document->getDocumentType(),
						'id'      => $id,
						'message' => $message,
					),
					admin_url( 'admin.php' )
				)
			);
		} else {
			// Redirect to the main documents list.
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'    => 'ihumbak-invoices',
						'message' => $message,
					),
					admin_url( 'admin.php' )
				)
			);
		}
		exit;
	}

	/**
	 * Get plugin settings.
	 *
	 * @return array<string, mixed>
	 */
	public function get_settings(): array {
		$settings = get_option( 'ihumbak_invoices_settings', array() );
		return wp_parse_args( $settings, $this->get_default_settings() );
	}

	/**
	 * Get default settings.
	 *
	 * @return array<string, mixed>
	 */
	public function get_default_settings(): array {
		return array(
			'seller'      => array(
				'name'    => '',
				'details' => '',
			),
			'numbering'   => array(
				'invoice_pattern'        => 'FV/{YYYY}/{MM}/{NNNN}',
				'receipt_pattern'        => 'PAR/{YYYY}/{MM}/{NNNN}',
				'credit_note_pattern'    => 'CN/{YYYY}/{MM}/{NNNN}',
				'receipt_return_pattern' => 'RR/{YYYY}/{MM}/{NNNN}',
				'correction_pattern'     => 'FK/{YYYY}/{MM}/{NNNN}', // Legacy - kept for backward compatibility.
				'reset_monthly'          => true,
			),
			'pdf'         => array(
				'template'    => 'default',
				'logo_id'     => 0,
				'footer_text' => '',
			),
			'display'     => array(
				'show_order_column' => true,
				'nip_meta_key'      => '_billing_nip',
				'order_status'      => array(
					'enabled' => false,
					'target'  => 'completed',
				),
			),
			'permissions' => array(
				'minimum_role' => PermissionService::DEFAULT_ROLE,
			),
			'email'       => array(
				'auto_send_invoice'        => false,
				'auto_send_receipt'        => false,
				'auto_send_credit_note'    => false,
				'auto_send_receipt_return' => false,
				'send_copy_to_admin'       => false,
				'admin_email_addresses'    => '',
			),
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array<string, mixed> $input Raw settings input.
	 * @return array<string, mixed>
	 */
	public function sanitize_settings( array $input ): array {
		// Retrieve existing settings to avoid overwriting values from other tabs.
		$existing  = get_option( 'ihumbak_invoices_settings', array() );
		$sanitized = is_array( $existing ) ? $existing : array();

		// Sanitize seller data.
		if ( isset( $input['seller'] ) && is_array( $input['seller'] ) ) {
			$sanitized['seller'] = array(
				'name'    => sanitize_text_field( $input['seller']['name'] ?? '' ),
				'details' => sanitize_textarea_field( $input['seller']['details'] ?? '' ),
			);
		}

		// Sanitize numbering settings.
		if ( isset( $input['numbering'] ) && is_array( $input['numbering'] ) ) {
			$sanitized['numbering'] = array(
				'invoice_pattern'        => sanitize_text_field( $input['numbering']['invoice_pattern'] ?? 'FV/{YYYY}/{MM}/{NNNN}' ),
				'receipt_pattern'        => sanitize_text_field( $input['numbering']['receipt_pattern'] ?? 'PAR/{YYYY}/{MM}/{NNNN}' ),
				'credit_note_pattern'    => sanitize_text_field( $input['numbering']['credit_note_pattern'] ?? 'CN/{YYYY}/{MM}/{NNNN}' ),
				'receipt_return_pattern' => sanitize_text_field( $input['numbering']['receipt_return_pattern'] ?? 'RR/{YYYY}/{MM}/{NNNN}' ),
				'correction_pattern'     => sanitize_text_field( $input['numbering']['correction_pattern'] ?? 'FK/{YYYY}/{MM}/{NNNN}' ),
				'reset_monthly'          => ! empty( $input['numbering']['reset_monthly'] ),
			);
		}

		// Sanitize PDF settings.
		if ( isset( $input['pdf'] ) && is_array( $input['pdf'] ) ) {
			$sanitized['pdf'] = array(
				'template'    => sanitize_text_field( $input['pdf']['template'] ?? 'default' ),
				'logo_id'     => absint( $input['pdf']['logo_id'] ?? 0 ),
				'footer_text' => sanitize_textarea_field( $input['pdf']['footer_text'] ?? '' ),
			);
		}

		// Sanitize display settings.
		if ( isset( $input['display'] ) && is_array( $input['display'] ) ) {
			// Preserve existing order_status settings if not being updated.
			$existing_order_status = $sanitized['display']['order_status'] ?? array(
				'enabled' => false,
				'target'  => 'completed',
			);

			$sanitized['display'] = array(
				'show_order_column' => ! empty( $input['display']['show_order_column'] ),
				'nip_meta_key'      => sanitize_text_field( $input['display']['nip_meta_key'] ?? '_billing_nip' ),
				'order_status'      => $existing_order_status,
			);

			// Sanitize order_status settings if provided.
			if ( isset( $input['display']['order_status'] ) && is_array( $input['display']['order_status'] ) ) {
				$order_status_input = $input['display']['order_status'];
				$target_status      = sanitize_text_field( $order_status_input['target'] ?? 'completed' );

				// Validate target against WooCommerce order statuses.
				if ( function_exists( 'wc_get_order_statuses' ) ) {
					$valid_statuses = array_keys( wc_get_order_statuses() );
					if ( ! in_array( 'wc-' . $target_status, $valid_statuses, true ) && ! in_array( $target_status, $valid_statuses, true ) ) {
						$target_status = 'completed';
					}
				}

				$sanitized['display']['order_status'] = array(
					'enabled' => ! empty( $order_status_input['enabled'] ),
					'target'  => $target_status,
				);
			}
		}

		// Sanitize permissions settings.
		if ( isset( $input['permissions'] ) && is_array( $input['permissions'] ) ) {
			$minimum_role = sanitize_text_field( $input['permissions']['minimum_role'] ?? PermissionService::DEFAULT_ROLE );

			$sanitized['permissions'] = array(
				'minimum_role' => PermissionService::isValidRole( $minimum_role )
					? $minimum_role
					: PermissionService::DEFAULT_ROLE,
			);
		}

		// Sanitize email settings.
		if ( isset( $input['email'] ) && is_array( $input['email'] ) ) {
			// Sanitize comma-separated admin email addresses.
			$admin_emails = '';
			if ( ! empty( $input['email']['admin_email_addresses'] ) ) {
				$raw_emails       = sanitize_text_field( $input['email']['admin_email_addresses'] );
				$email_array      = array_map( 'trim', explode( ',', $raw_emails ) );
				$valid_emails     = array_filter( $email_array, 'is_email' );
				$sanitized_emails = array_map( 'sanitize_email', $valid_emails );
				$admin_emails     = implode( ', ', $sanitized_emails );
			}

			$sanitized['email'] = array(
				'auto_send_invoice'        => ! empty( $input['email']['auto_send_invoice'] ),
				'auto_send_receipt'        => ! empty( $input['email']['auto_send_receipt'] ),
				'auto_send_credit_note'    => ! empty( $input['email']['auto_send_credit_note'] ),
				'auto_send_receipt_return' => ! empty( $input['email']['auto_send_receipt_return'] ),
				'send_copy_to_admin'       => ! empty( $input['email']['send_copy_to_admin'] ),
				'admin_email_addresses'    => $admin_emails,
			);
		}

		return $sanitized;
	}

	/**
	 * Get the container.
	 *
	 * @return Container
	 */
	public function container(): Container {
		return $this->container;
	}

	/**
	 * Get the permission service.
	 *
	 * @return PermissionService
	 */
	public function getPermissionService(): PermissionService {
		return $this->permission_service;
	}

	/**
	 * Get the email service.
	 *
	 * @return EmailService|null
	 */
	public function getEmailService(): ?EmailService {
		return $this->email_service;
	}

	/**
	 * Get the update service.
	 *
	 * @return UpdateService|null
	 */
	public function getUpdateService(): ?UpdateService {
		return $this->update_service;
	}

	/**
	 * Check if current page is WooCommerce order edit page.
	 *
	 * @param string $hook Current admin page hook.
	 * @return bool
	 */
	private function is_order_edit_page( string $hook ): bool {
		// HPOS: woocommerce_page_wc-orders.
		if ( 'woocommerce_page_wc-orders' === $hook ) {
			return true;
		}

		// Legacy: post.php with shop_order post type.
		if ( 'post.php' === $hook ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
			if ( $post_id && 'shop_order' === get_post_type( $post_id ) ) {
				return true;
			}
		}

		return false;
	}
}
