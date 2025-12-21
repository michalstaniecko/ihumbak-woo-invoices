# PDF Design Guide - Minimalist JetBrains Style

## Inspiracja

Design inspirowany fakturą JetBrains - minimalistyczny, profesjonalny, z wyraźną hierarchią wizualną.

**Kluczowe cechy:**
- Czytelność i prostota
- Ciemne nagłówki tabel z białym tekstem
- Czerwony akcent dla statusów (PAID/ZAPŁACONO)
- Dwukolumnowy układ dla danych
- Wycentrowany tytuł dokumentu

---

## Layout dokumentu

### Struktura strony

```
+------------------------------------------------------------------+
|  [LOGO]                                    Nazwa firmy           |
|                                            Ulica 123/45          |
|                                            00-000 Miasto         |
|                                            NIP: 1234567890       |
|                                            www.example.com       |
+------------------------------------------------------------------+
|                                                                  |
|                     Faktura VAT FV/2025/01/0001                  |
|                                                                  |
+------------------------------------------------------------------+
|  Szczegóły faktury:              |  Nabywca:                     |
|                                  |                               |
|  Numer: FV/2025/01/0001          |  Jan Kowalski                 |
|  Data wystawienia: 21.12.2025    |  ul. Przykładowa 1            |
|  Data sprzedaży: 21.12.2025      |  00-000 Warszawa              |
|  Termin płatności: 28.12.2025    |  NIP: 9876543210              |
|  Metoda płatności: Przelew       |                               |
+------------------------------------------------------------------+
|  LP | Nazwa produktu       | Ilość | Cena   | VAT  | Wartość    |
|-----|---------------------|-------|--------|------|------------|
|  1  | Usługa programist.  | 10 h  | 150,00 | 23%  | 1 845,00   |
|  2  | Hosting roczny      | 1 szt | 500,00 | 23%  | 615,00     |
+------------------------------------------------------------------+
|                                   Razem netto:      2 000,00 PLN |
|                                   VAT 23%:            460,00 PLN |
|                                   ------------------------------ |
|                                   Razem brutto:     2 460,00 PLN |
|                                                                  |
|                                          ZAPŁACONO               |
|                                   ══════════════════════════════ |
+------------------------------------------------------------------+
|  * Uwagi i informacje dodatkowe                                  |
+------------------------------------------------------------------+
```

### Wymiary strony (A4)

- **Format:** A4 (210mm x 297mm)
- **Marginesy:** 15mm (lewy/prawy), 12mm (góra/dół)
- **Obszar roboczy:** 180mm x 273mm

---

## Typografia

### Font główny: Open Sans

```css
font-family: "Open Sans", "DejaVu Sans", sans-serif;
```

**Open Sans** to profesjonalny, czytelny font zaprojektowany przez Steve'a Mattesona. Doskonale nadaje się do dokumentów biznesowych.

**Źródło:** https://fonts.google.com/specimen/Open+Sans

### Alternatywne fonty

