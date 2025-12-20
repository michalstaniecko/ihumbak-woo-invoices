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
use IHumbak\Invoices\Modules\PDF\PdfGenerator;
use IHumbak\Invoices\Modules\PDF\PdfCacheManager;
use IHumbak\Invoices\Modules\PDF\TemplateLoader;
use IHumbak\Invoices\Modules\PDF\TemplateRegistry;
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
		$this->init_hooks();
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
		// Register core services.
		$this->container->register( 'installer', fn() => new Installer() );

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
		}
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

		// WooCommerce hooks.
		add_action( 'woocommerce_order_status_changed', array( $this, 'handle_order_status_change' ), 10, 4 );
	}

	/**
	 * Register admin menu.
	 *
	 * @return void
	 */
	public function register_admin_menu(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Invoices', 'ihumbak-invoices' ),
			__( 'Invoices', 'ihumbak-invoices' ),
			'manage_woocommerce',
			'ihumbak-invoices',
			array( $this, 'render_admin_page' )
		);

		add_submenu_page(
			'woocommerce',
			__( 'Invoice Settings', 'ihumbak-invoices' ),
			__( 'Invoice Settings', 'ihumbak-invoices' ),
			'manage_woocommerce',
			'ihumbak-invoices-settings',
			array( $this, 'render_settings_page' )
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

		wp_localize_script(
			'ihumbak-invoices-admin',
			'ihumbakInvoices',
			array(
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'ihumbak_invoices_nonce' ),
				'selectLogo' => __( 'Select Logo', 'ihumbak-invoices' ),
				'useLogo'    => __( 'Use this logo', 'ihumbak-invoices' ),
				'i18n'       => array(
					'confirmDelete' => __( 'Are you sure you want to delete this item?', 'ihumbak-invoices' ),
					'calculating'   => __( 'Calculating...', 'ihumbak-invoices' ),
					'error'         => __( 'An error occurred. Please try again.', 'ihumbak-invoices' ),
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
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
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
				} else {
					$this->document_controller->render_invoice_edit( $id );
				}
				break;

			case 'pdf':
				// PDF download is handled early in admin_init to avoid output buffering issues.
				// This case should not be reached, but redirect to list if it is.
				wp_safe_redirect( admin_url( 'admin.php?page=ihumbak-invoices' ) );
				exit;

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
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'ihumbak-invoices' ) );
		}

		include IHUMBAK_INVOICES_PATH . 'templates/admin/settings.php';
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
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
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
	 * Handle order status change.
	 *
	 * @param int       $order_id   Order ID.
	 * @param string    $old_status Old status.
	 * @param string    $new_status New status.
	 * @param \WC_Order $order      Order object.
	 * @return void
	 */
	public function handle_order_status_change( int $order_id, string $old_status, string $new_status, \WC_Order $order ): void {
		$settings = $this->get_settings();

		if ( empty( $settings['automation']['auto_generate_invoice'] ) ) {
			return;
		}

		$trigger_status = $settings['automation']['trigger_status'] ?? 'completed';

		if ( $new_status === $trigger_status ) {
			/**
			 * Fires when an invoice should be auto-generated.
			 *
			 * @param int       $order_id Order ID.
			 * @param \WC_Order $order    Order object.
			 */
			do_action( 'ihumbak_auto_generate_invoice', $order_id, $order );
		}
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
			'seller'     => array(
				'name'         => '',
				'address'      => '',
				'city'         => '',
				'postcode'     => '',
				'country'      => 'PL',
				'nip'          => '',
				'bank_name'    => '',
				'bank_account' => '',
				'email'        => '',
				'phone'        => '',
			),
			'numbering'  => array(
				'invoice_pattern'    => 'FV/{YYYY}/{MM}/{NNNN}',
				'receipt_pattern'    => 'PAR/{YYYY}/{MM}/{NNNN}',
				'correction_pattern' => 'FK/{YYYY}/{MM}/{NNNN}',
				'reset_monthly'      => true,
			),
			'pdf'        => array(
				'template'    => 'default',
				'logo_id'     => 0,
				'footer_text' => '',
			),
			'automation' => array(
				'auto_generate_invoice' => false,
				'auto_generate_receipt' => false,
				'trigger_status'        => 'completed',
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
		$sanitized = array();

		// Sanitize seller data.
		if ( isset( $input['seller'] ) && is_array( $input['seller'] ) ) {
			$sanitized['seller'] = array(
				'name'         => sanitize_text_field( $input['seller']['name'] ?? '' ),
				'address'      => sanitize_text_field( $input['seller']['address'] ?? '' ),
				'city'         => sanitize_text_field( $input['seller']['city'] ?? '' ),
				'postcode'     => sanitize_text_field( $input['seller']['postcode'] ?? '' ),
				'country'      => sanitize_text_field( $input['seller']['country'] ?? 'PL' ),
				'nip'          => sanitize_text_field( $input['seller']['nip'] ?? '' ),
				'bank_name'    => sanitize_text_field( $input['seller']['bank_name'] ?? '' ),
				'bank_account' => sanitize_text_field( $input['seller']['bank_account'] ?? '' ),
				'email'        => sanitize_email( $input['seller']['email'] ?? '' ),
				'phone'        => sanitize_text_field( $input['seller']['phone'] ?? '' ),
			);
		}

		// Sanitize numbering settings.
		if ( isset( $input['numbering'] ) && is_array( $input['numbering'] ) ) {
			$sanitized['numbering'] = array(
				'invoice_pattern'    => sanitize_text_field( $input['numbering']['invoice_pattern'] ?? 'FV/{YYYY}/{MM}/{NNNN}' ),
				'receipt_pattern'    => sanitize_text_field( $input['numbering']['receipt_pattern'] ?? 'PAR/{YYYY}/{MM}/{NNNN}' ),
				'correction_pattern' => sanitize_text_field( $input['numbering']['correction_pattern'] ?? 'FK/{YYYY}/{MM}/{NNNN}' ),
				'reset_monthly'      => ! empty( $input['numbering']['reset_monthly'] ),
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

		// Sanitize automation settings.
		if ( isset( $input['automation'] ) && is_array( $input['automation'] ) ) {
			$sanitized['automation'] = array(
				'auto_generate_invoice' => ! empty( $input['automation']['auto_generate_invoice'] ),
				'auto_generate_receipt' => ! empty( $input['automation']['auto_generate_receipt'] ),
				'trigger_status'        => sanitize_text_field( $input['automation']['trigger_status'] ?? 'completed' ),
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
}
