# New Branch from GitHub Issue

Create a new git branch based on a GitHub issue.

**Input:** $ARGUMENTS

## Instructions

### Step 1: Parse the argument

Extract the issue ID from the argument format `issue-<ID>`.

**Example:** `issue-17` → Issue ID: `17`

If the argument doesn't match the expected format, inform the user and show usage:
```
Usage: /new-branch issue-<ID>
Example: /new-branch issue-17
```

### Step 2: Fetch issue details

Use GitHub CLI to get issue information:

```bash
gh issue view <issue-id> --json title,labels
```

### Step 3: Parse issue title and determine branch prefix

**Issue title format:** `<type>(<scope>): <description>` or `<type>: <description>`

**Type to branch prefix mapping:**

| Type | Branch Prefix |
|------|---------------|
| `feat` | `feature/` |
| `fix` | `fix/` |
| `refactor` | `refactor/` |
| `chore` | `chore/` |
| `docs` | `docs/` |
| `test` | `test/` |
| `style` | `style/` |
| Default | `feature/` |

### Step 4: Generate branch name

1. Extract the description part from the title (after the colon)
2. Slugify the description:
   - Convert to lowercase
   - Replace spaces with hyphens
   - Remove special characters (keep only letters, numbers, hyphens)
   - Trim leading/trailing hyphens
3. Combine prefix and slugified description

**Examples:**

| Issue Title | Branch Name |
|-------------|-------------|
| `feat(pdf): add watermark support` | `feature/add-watermark-support` |
| `fix(admin): resolve tax calculation bug` | `fix/resolve-tax-calculation-bug` |
| `refactor: improve performance` | `refactor/improve-performance` |
| `chore: update dependencies` | `chore/update-dependencies` |

### Step 5: Create the branch

Execute the following commands:

```bash
# Ensure develop is up to date
git checkout develop
git pull origin develop

# Create and checkout new branch
git checkout -b <branch-name>
```

### Step 6: Confirm success

Report to the user:
- Issue number and title
- Created branch name
- Current status

## Example Output

```
Created branch from issue #17:

Issue: feat(pdf): add watermark support
Branch: feature/add-watermark-support

You are now on branch 'feature/add-watermark-support'.
Ready to start working on the issue.
```

## Error Handling

- If issue doesn't exist: Report error and suggest checking the issue number
- If branch already exists: Report the existing branch and ask if user wants to switch to it
- If git operations fail: Report the error and suggest manual resolution

## Important

- Always work from an up-to-date `develop` branch
- Follow the project's branch naming conventions from CLAUDE.md
- If the issue title doesn't follow the expected format, try to infer the type from labels or default to `feature/`
