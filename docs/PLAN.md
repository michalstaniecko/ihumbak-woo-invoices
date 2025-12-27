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
**Status: UKONCZONA**

### 2.1 Modele domenowe
**Katalog:** `src/Models/`

| Plik | Opis | Status |
|------|------|--------|
| `Document.php` | Abstrakcyjna klasa bazowa dokumentu | [x] |
| `Invoice.php` | Faktura VAT (extends Document) | [x] |
| `Receipt.php` | Paragon (extends Document) | [x] |
| `DocumentItem.php` | Pozycja na dokumencie | [x] |
| `Buyer.php` | Value Object - dane nabywcy | [x] |
| `Seller.php` | Value Object - dane sprzedawcy | [x] |

### 2.2 Warstwa danych
**Katalog:** `src/Infrastructure/Database/`

| Plik | Opis | Status |
|------|------|--------|
| `DocumentRepository.php` | CRUD dokumentow (save, find, delete) | [x] |
| `DocumentItemRepository.php` | CRUD pozycji dokumentu z transakcjami | [x] |

### 2.3 Serwisy
**Katalog:** `src/Modules/Invoice/`

| Plik | Opis | Status |
|------|------|--------|
| `NumberingService.php` | Generowanie numerow wg wzorca z zabezpieczeniem przed race conditions | [x] |
| `CalculationService.php` | Obliczenia: netto/brutto, VAT, sumy (AJAX) | [x] |

### 2.4 Admin UI
**Katalog:** `src/Modules/Admin/`

| Plik | Opis | Status |
|------|------|--------|
| `DocumentListTable.php` | WP_List_Table - lista dokumentow z filtrami | [x] |
| `DocumentController.php` | Obsluga formularzy faktury i paragonu | [x] |
| `AjaxController.php` | Endpointy AJAX dla obliczen | [x] |

### 2.5 Szablony
**Katalog:** `templates/admin/`

| Plik | Opis | Status |
|------|------|--------|
| `documents-list.php` | Lista dokumentow z przyciskami | [x] |
| `invoice-edit.php` | Formularz faktury VAT | [x] |
| `receipt-edit.php` | Formularz paragonu | [x] |
| `partials/items-table.php` | Tabela pozycji (wspolna) z template JS | [x] |
| `partials/buyer-fields.php` | Pola danych nabywcy | [x] |
| `partials/seller-fields.php` | Pola danych sprzedawcy | [x] |

### 2.6 JavaScript
**Katalog:** `assets/js/`

| Plik | Opis | Status |
|------|------|--------|
| `document-edit.js` | Dynamiczne wiersze, AJAX do przeliczen z debounce | [x] |

**Przeplyw obliczen:**
```
[Uzytkownik zmienia wartosc]
    -> JS wysyla AJAX do CalculationService (z debounce 300ms)
    -> PHP oblicza netto/brutto/VAT/sumy
    -> JS aktualizuje pola w formularzu
```

### 2.7 Testy jednostkowe
**Katalog:** `tests/Unit/`

| Plik | Opis | Status |
|------|------|--------|
| `Modules/Invoice/CalculationServiceTest.php` | Testy obliczen VAT | [x] |
| `Models/DocumentItemTest.php` | Testy modelu pozycji | [x] |
| `Models/BuyerTest.php` | Testy value object Buyer | [x] |
| `Models/InvoiceTest.php` | Testy modelu Invoice | [x] |

**Podsumowanie testow Fazy 2:** 56 testow, 240 asercji

---

## Faza 3: Eksport PDF
**Status: UKONCZONA**

### 3.1 Serwisy PDF
**Katalog:** `src/Modules/PDF/`

| Plik | Opis | Status |
|------|------|--------|
| `PdfCacheManager.php` | Zarzadzanie cache PDF w wp-content/uploads/ | [x] |
| `TemplateLoader.php` | Ladowanie szablonow z hierarchia WP (theme override) | [x] |
| `TemplateRegistry.php` | Wykrywanie dostepnych zestawow szablonow | [x] |
| `PdfGenerator.php` | Generowanie PDF z DOMPDF | [x] |

### 3.2 Szablony PDF
**Katalog:** `templates/pdf/default/`

| Plik | Opis | Status |
|------|------|--------|
| `styles.css` | Style CSS dla PDF (DOMPDF compatible) | [x] |
| `invoice.php` | Szablon faktury VAT (EU Standard, English) | [x] |
| `receipt.php` | Szablon paragonu (EU Standard, English) | [x] |

### 3.3 Hierarchia szablonow

Szablony moga byc nadpisywane przez motywy:
1. `wp-content/themes/{child-theme}/ihumbak-invoices/{template-set}/`
2. `wp-content/themes/{parent-theme}/ihumbak-invoices/{template-set}/`
3. `{plugin}/templates/pdf/{template-set}/`

