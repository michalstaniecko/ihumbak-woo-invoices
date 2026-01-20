# Release Version Manager

Bump plugin version, create commit, tag, and push release.

## Arguments

- `$ARGUMENTS` - Version bump type or specific version:
  - `patch` - Bump patch version (0.5.0 → 0.5.1)
  - `minor` - Bump minor version (0.5.0 → 0.6.0)
  - `major` - Bump major version (0.5.0 → 1.0.0)
  - `X.Y.Z` - Set specific version (e.g., `1.0.0`)

## Usage Examples

```
/commit-tag-release patch
/commit-tag-release minor
/commit-tag-release 1.0.0
```

## Instructions

### Step 1: Determine new version

1. Read current version from `ihumbak-invoices.php` (line with `Version:` in header)
2. Calculate new version based on `$ARGUMENTS`:
   - If `patch`: increment last number (0.5.0 → 0.5.1)
   - If `minor`: increment middle number, reset patch (0.5.0 → 0.6.0)
   - If `major`: increment first number, reset others (0.5.0 → 1.0.0)
   - If specific version (X.Y.Z format): use that version
3. Validate version format (must be X.Y.Z where X, Y, Z are numbers)

### Step 2: Check prerequisites

1. Verify working directory is clean (`git status`)
2. Verify current branch (should be `develop` or `main` for releases)
3. Verify all tests pass (`composer check`)
4. If any check fails, abort and inform user

### Step 3: Update version in files

Update version in the following files:

1. **`ihumbak-invoices.php`** (2 places):
   - Plugin header: `Version: X.Y.Z`
   - Constant: `define( 'IHUMBAK_INVOICES_VERSION', 'X.Y.Z' );`

2. **`CLAUDE.md`**:
   - Quick Info table: `| Version | X.Y.Z |`

### Step 4: Create commit

```bash
git add ihumbak-invoices.php CLAUDE.md
git commit -m "chore: bump version to X.Y.Z"
```

### Step 5: Create annotated tag

```bash
git tag -a vX.Y.Z -m "Release vX.Y.Z"
```

### Step 6: Push changes and tag

```bash
git push origin <current-branch>
git push origin vX.Y.Z
```

### Step 7: Summary

Display summary:
- Previous version
- New version
- Commit hash
- Tag name
- Files updated
- Remote push status

## Important Notes

- NEVER run this on a dirty working directory
- ALWAYS verify tests pass before releasing
- Tag format is `vX.Y.Z` (with 'v' prefix)
- Commit message format: `chore: bump version to X.Y.Z`
- If pushing fails, the local commit and tag are still created

## Error Handling

- If version argument is missing or invalid, show usage examples and ask for input
- If working directory is dirty, list uncommitted changes and abort
- If tests fail, show test output and abort
- If push fails, inform user that local changes are ready and they need to push manually
