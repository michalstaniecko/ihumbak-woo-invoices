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
**Status: UKONCZONA**

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

### Manual Credit Note Entry Mode (korekty do faktur z poprzedniego systemu)
**Status: UKONCZONA**

**Problem:** Uzytkownik musi miec mozliwosc wystawiania korekt do faktur, ktore byly wystawione w poprzednim systemie (poza pluginem). Te faktury nie istnieja w bazie danych pluginu, wiec nie mozna ich wybrac jako dokumentu zrodlowego.

**Rozwiazanie:** Przelacznik w formularzu korekty: "Select existing invoice" / "Enter manually"

- [x] Modyfikacja modelu CreditNote
  - [x] Nowe pole `is_manual_entry` (bool) - flaga trybu recznego
  - [x] Nowe pole `original_document_number` (string) - numer oryginalnej faktury (reczny wpis)
  - [x] Nowe pole `original_document_date` (date) - data oryginalnej faktury (reczny wpis)
  - [x] Walidacja: corrected_document_id XOR (is_manual_entry + original_document_number)
- [x] Migracja bazy danych (v1.6.0)
  - [x] Kolumny: is_manual_entry, original_document_number, original_document_date
- [x] Modyfikacja formularza credit-note-edit.php
  - [x] Przelacznik (radio buttons): "Select invoice from system" / "Enter original invoice data manually"
  - [x] Tryb wyboru faktury (istniejacy)
    - [x] Select z fakturami z bazy danych
    - [x] Przycisk "Load Invoice Data"
  - [x] Tryb reczny (nowy)
    - [x] Pole tekstowe: Original Invoice Number
    - [x] Pole date: Original Invoice Date
    - [x] Wszystkie pozostale pola edytowalne (buyer, seller, items, dates)
    - [x] Numer korekty pozostaje automatyczny (bez zmian)
  - [x] JavaScript do przelaczania widocznosci sekcji
  - [x] Walidacja formularza (wymagane pola w kazdym trybie)
- [x] Modyfikacja DocumentController
  - [x] Obsluga nowych pol przy zapisie
  - [x] Walidacja wzajemnego wykluczania sie trybow
- [x] Modyfikacja szablonu PDF credit-note.php
  - [x] Obsluga trybu recznego w sekcji "CORRECTS DOCUMENT"
  - [x] Wyswietlanie original_document_number i original_document_date zamiast danych z bazy
- [x] Modyfikacja listy dokumentow (DocumentListTable)
  - [x] Wyswietlanie numeru oryginalnej faktury (z bazy lub recznego)
  - [x] Etykieta "(external)" dla faktur z zewnatrz
- [x] Testy jednostkowe (13 nowych testow)
  - [x] CreditNoteTest: nowe pola i walidacja
  - [x] 402 testy, 1021 asercji - wszystkie przechodza

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

### Raporty miesiezne
**Status: UKONCZONA**

Widok tabelaryczny z podsumowaniem wystawionych dokumentow w danym miesiacu.
Mozliwosc eksportu do CSV.

- [x] Serwis ReportService
  - [x] Metoda `getMonthlyReport(int $year, int $month, string $document_type)`
  - [x] Agregacja danych wg metod platnosci
  - [x] Obliczanie sum: ilosc dokumentow, netto, VAT, brutto
- [x] Kontroler ReportController
  - [x] Strona admina: WooCommerce > Faktury > Raporty
  - [x] Filtry: rok, miesiac, typ dokumentu (invoice/receipt/credit_note)
  - [x] Obsluga eksportu CSV
- [x] Szablon admin/reports.php
  - [x] Formularz filtrow (selecty: rok, miesiac, typ dokumentu)
  - [x] Tabela wynikow z kolumnami:
    - Metoda platnosci (payment_method_title lub payment_method)
    - Ilosc dokumentow
    - Suma netto
    - Suma VAT
    - Suma brutto
  - [x] Wiersz podsumowania (totale)
  - [x] Przycisk "Eksportuj do CSV"
