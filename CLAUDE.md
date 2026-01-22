# iHumbak WooCommerce Invoices

WordPress/WooCommerce plugin for generating VAT invoices, receipts, and credit notes.

## Table of Contents

- [Quick Info](#quick-info)
- [Language Policy](#language-policy)
- [Git Workflow](#git-workflow)
- [Architecture](#architecture)
- [Coding Standards](#coding-standards)
- [Commands](#commands)
- [Slash Commands (Agents)](#slash-commands-agents)
- [Documentation](#documentation)
- [Implementation Status](#implementation-status)

---

## Quick Info

| Item | Value |
|------|-------|
| Name | iHumbak WooCommerce Invoices |
| Version | 0.5.2 |
| Namespace | IHumbak\Invoices |
| Text Domain | ihumbak-invoices |
| License | GPL-2.0-or-later |
| PHP | 8.0+ |
| WordPress | 6.0+ |
| WooCommerce | 7.0+ |
| PDF Library | DOMPDF |

---

## Language Policy

**All project artifacts MUST be written in English:**
- Git commit messages
- Code comments
- Documentation (README, CLAUDE.md, docs/, etc.)
- Variable and function names
- UI strings (with i18n support for translations)

---

## Git Workflow

### Branches

| Type | Prefix | Example |
|------|--------|---------|
| Main develop | - | `develop` |
| New feature | `feature/` | `feature/email-notifications` |
| Bug fix | `fix/` | `fix/pdf-encoding` |
| Hotfix | `hotfix/` | `hotfix/critical-bug` |
| Release | `release/` | `release/1.0.0` |

### Starting New Work

```bash
# Make sure develop is up to date
git checkout develop
git pull origin develop

# Create and switch to feature branch
git checkout -b feature/feature-name
```

### Code Review (Before Merge)

**Required before merging to develop:**

1. Run `/code-review` command to review all changes on the working branch
2. Review the report for:
   - Code quality issues
   - Security vulnerabilities
   - Performance concerns
   - Coding standards violations
3. Fix all errors and implement suggestions from the review
4. Run `/code-review` again to verify all issues are resolved
5. Only proceed to merge when review passes without critical issues

```bash
# View changes that will be reviewed
git diff develop...HEAD

# Or view commit history
git log develop..HEAD --oneline
```

### Finishing Work

```bash
# Make sure everything is committed
git status

# Switch to develop and merge
git checkout develop
git merge feature/feature-name

# Delete feature branch
git branch -d feature/feature-name
```

---

## Architecture

### Design Patterns

- **PSR-4 Autoloading** - directory structure matches namespace
- **Dependency Injection** - service containers
- **Repository Pattern** - data access layer
- **Factory Pattern** - object creation
- **Service Provider Pattern** - service registration

### Source Structure

```
src/
├── Contracts/       # Interfaces
├── Core/            # Plugin core, activation, settings
├── Exceptions/      # Custom exceptions
├── Infrastructure/  # Services, repositories
├── Models/          # Data models (Invoice, Receipt, CreditNote)
└── Modules/         # Feature modules
```

---

## Coding Standards

### PHP Standard

- **WordPress Coding Standards (WPCS)** + PSR-4
- **PHP Version:** 8.0+ with strict types
- **Type Hints:** Full type hints for parameters and return values

### Naming Conventions

| Element | Convention | Example |
|---------|------------|---------|
| Classes | PascalCase | `InvoiceGenerator` |
| Class methods | camelCase | `generateFromOrder()` |
| WP functions | snake_case | `ihumbak_get_invoice()` |
| Constants | UPPER_SNAKE | `IHUMBAK_VERSION` |
| Hooks | snake_case | `ihumbak_invoice_created` |

### Security

| Category | Functions |
|----------|-----------|
| Sanitize input | `sanitize_text_field()`, `absint()`, `sanitize_email()` |
| Escape output | `esc_html()`, `esc_attr()`, `esc_url()` |
| Permissions | `current_user_can('manage_woocommerce')` |
| SQL | `$wpdb->prepare()` |

### Pre-Commit Checklist

- [ ] Code passes PHPCS (`composer phpcs`)
- [ ] Code passes PHPStan (`composer phpstan`)
- [ ] Tests pass (`composer test`)
- [ ] Input data is sanitized
- [ ] Output data is escaped
- [ ] Nonce is verified (forms)
- [ ] Permissions are checked
- [ ] Strings use text domain `ihumbak-invoices`

### Commit Convention

```
<type>(<scope>): <description>

Types: feat, fix, docs, refactor, test, chore
```

---

## Commands

### Composer Scripts

| Command | Description |
|---------|-------------|
| `composer install` | Install dependencies |
| `composer test` | Run PHPUnit tests |
| `composer phpcs` | Check code style |
| `composer phpcbf` | Fix code style |
| `composer phpstan` | Static analysis |
| `composer check` | Run all checks |

---

## Slash Commands (Agents)

| Command | Description |
|---------|-------------|
| `/coordinator` | Project Coordinator |
| `/php-dev` | PHP Developer |
| `/code-review` | Code Review |
| `/devops` | CI/CD and automation |
| `/docs` | Documentation |
| `/qa` | Tests and QA |
| `/new-issue <message>` | Create GitHub issue from brief description |
| `/new-branch issue-<ID>` | Create git branch from GitHub issue |
| `/new-worktree issue-<ID>` | Create git worktree with branch from GitHub issue |
| `/commit-tag-release <version>` | Bump version, commit, tag, and push release |

---

## Documentation

| File | Description |
|------|-------------|
| `PLAN.md` | Implementation plan |
| `docs/DATABASE.md` | Database schema |
| `docs/HOOKS-API.md` | Hooks and filters reference |
| `docs/CONFIGURATION.md` | Plugin configuration |
| `docs/DOCUMENT-TYPES.md` | Document types (invoice, receipt, credit note) |
| `docs/pdf-design-guide.md` | PDF design system |
| `docs/RELEASE.md` | Release process |
| `docs/super-admin-configuration.md` | Super-admin setup |

---

## Implementation Status

| Phase | Status |
|-------|--------|
| 1. Foundation | COMPLETE |
| 2. Admin Panel | COMPLETE |
| 3. PDF Export | COMPLETE |
| 4. Extensions | COMPLETE |
| 5. Finalization & Release | COMPLETE |
| 6. Receipt Returns | COMPLETE |
| 7. Items Table UI | PLANNED |

See `PLAN.md` for detailed implementation status.
