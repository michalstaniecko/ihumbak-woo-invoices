# New Worktree from GitHub Issue

Create a new git worktree with a branch based on a GitHub issue.

**Input:** $ARGUMENTS

## Instructions

### Step 1: Parse the argument

Extract the issue ID from the argument format `issue-<ID>`.

**Example:** `issue-17` → Issue ID: `17`

If the argument doesn't match the expected format, inform the user and show usage:
```
Usage: /new-worktree issue-<ID>
Example: /new-worktree issue-17
```

### Step 2: Fetch issue details

Use GitHub CLI to get issue information:

```bash
gh issue view <issue-id> --json title,labels
```

### Step 3: Parse issue title and determine prefixes

**Issue title format:** `<type>(<scope>): <description>` or `<type>: <description>`

**Type to prefix mapping:**

| Type | Branch Prefix | Worktree Prefix |
|------|---------------|-----------------|
| `feat` | `feature/` | `feature-` |
| `fix` | `fix/` | `fix-` |
| `refactor` | `refactor/` | `refactor-` |
| `chore` | `chore/` | `chore-` |
| `docs` | `docs/` | `docs-` |
| `test` | `test/` | `test-` |
| `style` | `style/` | `style-` |
| Default | `feature/` | `feature-` |

### Step 4: Generate names

1. Extract the description part from the title (after the colon)
2. Slugify the description:
   - Convert to lowercase
   - Replace spaces with hyphens
   - Remove special characters (keep only letters, numbers, hyphens)
   - Trim leading/trailing hyphens
3. Generate branch name: `<branch-prefix><slugified-description>`
4. Generate worktree path: `../<worktree-prefix><slugified-description>`

**Examples:**

| Issue Title | Branch Name | Worktree Path |
|-------------|-------------|---------------|
| `feat(pdf): add watermark support` | `feature/add-watermark-support` | `../feature-add-watermark-support` |
| `fix(admin): resolve tax calculation bug` | `fix/resolve-tax-calculation-bug` | `../fix-resolve-tax-calculation-bug` |
| `refactor: improve performance` | `refactor/improve-performance` | `../refactor-improve-performance` |

### Step 5: Create the worktree

Execute the following commands:

```bash
# Update develop branch without checkout
git fetch origin develop:develop

# Create worktree with new branch based on develop
git worktree add <worktree-path> -b <branch-name> develop
```

### Step 6: Confirm success

Report to the user:
- Issue number and title
- Created branch name
- Worktree path (absolute path)
- Instructions for navigating to the worktree

## Example Output

```
Created worktree from issue #17:

Issue: feat(pdf): add watermark support
Branch: feature/add-watermark-support
Worktree: /path/to/workspace/feature-add-watermark-support

To start working in the new worktree:
  cd ../feature-add-watermark-support

Or open a new terminal in the worktree directory.
```

## Error Handling

- If issue doesn't exist: Report error and suggest checking the issue number
- If branch already exists: Report the error and suggest using `/new-branch` to checkout existing branch
- If worktree path already exists: Report the conflict and suggest removing it or using a different path
- If git operations fail: Report the error and suggest manual resolution

## Important

- Worktrees are created in the parent directory (`../`) of the current repository
- Each worktree has its own working directory but shares the git history
- Use `git worktree list` to see all worktrees
- Use `git worktree remove <path>` to remove a worktree when done
- Follow the project's branch naming conventions from CLAUDE.md
- If the issue title doesn't follow the expected format, try to infer the type from labels or default to `feature/`