Uzytkownicy moga tworzyc wlasne zestawy szablonow (np. `pl/`, `de/`, `custom/`)
w katalogu motywu i wybrac je w ustawieniach pluginu.

### 3.4 Testy jednostkowe
**Katalog:** `tests/Unit/Modules/PDF/`

| Plik | Opis | Status |
|------|------|--------|
| `TemplateLoaderTest.php` | Testy ladowania szablonow | [x] |
| `TemplateRegistryTest.php` | Testy rejestru szablonow | [x] |
| `PdfCacheManagerTest.php` | Testy cache PDF | [x] |

**Podsumowanie testow Fazy 3:** 149 testow, 419 asercji

---

## Faza 4: Rozszerzenia
**Status: W TRAKCIE**

### Import pozycji z zamowienia WC
- [x] Metoda 1: Przycisk "Fetch Order Data" w formularzu faktury/paragonu
  - [x] Kolumna SKU w modelu DocumentItem i bazie danych
  - [x] Ustawienie NIP meta key w konfiguracji
  - [x] Serwis OrderDataExtractor (produkty, shipping, buyer, payment method)
  - [x] Endpoint AJAX fetch_order_data
  - [x] Modyfikacja formularzy (przycisk + spinner)
  - [x] JavaScript (fetch, confirm replace/append, populate)
  - [x] Testy jednostkowe dla OrderDataExtractor
- [x] Metoda 2: Przyciski na stronie zamowienia WC
  - [x] OrderMetaBox (Utworz fakture / Utworz paragon)
  - [x] Pre-fill formularza z GET parameter order_id
  - [x] Auto-fetch danych zamowienia przy otwarciu formularza

### Faktury korygujace (Credit Notes)
- [x] Model CreditNote (extends Document)
  - [x] Typ korekty: full/partial
  - [x] Powiazanie z dokumentem zrodlowym (corrected_document_id)
  - [x] Powod korekty (correction_reason)
  - [x] Opcjonalne powiazanie z WC refund (refund_id)
- [x] Serwis RefundDataExtractor
  - [x] Ekstrakcja danych z WC_Order_Refund
  - [x] Automatyczne ustawianie ujemnych wartosci
- [x] Szablon PDF credit-note.php
  - [x] Naglowek "CREDIT NOTE / CORRECTION INVOICE"
  - [x] Sekcja "CORRECTS DOCUMENT"
  - [x] Sekcja "CORRECTION REASON"
- [x] Formularz admin credit-note-edit.php
  - [x] Wybor dokumentu zrodlowego
  - [x] Pole powodu korekty
  - [x] Przycisk "Fetch Refund Data"
- [x] Migracja bazy danych
  - [x] Kolumny: corrected_document_id, correction_type, correction_reason, refund_id
  - [x] System wersjonowania migracji
- [x] Integracja z OrderMetaBox
  - [x] Przycisk "Create Credit Note" na stronie zamowienia

### Pozostale rozszerzenia
- [x] Kolumna z numerem dokumentu na liscie zamowien WC
  - [x] Klasa OrderListColumn (hook: manage_edit-shop_order_columns, manage_shop_order_custom_column)
  - [x] Opcja w ustawieniach: wlacz/wylacz kolumne
  - [x] Wyswietlanie numeru faktury/paragonu/korekty (z linkiem do dokumentu)
  - [x] Obsluga HPOS (High-Performance Order Storage)
- [x] Cofniecie statusu faktury (issued -> draft) dla super-adminow
  - [x] Stala `IHUMBAK_SUPER_ADMIN_IDS` w wp-config.php (string ID oddzielonych przecinkami, np. "1,5,12")
  - [x] Serwis `SuperAdminService` - sprawdzanie czy user ma uprawnienia super-admina
  - [x] Modyfikacja `DocumentController` - walidacja przy zmianie statusu
  - [x] UI: przycisk "Revert to Draft" widoczny tylko dla super-adminow
  - [x] Testy jednostkowe dla SuperAdminService

### Raporty miesięczne
Widok tabelaryczny z podsumowaniem wystawionych dokumentów w danym miesiącu.
Możliwość eksportu do CSV.

- [ ] Serwis ReportService
  - [ ] Metoda `getMonthlyReport(int $year, int $month, string $document_type)`
  - [ ] Agregacja danych wg metod płatności
  - [ ] Obliczanie sum: ilość dokumentów, netto, VAT, brutto
- [ ] Kontroler ReportController
  - [ ] Strona admina: WooCommerce > Faktury > Raporty
  - [ ] Filtry: rok, miesiąc, typ dokumentu (invoice/receipt/credit_note)
  - [ ] Obsługa eksportu CSV
