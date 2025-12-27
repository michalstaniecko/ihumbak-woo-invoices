# iHumbak WooCommerce Invoices

WordPress/WooCommerce plugin for generating VAT invoices, receipts, and credit notes.

## Table of Contents

- [Quick Info](#quick-info)
- [Architecture](#architecture)
- [Coding Standards](#coding-standards)
- [Git Workflow](#git-workflow)
- [Commands](#commands)
- [Documentation](#documentation)

## Quick Info

| Item | Value |
|------|-------|
| Name | iHumbak WooCommerce Invoices |
| Version | 0.1.0 (development) |
| Namespace | IHumbak\Invoices |
| Text Domain | ihumbak-invoices |
| License | GPL-2.0-or-later |
| PHP | 8.0+ |
| WordPress | 6.0+ |
| WooCommerce | 7.0+ |
| PDF Library | DOMPDF |

## Architecture

- **PSR-4 Autoloading** - directory structure matches namespace
- **Dependency Injection** - service containers
- **Repository Pattern** - data access layer
- **Factory Pattern** - object creation
- **Service Provider Pattern** - service registration

See [docs/PLAN.md](docs/PLAN.md) for full directory structure and implementation status.

## Coding Standards

- **PHP Standard:** WordPress Coding Standards (WPCS) + PSR-4
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

## Pre-Commit Checklist

- [ ] Code passes PHPCS (`composer phpcs`)
- [ ] Code passes PHPStan (`composer phpstan`)
- [ ] Tests pass (`composer test`)
- [ ] Input data is sanitized
- [ ] Output data is escaped
- [ ] Nonce is verified (forms)
- [ ] Permissions are checked
- [ ] Strings use text domain `ihumbak-invoices`

## Git Workflow (GitFlow)

### Branches

| Branch | Purpose |
|--------|---------|
| main | Stable production |
| develop | Integration |
| feature/* | New features |
| release/* | Release preparation |
| hotfix/* | Urgent fixes |

### Commit Convention

```
<type>(<scope>): <description>

Types: feat, fix, docs, refactor, test, chore
```

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

### Slash Commands (Agents)

| Command | Description |
|---------|-------------|
| `/coordinator` | Project Coordinator |
| `/php-dev` | PHP Developer |
| `/code-review` | Code Review |
| `/devops` | CI/CD and automation |
| `/docs` | Documentation |
| `/qa` | Tests and QA |

## Documentation

| File | Description |
|------|-------------|
| [docs/PLAN.md](docs/PLAN.md) | Implementation plan and status |
| [docs/DATABASE.md](docs/DATABASE.md) | Database schema |
| [docs/HOOKS-API.md](docs/HOOKS-API.md) | Hooks and filters reference |
| [docs/CONFIGURATION.md](docs/CONFIGURATION.md) | Plugin configuration |
| [docs/DOCUMENT-TYPES.md](docs/DOCUMENT-TYPES.md) | Document types (invoice, receipt, credit note) |
| [docs/pdf-design-guide.md](docs/pdf-design-guide.md) | PDF design system |
| [docs/super-admin-configuration.md](docs/super-admin-configuration.md) | Super-admin setup |

## Implementation Status

| Phase | Status |
|-------|--------|
| 1. Foundation | COMPLETE |
| 2. Admin Panel | COMPLETE |
| 3. PDF Export | COMPLETE |
| 4. Extensions | IN PROGRESS |

See [docs/PLAN.md](docs/PLAN.md) for detailed implementation status.
