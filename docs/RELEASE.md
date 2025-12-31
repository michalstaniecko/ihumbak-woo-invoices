# Release Process

This document describes the release workflow for iHumbak WooCommerce Invoices plugin.

## Prerequisites

- Git with clean working tree (no uncommitted changes)
- Composer installed globally
- `zip` command available
- (Optional) `jq` for proper JSON manipulation

## Version Numbering

This plugin follows [Semantic Versioning](https://semver.org/):

- **MAJOR.MINOR.PATCH** (e.g., `1.2.3`)
- **Pre-release**: `1.0.0-beta.1`, `1.0.0-rc.1`

| Version Part | When to Increment |
|--------------|-------------------|
| MAJOR | Breaking changes, incompatible API changes |
| MINOR | New features, backwards compatible |
| PATCH | Bug fixes, backwards compatible |

## Quick Release

```bash
cd ihumbak-woo-invoices

# Run the release script
./scripts/build-release.sh 1.0.0

# The ZIP file will be at: dist/ihumbak-invoices-1.0.0.zip
```

## Release Script Details

The `scripts/build-release.sh` script automates the entire release process:

### What It Does

1. **Validates version format** - Ensures semantic versioning compliance
2. **Updates version numbers** in:
   - `ihumbak-invoices.php` (plugin header + constant)
   - `composer.json` (version field)
3. **Installs production dependencies** - `composer install --no-dev --optimize-autoloader`
4. **Creates distributable ZIP** - Excludes development files
5. **Restores dev dependencies** - Ready for continued development

### Excluded Files

The following files/directories are excluded from the release ZIP:

- `.git`, `.gitignore`, `.gitattributes`
- `tests/` - Unit and integration tests
- `scripts/` - Development scripts
- `docs/` - Documentation (except embedded in code)
- `phpunit.xml`, `phpcs.xml`, `phpstan.neon`
- `composer.lock`
- `CLAUDE.md`
- `node_modules/`, `build/`, `dist/`
- IDE configs (`.vscode/`, `.idea/`)

### Output Structure

```
dist/
└── ihumbak-invoices-{version}.zip
    └── ihumbak-invoices/
        ├── ihumbak-invoices.php   # Main plugin file
        ├── src/                    # Source code
        ├── templates/              # Admin and PDF templates
        ├── assets/                 # CSS and JavaScript
        ├── languages/              # Translation files
        ├── vendor/                 # Production dependencies only
        └── readme.txt              # WordPress readme (if exists)
```

## Manual Release Process

If you need to release manually without the script:

### 1. Update Version Numbers

Edit `ihumbak-invoices.php`:
```php
* Version: X.X.X
...
define( 'IHUMBAK_INVOICES_VERSION', 'X.X.X' );
```

Edit `composer.json`:
```json
"version": "X.X.X",
```

### 2. Install Production Dependencies

```bash
rm -rf vendor
composer install --no-dev --optimize-autoloader
```

### 3. Create ZIP

```bash
# Create a clean directory
mkdir -p build/ihumbak-invoices

# Copy files (excluding dev files)
rsync -a \
  --exclude='.git' \
  --exclude='tests' \
  --exclude='scripts' \
  --exclude='docs' \
  --exclude='phpunit.xml' \
  --exclude='phpcs.xml' \
  --exclude='phpstan.neon' \
  --exclude='composer.lock' \
  --exclude='CLAUDE.md' \
  --exclude='build' \
  --exclude='dist' \
  ./ build/ihumbak-invoices/

# Create ZIP
cd build
zip -r ../dist/ihumbak-invoices-X.X.X.zip ihumbak-invoices
cd ..

# Cleanup
rm -rf build
```

### 4. Restore Dev Dependencies

```bash
composer install
```

## Post-Release Steps

After creating the ZIP file:

### 1. Test the Release

```bash
# Install in a test WordPress environment
# Verify:
# - Plugin activates without errors
# - All features work correctly
# - No PHP errors in debug.log
```

### 2. Create Git Tag

```bash
git add -A
git commit -m "chore(release): bump version to X.X.X"
git tag vX.X.X
git push origin develop
git push origin vX.X.X
```

### 3. Create GitHub Release

1. Go to repository releases page
2. Click "Create new release"
3. Select the tag `vX.X.X`
4. Add release title: `vX.X.X`
5. Add release notes (changelog)
6. Attach the ZIP file: `dist/ihumbak-invoices-X.X.X.zip`
7. Publish release

## Pre-Release Checklist

Before releasing, ensure:

- [ ] All tests pass: `composer test`
- [ ] Code style is clean: `composer phpcs`
- [ ] Static analysis passes: `composer phpstan`
- [ ] All features have been manually tested
- [ ] Documentation is up to date
- [ ] CHANGELOG has been updated (if applicable)
- [ ] Working tree is clean: `git status`

## Versioning Files

Files that contain version information:

| File | Location | Format |
|------|----------|--------|
| `ihumbak-invoices.php` | Line 6 | `* Version: X.X.X` |
| `ihumbak-invoices.php` | Line 31 | `define( 'IHUMBAK_INVOICES_VERSION', 'X.X.X' )` |
| `composer.json` | Root | `"version": "X.X.X"` |

## Troubleshooting

### Script fails with permission denied

```bash
chmod +x scripts/build-release.sh
```

### Composer not found

Install Composer globally: https://getcomposer.org/download/

### ZIP command not found

On macOS/Linux, zip is usually pre-installed. If not:
```bash
# Ubuntu/Debian
sudo apt-get install zip

# macOS (via Homebrew)
brew install zip
```

### jq not found (warning only)

The script works without jq using sed fallback. To install jq:
```bash
# Ubuntu/Debian
sudo apt-get install jq

# macOS
brew install jq
```
