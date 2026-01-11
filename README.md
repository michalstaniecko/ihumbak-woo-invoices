# iHumbak WooCommerce Invoices

A comprehensive WordPress/WooCommerce plugin for generating VAT invoices, receipts, credit notes, and receipt returns with PDF export capabilities.

## Features

- **VAT Invoices** - Generate professional invoices with automatic numbering
- **Receipts** - Create receipts for cash and card transactions
- **Credit Notes** - Issue correction documents for invoices
- **Receipt Returns** - Document returns for receipts
- **PDF Export** - Generate and download PDF documents using DOMPDF
- **WooCommerce Integration** - Import order data with one click
- **Customer Portal** - Customers can view and download their documents from My Account
- **Email Notifications** - Automatically send documents via email
- **Monthly Reports** - Generate reports with CSV export
- **Multi-language Support** - Full internationalization (i18n) with Norwegian translation included
- **Template Overrides** - Customize PDF templates via theme directory
- **HPOS Compatible** - Supports WooCommerce High-Performance Order Storage

## Requirements

| Requirement | Minimum Version |
|-------------|-----------------|
| PHP | 8.0+ |
| WordPress | 6.0+ |
| WooCommerce | 7.0+ |

## Installation

### From ZIP file

1. Download the latest release ZIP file
2. Go to **WordPress Admin > Plugins > Add New > Upload Plugin**
3. Upload the ZIP file and click **Install Now**
4. Activate the plugin

### From Source (Development)

```bash
# Clone the repository
git clone https://github.com/ihumbak/woo-invoices.git

# Install dependencies
cd woo-invoices
composer install

# For production build
./scripts/build-release.sh 1.0.0
```

## Configuration

After activation, configure the plugin at **WooCommerce > Invoices > Settings**:

### Seller Settings
- Company name, address, and contact information
- Tax ID (NIP) for VAT purposes
- Bank account details

### Numbering Settings
- Custom patterns for each document type
- Supported tokens: `{YYYY}`, `{MM}`, `{DD}`, `{NNNN}`
- Example: `INV/{YYYY}/{MM}/{NNNN}` produces `INV/2025/01/0001`

### PDF Settings
- Select template set
- Logo upload
- Paper size configuration

### Automation Settings
- Auto-send emails when documents are issued
- Configure which document types trigger emails

## Usage

### Creating Documents

1. Navigate to **WooCommerce > Invoices**
2. Click **Add Invoice**, **Add Receipt**, or **Add Credit Note**
3. Fill in the document details or import from a WooCommerce order
4. Save as draft or issue immediately
5. Download PDF or send via email

### From Order Page

1. Open any WooCommerce order
2. Use the **Documents** meta box to create documents
3. Click **Create Invoice** or **Create Receipt**
4. Document is pre-filled with order data

### Customer Portal

Customers can access their documents from **My Account > Invoices** and download PDFs directly.

## Document Types

| Type | Description | Use Case |
|------|-------------|----------|
| Invoice | VAT invoice | B2B transactions, tax documentation |
| Receipt | Sales receipt | B2C transactions, POS sales |
| Credit Note | Invoice correction | Returns, price adjustments for invoices |
| Receipt Return | Receipt correction | Returns for receipt-based sales |

## Template Customization

PDF templates can be overridden in your theme:

```
wp-content/themes/your-theme/ihumbak-invoices/default/
├── invoice.php
├── receipt.php
├── credit-note.php
├── receipt-return.php
└── styles.css
```

Create a custom template set by adding a new directory (e.g., `my-template/`) and selecting it in settings.

## Hooks & Filters

### Actions

```php
// After document is created
do_action('ihumbak_document_created', $document);

// After document is issued
do_action('ihumbak_document_issued', $document);

// Before PDF is generated
do_action('ihumbak_before_pdf_render', $document);
```

### Filters

```php
// Modify document number pattern
add_filter('ihumbak_numbering_pattern', function($pattern, $type) {
    return $pattern;
}, 10, 2);

// Custom capability for managing documents
add_filter('ihumbak_manage_documents_capability', function($cap) {
    return 'manage_woocommerce';
});
```

See [docs/HOOKS-API.md](docs/HOOKS-API.md) for complete reference.

## Development

### Directory Structure

```
ihumbak-woo-invoices/
├── assets/              # CSS and JavaScript files
├── docs/                # Documentation
├── languages/           # Translation files
├── src/                 # PHP source code
│   ├── Contracts/       # Interfaces
│   ├── Core/            # Plugin core, activation, settings
│   ├── Exceptions/      # Custom exceptions
│   ├── Infrastructure/  # Services, repositories
│   ├── Models/          # Data models
│   └── Modules/         # Feature modules (Admin, PDF, Email, etc.)
├── templates/           # Template files
│   ├── admin/           # Admin UI templates
│   ├── emails/          # Email templates
│   ├── frontend/        # Customer portal templates
│   └── pdf/             # PDF templates
├── tests/               # PHPUnit tests
└── vendor/              # Composer dependencies
```

### Commands

```bash
# Run tests
composer test

# Check coding standards
composer phpcs

# Fix coding standards
composer phpcbf

# Run static analysis
composer phpstan

# Run all checks
composer check
```

### Coding Standards

- PSR-4 autoloading
- WordPress Coding Standards (WPCS)
- PHP 8.0+ with strict types
- Full type hints for parameters and return values

## Documentation

| Document | Description |
|----------|-------------|
| [DATABASE.md](docs/DATABASE.md) | Database schema |
| [HOOKS-API.md](docs/HOOKS-API.md) | Hooks and filters reference |
| [CONFIGURATION.md](docs/CONFIGURATION.md) | Plugin configuration |
| [DOCUMENT-TYPES.md](docs/DOCUMENT-TYPES.md) | Document types guide |
| [RELEASE.md](docs/RELEASE.md) | Release process |

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes following the coding standards
4. Run all checks (`composer check`)
5. Commit your changes (`git commit -m 'feat: add amazing feature'`)
6. Push to the branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

## License

This plugin is licensed under the [GPL-2.0-or-later](https://www.gnu.org/licenses/gpl-2.0.html).

## Support

- [Report Issues](https://github.com/ihumbak/woo-invoices/issues)
- [Documentation](docs/)

## Credits

Developed by [iHumbak](https://ihumbak.com)
