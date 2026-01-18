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
    define( 'IHUMBAK_INVOICES_VERSION', '0.2.0' );
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

// Mock WordPress options storage for unit tests.
global $mock_wp_options;
$mock_wp_options = array();

// Mock WordPress functions for unit tests.
if ( ! function_exists( 'get_option' ) ) {
    function get_option( string $option, $default = false ) {
        global $mock_wp_options;
        return array_key_exists( $option, $mock_wp_options ) ? $mock_wp_options[ $option ] : $default;
    }
}

if ( ! function_exists( 'update_option' ) ) {
    function update_option( string $option, $value ): bool {
        global $mock_wp_options;
        $mock_wp_options[ $option ] = $value;
        return true;
    }
}

if ( ! function_exists( 'add_option' ) ) {
    function add_option( string $option, $value = '' ): bool {
        global $mock_wp_options;
        if ( ! array_key_exists( $option, $mock_wp_options ) ) {
            $mock_wp_options[ $option ] = $value;
        }
        return true;
    }
}

if ( ! function_exists( 'delete_option' ) ) {
    function delete_option( string $option ): bool {
        global $mock_wp_options;
        unset( $mock_wp_options[ $option ] );
        return true;
    }
}

if ( ! function_exists( '__' ) ) {
    function __( string $text, string $domain = 'default' ): string {
        return $text;
    }
}

if ( ! function_exists( '__return_true' ) ) {
    function __return_true(): bool {
        return true;
    }
}

