# Configuration

This document describes all configuration options for iHumbak WooCommerce Invoices plugin.

## wp-config.php Constants

### `IHUMBAK_SUPER_ADMIN_IDS`

Comma-separated list of user IDs with super-admin privileges.
Super-admins can revert document status from "issued" back to "draft".

```php
define('IHUMBAK_SUPER_ADMIN_IDS', '1,5,12');
```

See [super-admin-configuration.md](super-admin-configuration.md) for detailed setup instructions.

### Automatic Updates

The plugin supports automatic updates from GitHub releases. Configure these options to customize update behavior:

#### `IHUMBAK_UPDATE_BRANCH`

Branch to check for updates. Default is `develop`.

```php
define('IHUMBAK_UPDATE_BRANCH', 'develop');
```

#### `IHUMBAK_GITHUB_ACCESS_TOKEN`

GitHub personal access token for private repositories or to increase API rate limits.

```php
define('IHUMBAK_GITHUB_ACCESS_TOKEN', 'ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
```

**Note:** For public repositories, this is optional. For private repositories or to avoid rate limiting, create a token at GitHub > Settings > Developer settings > Personal access tokens with `repo` scope.

#### `IHUMBAK_DISABLE_UPDATES`

Disable automatic update checks entirely.

```php
define('IHUMBAK_DISABLE_UPDATES', true);
```

**Use cases:**
- Development environments where you manage updates manually
- When using a different update mechanism (e.g., WordPress.org)
- Testing specific plugin versions

---

## Plugin Settings (wp_options)

Settings are stored in `wp_options` table under key `ihumbak_invoices_settings`.

Access in admin: **WooCommerce > Invoices > Settings**

### Settings Structure

```php
[
    'seller' => [
        'name' => '',           // Company name
        'details' => '',        // Company details (address, NIP, bank, phone, email)
    ],
    'numbering' => [
        'invoice_pattern' => 'FV/{YYYY}/{MM}/{NNNN}',
        'receipt_pattern' => 'PAR/{YYYY}/{MM}/{NNNN}',
        'correction_pattern' => 'FK/{YYYY}/{MM}/{NNNN}',
        'reset_monthly' => true,
    ],
    'pdf' => [
        'template' => 'default',
        'logo_id' => 0,
        'footer_text' => '',
    ],
    'display' => [
        'show_order_column' => true,
        'nip_meta_key' => '_billing_nip',
    ],
]
```

---

## Settings Tabs

### 1. Seller Tab

| Option | Type | Description |
|--------|------|-------------|
| name | string | Company/seller name displayed on documents |
| details | textarea | Full seller details (multi-line) |

**Example details:**
```
ul. Example Street 123
00-000 Warsaw, Poland
NIP: 1234567890
Bank: PKO BP
Account: 00 1234 5678 9012 3456 7890 1234
Phone: +48 123 456 789
Email: invoices@example.com
```

### 2. Numbering Tab

| Option | Type | Description |
|--------|------|-------------|
| invoice_pattern | string | Invoice number pattern |
| receipt_pattern | string | Receipt number pattern |
| correction_pattern | string | Credit note number pattern |
| reset_monthly | boolean | Reset numbering each month |

**Available placeholders:**

| Placeholder | Description | Example |
|-------------|-------------|---------|
| {YYYY} | Year (4 digits) | 2025 |
| {YY} | Year (2 digits) | 25 |
| {MM} | Month (2 digits) | 01 |
| {DD} | Day (2 digits) | 15 |
| {NNNN} | Number (4 digits) | 0001 |
| {NNN} | Number (3 digits) | 001 |
| {NN} | Number (2 digits) | 01 |

### 3. PDF Tab

| Option | Type | Description |
|--------|------|-------------|
| template | string | Template set name (e.g., 'default') |
| logo_id | int | Media library attachment ID for logo |
| footer_text | string | Custom footer text for PDF |

**Template hierarchy:**
1. `wp-content/themes/{child-theme}/ihumbak-invoices/{template}/`
2. `wp-content/themes/{parent-theme}/ihumbak-invoices/{template}/`
3. `{plugin}/templates/pdf/{template}/`

### 4. Display Tab

| Option | Type | Description |
|--------|------|-------------|
| show_order_column | boolean | Show documents column in WooCommerce orders list |
| nip_meta_key | string | Order meta key for buyer NIP/VAT number |

---

## Programmatic Access

### Get Settings

```php
$settings = get_option('ihumbak_invoices_settings', []);

// Get specific setting
$seller_name = $settings['seller']['name'] ?? '';
$invoice_pattern = $settings['numbering']['invoice_pattern'] ?? 'FV/{YYYY}/{MM}/{NNNN}';
```

### Update Settings

```php
$settings = get_option('ihumbak_invoices_settings', []);
$settings['seller']['name'] = 'New Company Name';
update_option('ihumbak_invoices_settings', $settings);
```

---

## Related Files

- `src/Core/Plugin.php` - Settings registration
- `templates/admin/settings.php` - Settings page template
- `docs/super-admin-configuration.md` - Super-admin detailed setup
