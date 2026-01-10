# Release Manager

Automated release process for iHumbak WooCommerce Invoices plugin.

## Instructions

**IMPORTANT:** This command MUST be run from the `develop` branch.

### Step 1: Verify Prerequisites

1. Check current branch - must be `develop`:
   ```bash
   git branch --show-current
   ```
2. Check working directory is clean (no uncommitted changes):
   ```bash
   git status
   ```
3. Get the last tag version:
   ```bash
   git describe --tags --abbrev=0 2>/dev/null || echo "No tags found"
   ```

**If not on develop or directory has uncommitted changes - STOP and inform the user.**

### Step 2: Analyze Changes Since Last Release

1. Get all commits since the last tag:
   ```bash
   git log $(git describe --tags --abbrev=0 2>/dev/null || echo "HEAD~100")..HEAD --oneline
   ```
2. Get detailed changes:
   ```bash
   git log $(git describe --tags --abbrev=0 2>/dev/null || echo "HEAD~100")..HEAD --pretty=format:"%h %s"
   ```

3. Analyze commit types and determine version bump:
   - **MAJOR** (X.0.0): Breaking changes, `BREAKING CHANGE:` in commits
   - **MINOR** (0.X.0): New features (`feat:` commits)
   - **PATCH** (0.0.X): Bug fixes, docs, refactoring (`fix:`, `docs:`, `refactor:`, `chore:`)

### Step 3: Propose New Version

Based on the analysis:
1. Parse current version (from last tag or plugin file)
2. Calculate suggested new version based on commit types
3. Present to user:
   - Current version
   - List of changes (grouped by type)
   - Suggested new version
   - Ask user to confirm or provide different version

### Step 4: Update Version Numbers

Update version in these locations:
1. **ihumbak-invoices.php** (line ~6): Plugin header `* Version: X.X.X`
2. **ihumbak-invoices.php** (line ~31): Constant `define( 'IHUMBAK_INVOICES_VERSION', 'X.X.X' );`
3. **composer.json**: Add/update `"version": "X.X.X"`
4. **CLAUDE.md**: Update version in Quick Info table

### Step 5: Run Quality Checks

Before creating release, run:
```bash
composer check
```

This runs: PHPCS + PHPStan + PHPUnit tests.

**If checks fail - STOP and inform user. Fix issues before release.**

### Step 6: Create Release Commit

Create commit with all version changes:
```
chore(release): bump version to X.X.X

Changes in this release:
- [list of changes from commits]

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude <noreply@anthropic.com>
```

### Step 7: Create Git Tag

```bash
git tag -a vX.X.X -m "Release vX.X.X"
```

### Step 8: Build Release Package

Run the existing build script:
```bash
./scripts/build-release.sh X.X.X
```

This will create `dist/ihumbak-invoices-X.X.X.zip`

### Step 9: Summary and Next Steps

Display to user:
- Release version created
- ZIP file location and size
- Git tag created
- Next steps:
  1. Push changes: `git push origin develop`
  2. Push tag: `git push origin vX.X.X`
  3. Create GitHub release with the ZIP file

## Version Format

Valid formats (Composer-compatible):
- `1.0.0` - stable release
- `1.0.0-beta` - beta release
- `1.0.0-beta1` - numbered beta
- `1.0.0-rc1` - release candidate
- `1.0.0-alpha` - alpha release

## Files Modified

| File | Location | Update |
|------|----------|--------|
| ihumbak-invoices.php | Line ~6 | `* Version: X.X.X` |
| ihumbak-invoices.php | Line ~31 | `IHUMBAK_INVOICES_VERSION` constant |
| composer.json | Root | `"version": "X.X.X"` |
| CLAUDE.md | Quick Info table | `Version \| X.X.X` |

## Safety Checks

- Must be on `develop` branch
- Working directory must be clean
- All quality checks must pass
- User must confirm version before proceeding
- Never force push or amend public commits

## Example Output

```
╔══════════════════════════════════════════════╗
║     iHumbak WooCommerce Invoices Release     ║
╚══════════════════════════════════════════════╝

Current version: 0.2.0
Last tag: v0.2.0

Changes since v0.2.0:
  feat: 3 commits
  fix: 2 commits
  refactor: 1 commit

Suggested version: 0.3.0 (minor bump due to new features)

Proceed with version 0.3.0? [Y/n/custom]
```

Execute the release process for the current repository state.
