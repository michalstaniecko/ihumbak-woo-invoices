# Git Commit Helper

Analyze repository changes and create a commit following project conventions.

## Instructions

### Step 1: Analyze Changes
1. Check repository status (`git status`) and changes (`git diff --staged` or `git diff`)
2. Analyze all changed files
3. Determine change type:
   - `feat`: new feature
   - `fix`: bug fix
   - `docs`: documentation
   - `refactor`: refactoring (no functionality change)
   - `test`: tests
   - `chore`: auxiliary tasks (config, dependencies)
   - `style`: formatting, code style
4. Determine scope (optional): module/component the change affects (e.g., `invoice`, `admin`, `pdf`)

### Step 2: Update Documentation (BEFORE commit)
Check if changes require documentation updates:

**Update when:**
- New files/classes - add to directory structure
- New functionality - update implementation status
- Completed phase - change status in phase table
- New tests - update test count
- New hooks/filters - add to hooks reference
- Database changes - update schema

**DON'T update when:**
- Minor bug fixes
- Refactoring without API changes
- Code style changes
- Dependency updates

**Files to check:**

| File | When to Update |
|------|----------------|
| `../PLAN.md` | Phase completion, new tasks, implementation status |
| `./CLAUDE.md` | Architecture changes, new modules |
| `docs/*.md` | New hooks, configuration options |

If documentation needs changes - **update it now**, before committing.

### Step 3: Staging and Commit
1. Add all files to staging (including documentation if changed)
2. Write concise description in English (max 72 chars in first line)
3. Create commit with appropriate message

## Commit Format

```
<type>(<scope>): <description>

[optional: longer description of changes]

Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude <noreply@anthropic.com>
```

## Examples

- `feat(invoice): add PDF generation for VAT invoices`
- `fix(numbering): prevent race condition in document number generation`
- `refactor(models): extract date parsing to helper method`
- `chore: update phpcs.xml for PSR-4 compatibility`

## Important

- DO NOT commit files containing secrets (.env, credentials)
- DO NOT use `--force` or `--amend` without explicit request
- Check that tests pass before committing (if available)
- Use HEREDOC to pass commit message

Execute commit for current repository changes.