- [x] Eksport CSV (CsvExporter)
  - [x] Generowanie pliku CSV z naglowkami
  - [x] Poprawne kodowanie UTF-8 (BOM dla Excel)
  - [x] Nazwa pliku: `report-{type}-{year}-{month}.csv`
- [x] Testy jednostkowe
  - [x] ReportServiceTest
  - [x] CsvExporterTest

- [x] Portal klienta (My Account)
  - [x] PortalController - integracja z WooCommerce My Account
    - [x] Endpoint `/my-account/invoices/` z listą dokumentów
    - [x] Menu item w nawigacji My Account
    - [x] Sekcja dokumentów na stronie szczegółów zamówienia
    - [x] Bezpieczne pobieranie PDF (nonce, weryfikacja właściciela)
  - [x] PortalService - logika biznesowa
    - [x] Pobieranie dokumentów klienta (`getDocumentsForCustomer`)
    - [x] Pobieranie dokumentów zamówienia (`getDocumentsForOrder`)
    - [x] Weryfikacja dostępu (`canCustomerAccessDocument`)
    - [x] Filtrowanie widocznych statusów (issued, sent, paid)
  - [x] Szablony frontend
    - [x] `templates/frontend/portal/documents-list.php` - lista wszystkich dokumentów
    - [x] `templates/frontend/portal/order-documents.php` - dokumenty na stronie zamówienia
  - [x] Flush rewrite rules w Activator
  - [x] Testy jednostkowe (PortalControllerTest, PortalServiceTest)
- [x] Email z faktura
  - [x] EmailService - serwis do wysyłki z PDF załącznikami
  - [x] WC_Email classes (InvoiceEmail, ReceiptEmail, CreditNoteEmail)
  - [x] Szablony email (HTML i plain text)
  - [x] Ustawienia auto-send w panelu admina
  - [x] Akcja "Send Email" na liście dokumentów
  - [x] Integracja z WooCommerce (WC > Settings > Emails)
  - [x] Testy jednostkowe dla EmailService

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

## Faza 5: Finalizacja i Release
**Status: UKONCZONA**

### 5.1 PDF Language Fix - jezyk strony zamiast jezyka admina
**Status: UKONCZONA**

**Problem:** Gdy admin ma ustawiony inny jezyk niz strona (np. admin: EN, strona: NO), PDF generuje sie w jezyku admina zamiast w jezyku strony.

**Rozwiazanie:**
- [x] Modyfikacja PdfGenerator - przelaczanie locale przed renderowaniem
  - [x] Uzycie WPLANG option do pobrania locale strony (omija jezyk usera admina)
  - [x] Uzycie explicite `load_textdomain()` z locale path
  - [x] Logowanie bledow dla brakujacych plikow tlumaczen
- [x] Kompatybilnosc z PHP 8.1+ (`wp_readonly()` helper)
- [x] Testy jednostkowe dla przelaczania locale (18 testow)

### 5.2 Readonly mode dla issued documents
**Status: UKONCZONA**

**Problem:** Po zatwierdzeniu faktury (status "issued") pola pozycji sa nadal edytowalne w UI, mimo ze zmiany sie nie zapisuja.

**Rozwiazanie:**
- [x] Modyfikacja szablonow formularzy (invoice-edit.php, receipt-edit.php, credit-note-edit.php)
  - [x] Dodanie atrybutu `readonly`/`disabled` do inputow gdy status != 'draft'
  - [x] Ukrycie przyciskow "Add Item" i "Remove" dla wydanych dokumentow
  - [x] Stylizacja CSS dla trybu readonly (szare tlo, kursor not-allowed)
- [x] Modyfikacja JavaScript (document-edit.js)
  - [x] Wylaczenie dynamicznego dodawania wierszy gdy dokument jest wydany
  - [x] Wylaczenie AJAX recalculations dla readonly mode
