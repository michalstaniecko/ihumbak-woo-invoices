<?php
/**
 * PDF Generator.
 *
 * Generates PDF documents using DOMPDF.
 *
 * @package IHumbak\Invoices\Modules\PDF
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Modules\PDF;

use Dompdf\Dompdf;
use Dompdf\Options;
use IHumbak\Invoices\Models\Document;
use IHumbak\Invoices\Models\CreditNote;
use IHumbak\Invoices\Models\ReceiptReturn;
use IHumbak\Invoices\Infrastructure\Database\DocumentRepository;
use IHumbak\Invoices\Infrastructure\Database\DocumentItemRepository;
use IHumbak\Invoices\Infrastructure\Traits\SiteLocaleTrait;
use IHumbak\Invoices\Core\Plugin;

/**
 * Generates PDF documents from templates.
 */
class PdfGenerator {

	use SiteLocaleTrait;

	/**
	 * Template loader instance.
	 *
	 * @var TemplateLoader
	 */
	private TemplateLoader $template_loader;

	/**
	 * Cache manager instance.
	 *
	 * @var PdfCacheManager
	 */
	private PdfCacheManager $cache_manager;

	/**
	 * Template registry instance.
	 *
	 * @var TemplateRegistry
	 */
	private TemplateRegistry $template_registry;

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
	 * Constructor.
	 *
	 * @param TemplateLoader|null         $template_loader      Template loader instance.
	 * @param PdfCacheManager|null        $cache_manager        Cache manager instance.
	 * @param TemplateRegistry|null       $template_registry    Template registry instance.
	 * @param DocumentRepository|null     $document_repository  Document repository instance.
	 * @param DocumentItemRepository|null $item_repository      Document item repository instance.
	 */
	public function __construct(
		?TemplateLoader $template_loader = null,
		?PdfCacheManager $cache_manager = null,
		?TemplateRegistry $template_registry = null,
		?DocumentRepository $document_repository = null,
		?DocumentItemRepository $item_repository = null
	) {
		$this->template_loader     = $template_loader ?? new TemplateLoader();
		$this->cache_manager       = $cache_manager ?? new PdfCacheManager();
		$this->template_registry   = $template_registry ?? new TemplateRegistry( $this->template_loader );
		$this->document_repository = $document_repository ?? new DocumentRepository();
		$this->item_repository     = $item_repository ?? new DocumentItemRepository();
	}

	/**
	 * Generate PDF for a document and save to cache.
	 *
	 * @param Document $document The document.
	 * @param bool     $force    Force regeneration even if cached.
	 * @return string Path to the generated PDF file.
	 * @throws \RuntimeException If PDF generation fails.
	 */
	public function generate( Document $document, bool $force = false ): string {
		// Return cached version if available.
		if ( ! $force && $this->cache_manager->hasCachedPdf( $document ) ) {
			return $this->cache_manager->getCachePath( $document );
		}

		$content = $this->generateContent( $document );
		return $this->cache_manager->savePdf( $document, $content );
	}

	/**
	 * Generate PDF content for a document.
	 *
	 * @param Document $document The document.
	 * @return string PDF content.
	 * @throws \RuntimeException If PDF generation fails.
	 */
	public function generateContent( Document $document ): string {
		// Switch to site locale before generating PDF.
		// This ensures PDF uses site language instead of admin user language.
		$locale_switched = $this->switchToSiteLocale( 'ihumbak_pdf_locale' );

		try {
			$pdf_content = $this->generatePdfContent( $document );
		} finally {
			// Always restore locale, even if an exception occurs.
			if ( $locale_switched ) {
				$this->restoreLocale();
			}
		}

		return $pdf_content;
	}

	/**
	 * Internal method to generate PDF content.
	 *
	 * This is the actual PDF generation logic, extracted to allow locale switching wrapper.
	 *
	 * @param Document $document The document.
	 * @return string PDF content.
	 * @throws \RuntimeException If PDF generation fails.
	 */
	private function generatePdfContent( Document $document ): string {
		/**
		 * Fires before PDF generation starts.
		 *
		 * @param Document $document The document being rendered.
		 */
		do_action( 'ihumbak_before_pdf_render', $document );

		// Get template set from settings.
		$settings     = Plugin::get_instance()->get_settings();
		$template_set = $settings['pdf']['template'] ?? 'default';

		// Validate template set.
		if ( ! $this->template_registry->isValidTemplateSet( $template_set ) ) {
			$template_set = $this->template_registry->getDefaultTemplateSet();
		}

		// Prepare template data.
		$data = $this->prepareTemplateData( $document, $settings );

		/**
		 * Filter the data passed to PDF template.
		 *
		 * @param array<string, mixed> $data     Template data.
		 * @param Document             $document The document.
		 */
		$data = apply_filters( 'ihumbak_pdf_data', $data, $document );

		// Get template name for document type.
		$template_name = $this->template_loader->getTemplateNameForDocument( $document );

		// Render HTML.
		$html = $this->template_loader->render( $template_set, $template_name, $data );

		/**
		 * Filter the rendered HTML before PDF conversion.
		 *
		 * @param string   $html     Rendered HTML.
		 * @param Document $document The document.
		 */
		$html = apply_filters( 'ihumbak_pdf_html', $html, $document );

		// Generate PDF.
		$pdf_content = $this->renderPdf( $html );

		/**
		 * Fires after PDF has been generated.
		 *
		 * @param Document $document    The document.
		 * @param string   $pdf_content PDF content.
		 */
		do_action( 'ihumbak_after_pdf_generated', $document, $pdf_content );

		return $pdf_content;
	}

