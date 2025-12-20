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

**Podsumowanie testow:** 56 testow, 240 asercji - wszystkie przechodza

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

**Podsumowanie testow:** 149 testow, 419 asercji - wszystkie przechodza

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
- [ ] Metoda 2: Przyciski na stronie zamowienia WC
  - [ ] OrderMetaBox (Utworz fakture / Utworz paragon)
  - [ ] Pre-fill formularza z GET parameter order_id

### Pozostale rozszerzenia
- [ ] Faktury korygujace (correction.php)
- [ ] Portal klienta (My Account)
- [ ] Email z faktura
