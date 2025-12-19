# iHumbak WooCommerce Invoices

Plugin WordPress/WooCommerce do generowania faktur VAT, paragonów i faktur korygujących.

## Informacje o projekcie

- **Nazwa:** iHumbak WooCommerce Invoices
- **Wersja:** 0.1.0 (w rozwoju)
- **Text Domain:** ihumbak-invoices
- **Namespace:** IHumbak\Invoices
- **Licencja:** GPL-2.0-or-later

## Wymagania systemowe

- PHP: 8.0+
- WordPress: 6.0+
- WooCommerce: 7.0+
- Biblioteka PDF: DOMPDF

## Architektura

### Wzorce projektowe

- **PSR-4 Autoloading** - struktura katalogów odpowiada namespace
- **Dependency Injection** - kontenery usług
- **Repository Pattern** - dostęp do danych
- **Factory Pattern** - tworzenie obiektów
- **Service Provider Pattern** - rejestracja usług

### Struktura katalogów

```
ihumbak-woo-invoices/
├── ihumbak-invoices.php      # Główny plik pluginu
├── uninstall.php             # Handler dezinstalacji
├── src/
│   ├── Core/                 # Jądro aplikacji
│   │   ├── Plugin.php        # Singleton pluginu
│   │   ├── Container.php     # DI Container
│   │   ├── Activator.php     # Aktywacja pluginu
│   │   ├── Deactivator.php   # Dezaktywacja pluginu
│   │   └── Installer.php     # Instalacja bazy danych
│   ├── Contracts/            # Interfejsy
│   ├── Models/               # Modele domenowe
│   │   ├── Invoice.php
│   │   ├── InvoiceItem.php
│   │   ├── Buyer.php
│   │   └── Seller.php
│   ├── Exceptions/           # Wyjątki
│   ├── Infrastructure/
│   │   ├── Database/         # Repozytoria
│   │   └── Logger/           # Logowanie
│   └── Modules/
│       ├── Invoice/          # Generowanie dokumentów
│       ├── PDF/              # Eksport PDF (DOMPDF)
│       ├── Email/            # Wysyłka email
│       ├── Admin/            # Panel administracyjny
│       └── Portal/           # Portal klienta
├── templates/                # Szablony PHP
│   ├── admin/
│   ├── pdf/
│   └── frontend/
├── assets/                   # Zasoby frontend
│   ├── css/
│   └── js/
├── tests/                    # Testy PHPUnit
├── languages/                # Pliki tłumaczeń (.pot/.po/.mo)
└── docs/                     # Dokumentacja
```

## Baza danych

### Tabele

#### `{prefix}ihumbak_documents`

Główna tabela dokumentów (faktury, paragony, korekty).

| Kolumna | Typ | Opis |
|---------|-----|------|
| id | BIGINT | Primary key |
| order_id | BIGINT | FK do zamówienia WC |
| document_type | ENUM | 'invoice', 'receipt', 'correction' |
| document_number | VARCHAR(50) | Numer dokumentu |
| issue_date | DATE | Data wystawienia |
| sale_date | DATE | Data sprzedaży |
| due_date | DATE | Termin płatności (nullable) |
| corrected_document_id | BIGINT | FK dla korekt (nullable) |
| buyer_data | JSON | Dane nabywcy |
| seller_data | JSON | Dane sprzedawcy |
| subtotal | DECIMAL(10,2) | Suma netto |
| tax_total | DECIMAL(10,2) | Suma VAT |
| total | DECIMAL(10,2) | Suma brutto |
| currency | VARCHAR(3) | Kod waluty |
| status | ENUM | 'draft', 'issued', 'sent', 'paid', 'cancelled' |
| pdf_path | VARCHAR(255) | Ścieżka do PDF (nullable) |
| notes | TEXT | Uwagi (nullable) |
| created_at | DATETIME | Data utworzenia |
| updated_at | DATETIME | Data modyfikacji |

#### `{prefix}ihumbak_document_items`

Pozycje na dokumentach.

| Kolumna | Typ | Opis |
|---------|-----|------|
| id | BIGINT | Primary key |
| document_id | BIGINT | FK do dokumentu |
| product_id | BIGINT | FK do produktu WC (nullable) |
| name | VARCHAR(255) | Nazwa pozycji |
| quantity | DECIMAL(10,3) | Ilość |
| unit | VARCHAR(20) | Jednostka (szt, kg, etc.) |
| unit_price_net | DECIMAL(10,2) | Cena jednostkowa netto |
| unit_price_gross | DECIMAL(10,2) | Cena jednostkowa brutto |
| tax_rate | DECIMAL(5,2) | Stawka VAT (%) |
| tax_amount | DECIMAL(10,2) | Kwota VAT |
| line_total_net | DECIMAL(10,2) | Wartość netto |
| line_total_gross | DECIMAL(10,2) | Wartość brutto |

#### `{prefix}ihumbak_numbering`

System numeracji dokumentów.

| Kolumna | Typ | Opis |
|---------|-----|------|
| id | BIGINT | Primary key |
| document_type | VARCHAR(20) | Typ dokumentu |
| year | INT | Rok |
| month | INT | Miesiąc (nullable) |
| last_number | INT | Ostatni użyty numer |
| pattern | VARCHAR(100) | Wzorzec numeracji |

## System numeracji

### Dostępne placeholdery