| Font | Opis | Link |
|------|------|------|
| **Roboto** | Nowoczesny, czytelny font od Google | [Google Fonts](https://fonts.google.com/specimen/Roboto) |
| **Lato** | Elegancki font polskiego projektanta | [Google Fonts](https://fonts.google.com/specimen/Lato) |
| **Source Sans Pro** | Font Adobe, zaprojektowany dla czytelności | [Google Fonts](https://fonts.google.com/specimen/Source+Sans+Pro) |
| **DejaVu Sans** | Fallback - wbudowany w DOMPDF | (domyślny) |

### Instalacja fontu w DOMPDF

#### 1. Pobierz pliki fontu

Pobierz font z Google Fonts i wypakuj pliki `.ttf`:
- `OpenSans-Regular.ttf`
- `OpenSans-Bold.ttf`
- `OpenSans-Italic.ttf`
- `OpenSans-BoldItalic.ttf`

#### 2. Umieść pliki w katalogu pluginu

```
ihumbak-woo-invoices/
└── assets/
    └── fonts/
        ├── OpenSans-Regular.ttf
        ├── OpenSans-Bold.ttf
        ├── OpenSans-Italic.ttf
        └── OpenSans-BoldItalic.ttf
```

#### 3. Zarejestruj font w DOMPDF (PHP)

```php
use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isFontSubsettingEnabled', true);

$dompdf = new Dompdf($options);

// Pobierz font metrics z DOMPDF
$fontMetrics = $dompdf->getFontMetrics();

// Zarejestruj font
$fontDir = IHUMBAK_PLUGIN_PATH . 'assets/fonts/';

$fontMetrics->registerFont(
    ['family' => 'Open Sans', 'style' => 'normal', 'weight' => 'normal'],
    $fontDir . 'OpenSans-Regular.ttf'
);

$fontMetrics->registerFont(
    ['family' => 'Open Sans', 'style' => 'normal', 'weight' => 'bold'],
    $fontDir . 'OpenSans-Bold.ttf'
);

$fontMetrics->registerFont(
    ['family' => 'Open Sans', 'style' => 'italic', 'weight' => 'normal'],
    $fontDir . 'OpenSans-Italic.ttf'
);

$fontMetrics->registerFont(
    ['family' => 'Open Sans', 'style' => 'italic', 'weight' => 'bold'],
    $fontDir . 'OpenSans-BoldItalic.ttf'
);
```

#### 4. Użyj w CSS szablonu

```css
body {
    font-family: "Open Sans", "DejaVu Sans", sans-serif;
}
```

> **Uwaga:** DejaVu Sans jako fallback gwarantuje, że polskie znaki będą wyświetlane nawet jeśli Open Sans nie załaduje się poprawnie.

### Skala rozmiarów

| Token | Rozmiar | Zastosowanie |
|-------|---------|--------------|
| `xs`  | 8px     | Stopka, przypisy |
| `sm`  | 9px     | Komórki tabeli, etykiety |
| `base`| 10px    | Tekst podstawowy |
| `md`  | 11px    | Nagłówki sekcji |
| `lg`  | 14px    | Podtytuły |
| `xl`  | 18px    | Status badge |
| `xxl` | 22px    | Tytuł dokumentu |

### Grubości

- **Normal (400):** Tekst podstawowy, wartości
- **Bold (700):** Nagłówki, etykiety, sumy

---

## Kolorystyka

### Paleta główna

| Kolor | Hex | Zastosowanie |
|-------|-----|--------------|
| Primary | `#333333` | Tekst główny, nagłówki |
| Secondary | `#4A4A4A` | Nagłówki tabel |
| Accent | `#E91E63` | Status PAID, linie akcentowe |
| Muted | `#666666` | Tekst pomocniczy |
| Light | `#999999` | Tekst nieaktywny |

### Tła

| Kolor | Hex | Zastosowanie |
|-------|-----|--------------|
| Page | `#FFFFFF` | Tło strony |
| Table Header | `#4A4A4A` | Nagłówek tabeli |
| Table Alt | `#F9F9F9` | Alternatywne wiersze (opcjonalne) |
| Highlight | `#FFF3E0` | Wyróżnienia, korekty |

### Obramowania

| Kolor | Hex | Zastosowanie |
|-------|-----|--------------|
| Light | `#E0E0E0` | Separatory, obramowania komórek |
| Medium | `#CCCCCC` | Silniejsze separatory |
| Dark | `#4A4A4A` | Główne obramowania |

### Kolory statusów

| Status | Kolor | Hex |
|--------|-------|-----|
| Draft | Szary | `#9E9E9E` |
| Issued | Ciemny | `#4A4A4A` |
| Sent | Pomarańczowy | `#FF9800` |
| Paid | Różowy/Czerwony | `#E91E63` |
| Cancelled | Czerwony | `#F44336` |
| Corrected | Fioletowy | `#9C27B0` |

---

## Komponenty

### 1. Nagłówek dokumentu

```
+------------------------------------------------------------------+
|  [LOGO max 180x60px]               Nazwa Firmy Sp. z o.o.        |
|                                    ul. Biznesowa 123/45          |
|                                    00-001 Warszawa               |
|                                    NIP: PL1234567890             |
|                                    www.firma.pl                  |
+------------------------------------------------------------------+
```

**CSS:**
```css
.header {
    width: 100%;
    margin-bottom: 25px;
}
.header-table {
    width: 100%;
}
.logo-cell {
    width: 50%;
    vertical-align: top;
}
.logo {
    max-width: 180px;
    max-height: 60px;
}
.company-info {
    width: 50%;
    text-align: right;
    font-size: 9px;
    line-height: 1.6;
    vertical-align: top;
}
```

### 2. Tytuł dokumentu

```
                    Faktura VAT FV/2025/01/0001
```

**CSS:**
```css
.document-title {
    text-align: center;
    font-size: 22px;
    font-weight: bold;
    color: #333333;
    margin: 25px 0;
}
```

### 3. Sekcja szczegółów (dwie kolumny)

```
+--------------------------------+--------------------------------+
|  Szczegóły faktury:            |  Nabywca:                      |
|                                |                                |
|  Numer: FV/2025/01/0001        |  Firma ABC Sp. z o.o.          |
|  Data wystawienia: 21.12.2025  |  ul. Kliencka 99               |
|  Data sprzedaży: 21.12.2025    |  00-002 Kraków                 |
|  Termin płatności: 28.12.2025  |  NIP: PL9876543210             |
|  Metoda: Przelew               |                                |
+--------------------------------+--------------------------------+
```

**CSS:**
```css
.details-section {
    width: 100%;
    margin-bottom: 20px;
}
.details-table {
    width: 100%;
}
.details-left {
    width: 55%;
    vertical-align: top;
    padding-right: 20px;
}
.details-right {
    width: 45%;
    vertical-align: top;
}
.section-title {
    font-size: 11px;
    font-weight: bold;
    color: #333333;
    margin-bottom: 8px;
}
.detail-row {
    font-size: 10px;
    line-height: 1.6;
}
```

### 4. Tabela pozycji

```
+----+----------------------+-------+----------+------+------------+
| LP | Nazwa                | Ilość | Cena j.  | VAT  | Wartość    |
+----+----------------------+-------+----------+------+------------+
| 1  | Usługa programist.   | 10 h  | 150,00   | 23%  | 1 845,00   |
| 2  | Hosting roczny       | 1 szt | 500,00   | 23%  | 615,00     |
+----+----------------------+-------+----------+------+------------+
```

**CSS:**
```css
.items-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 15px;
}
.items-table th {
    background-color: #4A4A4A;
    color: #FFFFFF;
    font-size: 9px;
    font-weight: bold;
    padding: 8px 10px;
    text-align: left;
    border: 1px solid #4A4A4A;
}
.items-table th.numeric {
    text-align: right;
}
.items-table td {
    font-size: 9px;
    padding: 8px 10px;
    border: 1px solid #E0E0E0;
    vertical-align: top;
}
.items-table td.numeric {
    text-align: right;
}
.items-table td.center {
    text-align: center;
}
```

### 5. Podsumowanie

```
                                    Razem netto:      2 000,00 PLN
                                    VAT 23%:            460,00 PLN
                                    ──────────────────────────────
                                    Razem brutto:     2 460,00 PLN
```

**CSS:**
```css
.summary-wrapper {
    width: 100%;
    margin-top: 15px;
}
.summary-table {
    width: 45%;
    margin-left: auto;
}
.summary-row td {
    font-size: 10px;
    padding: 4px 0;
}
.summary-row td.label {
    text-align: left;
}
.summary-row td.value {
    text-align: right;
}
.summary-row.total td {
    font-size: 11px;
    font-weight: bold;
    padding-top: 8px;
    border-top: 1px solid #E0E0E0;
}
```

### 6. Status Badge

```
                                         ZAPŁACONO
                                    ═══════════════════
```

**CSS:**
```css
.status-badge {
    text-align: right;
    margin-top: 20px;
}
.status-text {
    font-size: 18px;
    font-weight: bold;
    color: #E91E63;
}
.status-underline {
    width: 45%;
    margin-left: auto;
    border-bottom: 2px solid #E91E63;
    margin-top: 5px;
}
```

### 7. Stopka

**CSS:**
```css
.footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 10px 15mm;
    font-size: 8px;
    color: #666666;
    border-top: 1px solid #E0E0E0;
}
.page-number {
    text-align: center;
}
```

---

## Warianty dokumentów

### Faktura VAT

- Pełny układ ze wszystkimi szczegółami
- Tytuł: "Faktura VAT [NUMER]"
- Sekcje: dane sprzedawcy/nabywcy, pozycje, VAT, termin płatności
- Status: DRAFT / WYSTAWIONA / WYSŁANA / ZAPŁACONA

### Paragon

- Uproszczony układ
- Tytuł: "Paragon [NUMER]"
- Bez NIP nabywcy (opcjonalnie)
- Bez terminu płatności
- Brak sekcji "Nabywca" lub uproszczona

```
+------------------------------------------------------------------+
|                        Paragon PAR/2025/01/0001                  |
+------------------------------------------------------------------+
|  Data: 21.12.2025                                                |
+------------------------------------------------------------------+
|  LP | Nazwa                          | Ilość | Cena   | Wartość  |
|-----|-------------------------------|-------|--------|----------|
|  1  | Produkt A                     | 2 szt | 50,00  | 100,00   |
+------------------------------------------------------------------+
|                                    Razem:           100,00 PLN   |
+------------------------------------------------------------------+
```

### Faktura korygująca

- Wyraźne odniesienie do faktury źródłowej
- Tytuł: "Faktura korygująca [NUMER]"
- Sekcja z powodem korekty (wyróżniona tłem)
- Wartości ujemne w kolorze czerwonym
- Tabela "PRZED" i "PO" dla czytelności

```
+------------------------------------------------------------------+
|                Faktura korygująca FK/2025/01/0001                |
+------------------------------------------------------------------+
|  ┃ Koryguje fakturę: FV/2025/01/0001 z dnia 15.12.2025          |
|  ┃ Powód korekty: Błędna ilość towaru                           |
+------------------------------------------------------------------+
|  BYŁO:                                                           |
|  LP | Nazwa          | Ilość | Cena   | VAT  | Wartość           |
|  1  | Produkt X      | 10    | 100,00 | 23%  | 1 230,00          |
+------------------------------------------------------------------+
|  JEST:                                                           |
|  LP | Nazwa          | Ilość | Cena   | VAT  | Wartość           |
|  1  | Produkt X      | 8     | 100,00 | 23%  | 984,00            |
+------------------------------------------------------------------+
|  RÓŻNICA:                                                        |
|                                    Razem netto:       -200,00    |
|                                    VAT 23%:            -46,00    |
|                                    Razem brutto:      -246,00    |
+------------------------------------------------------------------+
```

**CSS dla wartości ujemnych:**
```css
.negative-value {
    color: #F44336;
}
.correction-info {
    background-color: #FFF3E0;
    padding: 10px;
    border-left: 3px solid #FF9800;
    margin-bottom: 15px;
}
```

---

## Kompatybilność z DOMPDF

### Co działa

- `table`, `tr`, `td`, `th` - podstawa layoutu
- `float: left/right` - dla prostych układów
- `position: fixed` - dla stopki
- `border`, `border-collapse`
- `padding`, `margin`
- `font-size`, `font-weight`, `line-height`
- `color`, `background-color`
- `text-align`, `vertical-align`
- `width`, `height` (w px, %, mm)
- `page-break-before/after`

### Czego unikać

- **Flexbox** (`display: flex`) - nie działa
- **CSS Grid** (`display: grid`) - nie działa
- **Box-shadow** - nie działa
- **Złożone border-radius** - tylko pojedyncza wartość
- **CSS transforms** - nie działają
- **Pseudo-elementy** `::before`, `::after` - ograniczone wsparcie

### Rekomendacje

1. **Używaj tabel do layoutu** - to najbardziej niezawodna metoda
2. **Testuj każdą zmianę** - DOMPDF może różnie renderować
3. **Unikaj zagnieżdżonych float** - może powodować problemy
4. **Używaj jednostek bezwzględnych** - `px`, `pt`, `mm`
5. **Ogranicz CSS do minimum** - mniej = stabilniej

---

## Przykładowa struktura HTML

```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        /* Style z design systemu */
    </style>
</head>
<body>
    <!-- Nagłówek -->
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                <img src="logo.png" class="logo">
            </td>
            <td class="company-info">
                Nazwa Firmy<br>
                ul. Adres 123<br>
                00-000 Miasto<br>
                NIP: 1234567890
            </td>
        </tr>
    </table>

    <!-- Tytuł -->
    <div class="document-title">
        Faktura VAT FV/2025/01/0001
    </div>

    <!-- Szczegóły i nabywca -->
    <table class="details-table">
        <tr>
            <td class="details-left">
                <div class="section-title">Szczegóły faktury:</div>
                <!-- szczegóły -->
            </td>
            <td class="details-right">
                <div class="section-title">Nabywca:</div>
                <!-- dane nabywcy -->
            </td>
        </tr>
    </table>

    <!-- Tabela pozycji -->
    <table class="items-table">
        <tr>
            <th>LP</th>
            <th>Nazwa</th>
            <th class="numeric">Ilość</th>
            <th class="numeric">Cena j.</th>
            <th class="numeric">VAT</th>
            <th class="numeric">Wartość</th>
        </tr>
        <!-- pozycje -->
    </table>

    <!-- Podsumowanie -->
    <div class="summary-wrapper">
        <table class="summary-table">
            <!-- sumy -->
        </table>
    </div>

    <!-- Status -->
    <div class="status-badge">
        <div class="status-text">ZAPŁACONO</div>
        <div class="status-underline"></div>
    </div>

    <!-- Stopka -->
    <div class="footer">
        <div class="page-number">Strona 1 z 1</div>
    </div>
</body>
</html>
```

---

## Checklist implementacji

- [ ] Pobrać i zainstalować font Open Sans w `assets/fonts/`
- [ ] Zarejestrować font w PdfGenerator
- [ ] Zaimplementować bazowy szablon HTML
- [ ] Dodać style CSS zgodne z design systemem
- [ ] Przetestować renderowanie w DOMPDF
- [ ] Utworzyć wariant dla paragonu
- [ ] Utworzyć wariant dla faktury korygującej
- [ ] Przetestować z polskimi znakami (ąćęłńóśźż)
- [ ] Przetestować z logo w różnych formatach
- [ ] Sprawdzić łamanie stron przy wielu pozycjach