- [x] Wyrazne oznaczenie w UI ze dokument jest tylko do odczytu
- [x] Przekazywanie flagi isReadonly z PHP do JavaScript przez wp_localize_script

### 5.3 Release workflow
**Status: UKONCZONA**

**Cel:** Mechanizm budowania paczki ZIP do instalacji oraz aktualizacja wersji.

**Rozwiazanie:**
- [x] Skrypt bash `scripts/build-release.sh`
  - [x] Parametr: nowy numer wersji (np. `./build-release.sh 1.0.0`)
  - [x] Aktualizacja wersji w plikach:
    - [x] `ihumbak-invoices.php` (header: Version + constant)
    - [x] `composer.json` (version)
  - [x] Instalacja produkcyjnych zaleznosci (`composer install --no-dev --optimize-autoloader`)
  - [x] Tworzenie ZIP z odpowiednia struktura katalogow
  - [x] Wykluczenie plikow deweloperskich (.git, tests/, docs/, phpunit.xml, phpstan.neon, etc.)
  - [x] Nazwa pliku: `ihumbak-invoices-{version}.zip`
  - [x] Przywracanie dev dependencies po buildzie
- [x] Dokumentacja procesu release w docs/RELEASE.md

---

### 5.4 Konfigurowalny system uprawnien (RBAC)
**Status: UKONCZONA**

**Cel:** Elastyczne przydzielanie uprawnien do zarzadzania dokumentami.

**Rozwiazanie:**
- [x] Serwis PermissionService
  - [x] Metoda `canManageDocuments()` - sprawdza uprawnienia usera
  - [x] Domyslnie: `manage_woocommerce` capability
  - [x] Filtr `ihumbak_manage_documents_capability` do zmiany wymaganej capability
- [x] Integracja z kontrolerami
  - [x] Walidacja uprawnien w DocumentController
  - [x] Walidacja uprawnien w AjaxController
  - [x] Walidacja uprawnien w ReportController
- [x] Dokumentacja w docs/super-admin-configuration.md

---

## Faza 6: Zwroty paragonow (Receipt Returns)
**Status: UKONCZONA**

### Cel

Umozliwienie wystawiania dokumentow zwrotu dla paragonow, analogicznie do Credit Notes dla faktur. Dokument informacyjny (nie ksiegowy), umozliwiajacy pelne raportowanie sprzedazy i zwrotow w podziale na typy dokumentow.

### Uzasadnienie

Aktualnie system raportow miesieznych moze podsumowac:
- Faktury (invoice)
- Paragony (receipt)
- Korekty faktur (credit_note)

Brakuje mozliwosci osobnego raportowania zwrotow z paragonow. Dzieki nowemu typowi dokumentu bedzie mozliwe:
- Osobne podsumowanie zwrotow z faktur i zwrotow z paragonow
- Pelny obraz sprzedazy: faktury + paragony - korekty faktur - zwroty paragonow
- Latwiejsze uzgadnianie z systemami kasowymi/POS

### Roznice miedzy Credit Note a Receipt Return

| Aspekt | Credit Note | Receipt Return |
|--------|-------------|----------------|
| Dokument zrodlowy | Faktura (Invoice) | Paragon (Receipt) |
| Status prawny | Oficjalny dokument ksiegowy | Dokument informacyjny |
| Wymagany NIP nabywcy | Tak | Nie |
| Numeracja | Osobna (CN/{YYYY}/{MM}/{NNNN}) | Osobna (RR/{YYYY}/{MM}/{NNNN}) |
| Szablon PDF | credit-note.php | receipt-return.php |

### 6.1 Model ReceiptReturn
**Katalog:** `src/Models/`

