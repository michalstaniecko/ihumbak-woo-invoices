# Release Process

This document describes the release workflow for iHumbak WooCommerce Invoices plugin.

## Overview

Releases are **fully automated via GitHub Actions**. When you push a version tag, GitHub Actions will:
1. Validate the version format
2. Verify version numbers in plugin files
3. Build the production ZIP
4. Create a GitHub Release with the ZIP attached

## Version Numbering

This plugin follows [Semantic Versioning](https://semver.org/):

- **MAJOR.MINOR.PATCH** (e.g., `1.2.3`)
- **Pre-release**: `1.0.0-beta`, `1.0.0-rc1`

| Version Part | When to Increment |
|--------------|-------------------|
| MAJOR | Breaking changes, incompatible API changes |
| MINOR | New features, backwards compatible |
| PATCH | Bug fixes, backwards compatible |

## Quick Release

```bash
# 1. Update version in all files
#    - ihumbak-invoices.php (header + constant)
#    - composer.json
#    - CLAUDE.md

# 2. Commit
git add -A
git commit -m "chore(release): bump version to X.X.X"

# 3. Create and push tag
git tag vX.X.X
git push origin develop
git push origin vX.X.X

# 4. GitHub Actions handles the rest automatically
```

## Versioning Files

Files that contain version information:

| File | Location | Format |
|------|----------|--------|
| `ihumbak-invoices.php` | Line 6 | `* Version: X.X.X` |
| `ihumbak-invoices.php` | Line 31 | `define( 'IHUMBAK_INVOICES_VERSION', 'X.X.X' )` |
| `composer.json` | Root | `"version": "X.X.X"` |
| `CLAUDE.md` | Quick Info table | `Version \| X.X.X` |

## Pre-Release Checklist

Before releasing, ensure:

- [ ] All tests pass: `composer test`
- [ ] Code style is clean: `composer phpcs`
- [ ] Static analysis passes: `composer phpstan`
- [ ] All features have been manually tested
- [ ] Documentation is up to date
- [ ] Working tree is clean: `git status`

## GitHub Actions Workflow

The workflow (`.github/workflows/release.yml`) performs:

1. **Version Validation** - Ensures semver format
2. **Version Verification** - Checks version matches in all files
3. **Dependency Installation** - Production dependencies only
4. **ZIP Creation** - Excludes dev files (tests, docs, scripts, etc.)
5. **Release Creation** - Creates GitHub Release with ZIP attached

### Excluded from ZIP

- `.git`, `.gitignore`, `.gitattributes`
- `tests/`, `scripts/`, `docs/`
- `phpunit.xml`, `phpcs.xml`, `phpstan.neon`
- `composer.lock`, `CLAUDE.md`, `PLAN.md`
- `.github/`, `.claude/`
- IDE configs (`.vscode/`, `.idea/`)

### Output Structure

```
ihumbak-invoices-{version}.zip
└── ihumbak-invoices/
    ├── ihumbak-invoices.php   # Main plugin file
    ├── src/                    # Source code
    ├── templates/              # Admin and PDF templates
    ├── assets/                 # CSS and JavaScript
    ├── languages/              # Translation files
    ├── vendor/                 # Production dependencies only
    └── readme.txt              # WordPress readme
```

## Valid Version Formats

- `1.0.0` - stable release
- `1.0.0-beta` - beta release
- `1.0.0-beta1` - numbered beta
- `1.0.0-rc1` - release candidate
- `1.0.0-alpha` - alpha release
