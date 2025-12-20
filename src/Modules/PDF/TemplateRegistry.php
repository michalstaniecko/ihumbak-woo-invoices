<?php
/**
 * Template Registry.
 *
 * Discovers and manages available PDF template sets.
 *
 * @package IHumbak\Invoices\Modules\PDF
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Modules\PDF;

/**
 * Registry for discovering available template sets.
 */
class TemplateRegistry {

	/**
	 * Template loader instance.
	 *
	 * @var TemplateLoader
	 */
	private TemplateLoader $template_loader;

	/**
	 * Cache transient name.
	 */
	private const CACHE_KEY = 'ihumbak_template_sets';

	/**
	 * Cache expiration in seconds.
	 */
	private const CACHE_EXPIRATION = HOUR_IN_SECONDS;

	/**
	 * Required templates for a valid set.
	 *
	 * @phpstan-ignore-next-line Constant is reserved for future validation use.
	 */
	private const REQUIRED_TEMPLATES = array( 'invoice', 'receipt' );

	/**
	 * Constructor.
	 *
	 * @param TemplateLoader|null $template_loader Template loader instance.
	 */
	public function __construct( ?TemplateLoader $template_loader = null ) {
		$this->template_loader = $template_loader ?? new TemplateLoader();
	}

	/**
	 * Get all available template sets.
	 *
	 * @param bool $use_cache Whether to use cached results.
	 * @return array<string, array{name: string, path: string, source: string, has_invoice: bool, has_receipt: bool, has_styles: bool}>
	 */
	public function getAvailableTemplateSets( bool $use_cache = true ): array {
		if ( $use_cache ) {
			$cached = get_transient( self::CACHE_KEY );
			if ( false !== $cached && is_array( $cached ) ) {
				return $cached;
			}
		}

		$template_sets = array();
		$paths         = $this->template_loader->getTemplatePaths();

		foreach ( $paths as $path ) {
			if ( ! is_dir( $path ) ) {
				continue;
			}

			$source = $this->determineSource( $path );
			$dirs   = $this->scanDirectory( $path );

			foreach ( $dirs as $dir_name ) {
				// Skip if already found (theme takes priority).
				if ( isset( $template_sets[ $dir_name ] ) ) {
					continue;
				}

				$dir_path = trailingslashit( $path ) . $dir_name;
				$info     = $this->getTemplateSetInfo( $dir_name, $dir_path, $source );

				// Only include valid template sets.
				if ( $info['has_invoice'] || $info['has_receipt'] ) {
					$template_sets[ $dir_name ] = $info;
				}
			}
		}

		// Ensure 'default' is always first if it exists.
		if ( isset( $template_sets['default'] ) ) {
			$default = $template_sets['default'];
			unset( $template_sets['default'] );
			$template_sets = array( 'default' => $default ) + $template_sets;
		}

		// Cache the results.
		set_transient( self::CACHE_KEY, $template_sets, self::CACHE_EXPIRATION );

		return $template_sets;
	}

	/**
	 * Get information about a specific template set.
	 *
	 * @param string $set_name Template set name.
	 * @param string $path     Full path to the template set directory.
	 * @param string $source   Source ('plugin', 'theme', 'child-theme').
	 * @return array{name: string, path: string, source: string, has_invoice: bool, has_receipt: bool, has_styles: bool}
	 */
	private function getTemplateSetInfo( string $set_name, string $path, string $source ): array {
		return array(
			'name'        => $this->formatSetName( $set_name ),
			'path'        => $path,
			'source'      => $source,
			'has_invoice' => file_exists( trailingslashit( $path ) . 'invoice.php' ),
			'has_receipt' => file_exists( trailingslashit( $path ) . 'receipt.php' ),
			'has_styles'  => file_exists( trailingslashit( $path ) . 'styles.css' ),
		);
	}

	/**
	 * Check if a template set is valid.
	 *
	 * @param string $template_set Template set name.
	 * @return bool True if the template set exists and has required templates.
	 */
	public function isValidTemplateSet( string $template_set ): bool {
		$sets = $this->getAvailableTemplateSets();
		return isset( $sets[ $template_set ] );
	}

	/**
	 * Check if a template set is complete (has all required templates).
	 *
	 * @param string $template_set Template set name.
	 * @return bool True if all required templates exist.
	 */
	public function isCompleteTemplateSet( string $template_set ): bool {
		$sets = $this->getAvailableTemplateSets();

		if ( ! isset( $sets[ $template_set ] ) ) {
			return false;
		}

		$info = $sets[ $template_set ];
		return $info['has_invoice'] && $info['has_receipt'];
	}

