<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package IHumbak\Invoices\Tests
 */

declare(strict_types=1);

// Load Composer autoloader.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Define test constants.
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', '/tmp/wordpress/' );
}

if ( ! defined( 'IHUMBAK_INVOICES_VERSION' ) ) {
    define( 'IHUMBAK_INVOICES_VERSION', '0.1.0' );
}

if ( ! defined( 'IHUMBAK_INVOICES_FILE' ) ) {
    define( 'IHUMBAK_INVOICES_FILE', dirname( __DIR__ ) . '/ihumbak-invoices.php' );
}

if ( ! defined( 'IHUMBAK_INVOICES_PATH' ) ) {
    define( 'IHUMBAK_INVOICES_PATH', dirname( __DIR__ ) . '/' );
}

if ( ! defined( 'IHUMBAK_INVOICES_URL' ) ) {
    define( 'IHUMBAK_INVOICES_URL', 'http://localhost/wp-content/plugins/ihumbak-woo-invoices/' );
}

if ( ! defined( 'IHUMBAK_INVOICES_BASENAME' ) ) {
    define( 'IHUMBAK_INVOICES_BASENAME', 'ihumbak-woo-invoices/ihumbak-invoices.php' );
}

// Mock WordPress functions for unit tests.
if ( ! function_exists( 'get_option' ) ) {
    function get_option( string $option, $default = false ) {
        return $default;
    }
}

if ( ! function_exists( 'update_option' ) ) {
    function update_option( string $option, $value ): bool {
        return true;
    }
}

if ( ! function_exists( 'add_option' ) ) {
    function add_option( string $option, $value = '' ): bool {
        return true;
    }
}

if ( ! function_exists( 'delete_option' ) ) {
    function delete_option( string $option ): bool {
        return true;
    }
}

if ( ! function_exists( '__' ) ) {
    function __( string $text, string $domain = 'default' ): string {
        return $text;
    }
}

if ( ! function_exists( 'esc_html__' ) ) {
    function esc_html__( string $text, string $domain = 'default' ): string {
        return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
    }
}

if ( ! function_exists( 'esc_html' ) ) {
    function esc_html( string $text ): string {
        return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
    }
}

if ( ! function_exists( 'esc_attr' ) ) {
    function esc_attr( string $text ): string {
        return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
    }
}

if ( ! function_exists( 'wp_parse_args' ) ) {
    function wp_parse_args( $args, array $defaults = [] ): array {
        if ( is_object( $args ) ) {
            $args = get_object_vars( $args );
        } elseif ( is_string( $args ) ) {
            parse_str( $args, $args );
        }
        return array_merge( $defaults, (array) $args );
    }
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( string $str ): string {
        return trim( strip_tags( $str ) );
    }
}

if ( ! function_exists( 'sanitize_email' ) ) {
    function sanitize_email( string $email ): string {
        return filter_var( $email, FILTER_SANITIZE_EMAIL ) ?: '';
    }
}

if ( ! function_exists( 'absint' ) ) {
    function absint( $maybeint ): int {
        return abs( (int) $maybeint );
    }
}

if ( ! function_exists( 'wp_json_encode' ) ) {
    function wp_json_encode( $data, int $options = 0, int $depth = 512 ) {
        return json_encode( $data, $options, $depth );
    }
}

// WordPress time constants.
if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
    define( 'HOUR_IN_SECONDS', 3600 );
}

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
    define( 'DAY_IN_SECONDS', 86400 );
}

// Mock WordPress theme/filesystem functions.
if ( ! function_exists( 'is_child_theme' ) ) {
    function is_child_theme(): bool {
        return false;
    }
}

if ( ! function_exists( 'get_stylesheet_directory' ) ) {
    function get_stylesheet_directory(): string {
        return '/tmp/wordpress/wp-content/themes/theme';
    }
}

if ( ! function_exists( 'get_template_directory' ) ) {
    function get_template_directory(): string {
        return '/tmp/wordpress/wp-content/themes/theme';
    }
}

if ( ! function_exists( 'trailingslashit' ) ) {
    function trailingslashit( string $value ): string {
        return rtrim( $value, '/\\' ) . '/';
    }
}

if ( ! function_exists( 'wp_upload_dir' ) ) {
    function wp_upload_dir(): array {
        return array(
            'basedir' => '/tmp/wordpress/wp-content/uploads',
            'baseurl' => 'http://localhost/wp-content/uploads',
            'path'    => '/tmp/wordpress/wp-content/uploads/' . date( 'Y/m' ),
            'url'     => 'http://localhost/wp-content/uploads/' . date( 'Y/m' ),
        );
    }
}

if ( ! function_exists( 'wp_mkdir_p' ) ) {
    function wp_mkdir_p( string $target ): bool {
        if ( is_dir( $target ) ) {
            return true;
        }
        return @mkdir( $target, 0755, true );
    }
}

if ( ! function_exists( 'wp_delete_file' ) ) {
    function wp_delete_file( string $file ): bool {
        if ( file_exists( $file ) ) {
            return @unlink( $file );
        }
        return false;
    }
}

if ( ! function_exists( 'apply_filters' ) ) {
    function apply_filters( string $tag, $value, ...$args ) {
        return $value;
    }
}

if ( ! function_exists( 'do_action' ) ) {
    function do_action( string $tag, ...$args ): void {
        // Do nothing in tests.
    }
}

if ( ! function_exists( 'get_transient' ) ) {
    function get_transient( string $transient ) {
        return false;
    }
}

if ( ! function_exists( 'set_transient' ) ) {
    function set_transient( string $transient, $value, int $expiration = 0 ): bool {
        return true;
    }
}

if ( ! function_exists( 'delete_transient' ) ) {
    function delete_transient( string $transient ): bool {
        return true;
    }
}
