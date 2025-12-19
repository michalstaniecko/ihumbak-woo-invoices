# Git Commit Helper

Przeanalizuj zmiany w repozytorium i utwórz commit zgodnie z konwencją projektu.

## Instrukcje

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
5. Napisz zwięzły opis w języku angielskim (max 72 znaki w pierwszej linii)
6. Dodaj pliki do stagingu jeśli nie są dodane
7. Utwórz commit z odpowiednim komunikatem

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