- [ ] Szablon admin/reports.php
  - [ ] Formularz filtrów (selecty: rok, miesiąc, typ dokumentu)
  - [ ] Tabela wyników z kolumnami:
    - Metoda płatności (payment_method_title lub payment_method)
    - Ilość dokumentów
    - Suma netto
    - Suma VAT
    - Suma brutto
  - [ ] Wiersz podsumowania (totale)
  - [ ] Przycisk "Eksportuj do CSV"
- [ ] Eksport CSV (CsvExporter)
  - [ ] Generowanie pliku CSV z nagłówkami
  - [ ] Poprawne kodowanie UTF-8 (BOM dla Excel)
  - [ ] Nazwa pliku: `report-{type}-{year}-{month}.csv`
- [ ] Testy jednostkowe
  - [ ] ReportServiceTest
  - [ ] CsvExporterTest

- [ ] Portal klienta (My Account)
- [ ] Email z faktura

### Metoda platnosci - pelne dane z zamowienia WC
**Status: UKONCZONA**

Aktualnie `payment_method` przechowuje tylko typ (transfer, cash, card, online).
Potrzebujemy pelnych danych z zamowienia WC, aby zachowac historie nawet gdy metoda zostanie zmieniona/usunieta.

- [x] Rozszerzenie modelu Invoice
  - [x] Nowe pole `payment_method_id` (string) - ID metody z WC (np. "bacs", "przelewy24")
  - [x] Nowe pole `payment_method_title` (string) - Nazwa slowna z WC (np. "Przelewy24", "Przelew bankowy")
  - [x] Zachowanie `payment_method` jako typ (transfer/cash/card/online) dla kategoryzacji
  - [x] Metoda `getPaymentMethodDisplayName()` - zwraca title lub fallback do typu
- [x] Migracja bazy danych
  - [x] Dodanie kolumn `payment_method_id` i `payment_method_title` do tabeli `ihumbak_documents`
  - [x] Migracja istniejacych danych z zamowien WC (automatyczna przy upgrade do 1.3.0)
- [x] Modyfikacja OrderDataExtractor
  - [x] `extractPaymentMethod()` zwraca array z id, title, type
  - [x] Uzycie `$order->get_payment_method()` dla ID
  - [x] Uzycie `$order->get_payment_method_title()` dla nazwy slownej
- [x] Modyfikacja formularzy admin
  - [x] Wyswietlanie nazwy slownej metody platnosci (Payment Method Name)
  - [x] Edycja/wybor typu platnosci (Payment Type)
  - [x] Walidacja dlugosci pol (payment_method_id: 50, payment_method_title: 255)
- [x] Modyfikacja szablonow PDF
  - [x] Wyswietlanie nazwy slownej zamiast typu (getPaymentMethodDisplayName)
- [x] Aktualizacja testow jednostkowych
  - [x] Testy Invoice: payment_method_id, payment_method_title, getPaymentMethodDisplayName
  - [x] Testy OrderDataExtractor: nowy format zwracanych danych

### Internacjonalizacja (i18n)
- [x] Audyt szablonow pod katem hardcodowanych fraz
  - [x] Szablony PDF (`templates/pdf/default/`)
    - [x] invoice.php
    - [x] receipt.php
    - [x] credit-note.php
    - [x] styles.css (brak tekstow - tylko CSS)
  - [x] Szablony admin (`templates/admin/`) - juz byly zinternacjonalizowane
    - [x] documents-list.php
    - [x] invoice-edit.php
    - [x] receipt-edit.php
    - [x] credit-note-edit.php
    - [x] settings.php
    - [x] partials/*.php
  - [x] Klasy PHP (komunikaty, etykiety) - juz byly zinternacjonalizowane
- [x] Zamiana hardcodowanych fraz na funkcje i18n
  - [x] `__()` dla tekstow
  - [x] `_e()` dla tekstow wyswietlanych bezposrednio
  - [x] `esc_html__()` / `esc_attr__()` dla escapowanych tekstow
  - [x] `_n()` dla form liczby mnogiej
  - [x] Text domain: `ihumbak-invoices`
- [x] Generowanie pliku POT (szablon tlumaczen)
  - [x] Konfiguracja xgettext do ekstrakcji
  - [x] Plik `languages/ihumbak-invoices.pot` (1312 linii, ~200 stringow)
- [x] Ladowanie tlumaczen w pluginie
  - [x] `load_plugin_textdomain()` w Plugin.php - juz zaimplementowane
  - [x] Sciezka: `languages/`
- [x] Tlumaczenie norweskie (nb_NO)
  - [x] `languages/ihumbak-invoices-nb_NO.po`
  - [x] `languages/ihumbak-invoices-nb_NO.mo`

---

## Podsumowanie testow

**Lacznie:** 252 testy, 756 asercji - wszystkie przechodza
