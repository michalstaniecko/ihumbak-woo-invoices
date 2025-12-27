# Database Schema

This document describes the database structure for iHumbak WooCommerce Invoices plugin.

## Tables Overview

| Table | Description |
|-------|-------------|
| `{prefix}ihumbak_documents` | Main documents table (invoices, receipts, credit notes) |
| `{prefix}ihumbak_document_items` | Document line items |
| `{prefix}ihumbak_numbering` | Document numbering system |

---

## `{prefix}ihumbak_documents`

Main table for all document types (invoices, receipts, credit notes).

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary key |
| order_id | BIGINT | FK to WooCommerce order |
| document_type | ENUM | 'invoice', 'receipt', 'correction' |
| document_number | VARCHAR(50) | Document number |
| issue_date | DATE | Issue date |
| sale_date | DATE | Sale date |
| due_date | DATE | Payment due date (nullable) |
| payment_date | DATE | Payment date (nullable) |
| corrected_document_id | BIGINT | FK for credit notes (nullable) |
| buyer_data | JSON | Buyer information |
| seller_data | JSON | Seller information |
| subtotal | DECIMAL(10,2) | Net total |
| tax_total | DECIMAL(10,2) | VAT total |
| total | DECIMAL(10,2) | Gross total |
| currency | VARCHAR(3) | Currency code |
| payment_method | VARCHAR(20) | Payment type (transfer/cash/card/online) |
| payment_method_id | VARCHAR(50) | WC payment method ID (e.g., "bacs", "przelewy24") |
| payment_method_title | VARCHAR(255) | WC payment method title (e.g., "Bank Transfer") |
| status | ENUM | 'draft', 'issued', 'sent', 'paid', 'cancelled' |
| pdf_path | VARCHAR(255) | Path to cached PDF (nullable) |
| notes | TEXT | Notes (nullable) |
| created_at | DATETIME | Created timestamp |
| updated_at | DATETIME | Updated timestamp |

### Indexes
- PRIMARY KEY (id)
- INDEX (order_id)
- INDEX (document_type, status)
- INDEX (document_number)

---

## `{prefix}ihumbak_document_items`

Line items for documents.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary key |
| document_id | BIGINT | FK to document |
| product_id | BIGINT | FK to WC product (nullable) |
| sku | VARCHAR(100) | Product SKU (nullable) |
| name | VARCHAR(255) | Item name |
| quantity | DECIMAL(10,3) | Quantity |
| unit | VARCHAR(20) | Unit (pcs, kg, etc.) |
| unit_price_net | DECIMAL(10,2) | Unit price net |
| unit_price_gross | DECIMAL(10,2) | Unit price gross |
| tax_rate | DECIMAL(5,2) | VAT rate (%) |
| tax_amount | DECIMAL(10,2) | VAT amount |
| line_total_net | DECIMAL(10,2) | Line total net |
| line_total_gross | DECIMAL(10,2) | Line total gross |

### Indexes
- PRIMARY KEY (id)
- INDEX (document_id)
- INDEX (product_id)

---

## `{prefix}ihumbak_numbering`

Document numbering sequences.

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary key |
| document_type | VARCHAR(20) | Document type |
| year | INT | Year |
| month | INT | Month (nullable, for monthly reset) |
| last_number | INT | Last used number |
| pattern | VARCHAR(100) | Numbering pattern |

### Indexes
- PRIMARY KEY (id)
- UNIQUE (document_type, year, month)

---

## Numbering System

### Available Placeholders

| Placeholder | Description | Example |
|-------------|-------------|---------|
| {YYYY} | Year (4 digits) | 2025 |
| {YY} | Year (2 digits) | 25 |
| {MM} | Month (2 digits) | 12 |
| {DD} | Day (2 digits) | 18 |
| {NNNN} | Number (4 digits) | 0001 |
| {NNN} | Number (3 digits) | 001 |
| {NN} | Number (2 digits) | 01 |

### Default Patterns

| Document Type | Pattern | Example |
|---------------|---------|---------|
| Invoice | `FV/{YYYY}/{MM}/{NNNN}` | FV/2025/01/0001 |
| Receipt | `PAR/{YYYY}/{MM}/{NNNN}` | PAR/2025/01/0001 |
| Credit Note | `CN/{YYYY}/{MM}/{NNNN}` | CN/2025/01/0001 |

---

## JSON Structures

### buyer_data

```json
{
  "name": "Company Name",
  "address": "Street 123",
  "city": "City",
  "postal_code": "00-000",
  "country": "PL",
  "nip": "1234567890",
  "email": "email@example.com",
  "phone": "+48 123 456 789"
}
```

### seller_data

```json
{
  "name": "Seller Company",
  "details": "Full address, NIP, bank account, etc."
}
```

---

## Related Files

- `src/Core/Installer.php` - Database table creation
- `src/Infrastructure/Database/DocumentRepository.php` - Document CRUD
- `src/Infrastructure/Database/DocumentItemRepository.php` - Item CRUD
- `src/Modules/Invoice/NumberingService.php` - Numbering logic
