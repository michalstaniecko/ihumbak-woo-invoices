#!/bin/bash
#
# Release Build Script for iHumbak WooCommerce Invoices
#
# Usage: ./scripts/build-release.sh <version>
# Example: ./scripts/build-release.sh 1.0.0
#
# This script:
# 1. Validates version format (semver)
# 2. Updates version numbers in plugin files
# 3. Installs production dependencies
# 4. Creates a distributable ZIP file
#

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Plugin information
PLUGIN_SLUG="ihumbak-invoices"
PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BUILD_DIR="${PLUGIN_DIR}/build"
DIST_DIR="${PLUGIN_DIR}/dist"

# Files to exclude from the release
EXCLUDE_FILES=(
    ".git"
    ".gitignore"
    ".gitattributes"
    ".editorconfig"
    ".phpcs.xml"
    ".phpunit.result.cache"
    ".claude"
    "phpcs.xml"
    "phpstan.neon"
    "phpunit.xml"
    "composer.lock"
    "CLAUDE.md"
    "tests"
    "scripts"
    "stubs"
    "docs"
    "build"
    "dist"
    "node_modules"
    ".github"
    ".vscode"
    ".idea"
    "*.log"
    "*.cache"
)

# Print colored message
print_message() {
    local color=$1
    local message=$2
    echo -e "${color}${message}${NC}"
}

# Print step header
print_step() {
    echo ""
    print_message "$BLUE" "==> $1"
}

# Print success message
print_success() {
    print_message "$GREEN" "    ✓ $1"
}

# Print warning message
print_warning() {
    print_message "$YELLOW" "    ! $1"
}

# Print error message and exit
print_error() {
    print_message "$RED" "    ✗ Error: $1"
    exit 1
}

# Validate semantic version format (Composer compatible)
# Accepts: 1.0.0, 1.0.0-beta, 1.0.0-beta1, 1.0.0-alpha, 1.0.0-rc1, 1.0.0-dev
validate_version() {
    local version=$1
    # Composer-compatible version regex
    if [[ ! $version =~ ^[0-9]+\.[0-9]+\.[0-9]+(-(stable|beta|b|rc|alpha|a|patch|pl|p|dev)[0-9]*)?$ ]]; then
        print_error "Invalid version format. Use Composer-compatible versioning:
    Valid: 1.0.0, 1.0.0-beta, 1.0.0-beta1, 1.0.0-rc1, 1.0.0-alpha, 1.0.0-dev
    Invalid: 1.0.0-test, 1.0.0-foo"
    fi
}

# Update version in main plugin file
update_plugin_version() {
    local version=$1
    local plugin_file="${PLUGIN_DIR}/ihumbak-invoices.php"

    if [[ ! -f "$plugin_file" ]]; then
        print_error "Plugin file not found: $plugin_file"
    fi

    # Update Version header (preserves " * Version: " format)
    sed -i.bak "s/^\( \* Version: \)[0-9][0-9a-zA-Z.-]*/\1${version}/" "$plugin_file"
    rm -f "${plugin_file}.bak"

    # Update IHUMBAK_INVOICES_VERSION constant
    sed -i.bak "s/\(define( 'IHUMBAK_INVOICES_VERSION', '\)[^']*'/\1${version}'/" "$plugin_file"
    rm -f "${plugin_file}.bak"

    print_success "Updated version in ihumbak-invoices.php"
}

# Update version in composer.json
update_composer_version() {
    local version=$1
    local composer_file="${PLUGIN_DIR}/composer.json"

    if [[ ! -f "$composer_file" ]]; then
        print_error "composer.json not found: $composer_file"
    fi

    # Check if jq is available for proper JSON manipulation
    if command -v jq &> /dev/null; then
        jq --arg v "$version" '.version = $v' "$composer_file" > "${composer_file}.tmp" && mv "${composer_file}.tmp" "$composer_file"
    else
        # Fallback: use sed (less robust but works for simple cases)
        if grep -q '"version"' "$composer_file"; then
            sed -i.bak "s/\"version\":\s*\"[^\"]*\"/\"version\": \"${version}\"/" "$composer_file"
        else
            # Add version field after description
            sed -i.bak "s/\"description\":\s*\"[^\"]*\"/&,\n    \"version\": \"${version}\"/" "$composer_file"
        fi
        rm -f "${composer_file}.bak"
    fi

    print_success "Updated version in composer.json"
}

