<?php
/**
 * Template Loader.
 *
 * Locates and loads PDF templates with WordPress theme hierarchy support.
 *
 * @package IHumbak\Invoices\Modules\PDF
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Modules\PDF;

use IHumbak\Invoices\Models\Document;

/**
 * Loads PDF templates with theme override support.
 *
 * Template hierarchy:
 * 1. {child-theme}/ihumbak-invoices/{template-set}/{template}.php
 * 2. {parent-theme}/ihumbak-invoices/{template-set}/{template}.php
 * 3. {plugin}/templates/pdf/{template-set}/{template}.php
 */
class TemplateLoader {

	/**
	 * Theme template directory name.
	 */
	private const THEME_DIR = 'ihumbak-invoices';

	/**
	 * Plugin template directory.
	 */
	private const PLUGIN_DIR = 'templates/pdf';

	/**
	 * Cached template paths.
	 *
	 * @var array<string, string>
	 */
	private array $cache = array();

	/**
	 * Get all template search paths.
	 *
	 * @return array<string> List of paths to search.
	 */
	public function getTemplatePaths(): array {
		$paths = array();

		// Child theme path (if applicable).
		if ( is_child_theme() ) {
			$paths[] = trailingslashit( get_stylesheet_directory() ) . self::THEME_DIR;
		}

		// Parent/current theme path.
		$paths[] = trailingslashit( get_template_directory() ) . self::THEME_DIR;

		// Plugin path.
		$paths[] = trailingslashit( IHUMBAK_INVOICES_PATH ) . self::PLUGIN_DIR;

		/**
		 * Filter the template search paths.
		 *
		 * @param array<string> $paths List of paths to search.
		 */
		return apply_filters( 'ihumbak_template_paths', $paths );
	}

	/**
	 * Locate a template file.
	 *
	 * @param string $template_set  Template set name (e.g., 'default', 'custom').
	 * @param string $template_name Template name without extension (e.g., 'invoice', 'receipt').
	 * @return string|null Full path to the template file, or null if not found.
	 */
	public function locate( string $template_set, string $template_name ): ?string {
		$cache_key = $template_set . '/' . $template_name;

		if ( isset( $this->cache[ $cache_key ] ) ) {
			return $this->cache[ $cache_key ];
		}

		$filename = $template_name . '.php';
		$paths    = $this->getTemplatePaths();

		foreach ( $paths as $path ) {
			$template_path = trailingslashit( $path ) . $template_set . '/' . $filename;

			if ( file_exists( $template_path ) && is_readable( $template_path ) ) {
				/**
				 * Filter the located template path.
				 *
				 * @param string $template_path Full path to the template file.
				 * @param string $template_set  Template set name.
				 * @param string $template_name Template name.
				 */
				$template_path = apply_filters(
					'ihumbak_locate_template',
					$template_path,
					$template_set,
					$template_name
				);

				$this->cache[ $cache_key ] = $template_path;
				return $template_path;
			}
		}

		return null;
	}

	/**
	 * Locate the stylesheet for a template set.
	 *
	 * @param string $template_set Template set name.
	 * @return string|null Full path to the stylesheet, or null if not found.
	 */
	public function locateStylesheet( string $template_set ): ?string {
		$cache_key = $template_set . '/styles.css';

		if ( isset( $this->cache[ $cache_key ] ) ) {
			return $this->cache[ $cache_key ];
		}

		$paths = $this->getTemplatePaths();

		foreach ( $paths as $path ) {
			$stylesheet_path = trailingslashit( $path ) . $template_set . '/styles.css';

			if ( file_exists( $stylesheet_path ) && is_readable( $stylesheet_path ) ) {
				$this->cache[ $cache_key ] = $stylesheet_path;
				return $stylesheet_path;
			}
		}

		return null;
	}

	/**
	 * Load stylesheet content for a template set.
	 *
	 * @param string $template_set Template set name.
	 * @return string CSS content or empty string if not found.
	 */
	public function loadStylesheet( string $template_set ): string {
		$path = $this->locateStylesheet( $template_set );

		if ( null === $path ) {
			return '';
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading local CSS file.
		$content = file_get_contents( $path );
		return false !== $content ? $content : '';
	}

	/**
	 * Render a template with data.
	 *
	 * @param string               $template_set  Template set name.
	 * @param string               $template_name Template name.
	 * @param array<string, mixed> $data          Data to pass to the template.
	 * @return string Rendered HTML.
	 * @throws \RuntimeException If template is not found.
	 */
	public function render( string $template_set, string $template_name, array $data = array() ): string {
		$template_path = $this->locate( $template_set, $template_name );

		if ( null === $template_path ) {
			// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Internal exception message.
			throw new \RuntimeException(
				sprintf(
					'Template not found: %s/%s.php',
					$template_set,
					$template_name
				)
			);
			// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		// Load stylesheet if available.
		$data['styles'] = $this->loadStylesheet( $template_set );

		// Start output buffering.
		ob_start();

		// Extract data to local variables.
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract( $data, EXTR_SKIP );

		// Include the template.
		include $template_path;

		// Get the output and clean the buffer.
		$output = ob_get_clean();

		return false !== $output ? $output : '';
	}

	/**
	 * Get the template name for a document type.
	 *
	 * @param Document $document The document.
	 * @return string Template name.
	 */
	public function getTemplateNameForDocument( Document $document ): string {
		$type = $document->getDocumentType();

		$template_map = array(
			'invoice'     => 'invoice',
			'receipt'     => 'receipt',
			'credit_note' => 'credit-note',
			'correction'  => 'correction', // Legacy - kept for backward compatibility.
		);

		return $template_map[ $type ] ?? 'invoice';
	}

	/**
	 * Check if a template exists.
	 *
	 * @param string $template_set  Template set name.
	 * @param string $template_name Template name.
	 * @return bool True if template exists.
	 */
	public function templateExists( string $template_set, string $template_name ): bool {
		return null !== $this->locate( $template_set, $template_name );
	}

	/**
	 * Get the source of a template (theme or plugin).
	 *
	 * @param string $template_set  Template set name.
	 * @param string $template_name Template name.
	 * @return string 'theme', 'child-theme', 'plugin', or 'unknown'.
	 */
	public function getTemplateSource( string $template_set, string $template_name ): string {
		$template_path = $this->locate( $template_set, $template_name );

		if ( null === $template_path ) {
			return 'unknown';
		}

		$plugin_path = trailingslashit( IHUMBAK_INVOICES_PATH ) . self::PLUGIN_DIR;
		$theme_path  = trailingslashit( get_template_directory() ) . self::THEME_DIR;
		$child_path  = trailingslashit( get_stylesheet_directory() ) . self::THEME_DIR;

		if ( is_child_theme() && strpos( $template_path, $child_path ) === 0 ) {
			return 'child-theme';
		}

		if ( strpos( $template_path, $theme_path ) === 0 ) {
			return 'theme';
		}

		if ( strpos( $template_path, $plugin_path ) === 0 ) {
			return 'plugin';
		}

		return 'unknown';
	}

	/**
	 * Clear the template path cache.
	 *
	 * @return void
	 */
	public function clearCache(): void {
		$this->cache = array();
	}
}
