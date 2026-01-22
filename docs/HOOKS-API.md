# Hooks and Filters API

This document describes all available hooks and filters in iHumbak WooCommerce Invoices plugin.

## Actions

### Document Lifecycle

#### `ihumbak_document_created`
Fired after a document is created.

```php
do_action('ihumbak_document_created', Invoice $invoice, WC_Order $order);
```

| Parameter | Type | Description |
|-----------|------|-------------|
| $invoice | Invoice | The created document |
| $order | WC_Order | Associated WooCommerce order |

#### `ihumbak_document_reverted_to_draft`
Fired after a document status is reverted to draft by a super-admin.

```php
do_action('ihumbak_document_reverted_to_draft', Document $document, int $user_id);
```

| Parameter | Type | Description |
|-----------|------|-------------|
| $document | Document | The reverted document |
| $user_id | int | User ID who performed the action |

### Order Status Change

#### `ihumbak_before_order_status_change`
Fired before an order status is changed when issuing a document.

```php
do_action('ihumbak_before_order_status_change', int $order_id, string $new_status, Document $document);
```

| Parameter | Type | Description |
|-----------|------|-------------|
| $order_id | int | WooCommerce order ID |
| $new_status | string | New status being set |
| $document | Document | The issued document |

#### `ihumbak_after_order_status_change`
Fired after an order status is changed when issuing a document.

```php
do_action('ihumbak_after_order_status_change', int $order_id, string $new_status, string $old_status, Document $document);
```

| Parameter | Type | Description |
|-----------|------|-------------|
| $order_id | int | WooCommerce order ID |
| $new_status | string | New status that was set |
| $old_status | string | Previous order status |
| $document | Document | The issued document |

### PDF Generation

#### `ihumbak_before_pdf_render`
Fired before PDF generation starts.

```php
do_action('ihumbak_before_pdf_render', Invoice $invoice);
```

| Parameter | Type | Description |
|-----------|------|-------------|
| $invoice | Invoice | Document to be rendered |

### Email

#### `ihumbak_before_email_send`
Fired before sending a document email.

```php
do_action('ihumbak_before_email_send', Document $document, string $recipient);
```

| Parameter | Type | Description |
|-----------|------|-------------|
| $document | Document | The document to be sent |
| $recipient | string | Recipient email address |

#### `ihumbak_email_sent`
Fired after a document email is sent successfully.

```php
do_action('ihumbak_email_sent', Document $document, string $recipient);
```

| Parameter | Type | Description |
|-----------|------|-------------|
| $document | Document | The sent document |
| $recipient | string | Recipient email address |

#### `ihumbak_email_failed`
Fired when email sending fails.

```php
do_action('ihumbak_email_failed', Document $document, string $error);
```

| Parameter | Type | Description |
|-----------|------|-------------|
| $document | Document | The document |
| $error | string | Error message |

#### `ihumbak_send_{type}_email`
Fired to trigger sending email for specific document type. Replace `{type}` with `invoice`, `receipt`, or `credit_note`.

```php
do_action('ihumbak_send_invoice_email', int $document_id, Document $document);
do_action('ihumbak_send_receipt_email', int $document_id, Document $document);
do_action('ihumbak_send_credit_note_email', int $document_id, Document $document);
```

| Parameter | Type | Description |
|-----------|------|-------------|
| $document_id | int | Document ID |
| $document | Document | The document (optional) |

### Translation / Localization

#### `ihumbak_translation_file_missing`
Fired when a translation file (.mo) is not found for the target locale during PDF generation or email sending.

```php
do_action('ihumbak_translation_file_missing', string $locale, string $plugin_mo_file, string $global_mo_file);
```

| Parameter | Type | Description |
|-----------|------|-------------|
| $locale | string | The target locale (e.g., 'nb_NO') |
| $plugin_mo_file | string | Path to plugin language file checked |
| $global_mo_file | string | Path to global language file checked |

**Example:**
```php
// Log missing translations to a monitoring service
add_action('ihumbak_translation_file_missing', function($locale, $plugin_mo, $global_mo) {
    // Send alert to monitoring system
    error_log("Missing translation for {$locale}");

    // Or attempt to download from translate.wordpress.org
    // wp_download_language_pack($locale);
}, 10, 3);
```

---

## Filters

### Document Numbering

#### `ihumbak_document_number`
Modify the generated document number.

```php
apply_filters('ihumbak_document_number', string $number, Invoice $invoice);
```

| Parameter | Type | Description |
|-----------|------|-------------|
| $number | string | Generated document number |
| $invoice | Invoice | The document |

**Example:**
```php
add_filter('ihumbak_document_number', function($number, $invoice) {
    // Add prefix based on customer type
    if ($invoice->getBuyer()->isCompany()) {
        return 'B2B-' . $number;
    }
    return $number;
}, 10, 2);
```

### PDF Data