- [x] Klasa `ReceiptReturn` (extends Document)
  - [x] Stala `TYPE = 'receipt_return'`
  - [x] Pole `correction_type` (full/partial) - analogicznie do CreditNote
  - [x] Pole `corrected_document_id` - ID oryginalnego paragonu
  - [x] Pole `correction_reason` - powod zwrotu (opcjonalny)
  - [x] Pole `refund_id` - opcjonalne powiazanie z WC refund
  - [x] Tryb reczny (manual entry) - analogicznie do CreditNote:
    - [x] `is_manual_entry` (bool)
    - [x] `original_document_number` (string)
    - [x] `original_document_date` (date)
  - [x] Metoda `getDocumentTypeLabel()` -> "Receipt Return"
  - [x] Metoda `getDisplayCorrectedDocumentNumber()`
  - [x] Metoda `fromArray()` i `toArray()`

### 6.2 Migracja bazy danych
**Wersja:** 1.7.0

Brak nowych kolumn - wykorzystanie istniejacych pol z CreditNote:
- `document_type = 'receipt_return'`
- `corrected_document_id`, `correction_type`, `correction_reason`, `refund_id`
- `is_manual_entry`, `original_document_number`, `original_document_date`

Jedyna zmiana:
- [x] Walidacja `corrected_document_id` musi wskazywac na `receipt` (nie `invoice`)

### 6.3 System numeracji
**Katalog:** `src/Modules/Invoice/`

- [x] Rozszerzenie NumberingService
  - [x] Obsluga typu `receipt_return`
  - [x] Domyslny wzorzec: `RR/{YYYY}/{MM}/{NNNN}`
- [x] Rozszerzenie ustawien (Settings)
  - [x] Nowe pole: `numbering.receipt_return_pattern`
  - [x] Osobny licznik w tabeli `ihumbak_numbering`

### 6.4 Formularz administracyjny
**Katalog:** `templates/admin/`

- [x] Nowy szablon `receipt-return-edit.php`
  - [x] Analogiczny do credit-note-edit.php
  - [x] Przelacznik: "Select receipt from system" / "Enter manually"
  - [x] Tryb wyboru paragonu:
    - [x] Select z paragonami (status: issued)
    - [x] Przycisk "Load Receipt Data"
  - [x] Tryb reczny:
    - [x] Original Receipt Number
    - [x] Original Receipt Date
  - [x] Pola wspolne:
    - [x] Correction Type (full/partial)
    - [x] Correction Reason (opcjonalny)
    - [x] Issue Date
    - [x] Buyer data (bez wymogu NIP)
    - [x] Items (ujemne wartosci)

### 6.5 Kontroler i routing
**Katalog:** `src/Modules/Admin/`

- [x] Rozszerzenie DocumentController
  - [x] Metoda `render_receipt_return_edit()`
  - [x] Metoda `handle_save_receipt_return()`
  - [x] Hook `admin_post_ihumbak_save_receipt_return`
  - [x] Walidacja: corrected_document_id musi byc paragonem
- [x] Rozszerzenie DocumentListTable
  - [x] Filtr `document_type = 'receipt_return'`
  - [x] Wyswietlanie oryginalnego paragonu w kolumnie Type
  - [x] Etykieta "(external)" dla trybu recznego

### 6.6 Integracja z OrderMetaBox
**Katalog:** `src/Modules/Admin/`

- [x] Przycisk "Create Receipt Return" w OrderMetaBox
  - [x] Widoczny gdy istnieje paragon dla zamowienia
  - [x] Link z pre-fill paragonu

### 6.7 Szablon PDF
**Katalog:** `templates/pdf/default/`

- [x] Nowy szablon `receipt-return.php`
  - [x] Naglowek: "RECEIPT RETURN" / "ZWROT PARAGONU"
  - [x] Sekcja: "CORRECTS RECEIPT" z numerem i data oryginalnego paragonu
  - [x] Sekcja: "REASON FOR RETURN" (jesli podano)
  - [x] Brak numeru NIP (opcjonalny)
  - [x] Informacja: "This is an informational document, not an official accounting document"
  - [x] Tabela pozycji z ujemnymi wartosciami
  - [x] Suma zwrotu

### 6.8 Integracja z raportami
**Katalog:** `src/Modules/Admin/`

