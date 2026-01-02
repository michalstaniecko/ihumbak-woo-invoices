# Document Types

This document describes the different document types supported by iHumbak WooCommerce Invoices plugin.

## Overview

| Type | Class | Description |
|------|-------|-------------|
| Invoice | `Invoice` | Full VAT invoice |
| Receipt | `Receipt` | Simplified receipt |
| Credit Note | `CreditNote` | Correction invoice |
| Receipt Return | `ReceiptReturn` | Return document for receipts (informational) |

---

## Invoice (VAT Invoice)

**Type identifier:** `invoice`
**Class:** `IHumbak\Invoices\Models\Invoice`

A full VAT invoice compliant with Polish and EU regulations.

### Characteristics

- Complete seller and buyer data with NIP (VAT ID)
- Line items with VAT breakdown
- Payment due date
- Payment method information
- Legal document for B2B transactions

### Required Fields

| Field | Description |
|-------|-------------|
| document_number | Unique invoice number |
| issue_date | Date of issue |
| sale_date | Date of sale/delivery |
| seller_data | Full seller information |
| buyer_data | Full buyer information with NIP |
| items | At least one line item |
| payment_method | Payment type |

### Optional Fields

| Field | Description |
|-------|-------------|
| due_date | Payment deadline |
| payment_date | Date when payment was received |
| notes | Additional notes |
| order_id | Link to WooCommerce order |

### PDF Template

- File: `templates/pdf/default/invoice.php`
- Includes: Header, seller/buyer data, items table, totals, payment info, footer

---

## Receipt

**Type identifier:** `receipt`
**Class:** `IHumbak\Invoices\Models\Receipt`

A simplified document for retail/B2C transactions.

### Characteristics

- Simplified buyer data (NIP optional)
- No payment due date required
- For individual customers (non-business)
- Lighter regulatory requirements

### Required Fields

| Field | Description |
|-------|-------------|
| document_number | Unique receipt number |
| issue_date | Date of issue |
| sale_date | Date of sale |
| seller_data | Full seller information |
| items | At least one line item |

### Optional Fields

| Field | Description |
|-------|-------------|
| buyer_data | Buyer information (simplified) |
| payment_method | Payment type |
| notes | Additional notes |
| order_id | Link to WooCommerce order |

### PDF Template

- File: `templates/pdf/default/receipt.php`
- Simplified layout compared to invoice

---

## Credit Note (Correction Invoice)

**Type identifier:** `correction` / `credit_note`
**Class:** `IHumbak\Invoices\Models\CreditNote`

A document that corrects or cancels a previously issued invoice.

### Characteristics

- References original document
- Correction type: full or partial
- Correction reason required
- Negative line item values
- Optional link to WooCommerce refund

### Required Fields

| Field | Description |
|-------|-------------|
| document_number | Unique credit note number |
| issue_date | Date of issue |
| corrected_document_id | ID of original document |
| correction_type | 'full' or 'partial' |
| correction_reason | Reason for correction |
| items | Correction items (negative values) |

### Optional Fields

| Field | Description |
|-------|-------------|
| refund_id | Link to WC_Order_Refund |
| notes | Additional notes |

### Correction Types

| Type | Description |
|------|-------------|
| full | Complete cancellation of original document |
| partial | Partial correction (specific items/amounts) |

### PDF Template

- File: `templates/pdf/default/credit-note.php`
- Includes: "CORRECTS DOCUMENT" section, correction reason, negative totals

---

## Receipt Return

**Type identifier:** `receipt_return`
**Class:** `IHumbak\Invoices\Models\ReceiptReturn`

An informational document for tracking returns/refunds related to receipts. This is **NOT** an official accounting document.

### Key Differences from Credit Note

| Aspect | Credit Note | Receipt Return |
|--------|-------------|----------------|
| Source document | Invoice | Receipt |
| Legal status | Official accounting document | Informational document |
| Buyer NIP | Required | Optional |
| Numbering pattern | `CN/{YYYY}/{MM}/{NNNN}` | `RR/{YYYY}/{MM}/{NNNN}` |

### Characteristics

- References original receipt (not invoice)
- Return type: full or partial
- Return reason optional
- Negative line item values
- Optional link to WooCommerce refund
- Supports manual entry mode for external receipts
- Informational document disclaimer on PDF

### Required Fields

| Field | Description |
|-------|-------------|
| document_number | Unique receipt return number |
| issue_date | Date of issue |
| corrected_document_id | ID of original receipt (or manual entry) |
| correction_type | 'full' or 'partial' |
| items | Return items (negative values) |

### Optional Fields

| Field | Description |
|-------|-------------|
| correction_reason | Reason for return |
| refund_id | Link to WC_Order_Refund |
| is_manual_entry | Manual entry mode flag |
| original_document_number | Original receipt number (manual mode) |
| original_document_date | Original receipt date (manual mode) |
| notes | Additional notes |

### Return Types

| Type | Description |
|------|-------------|
| full | Complete cancellation of original receipt |
| partial | Partial return (specific items/amounts) |

### PDF Template

- File: `templates/pdf/default/receipt-return.php`
- Includes: "CORRECTS RECEIPT" section, return reason, negative totals
- **Important:** Includes disclaimer: "This is an informational document, not an official accounting document."

---

## Document Status Flow

```
draft → issued → sent → paid
              ↘ cancelled
```

| Status | Description |
|--------|-------------|
| draft | Document being prepared, can be edited |
| issued | Document finalized, limited editing |
| sent | Document sent to customer |
| paid | Payment received |
| cancelled | Document cancelled |

### Status Transitions

- `draft` → `issued`: Normal finalization
- `issued` → `draft`: Only by super-admin (special permission)
- `issued` → `sent`: After email delivery
- `issued`/`sent` → `paid`: After payment confirmation
- Any → `cancelled`: Document cancellation

---

## Related Files

### Models
- `src/Models/Document.php` - Abstract base class
- `src/Models/Invoice.php` - Invoice implementation
- `src/Models/Receipt.php` - Receipt implementation
- `src/Models/CreditNote.php` - Credit note implementation
- `src/Models/ReceiptReturn.php` - Receipt return implementation
- `src/Models/DocumentItem.php` - Line item model
- `src/Models/Buyer.php` - Buyer value object
- `src/Models/Seller.php` - Seller value object

### Repositories
- `src/Infrastructure/Database/DocumentRepository.php`
- `src/Infrastructure/Database/DocumentItemRepository.php`

### PDF Templates
- `templates/pdf/default/invoice.php`
- `templates/pdf/default/receipt.php`
- `templates/pdf/default/credit-note.php`
- `templates/pdf/default/receipt-return.php`
- `templates/pdf/default/styles.css`

### Admin Forms
- `templates/admin/invoice-edit.php`
- `templates/admin/receipt-edit.php`
- `templates/admin/credit-note-edit.php`
- `templates/admin/receipt-return-edit.php`