if ( ! function_exists( '__return_false' ) ) {
    function __return_false(): bool {
        return false;
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

// Mock WordPress filter system for unit tests.
global $mock_wp_filters;
$mock_wp_filters = array();

if ( ! function_exists( 'add_filter' ) ) {
    function add_filter( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): bool {
        global $mock_wp_filters;
        if ( ! isset( $mock_wp_filters[ $hook ] ) ) {
            $mock_wp_filters[ $hook ] = array();
        }
        if ( ! isset( $mock_wp_filters[ $hook ][ $priority ] ) ) {
            $mock_wp_filters[ $hook ][ $priority ] = array();
        }
        $mock_wp_filters[ $hook ][ $priority ][] = array(
            'callback'      => $callback,
            'accepted_args' => $accepted_args,
        );
        return true;
    }
}

if ( ! function_exists( 'remove_filter' ) ) {
    function remove_filter( string $hook, $callback, int $priority = 10 ): bool {
        global $mock_wp_filters;
        if ( ! isset( $mock_wp_filters[ $hook ][ $priority ] ) ) {
            return false;
        }
        foreach ( $mock_wp_filters[ $hook ][ $priority ] as $key => $filter ) {
            if ( $filter['callback'] === $callback ) {
                unset( $mock_wp_filters[ $hook ][ $priority ][ $key ] );
                return true;
            }
        }
        return false;
    }
}

if ( ! function_exists( 'apply_filters' ) ) {
    function apply_filters( string $tag, $value, ...$args ) {
        global $mock_wp_filters;
        if ( ! isset( $mock_wp_filters[ $tag ] ) ) {
            return $value;
        }
        // Sort by priority.
        ksort( $mock_wp_filters[ $tag ] );
        foreach ( $mock_wp_filters[ $tag ] as $priority => $filters ) {
            foreach ( $filters as $filter ) {
                $callback      = $filter['callback'];
                $accepted_args = $filter['accepted_args'];
                // Prepend $value to args.
                $all_args = array_merge( array( $value ), $args );
                // Slice to accepted_args count.
                $call_args = array_slice( $all_args, 0, $accepted_args );
                $value     = call_user_func_array( $callback, $call_args );
            }
        }
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

if ( ! function_exists( 'get_current_user_id' ) ) {
    /**
     * Mock get_current_user_id function.
     *
     * Returns 0 by default (not logged in).
     * Tests can define IHUMBAK_TEST_CURRENT_USER_ID to override.
     *
     * @return int Current user ID.
     */
    function get_current_user_id(): int {
        if ( defined( 'IHUMBAK_TEST_CURRENT_USER_ID' ) ) {
            return (int) IHUMBAK_TEST_CURRENT_USER_ID;
        }
        return 0;
    }
}

// Mock WordPress user capabilities storage for unit tests.
global $mock_wp_user_capabilities;
$mock_wp_user_capabilities = array();

if ( ! function_exists( 'current_user_can' ) ) {
    /**
     * Mock current_user_can function.
     *
     * Returns false by default (no capabilities).
     * Use global $mock_wp_user_capabilities array to set capabilities for testing.
     *
     * @param string $capability Capability to check.
     * @return bool Whether current user has the capability.
     */
    function current_user_can( string $capability ): bool {
        global $mock_wp_user_capabilities;
        return in_array( $capability, $mock_wp_user_capabilities, true );
    }
}

// =============================================================================
// Mock WooCommerce classes for unit tests.
// These mocks simulate WooCommerce order-related classes to enable unit testing
// of OrderDataExtractor without requiring the full WooCommerce installation.
// =============================================================================

if ( ! class_exists( 'WC_DateTime' ) ) {
    /**
     * Mock WC_DateTime class for unit tests.
     */
    class WC_DateTime extends \DateTime {
        /**
         * Constructor.
         *
         * @param string $datetime Date/time string.
         */
        public function __construct( string $datetime = 'now' ) {
            parent::__construct( $datetime );
        }
    }
}

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
         * Get payment method title.
         *
         * @return string
         */
        public function get_payment_method_title(): string {
            return $this->data['payment_method_title'] ?? '';
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

        /**
         * Get date paid.
         *
         * @return \WC_DateTime|null
         */
        public function get_date_paid(): ?\WC_DateTime {
            if ( empty( $this->data['date_paid'] ) ) {
                return null;
            }
            return new \WC_DateTime( $this->data['date_paid'] );
        }

        /**
         * Set date paid.
         *
         * @param string|null $date Date string or null.
         */
        public function set_date_paid( ?string $date ): void {
            $this->data['date_paid'] = $date;
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
         * Get total (after discounts).
         *
         * Falls back to subtotal if total is not set (for backward compatibility).
         *
         * @return string
         */
        public function get_total(): string {
            return (string) ( $this->data['total'] ?? $this->data['subtotal'] ?? '0' );
        }

        /**
         * Get total tax (after discounts).
         *
         * Falls back to subtotal_tax if total_tax is not set (for backward compatibility).
         *
         * @return string
         */
        public function get_total_tax(): string {
            return (string) ( $this->data['total_tax'] ?? $this->data['subtotal_tax'] ?? '0' );
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

// =============================================================================
// Additional WordPress function mocks for Plugin class tests.
// =============================================================================

if ( ! function_exists( 'load_plugin_textdomain' ) ) {
    function load_plugin_textdomain( string $domain, $deprecated = false, string $plugin_rel_path = '' ): bool {
        return true;
    }
}

if ( ! function_exists( 'wp_doing_ajax' ) ) {
    function wp_doing_ajax(): bool {
        return defined( 'DOING_AJAX' ) && DOING_AJAX;
    }
}

if ( ! function_exists( 'is_admin' ) ) {
    function is_admin(): bool {
        return defined( 'WP_ADMIN' ) && WP_ADMIN;
    }
}

if ( ! function_exists( 'add_action' ) ) {
    function add_action( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): bool {
        return true;
    }
}

if ( ! function_exists( 'register_setting' ) ) {
    function register_setting( string $option_group, string $option_name, $args = array() ): void {
        // Do nothing in tests.
    }
}

if ( ! function_exists( 'add_settings_section' ) ) {
    function add_settings_section( string $id, string $title, $callback, string $page, array $args = array() ): void {
        // Do nothing in tests.
    }
}

if ( ! function_exists( 'add_menu_page' ) ) {
    function add_menu_page( string $page_title, string $menu_title, string $capability, string $menu_slug, $callback = '', string $icon_url = '', $position = null ): string {
        return '';
    }
}

if ( ! function_exists( 'add_submenu_page' ) ) {
    function add_submenu_page( string $parent_slug, string $page_title, string $menu_title, string $capability, string $menu_slug, $callback = '', $position = null ): string|false {
        return '';
    }
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
    function sanitize_textarea_field( string $str ): string {
        return trim( strip_tags( $str ) );
    }
}

if ( ! function_exists( 'sanitize_file_name' ) ) {
    function sanitize_file_name( string $filename ): string {
        // Basic sanitization for tests - removes special characters.
        return preg_replace( '/[^a-zA-Z0-9._-]/', '', $filename ) ?: $filename;
    }
}

// =============================================================================
// Mock WordPress URL functions for Portal tests.
// =============================================================================

if ( ! function_exists( 'add_query_arg' ) ) {
    /**
     * Mock add_query_arg function.
     *
     * @param array|string $args Query args.
     * @param string       $url  Base URL.
     * @return string URL with query args.
     */
    function add_query_arg( $args, string $url = '' ): string {
        if ( empty( $url ) ) {
            $url = 'http://localhost/my-account/';
        }
        $query = http_build_query( $args );
        return $url . ( strpos( $url, '?' ) !== false ? '&' : '?' ) . $query;
    }
}

if ( ! function_exists( 'wp_create_nonce' ) ) {
    /**
     * Mock wp_create_nonce function.
     *
     * @param string $action Nonce action.
     * @return string Nonce value.
     */
    function wp_create_nonce( string $action = '' ): string {
        return 'mock_nonce_' . md5( $action );
    }
}

if ( ! function_exists( 'wp_verify_nonce' ) ) {
    /**
     * Mock wp_verify_nonce function.
     *
     * @param string $nonce  Nonce to verify.
     * @param string $action Nonce action.
     * @return int|false
     */
    function wp_verify_nonce( string $nonce, string $action = '' ) {
        return $nonce === 'mock_nonce_' . md5( $action ) ? 1 : false;
    }
}

if ( ! function_exists( 'wc_get_account_endpoint_url' ) ) {
    /**
     * Mock wc_get_account_endpoint_url function.
     *
     * @param string $endpoint Endpoint slug.
     * @return string Endpoint URL.
     */
    function wc_get_account_endpoint_url( string $endpoint ): string {
        return 'http://localhost/my-account/' . $endpoint . '/';
    }
}

if ( ! function_exists( 'wc_get_page_permalink' ) ) {
    /**
     * Mock wc_get_page_permalink function.
     *
     * @param string $page Page slug (e.g., 'myaccount', 'shop', 'cart').
     * @return string Page permalink.
     */
    function wc_get_page_permalink( string $page ): string {
        $pages = array(
            'myaccount' => 'http://localhost/my-account/',
            'shop'      => 'http://localhost/shop/',
            'cart'      => 'http://localhost/cart/',
            'checkout'  => 'http://localhost/checkout/',
        );
        return $pages[ $page ] ?? 'http://localhost/' . $page . '/';
    }
}

// =============================================================================
// Mock WordPress database class for unit tests.
// =============================================================================

if ( ! class_exists( 'wpdb' ) ) {
    /**
     * Mock wpdb class for unit tests.
     */
    class wpdb {
        /**
         * Table prefix.
         *
         * @var string
         */
        public string $prefix = 'wp_';

        /**
         * Last error message.
         *
         * @var string
         */
        public string $last_error = '';

        /**
         * Last insert ID.
         *
         * @var int
         */
        public int $insert_id = 0;

        /**
         * Prepare a SQL query.
         *
         * @param string $query  Query with placeholders.
         * @param mixed  ...$args Values to replace placeholders.
         * @return string Prepared query.
         */
        public function prepare( string $query, ...$args ): string {
            return vsprintf( str_replace( array( '%s', '%d', '%f' ), "'%s'", $query ), $args );
        }

        /**
         * Get a single row.
         *
         * @param string $query  SQL query.
         * @param string $output Output type.
         * @return array|object|null
         */
        public function get_row( string $query, $output = OBJECT ) {
            return null;
        }

        /**
         * Get multiple rows.
         *
         * @param string $query  SQL query.
         * @param string $output Output type.
         * @return array
         */
        public function get_results( string $query, $output = OBJECT ): array {
            return array();
        }

        /**
         * Get a single variable.
         *
         * @param string $query SQL query.
         * @return string|null
         */
        public function get_var( string $query ): ?string {
            return null;
        }

        /**
         * Insert a row.
         *
         * @param string $table  Table name.
         * @param array  $data   Data to insert.
         * @param array  $format Format array.
         * @return int|false
         */
        public function insert( string $table, array $data, array $format = array() ) {
            $this->insert_id = 1;
            return 1;
        }

        /**
         * Update a row.
         *
         * @param string $table        Table name.
         * @param array  $data         Data to update.
         * @param array  $where        Where conditions.
         * @param array  $format       Format array.
         * @param array  $where_format Where format array.
         * @return int|false
         */
        public function update( string $table, array $data, array $where, array $format = array(), array $where_format = array() ) {
            return 1;
        }

        /**
         * Delete a row.
         *
         * @param string $table        Table name.
         * @param array  $where        Where conditions.
         * @param array  $where_format Where format array.
         * @return int|false
         */
        public function delete( string $table, array $where, array $where_format = array() ) {
            return 1;
        }

        /**
         * Execute a query.
         *
         * @param string $query SQL query.
         * @return int|bool
         */
        public function query( string $query ) {
            return true;
        }

        /**
         * Escape like pattern.
         *
         * @param string $text Text to escape.
         * @return string
         */
        public function esc_like( string $text ): string {
            return addcslashes( $text, '_%\\' );
        }
    }
}

// Initialize global wpdb mock.
global $wpdb;
if ( ! isset( $wpdb ) ) {
    $wpdb = new wpdb();
}

// =============================================================================
// Mock WordPress locale functions for PDF locale switching tests.
// =============================================================================

if ( ! defined( 'WP_LANG_DIR' ) ) {
    define( 'WP_LANG_DIR', '/tmp/wordpress/wp-content/languages' );
}

// Mock locale stack for switch_to_locale/restore_previous_locale.
global $mock_locale_stack;
$mock_locale_stack = array();

// Mock current locale value.
global $mock_current_locale;
$mock_current_locale = 'en_US';

if ( ! function_exists( 'determine_locale' ) ) {
    /**
     * Mock determine_locale function.
     *
     * @return string Current locale.
     */
    function determine_locale(): string {
        global $mock_current_locale;
        return $mock_current_locale ?? 'en_US';
    }
}

if ( ! function_exists( 'switch_to_locale' ) ) {
    /**
     * Mock switch_to_locale function.
     *
     * @param string $locale Locale to switch to.
     * @return bool True if switched.
     */
    function switch_to_locale( string $locale ): bool {
        global $mock_locale_stack, $mock_current_locale;
        $mock_locale_stack[] = $mock_current_locale;
        $mock_current_locale = $locale;
        return true;
    }
}

if ( ! function_exists( 'restore_previous_locale' ) ) {
    /**
     * Mock restore_previous_locale function.
     *
     * @return string|false Previous locale or false.
     */
    function restore_previous_locale() {
        global $mock_locale_stack, $mock_current_locale;
        if ( empty( $mock_locale_stack ) ) {
            return false;
        }
        $mock_current_locale = array_pop( $mock_locale_stack );
        return $mock_current_locale;
    }
}

if ( ! function_exists( 'unload_textdomain' ) ) {
    /**
     * Mock unload_textdomain function.
     *
     * @param string $domain Text domain.
     * @return bool True.
     */
    function unload_textdomain( string $domain ): bool {
        return true;
    }
}

if ( ! function_exists( 'load_textdomain' ) ) {
    /**
     * Mock load_textdomain function.
     *
     * @param string $domain Text domain.
     * @param string $mofile Path to .mo file.
     * @return bool True if file exists.
     */
    function load_textdomain( string $domain, string $mofile ): bool {
        return file_exists( $mofile );
    }
}

// =============================================================================
// Mock WC_Email class for unit tests.
// =============================================================================

if ( ! class_exists( 'WC_Email' ) ) {
    /**
     * Mock WC_Email class for unit tests.
     */
    class WC_Email {
        /**
         * Email ID.
         *
         * @var string
         */
        public string $id = '';

        /**
         * Email title.
         *
         * @var string
         */
        public string $title = '';

        /**
         * Email description.
         *
         * @var string
         */
        public string $description = '';

        /**
         * HTML template path.
         *
         * @var string
         */
        public string $template_html = '';

        /**
         * Plain text template path.
         *
         * @var string
         */
        public string $template_plain = '';

        /**
         * Template base path.
         *
         * @var string
         */
        public string $template_base = '';

        /**
         * Placeholders.
         *
         * @var array<string, string>
         */
        public array $placeholders = array();

        /**
         * Recipient email address.
         *
         * @var string
         */
        public string $recipient = '';

        /**
         * Email settings storage.
         *
         * @var array<string, mixed>
         */
        protected array $settings = array();

        /**
         * Constructor.
         */
        public function __construct() {
            // Do nothing in tests.
        }

        /**
         * Get option value.
         *
         * @param string $key     Option key.
         * @param mixed  $default Default value.
         * @return mixed
         */
        public function get_option( string $key, $default = null ) {
            return $this->settings[ $key ] ?? $default;
        }

        /**
         * Get blog name.
         *
         * @return string
         */
        public function get_blogname(): string {
            return get_option( 'blogname', 'Test Site' );
        }

        /**
         * Check if email is enabled.
         *
         * @return bool
         */
        public function is_enabled(): bool {
            return $this->get_option( 'enabled', 'yes' ) === 'yes';
        }

        /**
         * Get recipient.
         *
         * @return string
         */
        public function get_recipient(): string {
            return $this->recipient;
        }

        /**
         * Send email.
         *
         * @param string $to          Recipient.
         * @param string $subject     Subject.
         * @param string $message     Message.
         * @param string $headers     Headers.
         * @param array  $attachments Attachments.
         * @return bool
         */
        public function send( string $to, string $subject, string $message, string $headers = '', array $attachments = array() ): bool {
            return true;
        }

        /**
         * Get email type options.
         *
         * @return array<string, string>
         */
        public function get_email_type_options(): array {
            return array(
                'plain'     => 'Plain text',
                'html'      => 'HTML',
                'multipart' => 'Multipart',
            );
        }

        /**
         * Format string with placeholders.
         *
         * @param string $string String to format.
         * @return string
         */
        public function format_string( string $string ): string {
            return str_replace( array_keys( $this->placeholders ), array_values( $this->placeholders ), $string );
        }

        /**
         * Get attachments.
         *
         * @return array<string>
         */
        public function get_attachments(): array {
            return array();
        }

        /**
         * Get content.
         *
         * @return string
         */
        public function get_content(): string {
            return '';
        }

        /**
         * Get headers.
         *
         * @return string
         */
        public function get_headers(): string {
            return '';
        }
    }
}

// =============================================================================
// Mock WooCommerce functions for email tests.
// =============================================================================

// Mock WooCommerce orders storage.
global $mock_wc_orders;
$mock_wc_orders = array();

if ( ! function_exists( 'wc_get_order' ) ) {
    /**
     * Mock wc_get_order function.
     *
     * @param int $order_id Order ID.
     * @return WC_Order|false
     */
    function wc_get_order( int $order_id ) {
        global $mock_wc_orders;
        return $mock_wc_orders[ $order_id ] ?? false;
    }
}

if ( ! function_exists( 'wc_get_template_html' ) ) {
    /**
     * Mock wc_get_template_html function.
     *
     * @param string $template_name Template name.
     * @param array  $args          Template arguments.
     * @param string $template_path Template path.
     * @param string $default_path  Default path.
     * @return string
     */
    function wc_get_template_html( string $template_name, array $args = array(), string $template_path = '', string $default_path = '' ): string {
        return '<html><body>Mock email template: ' . esc_html( $template_name ) . '</body></html>';
    }
}

if ( ! function_exists( 'get_woocommerce_currency' ) ) {
    /**
     * Mock get_woocommerce_currency function.
     *
     * @return string
     */
    function get_woocommerce_currency(): string {
        return get_option( 'woocommerce_currency', 'PLN' );
    }
}