- [x] Rozszerzenie ReportController
  - [x] Nowy filtr: `document_type = 'receipt_return'`
  - [x] Opcja w dropdown: "Receipt Returns"
- [x] Rozszerzenie ReportService
  - [x] Obsluga typu `receipt_return` w `getMonthlyReport()`
- [x] Rozszerzenie szablonu reports.php
  - [x] Nowa opcja w select: "Receipt Returns"

### 6.9 Portal klienta
**Katalog:** `src/Modules/Frontend/`

- [x] Rozszerzenie PortalService
  - [x] Wyswietlanie Receipt Returns na liscie dokumentow
  - [x] Pobieranie PDF dla Receipt Return
- [x] Rozszerzenie szablonow frontend
  - [x] Obsluga `receipt_return` w documents-list.php

### 6.10 Email
**Katalog:** `src/Modules/Email/`

- [x] Nowa klasa `ReceiptReturnEmail` (extends WC_Email)
  - [x] Szablon HTML i plain text
  - [x] Zalacznik PDF
- [x] Integracja z WooCommerce Emails

### 6.11 JavaScript
**Katalog:** `assets/js/`

- [x] Rozszerzenie document-edit.js
  - [x] Obsluga formularza receipt-return-edit
  - [x] Toggle entry mode (analogicznie do credit note)
  - [x] AJAX fetch receipt data

### 6.12 Testy jednostkowe
**Katalog:** `tests/Unit/`

- [x] `Models/ReceiptReturnTest.php`
  - [x] Testy modelu (analogicznie do CreditNoteTest)
  - [x] Testy walidacji (corrected_document_id musi byc paragonem)
  - [x] Testy trybu recznego
- [x] Rozszerzenie istniejacych testow
  - [x] ReportServiceTest - obsluga receipt_return
  - [x] DocumentRepositoryTest - hydrate receipt_return

### 6.13 Dokumentacja
**Katalog:** `docs/`

- [x] Aktualizacja DOCUMENT-TYPES.md
  - [x] Nowa sekcja: Receipt Return
  - [x] Tabela porownawcza: Credit Note vs Receipt Return
- [x] Aktualizacja HOOKS-API.md
  - [x] Nowe hooki dla receipt_return

---

## Faza 7: Poprawa czytelnosci tabeli pozycji (Items Table UI)
**Status: UKONCZONA**

### Problem

Tabela pozycji dokumentu miala 11 kolumn, co przy ograniczonej szerokosci powodowalo obcinanie zawartosci pol:
- Name: tekst obciety (np. "Deksler Luftinntak Sid" nie miescil sie)
- SKU: widoczne tylko pierwsze znaki
- Qty, Unit: wartosci ledwo widoczne
- Price Net, VAT %, Price Gross: wartosci obciete

### Cel

Poprawa czytelnosci i UX tabeli pozycji na wszystkich typach dokumentow (Invoice, Receipt, Credit Note, Receipt Return).

### 7.1 Dwurzedowy uklad (Two-Row Layout)
**Priorytet: WYSOKI** - ZAIMPLEMENTOWANY

Kazda pozycja dokumentu wyswietlana jest jako 2 wiersze:
- **Wiersz 1 (Name Row)**: Pole Name rozciagniete na cala szerokosc + SKU ponizej + przycisk usuwania (rowspan=2)
- **Wiersz 2 (Values Row)**: Qty, Price Net, VAT%, Total Net, VAT, Total Gross

```
┌────────────────────────────────────────────────────────────────────┬─────────┐
│ [Product name input ─────────────────────────────────────────────] │         │
│ [SKU input (smaller) ───────────]                                  │  [🗑️]   │
├──────────┬────────────┬─────────┬────────────┬──────────┬──────────┤         │
│ [Qty]    │ [Price Net]│ [VAT %] │ Total Net  │   VAT    │  Total   │         │
└──────────┴────────────┴─────────┴────────────┴──────────┴──────────┴─────────┘
```

