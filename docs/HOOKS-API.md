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

#### `ihumbak_email_sent`
Fired after an email with invoice is sent.

```php
do_action('ihumbak_email_sent', Invoice $invoice, string $email);
```

| Parameter | Type | Description |
|-----------|------|-------------|
| $invoice | Invoice | The sent document |
| $email | string | Recipient email address |

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

### Email Template

#### `ihumbak_email_template`
Modify email template path.

```php
apply_filters('ihumbak_email_template', string $template, Invoice $invoice);
```

| Parameter | Type | Description |
|-----------|------|-------------|
| $template | string | Template file path |
| $invoice | Invoice | The document |

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

---

## Related Files

- `src/Modules/Invoice/NumberingService.php` - Numbering hooks
- `src/Modules/Invoice/OrderDataExtractor.php` - Payment method hooks
- `src/Modules/Admin/SuperAdminService.php` - Super-admin hooks
- `src/Modules/PDF/PdfGenerator.php` - PDF hooks
