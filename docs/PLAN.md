# Plan implementacji - iHumbak WooCommerce Invoices

## Decyzje projektowe

| Decyzja | Wybor |
|---------|-------|
| Formularze | **Osobne** dla faktury i paragonu (mniejsze ryzyko bledu) |
| Dodawanie pozycji | **Dynamiczne wiersze** w tabeli |
| Powiazanie z WC | **Tylko numer zamowienia** (import danych w przyszlosci) |
| Obliczenia | **PHP via AJAX** (wszystkie kalkulacje po stronie serwera) |

## Flow uzytkownika

1. Uzytkownik otwiera **WooCommerce > Faktury**
2. Widzi liste dokumentow z przyciskami **"Dodaj fakture"** / **"Dodaj paragon"**
3. Klika wybrany przycisk - otwiera sie **dedykowany formularz**
4. W formularzu:
   - Wpisuje/edytuje **dane sprzedawcy** (domyslnie z ustawien)
   - Wpisuje **dane kupujacego** (recznie)
   - Dodaje **pozycje** przyciskiem "Dodaj pozycje" (dynamiczne wiersze)
   - Widzi **podglad numeru** dokumentu (automatyczny)
   - Wybiera **date wystawienia** i **date sprzedazy**
   - Opcjonalnie wybiera **numer zamowienia WC** (tylko powiazanie)
5. Klika **Zapisz** - dokument zostaje zapisany
6. Moze **pobrac PDF** lub **edytowac** dokument

---

## Faza 1: Fundament
**Status: UKONCZONA**

- [x] Konfiguracja projektu (composer.json, phpcs.xml, phpstan.neon, phpunit.xml)
- [x] Glowny plik pluginu (ihumbak-invoices.php)
- [x] Klasy Core (Plugin, Container, Activator, Deactivator)
- [x] Instalator bazy danych (Installer)
- [x] Klasy wyjatkow (ContainerException, NotFoundException)
- [x] Panel ustawien (4 zakladki: Seller, Numbering, PDF, Automation)
- [x] Szablony admin (invoices-list.php, settings.php)
- [x] Assets (CSS/JS dla admin i frontend)
- [x] Handler dezinstalacji (uninstall.php)
- [x] Testy jednostkowe (ContainerTest - 8 testow)

---

## Faza 2: Panel administracyjny faktur
**Status: W TRAKCIE**

### 2.1 Modele domenowe
**Katalog:** `src/Models/`

| Plik | Opis | Status |
|------|------|--------|
| `Document.php` | Abstrakcyjna klasa bazowa dokumentu | [ ] |
| `Invoice.php` | Faktura VAT (extends Document) | [ ] |
| `Receipt.php` | Paragon (extends Document) | [ ] |
| `DocumentItem.php` | Pozycja na dokumencie | [ ] |
| `Buyer.php` | Value Object - dane nabywcy | [ ] |
| `Seller.php` | Value Object - dane sprzedawcy | [ ] |

### 2.2 Warstwa danych
**Katalog:** `src/Infrastructure/Database/`

| Plik | Opis | Status |
|------|------|--------|
| `DocumentRepository.php` | CRUD dokumentow (save, find, delete) | [ ] |
| `DocumentItemRepository.php` | CRUD pozycji dokumentu | [ ] |

### 2.3 Serwisy
**Katalog:** `src/Modules/Invoice/`

| Plik | Opis | Status |
|------|------|--------|
| `NumberingService.php` | Generowanie numerow wg wzorca | [ ] |
| `DocumentService.php` | Tworzenie, edycja, usuwanie dokumentow | [ ] |
| `CalculationService.php` | Obliczenia: netto/brutto, VAT, sumy (AJAX) | [ ] |

### 2.4 Admin UI
**Katalog:** `src/Modules/Admin/`

| Plik | Opis | Status |
|------|------|--------|
| `DocumentListTable.php` | WP_List_Table - lista dokumentow | [ ] |
| `InvoiceController.php` | Obsluga formularza faktury | [ ] |
| `ReceiptController.php` | Obsluga formularza paragonu | [ ] |

### 2.5 Szablony
**Katalog:** `templates/admin/`

| Plik | Opis | Status |
|------|------|--------|
| `documents-list.php` | Lista dokumentow z przyciskami | [ ] |
| `invoice-edit.php` | Formularz faktury VAT | [ ] |
| `receipt-edit.php` | Formularz paragonu | [ ] |
| `partials/items-table.php` | Tabela pozycji (wspolna) | [ ] |
| `partials/buyer-fields.php` | Pola danych nabywcy | [ ] |
| `partials/seller-fields.php` | Pola danych sprzedawcy | [ ] |

### 2.6 JavaScript
**Katalog:** `assets/js/`

| Plik | Opis | Status |
|------|------|--------|
| `document-edit.js` | Dynamiczne wiersze, AJAX do przeliczen | [ ] |

**Przeplyw obliczen:**
```
[Uzytkownik zmienia wartosc]
    -> JS wysyla AJAX do CalculationService
    -> PHP oblicza netto/brutto/VAT/sumy
    -> JS aktualizuje pola w formularzu
```

### Kolejnosc implementacji

```
1. Modele (Document, Invoice, Receipt, DocumentItem, Buyer, Seller)
      |
2. DocumentRepository + DocumentItemRepository
      |
3. NumberingService + CalculationService
      |
4. DocumentListTable + documents-list.php
      |
5. Szablony formularzy (invoice-edit.php, receipt-edit.php)
      |
6. InvoiceController + ReceiptController + AJAX endpoints
      |
7. document-edit.js (dynamiczne pozycje + AJAX)
      |
8. Testy jednostkowe
```

---

## Faza 3: Eksport PDF
**Status: DO ZROBIENIA**

- [ ] `PdfGenerator.php` - generowanie PDF z DOMPDF
- [ ] `templates/pdf/invoice.php` - szablon PDF faktury
- [ ] `templates/pdf/receipt.php` - szablon PDF paragonu

---

## Faza 4: Rozszerzenia
**Status: DO ZROBIENIA**

- [ ] Import pozycji z zamowienia WC
- [ ] Faktury korygujace
- [ ] Portal klienta (My Account)
- [ ] Email z faktura