	/**
	 * Stream PDF directly to browser.
	 *
	 * @param Document $document The document.
	 * @param bool     $force    Force regeneration even if cached.
	 * @return void
	 */
	public function stream( Document $document, bool $force = false ): void {
		$content  = $force ? $this->generateContent( $document ) : null;
		$filename = $this->cache_manager->generateFilename( $document );

		if ( null === $content ) {
			if ( $this->cache_manager->hasCachedPdf( $document ) ) {
				$content = $this->cache_manager->getCachedPdf( $document );
			} else {
				$content = $this->generateContent( $document );
				$this->cache_manager->savePdf( $document, $content );
			}
		}

		// Set headers.
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: inline; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( $content ) );
		header( 'Cache-Control: private, max-age=0, must-revalidate' );
		header( 'Pragma: public' );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $content;
	}

	/**
	 * Download PDF (force download).
	 *
	 * @param Document $document The document.
	 * @param bool     $force    Force regeneration even if cached.
	 * @return void
	 */
	public function download( Document $document, bool $force = false ): void {
		$content  = $force ? $this->generateContent( $document ) : null;
		$filename = $this->cache_manager->generateFilename( $document );

		if ( null === $content ) {
			if ( $this->cache_manager->hasCachedPdf( $document ) ) {
				$content = $this->cache_manager->getCachedPdf( $document );
			} else {
				$content = $this->generateContent( $document );
				$this->cache_manager->savePdf( $document, $content );
			}
		} else {
			// Save regenerated PDF to cache.
			$this->cache_manager->savePdf( $document, $content );
		}

		// Set headers for download.
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( $content ) );
		header( 'Cache-Control: private, max-age=0, must-revalidate' );
		header( 'Pragma: public' );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $content;
	}

	/**
	 * Prepare data for the template.
	 *
	 * @param Document             $document The document.
	 * @param array<string, mixed> $settings Plugin settings.
	 * @return array<string, mixed>
	 */
	private function prepareTemplateData( Document $document, array $settings ): array {
		$data = array(
			'document' => $document,
			'seller'   => $document->getSeller(),
			'buyer'    => $document->getBuyer(),
			'items'    => $document->getItems(),
			'settings' => $settings,
			'logo_url' => $this->getLogoUrl( $settings ),
		);

		// Add VAT breakdown.
		$data['vat_breakdown'] = $this->calculateVatBreakdown( $document->getItems() );

		// Add formatted values.
		$data['formatted'] = array(
			'subtotal'  => $this->formatMoney( $document->getSubtotal(), $document->getCurrency() ),
			'tax_total' => $this->formatMoney( $document->getTaxTotal(), $document->getCurrency() ),
			'total'     => $this->formatMoney( $document->getTotal(), $document->getCurrency() ),
		);

		// Add original document data for credit notes and receipt returns.
		if ( ( $document instanceof CreditNote || $document instanceof ReceiptReturn ) && $document->getCorrectedDocumentId() ) {
			$original_document = $this->document_repository->find( $document->getCorrectedDocumentId() );
			$original_items    = $this->item_repository->findByDocumentId( $document->getCorrectedDocumentId() );

			$data['original_document'] = $original_document;
			$data['original_items']    = $original_items;
		}

		return $data;
	}

	/**
	 * Calculate VAT breakdown by tax rate.
	 *
	 * @param array<\IHumbak\Invoices\Models\DocumentItem> $items Document items.
	 * @return array<string, array{rate: float, net: float, tax: float, gross: float}>
	 */
	private function calculateVatBreakdown( array $items ): array {
		$breakdown = array();

		foreach ( $items as $item ) {
			$rate = (string) $item->getTaxRate();

			if ( ! isset( $breakdown[ $rate ] ) ) {
				$breakdown[ $rate ] = array(
					'rate'  => $item->getTaxRate(),
					'net'   => 0.0,
					'tax'   => 0.0,
					'gross' => 0.0,
				);
			}

			$breakdown[ $rate ]['net']   += $item->getLineTotalNet();
			$breakdown[ $rate ]['tax']   += $item->getTaxAmount();
			$breakdown[ $rate ]['gross'] += $item->getLineTotalGross();
		}

		// Sort by rate.
		ksort( $breakdown );

		return $breakdown;
	}

	/**
	 * Get logo URL from settings.
	 *
	 * @param array<string, mixed> $settings Plugin settings.
	 * @return string|null Logo URL or null.
	 */
	private function getLogoUrl( array $settings ): ?string {
		$logo_id = $settings['pdf']['logo_id'] ?? 0;

		if ( empty( $logo_id ) ) {
			return null;
		}

		$logo_path = get_attached_file( $logo_id );

		if ( ! $logo_path || ! file_exists( $logo_path ) ) {
			return null;
		}

		// Convert to base64 data URI for DOMPDF.
		$mime_type = mime_content_type( $logo_path );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading local image file.
		$logo_data = file_get_contents( $logo_path );

		if ( false === $logo_data ) {
			return null;
		}

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Required for data URI in PDF.
		return 'data:' . $mime_type . ';base64,' . base64_encode( $logo_data );
	}

	/**
	 * Render HTML to PDF using DOMPDF.
	 *
	 * @param string $html HTML content.
	 * @return string PDF content.
	 * @throws \RuntimeException If PDF rendering fails.
	 */
	private function renderPdf( string $html ): string {
		$options = $this->getDompdfOptions();

		/**
		 * Filter DOMPDF options.
		 *
		 * @param Options $options DOMPDF options.
		 */
		$options = apply_filters( 'ihumbak_pdf_options', $options );

		$dompdf = new Dompdf( $options );

		// Set paper size.
		$dompdf->setPaper( 'A4', 'portrait' );

		// Load HTML.
		$dompdf->loadHtml( $html );

		// Render PDF.
		$dompdf->render();

		// Get output.
		$output = $dompdf->output();

		if ( empty( $output ) ) {
			throw new \RuntimeException( 'Failed to generate PDF content.' );
		}

		return $output;
	}

	/**
	 * Get DOMPDF options.
	 *
	 * @return Options
	 */
	private function getDompdfOptions(): Options {
		$options = new Options();

		// Enable remote content (for logos).
		$options->set( 'isRemoteEnabled', true );

		// Use HTML5 parser.
		$options->set( 'isHtml5ParserEnabled', true );

		// Default font with UTF-8 support.
		$options->set( 'defaultFont', 'DejaVu Sans' );

		// Set allowed paths for security.
		$chroot = array(
			IHUMBAK_INVOICES_PATH . 'templates/',
			get_stylesheet_directory() . '/ihumbak-invoices/',
			get_template_directory() . '/ihumbak-invoices/',
		);

		// Add uploads directory for logos.
		$upload_dir = wp_upload_dir();
		$chroot[]   = $upload_dir['basedir'];

		$options->set( 'chroot', $chroot );

		// Enable font subsetting.
		$options->set( 'isFontSubsettingEnabled', true );

		return $options;
	}

	/**
	 * Format money value.
	 *
	 * @param float  $amount   Amount.
	 * @param string $currency Currency code.
	 * @return string Formatted value.
	 */
	private function formatMoney( float $amount, string $currency = 'EUR' ): string {
		return number_format( $amount, 2, '.', ' ' ) . ' ' . $currency;
	}

	/**
	 * Delete cached PDF for a document.
	 *
	 * @param Document $document The document.
	 * @return bool True if deleted.
	 */
	public function deleteCachedPdf( Document $document ): bool {
		return $this->cache_manager->deletePdf( $document );
	}

	/**
	 * Check if a cached PDF exists for a document.
	 *
	 * @param Document $document The document.
	 * @return bool True if cached.
	 */
	public function hasCachedPdf( Document $document ): bool {
		return $this->cache_manager->hasCachedPdf( $document );
	}

	/**
	 * Get the cache manager instance.
	 *
	 * @return PdfCacheManager
	 */
	public function getCacheManager(): PdfCacheManager {
		return $this->cache_manager;
	}

	/**
	 * Get the template loader instance.
	 *
	 * @return TemplateLoader
	 */
	public function getTemplateLoader(): TemplateLoader {
		return $this->template_loader;
	}

	/**
	 * Get the template registry instance.
	 *
	 * @return TemplateRegistry
	 */
	public function getTemplateRegistry(): TemplateRegistry {
		return $this->template_registry;
	}
}