# Install production dependencies
install_dependencies() {
    print_step "Installing production dependencies..."

    cd "$PLUGIN_DIR"

    if [[ ! -f "composer.json" ]]; then
        print_error "composer.json not found"
    fi

    # Remove existing vendor directory
    rm -rf vendor

    # Install production dependencies only
    composer install --no-dev --optimize-autoloader --no-interaction --quiet

    if [[ $? -eq 0 ]]; then
        print_success "Production dependencies installed"
    else
        print_error "Failed to install dependencies"
    fi
}

# Create build directory with plugin files
create_build() {
    print_step "Creating build directory..."

    # Clean up previous build
    rm -rf "$BUILD_DIR"
    mkdir -p "$BUILD_DIR/${PLUGIN_SLUG}"

    # Build exclude pattern for rsync
    local exclude_args=""
    for item in "${EXCLUDE_FILES[@]}"; do
        exclude_args="$exclude_args --exclude=$item"
    done

    # Copy files to build directory
    rsync -a $exclude_args "${PLUGIN_DIR}/" "${BUILD_DIR}/${PLUGIN_SLUG}/"

    print_success "Build directory created"
}

# Create ZIP distribution file
create_zip() {
    local version=$1
    local zip_name="${PLUGIN_SLUG}-${version}.zip"

    print_step "Creating ZIP distribution..."

    # Create dist directory
    mkdir -p "$DIST_DIR"

    # Remove existing ZIP if present
    rm -f "${DIST_DIR}/${zip_name}"

    # Create ZIP
    cd "$BUILD_DIR"
    zip -r -q "${DIST_DIR}/${zip_name}" "${PLUGIN_SLUG}"

    if [[ $? -eq 0 ]]; then
        print_success "Created: dist/${zip_name}"

        # Show file size
        local size=$(du -h "${DIST_DIR}/${zip_name}" | cut -f1)
        print_success "File size: ${size}"
    else
        print_error "Failed to create ZIP file"
    fi
}

# Cleanup build artifacts
cleanup() {
    print_step "Cleaning up..."
    rm -rf "$BUILD_DIR"
    print_success "Build directory removed"
}

# Restore dev dependencies
restore_dev_dependencies() {
    print_step "Restoring development dependencies..."

    cd "$PLUGIN_DIR"
    composer install --quiet --no-interaction

    print_success "Development dependencies restored"
}

# Main function
main() {
    local version=$1

    echo ""
    print_message "$BLUE" "╔══════════════════════════════════════════════╗"
    print_message "$BLUE" "║  iHumbak WooCommerce Invoices Release Build  ║"
    print_message "$BLUE" "╚══════════════════════════════════════════════╝"

    # Check if version argument is provided
    if [[ -z "$version" ]]; then
        echo ""
        print_message "$YELLOW" "Usage: $0 <version>"
        print_message "$YELLOW" "Example: $0 1.0.0"
        echo ""
        exit 1
    fi

    # Validate version format
    print_step "Validating version format..."
    validate_version "$version"
    print_success "Version format valid: $version"

    # Update version in files
    print_step "Updating version numbers..."
    update_plugin_version "$version"
    update_composer_version "$version"

    # Install production dependencies
    install_dependencies

    # Create build
    create_build

    # Create ZIP
    create_zip "$version"

    # Cleanup
    cleanup

    # Restore dev dependencies
    restore_dev_dependencies

    # Final message
    echo ""
    print_message "$GREEN" "╔══════════════════════════════════════════════╗"
    print_message "$GREEN" "║          Release build completed!            ║"
    print_message "$GREEN" "╚══════════════════════════════════════════════╝"
    echo ""
    print_message "$GREEN" "Distribution file: dist/${PLUGIN_SLUG}-${version}.zip"
    echo ""
    print_message "$YELLOW" "Next steps:"
    print_message "$NC" "  1. Test the ZIP by installing in WordPress"
    print_message "$NC" "  2. Create a git tag: git tag v${version}"
    print_message "$NC" "  3. Push the tag: git push origin v${version}"
    print_message "$NC" "  4. Create GitHub release with the ZIP file"
    echo ""
}

# Run main function
main "$@"
