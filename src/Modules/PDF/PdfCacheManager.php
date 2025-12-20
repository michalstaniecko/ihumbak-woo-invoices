<?php
/**
 * PDF Cache Manager.
 *
 * Manages PDF file cache in wp-content/uploads/ihumbak-invoices/.
 *
 * @package IHumbak\Invoices\Modules\PDF
 */

declare(strict_types=1);

namespace IHumbak\Invoices\Modules\PDF;

use IHumbak\Invoices\Models\Document;

/**
 * Manages PDF file caching and storage.
 */
class PdfCacheManager {

	/**
	 * Base directory name for PDF cache.
	 */
	private const CACHE_DIR = 'ihumbak-invoices';

	/**
	 * Get the base cache directory path.
	 *
	 * @return string
	 */
	public function getBaseDir(): string {
		$upload_dir = wp_upload_dir();
		return trailingslashit( $upload_dir['basedir'] ) . self::CACHE_DIR;
	}

	/**
	 * Get the base cache directory URL.
	 *
	 * @return string
	 */
	public function getBaseUrl(): string {
		$upload_dir = wp_upload_dir();
		return trailingslashit( $upload_dir['baseurl'] ) . self::CACHE_DIR;
	}

	/**
	 * Get the cache path for a document.
	 *
	 * @param Document $document The document.
	 * @return string Full path to the PDF file.
	 */
	public function getCachePath( Document $document ): string {
		$issue_date = $document->getIssueDate();
		$year       = $issue_date ? $issue_date->format( 'Y' ) : gmdate( 'Y' );
		$month      = $issue_date ? $issue_date->format( 'm' ) : gmdate( 'm' );

		$filename = $this->generateFilename( $document );

		return trailingslashit( $this->getBaseDir() ) . $year . '/' . $month . '/' . $filename;
	}

	/**
	 * Get the cache URL for a document.
	 *
	 * @param Document $document The document.
	 * @return string Full URL to the PDF file.
	 */
	public function getCacheUrl( Document $document ): string {
		$issue_date = $document->getIssueDate();
		$year       = $issue_date ? $issue_date->format( 'Y' ) : gmdate( 'Y' );
		$month      = $issue_date ? $issue_date->format( 'm' ) : gmdate( 'm' );

		$filename = $this->generateFilename( $document );

		return trailingslashit( $this->getBaseUrl() ) . $year . '/' . $month . '/' . $filename;
	}

	/**
	 * Generate a filename for a document.
	 *
	 * @param Document $document The document.
	 * @return string The filename.
	 */
	public function generateFilename( Document $document ): string {
		$type   = $document->getDocumentType();
		$number = $document->getDocumentNumber();

		// Sanitize document number for filesystem.
		$safe_number = preg_replace( '/[^a-zA-Z0-9\-_]/', '-', $number );
		$safe_number = preg_replace( '/-+/', '-', $safe_number );
		$safe_number = trim( $safe_number, '-' );

		return $type . '-' . $safe_number . '.pdf';
	}

	/**
	 * Check if a cached PDF exists for a document.
	 *
	 * @param Document $document The document.
	 * @return bool True if cached PDF exists.
	 */
	public function hasCachedPdf( Document $document ): bool {
		$path = $this->getCachePath( $document );
		return file_exists( $path ) && is_readable( $path );
	}

