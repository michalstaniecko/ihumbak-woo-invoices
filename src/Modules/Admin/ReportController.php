<?php
/**
 * Report Controller.
 *
 * @package IHumbak\Invoices\Modules\Admin
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Modules\Admin;

use IHumbak\Invoices\Modules\Report\ReportService;
use IHumbak\Invoices\Modules\Report\CsvExporter;
use IHumbak\Invoices\Core\Plugin;

/**
 * Controller for monthly reports admin page.
 */
class ReportController {

	/**
	 * Nonce action for report export.
	 *
	 * @var string
	 */
	public const EXPORT_NONCE_ACTION = 'ihumbak_export_report';

	/**
	 * Report service.
	 *
	 * @var ReportService
	 */
	private ReportService $report_service;

	/**
	 * CSV exporter.
	 *
	 * @var CsvExporter
	 */
	private CsvExporter $csv_exporter;

	/**
	 * Constructor.
	 *
	 * @param ReportService|null $report_service Optional report service for DI.
	 * @param CsvExporter|null   $csv_exporter   Optional CSV exporter for DI.
	 */
	public function __construct(
		?ReportService $report_service = null,
		?CsvExporter $csv_exporter = null
	) {
		$this->report_service = $report_service ?? new ReportService();
		$this->csv_exporter   = $csv_exporter ?? new CsvExporter();
	}

	/**
	 * Initialize controller hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_post_ihumbak_export_report_csv', array( $this, 'handle_csv_export' ) );
	}

	/**
	 * Render reports page.
	 *
	 * @return void
	 */
	public function render_reports_page(): void {
		$filters = $this->get_filter_values();

		$year          = $filters['year'];
		$month         = $filters['month'];
		$document_type = $filters['document_type'];

		$report_data = $this->report_service->getMonthlyReport( $year, $month, $document_type );
		$totals      = $this->report_service->calculateTotals( $report_data );

		$available_years       = $this->report_service->getAvailableYears();
		$month_options         = $this->report_service->getMonthOptions();
		$document_type_options = $this->report_service->getDocumentTypeOptions();

		include IHUMBAK_INVOICES_PATH . 'templates/admin/reports.php';
	}

	/**
	 * Handle CSV export.
	 *
	 * @return void
	 */
	public function handle_csv_export(): void {
		// Check permissions.
		if ( ! Plugin::get_instance()->getPermissionService()->canManageDocuments() ) {
			wp_die( esc_html__( 'You do not have permission to export reports.', 'ihumbak-invoices' ) );
		}

		// Verify nonce.
		$nonce = isset( $_POST['ihumbak_export_nonce'] )
			? sanitize_text_field( wp_unslash( $_POST['ihumbak_export_nonce'] ) )
			: '';

		if ( ! wp_verify_nonce( $nonce, self::EXPORT_NONCE_ACTION ) ) {
			wp_die( esc_html__( 'Security check failed.', 'ihumbak-invoices' ) );
		}

		// Get and validate parameters.
		$year          = isset( $_POST['year'] ) ? absint( $_POST['year'] ) : (int) gmdate( 'Y' );
		$month         = isset( $_POST['month'] ) ? absint( $_POST['month'] ) : (int) gmdate( 'n' );
		$document_type = isset( $_POST['document_type'] )
			? sanitize_text_field( wp_unslash( $_POST['document_type'] ) )
			: 'invoice';

		// Validate month range.
		if ( $month < 1 || $month > 12 ) {
			$month = (int) gmdate( 'n' );
		}

		// Validate document type.
		if ( ! $this->report_service->isValidDocumentType( $document_type ) ) {
			$document_type = 'invoice';
		}

		// Generate report.
		$report_data = $this->report_service->getMonthlyReport( $year, $month, $document_type );
		$totals      = $this->report_service->calculateTotals( $report_data );
		$filename    = CsvExporter::generateFilename( $document_type, $year, $month );

		// Export CSV.
		$this->csv_exporter->export( $report_data, $totals, $filename );
	}

	/**
	 * Get filter values from request.
	 *
	 * @return array<string, mixed> Filter values.
	 */
	private function get_filter_values(): array {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$year          = isset( $_GET['year'] ) ? absint( $_GET['year'] ) : (int) gmdate( 'Y' );
		$month         = isset( $_GET['month'] ) ? absint( $_GET['month'] ) : (int) gmdate( 'n' );
		$document_type = isset( $_GET['document_type'] )
			? sanitize_text_field( wp_unslash( $_GET['document_type'] ) )
			: 'invoice';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// Validate month range.
		if ( $month < 1 || $month > 12 ) {
			$month = (int) gmdate( 'n' );
		}

		// Validate document type.
		if ( ! $this->report_service->isValidDocumentType( $document_type ) ) {
			$document_type = 'invoice';
		}

		return array(
			'year'          => $year,
			'month'         => $month,
			'document_type' => $document_type,
		);
	}
}