| Placeholder | Opis | Przykład |
|-------------|------|----------|
| {YYYY} | Rok (4 cyfry) | 2025 |
| {YY} | Rok (2 cyfry) | 25 |
| {MM} | Miesiąc (2 cyfry) | 12 |
| {DD} | Dzień (2 cyfry) | 18 |
| {NNNN} | Numer (4 cyfry) | 0001 |
| {NNN} | Numer (3 cyfry) | 001 |
| {NN} | Numer (2 cyfry) | 01 |

### Domyślne wzorce

- Faktura: `FV/{YYYY}/{MM}/{NNNN}`
- Paragon: `PAR/{YYYY}/{MM}/{NNNN}`
- Korekta: `FK/{YYYY}/{MM}/{NNNN}`

## Typy dokumentów

### Faktura VAT (invoice)

- Pełna faktura zgodna z polskimi przepisami
- Dane sprzedawcy i nabywcy z NIP
- Pozycje z VAT
- Termin płatności

### Paragon (receipt)

- Uproszczony dokument
- Bez NIP nabywcy (opcjonalnie)
- Dla klientów indywidualnych

### Faktura korygująca (correction)

- Odniesienie do faktury źródłowej
- Powód korekty
- Różnica wartości

## Hooki i filtry

### Akcje

```php
// Po utworzeniu dokumentu
do_action('ihumbak_document_created', Invoice $invoice, WC_Order $order);

// Przed generowaniem PDF
do_action('ihumbak_before_pdf_render', Invoice $invoice);

// Po wysłaniu emaila
do_action('ihumbak_email_sent', Invoice $invoice, string $email);
```

### Filtry

```php
// Modyfikacja numeru dokumentu
apply_filters('ihumbak_document_number', string $number, Invoice $invoice);

// Modyfikacja danych PDF
apply_filters('ihumbak_pdf_data', array $data, Invoice $invoice);

// Modyfikacja szablonu email
apply_filters('ihumbak_email_template', string $template, Invoice $invoice);
```

## Ustawienia pluginu

Zapisywane w `wp_options` pod kluczem `ihumbak_invoices_settings`.

```php
[
    'seller' => [
        'name' => '',           // Nazwa firmy
        'address' => '',        // Adres
        'city' => '',           // Miasto
        'postcode' => '',       // Kod pocztowy
        'country' => 'PL',      // Kraj
        'nip' => '',            // NIP
        'bank_name' => '',      // Nazwa banku
        'bank_account' => '',   // Numer konta
        'email' => '',          // Email
        'phone' => '',          // Telefon
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
    'automation' => [
        'auto_generate_invoice' => false,
        'auto_generate_receipt' => false,
        'trigger_status' => 'completed',
    ],
]
```

## Konwencje kodowania

### PHP

- **Standard:** WordPress Coding Standards (WPCS) + PSR-4
- **Wersja:** PHP 8.0+ (strict types)
- **Typy:** Pełne type hints dla parametrów i zwracanych wartości

### Nazewnictwo

| Element | Konwencja | Przykład |
|---------|-----------|----------|
| Klasy | PascalCase | `InvoiceGenerator` |
| Metody klas | camelCase | `generateFromOrder()` |
| Funkcje WP | snake_case | `ihumbak_get_invoice()` |
| Stałe | UPPER_SNAKE | `IHUMBAK_VERSION` |
| Hooki | snake_case | `ihumbak_invoice_created` |

### Bezpieczeństwo

1. **Sanityzacja danych wejściowych:**
   - `sanitize_text_field()` - tekst
   - `absint()` - liczby całkowite
   - `sanitize_email()` - email

2. **Escapowanie danych wyjściowych:**
   - `esc_html()` - tekst w HTML
   - `esc_attr()` - atrybuty HTML
   - `esc_url()` - URL

3. **Uprawnienia:**
   - Sprawdzaj `current_user_can('manage_woocommerce')`
   - Weryfikuj nonce dla formularzy

4. **SQL:**
   - Używaj `$wpdb->prepare()` dla wszystkich zapytań

## Git Workflow (GitFlow)

### Branches

- `main` - stabilna produkcja
- `develop` - integracja zmian
- `feature/*` - nowe funkcje
- `release/*` - przygotowanie wydania
- `hotfix/*` - pilne poprawki

### Konwencja commitów

```
<type>(<scope>): <description>

Typy:
- feat: nowa funkcja
- fix: poprawka błędu
- docs: dokumentacja
- refactor: refaktoryzacja
- test: testy
- chore: zadania pomocnicze
```

## Dostępne komendy

### Slash commands (agenci)

- `/coordinator` - Koordynator projektu
- `/php-dev` - Deweloper PHP
- `/review` - Code review
- `/devops` - CI/CD i automatyzacja
- `/docs` - Dokumentacja
- `/qa` - Testy i QA

### Composer scripts

```bash
composer install          # Instalacja zależności
composer test             # Uruchom testy PHPUnit
composer phpcs            # Sprawdź styl kodu
composer phpcbf           # Napraw styl kodu
composer phpstan          # Statyczna analiza
composer check            # Wszystkie sprawdzenia
```

## Checklist przed commitem

- [ ] Kod przechodzi PHPCS (`composer phpcs`)
- [ ] Kod przechodzi PHPStan (`composer phpstan`)
- [ ] Testy przechodzą (`composer test`)
- [ ] Dane wejściowe są sanityzowane
- [ ] Dane wyjściowe są escapowane
- [ ] Nonce jest weryfikowany (formularze)
- [ ] Uprawnienia są sprawdzane
- [ ] Stringi używają text domain `ihumbak-invoices`
- [ ] Dokumentacja jest aktualna

## Plan prac

Szczegolowy plan implementacji znajduje sie w pliku: **[docs/PLAN.md](docs/PLAN.md)**
