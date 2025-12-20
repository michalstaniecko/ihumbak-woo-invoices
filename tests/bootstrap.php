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

// =============================================================================
// Mock WooCommerce classes for unit tests.
// These mocks simulate WooCommerce order-related classes to enable unit testing
// of OrderDataExtractor without requiring the full WooCommerce installation.
// =============================================================================

if ( ! class_exists( 'WC_Order' ) ) {
    /**
     * Mock WC_Order class for unit tests.
     */
    class WC_Order {
        /**
         * Order data.
         *
         * @var array<string, mixed>
         */
        private array $data = array();

        /**
         * Order meta data.
         *
         * @var array<string, mixed>
         */
        private array $meta = array();

        /**
         * Order items.
         *
         * @var array<int, object>
         */
        private array $items = array();

        /**
         * Set order data.
         *
         * @param array<string, mixed> $data Order data.
         */
        public function set_data( array $data ): void {
            $this->data = $data;
        }

        /**
         * Set meta value.
         *
         * @param string $key   Meta key.
         * @param mixed  $value Meta value.
         */
        public function set_meta( string $key, $value ): void {
            $this->meta[ $key ] = $value;
        }

        /**
         * Add item to order.
         *
         * @param object $item Order item.
         */
        public function add_item( $item ): void {
            $this->items[] = $item;
        }

        /**
         * Get order items.
         *
         * @return array<int, object>
         */
        public function get_items(): array {
            return $this->items;
        }

        /**
         * Get currency.
         *
         * @return string
         */
        public function get_currency(): string {
            return $this->data['currency'] ?? 'PLN';
        }

        /**
         * Get shipping total.
         *
         * @return string
         */
        public function get_shipping_total(): string {
            return (string) ( $this->data['shipping_total'] ?? '0' );
        }

        /**
         * Get shipping tax.
         *
         * @return string
         */
        public function get_shipping_tax(): string {
            return (string) ( $this->data['shipping_tax'] ?? '0' );
        }

        /**
         * Get shipping method.
         *
         * @return string|null
         */
        public function get_shipping_method(): ?string {
            return $this->data['shipping_method'] ?? null;
        }

        /**
         * Get payment method.
         *
         * @return string
         */
        public function get_payment_method(): string {
            return $this->data['payment_method'] ?? '';
        }

        /**
         * Get billing company.
         *
         * @return string
         */
        public function get_billing_company(): string {
            return $this->data['billing_company'] ?? '';
        }

        /**
         * Get billing first name.
         *
         * @return string
         */
        public function get_billing_first_name(): string {
            return $this->data['billing_first_name'] ?? '';
        }

        /**
         * Get billing last name.
         *
         * @return string
         */
        public function get_billing_last_name(): string {
            return $this->data['billing_last_name'] ?? '';
        }

        /**
         * Get billing address line 1.
         *
         * @return string
         */
        public function get_billing_address_1(): string {
            return $this->data['billing_address_1'] ?? '';
        }

        /**
         * Get billing address line 2.
         *
         * @return string
         */
        public function get_billing_address_2(): string {
            return $this->data['billing_address_2'] ?? '';
        }

        /**
         * Get billing postcode.
         *
         * @return string
         */
        public function get_billing_postcode(): string {
            return $this->data['billing_postcode'] ?? '';
        }

        /**
         * Get billing city.
         *
         * @return string
         */
        public function get_billing_city(): string {
            return $this->data['billing_city'] ?? '';
        }

        /**
         * Get billing country.
         *
         * @return string
         */
        public function get_billing_country(): string {
            return $this->data['billing_country'] ?? '';
        }

        /**
         * Get billing email.
         *
         * @return string
         */
        public function get_billing_email(): string {
            return $this->data['billing_email'] ?? '';
        }

        /**
         * Get billing phone.
         *
         * @return string
         */
        public function get_billing_phone(): string {
            return $this->data['billing_phone'] ?? '';
        }

        /**
         * Get meta value.
         *
         * @param string $key    Meta key.
         * @param bool   $single Return single value.
         * @return mixed
         */
        public function get_meta( string $key, bool $single = true ) {
            return $this->meta[ $key ] ?? '';
        }
    }
}

if ( ! class_exists( 'WC_Order_Item_Product' ) ) {
    /**
     * Mock WC_Order_Item_Product class for unit tests.
     */
    class WC_Order_Item_Product {
        /**
         * Item data.
         *
         * @var array<string, mixed>
         */
        private array $data = array();

        /**
         * Associated product.
         *
         * @var WC_Product|null
         */
        private ?WC_Product $product = null;

        /**
         * Set item data.
         *
         * @param array<string, mixed> $data Item data.
         */
        public function set_data( array $data ): void {
            $this->data = $data;
        }

        /**
         * Set associated product.
         *
         * @param WC_Product|null $product Product object.
         */
        public function set_product( ?WC_Product $product ): void {
            $this->product = $product;
        }

        /**
         * Get quantity.
         *
         * @return float|int|string
         */
        public function get_quantity() {
            return $this->data['quantity'] ?? 1;
        }

        /**
         * Get subtotal (net).
         *
         * @return string
         */
        public function get_subtotal(): string {
            return (string) ( $this->data['subtotal'] ?? '0' );
        }

        /**
         * Get subtotal tax.
         *
         * @return string
         */
        public function get_subtotal_tax(): string {
            return (string) ( $this->data['subtotal_tax'] ?? '0' );
        }

        /**
         * Get product ID.
         *
         * @return int
         */
        public function get_product_id(): int {
            return $this->data['product_id'] ?? 0;
        }

        /**
         * Get item name.
         *
         * @return string
         */
        public function get_name(): string {
            return $this->data['name'] ?? '';
        }

        /**
         * Get associated product.
         *
         * @return WC_Product|false
         */
        public function get_product() {
            return $this->product ?? false;
        }
    }
}

if ( ! class_exists( 'WC_Product' ) ) {
    /**
     * Mock WC_Product class for unit tests.
     */
    class WC_Product {
        /**
         * Product SKU.
         *
         * @var string
         */
        private string $sku = '';

        /**
         * Constructor.
         *
         * @param string $sku Product SKU.
         */
        public function __construct( string $sku = '' ) {
            $this->sku = $sku;
        }

        /**
         * Get SKU.
         *
         * @return string
         */
        public function get_sku(): string {
            return $this->sku;
        }
    }
}