	/**
	 * Get the cached PDF content for a document.
	 *
	 * @param Document $document The document.
	 * @return string|null PDF content or null if not cached.
	 */
	public function getCachedPdf( Document $document ): ?string {
		if ( ! $this->hasCachedPdf( $document ) ) {
			return null;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading local PDF file.
		$content = file_get_contents( $this->getCachePath( $document ) );
		return false !== $content ? $content : null;
	}

	/**
	 * Save PDF content to cache.
	 *
	 * @param Document $document The document.
	 * @param string   $content  PDF content.
	 * @return string Path to the saved PDF file.
	 * @throws \RuntimeException If unable to save the file.
	 */
	public function savePdf( Document $document, string $content ): string {
		$path = $this->getCachePath( $document );
		$dir  = dirname( $path );

		// Ensure directory exists.
		if ( ! $this->ensureDirectoryExists( $dir ) ) {
			throw new \RuntimeException(
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Internal exception message.
				sprintf( 'Unable to create cache directory: %s', $dir )
			);
		}

		// Save the file.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Direct file write for PDF cache.
		$result = file_put_contents( $path, $content );

		if ( false === $result ) {
			throw new \RuntimeException(
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Internal exception message.
				sprintf( 'Unable to save PDF file: %s', $path )
			);
		}

		return $path;
	}

	/**
	 * Delete cached PDF for a document.
	 *
	 * @param Document $document The document.
	 * @return bool True if deleted successfully.
	 */
	public function deletePdf( Document $document ): bool {
		$path = $this->getCachePath( $document );

		if ( ! file_exists( $path ) ) {
			return true;
		}

		return wp_delete_file( $path );
	}

	/**
	 * Clear old cached PDFs.
	 *
	 * @param int $days Number of days to keep files. Default 365.
	 * @return int Number of files deleted.
	 */
	public function clearOldCache( int $days = 365 ): int {
		$base_dir = $this->getBaseDir();
		$deleted  = 0;

		if ( ! is_dir( $base_dir ) ) {
			return 0;
		}

		$cutoff_time = time() - ( $days * DAY_IN_SECONDS );

		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $base_dir, \RecursiveDirectoryIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ( $iterator as $file ) {
			if ( $file->isFile() && 'pdf' === strtolower( $file->getExtension() ) ) {
				if ( $file->getMTime() < $cutoff_time ) {
					if ( wp_delete_file( $file->getPathname() ) ) {
						++$deleted;
					}
				}
			}
		}

		/**
		 * Fires after old PDF cache has been cleared.
		 *
		 * @param int $deleted Number of files deleted.
		 */
		do_action( 'ihumbak_pdf_cache_cleared', $deleted );

		return $deleted;
	}

	/**
	 * Ensure a directory exists with proper protection.
	 *
	 * @param string $dir Directory path.
	 * @return bool True if directory exists or was created.
	 */
	public function ensureDirectoryExists( string $dir = '' ): bool {
		if ( empty( $dir ) ) {
			$dir = $this->getBaseDir();
		}

		if ( is_dir( $dir ) ) {
			return true;
		}

		// Create directory recursively.
		if ( ! wp_mkdir_p( $dir ) ) {
			return false;
		}

		// Protect directory with .htaccess and index.php in base dir only.
		$base_dir = $this->getBaseDir();
		if ( strpos( $dir, $base_dir ) === 0 ) {
			$this->protectDirectory( $base_dir );
		}

		return true;
	}

	/**
	 * Protect directory with .htaccess and index.php.
	 *
	 * @param string $dir Directory path.
	 * @return void
	 */
	private function protectDirectory( string $dir ): void {
		// Create .htaccess to deny direct access.
		$htaccess_path = trailingslashit( $dir ) . '.htaccess';
		if ( ! file_exists( $htaccess_path ) ) {
			$htaccess_content  = "# Protect PDF files from direct access\n";
			$htaccess_content .= "<IfModule mod_authz_core.c>\n";
			$htaccess_content .= "    Require all denied\n";
			$htaccess_content .= "</IfModule>\n";
			$htaccess_content .= "<IfModule !mod_authz_core.c>\n";
			$htaccess_content .= "    Order deny,allow\n";
			$htaccess_content .= "    Deny from all\n";
			$htaccess_content .= "</IfModule>\n";

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Creating security file.
			file_put_contents( $htaccess_path, $htaccess_content );
		}

		// Create index.php to prevent directory listing.
		$index_path = trailingslashit( $dir ) . 'index.php';
		if ( ! file_exists( $index_path ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Creating security file.
			file_put_contents( $index_path, "<?php\n// Silence is golden.\n" );
		}
	}

	/**
	 * Get cache statistics.
	 *
	 * @return array{total_files: int, total_size: int, oldest_file: int|null}
	 */
	public function getStats(): array {
		$base_dir    = $this->getBaseDir();
		$total_files = 0;
		$total_size  = 0;
		$oldest_file = null;

		if ( ! is_dir( $base_dir ) ) {
			return array(
				'total_files' => 0,
				'total_size'  => 0,
				'oldest_file' => null,
			);
		}

		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $base_dir, \RecursiveDirectoryIterator::SKIP_DOTS )
		);

		foreach ( $iterator as $file ) {
			if ( $file->isFile() && 'pdf' === strtolower( $file->getExtension() ) ) {
				++$total_files;
				$total_size += $file->getSize();

				$mtime = $file->getMTime();
				if ( null === $oldest_file || $mtime < $oldest_file ) {
					$oldest_file = $mtime;
				}
			}
		}

		return array(
			'total_files' => $total_files,
			'total_size'  => $total_size,
			'oldest_file' => $oldest_file,
		);
	}
}
