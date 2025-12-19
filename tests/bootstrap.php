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
