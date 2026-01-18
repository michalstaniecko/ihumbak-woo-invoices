# GitHub Issue Creator

Create a comprehensive GitHub issue from a brief description.

**Input:** $ARGUMENTS

## Instructions

### Step 1: Analyze the request

Parse the user's message to understand:
1. **Issue type:** bug, feature request, enhancement, documentation, refactor, or question
2. **Affected area:** which module/component is involved (invoice, pdf, admin, settings, etc.)
3. **Priority indicators:** urgency keywords, blocking issues, critical functionality

### Step 2: Research the codebase (if needed)

If the issue relates to existing code:
1. Search for relevant files and classes
2. Identify related components
3. Check for existing similar issues or related code
4. Note any dependencies or affected areas

### Step 3: Generate comprehensive issue content

Create a well-structured issue with:

**Title format:**
```
<type>(<scope>): <description>
```

**Types:**
- `feat` - new feature or functionality
- `fix` - bug fix
- `refactor` - code refactoring without behavior change
- `docs` - documentation changes
- `chore` - maintenance tasks, dependencies
- `test` - test-related changes

**Scope (optional):**
- `admin`, `pdf`, `invoice`, `receipt`, `settings`, `js`, `templates`

**Rules:**
- Type is required, always lowercase
- Scope is optional, in parentheses
- Description in lowercase, no period at end
- Use imperative mood ("add" not "added")

**Body structure:**
```markdown
## Description
[Expanded description of what needs to be done/fixed]

## Context
[Why this is needed, background information]

## Acceptance Criteria
- [ ] Criterion 1
- [ ] Criterion 2
- [ ] Criterion 3

## Technical Notes
[Relevant technical details, affected files, implementation hints]

## Related
[Links to related issues, PRs, or documentation if any]
```

### Step 4: Determine labels

Select appropriate labels based on the issue:
- Type: `bug`, `enhancement`, `feature`, `documentation`, `refactor`
- Priority: `priority:high`, `priority:medium`, `priority:low`
- Area: `area:pdf`, `area:admin`, `area:invoice`, `area:settings`

### Step 5: Create the issue

Use the GitHub CLI to create the issue:

```bash
gh issue create --title "<title>" --body "<body>" --label "<labels>"
```

## Example transformations

**Input:** "PDF nie renderuje polskich znaków"
**Output:**
- Title: `fix(pdf): Polish characters not rendering correctly`
- Labels: `bug`, `area:pdf`, `priority:high`
- Body with description, reproduction steps, expected behavior, technical notes about DOMPDF encoding

**Input:** "dodaj eksport do CSV"
**Output:**
- Title: `feat: add CSV export functionality`
- Labels: `feature`, `enhancement`
- Body with description, use cases, acceptance criteria, technical considerations

**Input:** "popraw obsługę tax_rate w JavaScript"
**Output:**
- Title: `refactor(js): improve tax_rate handling in collectItemsData`
- Labels: `refactor`, `area:admin`
- Body with description, affected files, implementation approach

## Important

- Always write issue content in **English** (per project language policy)
- Be specific and actionable in acceptance criteria
- Include technical context that will help implementation
- Reference relevant documentation or code paths when applicable
- Ask the user for confirmation before creating the issue