#### `ihumbak_pdf_data`
Modify data passed to PDF template.

```php
apply_filters('ihumbak_pdf_data', array $data, Invoice $invoice);
```

| Parameter | Type | Description |
|-----------|------|-------------|
| $data | array | Template data |
| $invoice | Invoice | The document |

**Example:**
```php
add_filter('ihumbak_pdf_data', function($data, $invoice) {
    $data['custom_field'] = 'Custom value';
    return $data;
}, 10, 2);
```

### Email

#### `ihumbak_email_recipient`
Modify the recipient email address for document emails.

```php
apply_filters('ihumbak_email_recipient', string $email, Document $document, WC_Order $order);
```

| Parameter | Type | Description |
|-----------|------|-------------|
| $email | string | Recipient email address |
| $document | Document | The document |
| $order | WC_Order | Associated WooCommerce order |

**Example:**
```php
add_filter('ihumbak_email_recipient', function($email, $document, $order) {
    // Send invoices to accounting department
    if ($document->getDocumentType() === 'invoice') {
        return 'accounting@example.com';
    }
    return $email;
}, 10, 3);
```

#### `ihumbak_email_attachments`
Modify email attachments.

```php
apply_filters('ihumbak_email_attachments', array $attachments, Document $document, WC_Email $email);
```

| Parameter | Type | Description |
|-----------|------|-------------|
| $attachments | array | Attachment file paths |
| $document | Document | The document |
| $email | WC_Email | Email instance |

**Example:**
```php
add_filter('ihumbak_email_attachments', function($attachments, $document, $email) {
    // Add additional terms and conditions PDF
    $attachments[] = '/path/to/terms.pdf';
    return $attachments;
}, 10, 3);
```

#### `ihumbak_email_template`
Modify email template path.

```php
apply_filters('ihumbak_email_template', string $template, Document $document);
```

| Parameter | Type | Description |
|-----------|------|-------------|
| $template | string | Template file path |
| $document | Document | The document |

### Document Items

#### `ihumbak_invoice_item_unit`
Modify unit for invoice line items.

```php
apply_filters('ihumbak_invoice_item_unit', string $unit, ?int $product_id, ?WC_Order_Item_Product $item);
```

| Parameter | Type | Description |
|-----------|------|-------------|
| $unit | string | Item unit (default: 'pcs') |
| $product_id | int\|null | WC product ID |
| $item | WC_Order_Item_Product\|null | Order item |

**Example:**
```php
add_filter('ihumbak_invoice_item_unit', function($unit, $product_id, $item) {
    if ($product_id) {
        $product = wc_get_product($product_id);
        if ($product && $product->get_weight()) {
            return 'kg';
        }
    }
    return $unit;
}, 10, 3);
```

#### `ihumbak_invoice_shipping_unit`
Modify unit for shipping line item.

```php
apply_filters('ihumbak_invoice_shipping_unit', string $unit, WC_Order $order);
```

| Parameter | Type | Description |
|-----------|------|-------------|
| $unit | string | Shipping unit (default: 'service') |
| $order | WC_Order | The order |

### Payment Methods

#### `ihumbak_payment_method_map`
Modify payment method type mapping.

```php
apply_filters('ihumbak_payment_method_map', array $map);
```

| Parameter | Type | Description |
|-----------|------|-------------|
| $map | array | Payment method ID to type mapping |

**Default mapping:**
```php
[
    'bacs' => 'transfer',
    'cheque' => 'transfer',
    'cod' => 'cash',
    'paypal' => 'online',
    'stripe' => 'card',
    'przelewy24' => 'online',
]
```

**Example:**
```php
add_filter('ihumbak_payment_method_map', function($map) {
    $map['my_custom_gateway'] = 'online';
    return $map;
});
```

### Super Admin

#### `ihumbak_is_current_user_super_admin`
Override super-admin check for current user.

```php
apply_filters('ihumbak_is_current_user_super_admin', bool $is_super_admin, int $user_id);
```

| Parameter | Type | Description |
|-----------|------|-------------|
| $is_super_admin | bool | Current status |
| $user_id | int | Current user ID |

#### `ihumbak_is_user_super_admin`
Override super-admin check for specific user.

```php
apply_filters('ihumbak_is_user_super_admin', bool $is_super_admin, int $user_id);
```

| Parameter | Type | Description |
|-----------|------|-------------|
| $is_super_admin | bool | Current status |
| $user_id | int | User ID to check |

**Example:**
```php
// Grant super-admin to all administrators
add_filter('ihumbak_is_user_super_admin', function($is_super_admin, $user_id) {
    $user = get_user_by('id', $user_id);
    if ($user && in_array('administrator', $user->roles)) {
        return true;
    }
    return $is_super_admin;
}, 10, 2);
```

### Order Status Change

#### `ihumbak_order_status_change_enabled`
Override whether automatic order status change should happen for a specific document.