	/**
	 * Get template set info by name.
	 *
	 * @param string $template_set Template set name.
	 * @return array{name: string, path: string, source: string, has_invoice: bool, has_receipt: bool, has_styles: bool}|null
	 */
	public function getTemplateSet( string $template_set ): ?array {
		$sets = $this->getAvailableTemplateSets();
		return $sets[ $template_set ] ?? null;
	}

	/**
	 * Get template sets formatted for a select field.
	 *
	 * @return array<string, string> Key-value pairs for select options.
	 */
	public function getSelectOptions(): array {
		$sets    = $this->getAvailableTemplateSets();
		$options = array();

		foreach ( $sets as $key => $info ) {
			$label = $info['name'];

			// Add source indicator for non-plugin templates.
			if ( 'plugin' !== $info['source'] ) {
				$source_label = 'child-theme' === $info['source']
					? __( 'Child Theme', 'ihumbak-invoices' )
					: __( 'Theme', 'ihumbak-invoices' );
				$label       .= ' (' . $source_label . ')';
			}

			// Add warning if incomplete.
			if ( ! $info['has_invoice'] || ! $info['has_receipt'] ) {
				$label .= ' ' . __( '[Incomplete]', 'ihumbak-invoices' );
			}

			$options[ $key ] = $label;
		}

		return $options;
	}

	/**
	 * Clear the template sets cache.
	 *
	 * @return void
	 */
	public function clearCache(): void {
		delete_transient( self::CACHE_KEY );
		$this->template_loader->clearCache();
	}

	/**
	 * Scan a directory for subdirectories.
	 *
	 * @param string $path Directory path.
	 * @return array<string> List of subdirectory names.
	 */
	private function scanDirectory( string $path ): array {
		$dirs = array();

		$iterator = new \DirectoryIterator( $path );

		foreach ( $iterator as $item ) {
			if ( $item->isDot() || ! $item->isDir() ) {
				continue;
			}

			$name = $item->getFilename();

			// Skip hidden directories.
			if ( strpos( $name, '.' ) === 0 ) {
				continue;
			}

			$dirs[] = $name;
		}

		sort( $dirs );
		return $dirs;
	}

	/**
	 * Determine the source of a template path.
	 *
	 * @param string $path Template path.
	 * @return string 'plugin', 'theme', or 'child-theme'.
	 */
	private function determineSource( string $path ): string {
		$plugin_path = trailingslashit( IHUMBAK_INVOICES_PATH ) . 'templates/pdf';
		$theme_path  = trailingslashit( get_template_directory() ) . 'ihumbak-invoices';
		$child_path  = trailingslashit( get_stylesheet_directory() ) . 'ihumbak-invoices';

		if ( is_child_theme() && strpos( $path, $child_path ) !== false ) {
			return 'child-theme';
		}

		if ( strpos( $path, $theme_path ) !== false ) {
			return 'theme';
		}

		return 'plugin';
	}

	/**
	 * Format a set name for display.
	 *
	 * @param string $set_name Raw set name.
	 * @return string Formatted name.
	 */
	private function formatSetName( string $set_name ): string {
		// Handle common abbreviations.
		$labels = array(
			'default' => __( 'Default (EU Standard)', 'ihumbak-invoices' ),
			'eu'      => __( 'EU Standard', 'ihumbak-invoices' ),
			'pl'      => __( 'Polish', 'ihumbak-invoices' ),
			'de'      => __( 'German', 'ihumbak-invoices' ),
			'fr'      => __( 'French', 'ihumbak-invoices' ),
			'es'      => __( 'Spanish', 'ihumbak-invoices' ),
			'it'      => __( 'Italian', 'ihumbak-invoices' ),
		);

		if ( isset( $labels[ $set_name ] ) ) {
			return $labels[ $set_name ];
		}

		// Convert slug to title case.
		return ucwords( str_replace( array( '-', '_' ), ' ', $set_name ) );
	}

	/**
	 * Get the default template set name.
	 *
	 * @return string Default template set name.
	 */
	public function getDefaultTemplateSet(): string {
		$sets = $this->getAvailableTemplateSets();

		// Prefer 'default' if available.
		if ( isset( $sets['default'] ) ) {
			return 'default';
		}

		// Return first available set.
		$keys = array_keys( $sets );
		return ! empty( $keys ) ? $keys[0] : 'default';
	}
}
