# Git Commit Helper

Przeanalizuj zmiany w repozytorium i utwórz commit zgodnie z konwencją projektu.

## Instrukcje

### Krok 1: Analiza zmian
1. Sprawdź status repozytorium (`git status`) i zmiany (`git diff --staged` lub `git diff`)
2. Przeanalizuj wszystkie zmienione pliki
3. Określ typ zmiany:
   - `feat`: nowa funkcja
   - `fix`: poprawka błędu
   - `docs`: dokumentacja
   - `refactor`: refaktoryzacja (bez zmiany funkcjonalności)
   - `test`: testy
   - `chore`: zadania pomocnicze (config, dependencies)
   - `style`: formatowanie, styl kodu
4. Określ scope (opcjonalnie): moduł/komponent którego dotyczy zmiana (np. `invoice`, `admin`, `pdf`)

### Krok 2: Aktualizacja CLAUDE.md (PRZED commitem)
Sprawdź czy zmiany wymagają aktualizacji CLAUDE.md:

**Aktualizuj gdy:**
- Nowe pliki/klasy - dodaj do "Struktura katalogów"
- Nowa funkcjonalność - zaktualizuj "Status implementacji" i "Zaimplementowane komponenty"
- Ukończona faza - zmień status w tabeli faz
- Nowe testy - zaktualizuj liczbę testów
- Nowe hooki/filtry - dodaj do sekcji "Hooki i filtry"
- Zmiany w bazie danych - zaktualizuj schemat

**NIE aktualizuj gdy:**
- Drobne poprawki błędów (fix)
- Refaktoryzacja bez zmiany API
- Zmiany stylu kodu
- Aktualizacje zależności

**Sekcje CLAUDE.md do sprawdzenia:**
```
- Struktura katalogów (nowe pliki/moduły)
- Status implementacji (tabela faz)
- Zaimplementowane komponenty (nowe klasy/serwisy)
- Do implementacji (usunięcie zrealizowanych zadań)
```

Jeśli CLAUDE.md wymaga zmian - **zaktualizuj go teraz**, przed commitem.

### Krok 3: Staging i commit
1. Dodaj wszystkie pliki do stagingu (włącznie z CLAUDE.md jeśli był zmieniony)
2. Napisz zwięzły opis w języku angielskim (max 72 znaki w pierwszej linii)
3. Utwórz commit z odpowiednim komunikatem

## Format commita

```
<type>(<scope>): <description>

[opcjonalnie: dłuższy opis zmian]

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude <noreply@anthropic.com>
```

## Przykłady

- `feat(invoice): add PDF generation for VAT invoices`
- `fix(numbering): prevent race condition in document number generation`
- `refactor(models): extract date parsing to helper method`
- `chore: update phpcs.xml for PSR-4 compatibility`

## Ważne

- NIE commituj plików zawierających sekrety (.env, credentials)
- NIE używaj `--force` ani `--amend` bez wyraźnej prośby
- Sprawdź czy testy przechodzą przed commitem (jeśli dostępne)
- Użyj HEREDOC do przekazania wiadomości commit

Wykonaj commit dla aktualnych zmian w repozytorium.