```php
apply_filters('ihumbak_order_status_change_enabled', bool $enabled, int $order_id, Document $document);
```

| Parameter | Type | Description |
|-----------|------|-------------|
| $enabled | bool | Whether status change is enabled |
| $order_id | int | WooCommerce order ID |
| $document | Document | The document being issued |

**Example:**
```php
// Disable status change for orders from specific customer
add_filter('ihumbak_order_status_change_enabled', function($enabled, $order_id, $document) {
    $order = wc_get_order($order_id);
    if ($order && $order->get_customer_id() === 123) {
        return false;
    }
    return $enabled;
}, 10, 3);
```

#### `ihumbak_order_status_change_target`
Override the target order status for a specific document.

```php
apply_filters('ihumbak_order_status_change_target', string $status, int $order_id, Document $document);
```

| Parameter | Type | Description |
|-----------|------|-------------|
| $status | string | Target order status |
| $order_id | int | WooCommerce order ID |
| $document | Document | The document being issued |

**Example:**
```php
// Set different status for receipts
add_filter('ihumbak_order_status_change_target', function($status, $order_id, $document) {
    if ($document->getDocumentType() === 'receipt') {
        return 'processing';
    }
    return $status;
}, 10, 3);
```

#### `ihumbak_order_status_change_checkbox_default`
Change the default checkbox state for order status change.

```php
apply_filters('ihumbak_order_status_change_checkbox_default', bool $checked, int|null $order_id);
```

| Parameter | Type | Description |
|-----------|------|-------------|
| $checked | bool | Default checked state |
| $order_id | int\|null | WooCommerce order ID or null for new documents |

**Example:**
```php
// Default to unchecked for all documents
add_filter('ihumbak_order_status_change_checkbox_default', function($checked, $order_id) {
    return false;
}, 10, 2);
```

### Automatic Updates

#### `ihumbak_updates_enabled`
Override whether automatic updates are enabled.

```php
apply_filters('ihumbak_updates_enabled', bool $enabled);
```

| Parameter | Type | Description |
|-----------|------|-------------|
| $enabled | bool | Whether updates are enabled (default: true) |

**Example:**
```php
// Disable updates in development environment
add_filter('ihumbak_updates_enabled', function($enabled) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        return false;
    }
    return $enabled;
});
```

#### `ihumbak_update_repository_url`
Override the GitHub repository URL.

```php
apply_filters('ihumbak_update_repository_url', string $url);
```

| Parameter | Type | Description |
|-----------|------|-------------|
| $url | string | Repository URL |

**Example:**
```php
// Use a fork repository
add_filter('ihumbak_update_repository_url', function($url) {
    return 'https://github.com/my-org/my-fork/';
});
```

#### `ihumbak_update_branch`
Override the branch to check for updates.

```php
apply_filters('ihumbak_update_branch', string $branch);
```

| Parameter | Type | Description |
|-----------|------|-------------|
| $branch | string | Branch name (default: 'develop') |

**Example:**
```php
// Check develop branch for beta updates
add_filter('ihumbak_update_branch', function($branch) {
    if (get_option('ihumbak_beta_updates')) {
        return 'develop';
    }
    return $branch;
});
```

#### `ihumbak_github_access_token`
Provide GitHub access token programmatically.

```php
apply_filters('ihumbak_github_access_token', string $token);
```

| Parameter | Type | Description |
|-----------|------|-------------|
| $token | string | GitHub personal access token |

**Example:**
```php
// Get token from options instead of constant
add_filter('ihumbak_github_access_token', function($token) {
    return get_option('ihumbak_github_token', '');
});
```

#### `ihumbak_update_info`
Modify update information before it's displayed or used.

```php
apply_filters('ihumbak_update_info', object $info);
```

| Parameter | Type | Description |
|-----------|------|-------------|
| $info | object | Update info (version, download URL, changelog, etc.) |

**Example:**
```php
// Modify changelog display
add_filter('ihumbak_update_info', function($info) {
    if (isset($info->sections['changelog'])) {
        $info->sections['changelog'] = '<h4>Changes</h4>' . $info->sections['changelog'];
    }
    return $info;
});
```

---

## Related Files

- `src/Modules/Invoice/NumberingService.php` - Numbering hooks
- `src/Modules/Invoice/OrderDataExtractor.php` - Payment method hooks
- `src/Modules/Invoice/OrderStatusService.php` - Order status change hooks
- `src/Modules/Invoice/SuperAdminService.php` - Super-admin hooks
- `src/Modules/PDF/PdfGenerator.php` - PDF hooks
- `src/Modules/Email/EmailService.php` - Email hooks
- `src/Modules/Email/AbstractDocumentEmail.php` - Email template hooks
- `src/Infrastructure/Traits/SiteLocaleTrait.php` - Translation/locale hooks
- `src/Modules/Updates/UpdateService.php` - Automatic update hooks