### 7.2 Usuniete/ukryte kolumny

- [x] **Price Gross** - przeniesiony do hidden input (wartosc zachowana dla kompatybilnosci)
- [x] **Unit** - przeniesiony do hidden input (nie wyswietlany w PDF, dane zachowane)

### 7.3 Finalna struktura kolumn (8 kolumn w naglowku)

| Kolumna | Szerokosc | Zawartosc |
|---------|-----------|-----------|
| Name | 35% (min 200px) | Name input + SKU input (colspan=6 w wierszu name) |
| Qty | 70px | Input numeryczny |
| Price Net | 100px | Input numeryczny |
| VAT % | 70px | Input numeryczny |
| Total Net | 100px | Wyswietlana wartosc |
| VAT | 90px | Wyswietlana wartosc |
| Total Gross | 110px | Wyswietlana wartosc |
| Actions | 50px | Przycisk usuwania (rowspan=2) |

### 7.4 Responsywnosc

- [x] Wrapper `.ihumbak-items-card` z `overflow-x: auto`
- [x] Min-width dla tabeli: 700px
- [x] Wizualne oddzielenie pozycji (border-bottom na values row)

### 7.5 Poprawki UX

- [x] Placeholdery we wszystkich polach
- [x] Stylizacja placeholderow (kursywa, szary kolor)
- [x] Tlo dla wiersza name (#fafafa)
- [x] Wieksza czcionka dla nazwy produktu (14px)

### Zmodyfikowane pliki

| Plik | Zmiany |
|------|--------|
| `templates/admin/partials/items-table.php` | Dwurzedowa struktura, JS template |
| `assets/css/admin.css` | Style dla two-row layout |
| `assets/js/document-edit.js` | Obsluga usuwania obu wierszy, focus na name row |

### Testy

- [x] Testy jednostkowe (430 testow, 1138 asercji - wszystkie przechodza)

---

## Podsumowanie testow

**Lacznie:** 430 testow, 1138 asercji - wszystkie przechodza

---

## Historia wersji

| Wersja | Data | Opis |
|--------|------|------|
| 0.4.0 | TBD | Items Table UI - poprawa czytelnosci tabeli pozycji |
| 0.3.0 | 2025-01 | Receipt Returns - dokumenty zwrotu dla paragonow |
| 0.2.0 | 2025-01 | Manual credit note entry, customer portal, email, permissions, i18n |
| 0.1.0 | 2024-12 | Initial development version |

---

## Architektura typow dokumentow

```
Document (abstract)
├── Invoice          [invoice]        - Faktura VAT
├── Receipt          [receipt]        - Paragon
├── CreditNote       [credit_note]    - Korekta faktury (oficjalny dokument ksiegowy)
└── ReceiptReturn    [receipt_return] - Zwrot paragonu (dokument informacyjny)
```

### Powiazania dokumentow

```
Invoice ─────────────────> CreditNote
   │                          │
   │ (corrected_document_id)  │
   │                          │
   └──────────────────────────┘

Receipt ─────────────────> ReceiptReturn
   │                          │
   │ (corrected_document_id)  │
   │                          │
   └──────────────────────────┘
```

### Raportowanie miesiezne

| Typ dokumentu | Charakter | Wplyw na obroty |
|---------------|-----------|-----------------|
| Invoice | Sprzedaz | + (dodatni) |
| Receipt | Sprzedaz | + (dodatni) |
| Credit Note | Korekta | - (ujemny) |
| Receipt Return | Korekta | - (ujemny) |

**Przyklad raportu:**
```
Styczen 2025:
  Faktury:           +50,000 PLN (100 dok.)
  Paragony:          +15,000 PLN (500 dok.)
  Korekty faktur:     -2,500 PLN (5 dok.)
  Zwroty paragonow:     -800 PLN (12 dok.)
  ─────────────────────────────────────────
  RAZEM:             +61,700 PLN (617 dok.)
```
