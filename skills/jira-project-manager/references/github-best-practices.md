# GitHub Project Management Best Practices

## Issue Templates

Standardize issue creation using `.github/ISSUE_TEMPLATE/` configuration.

### 1. Bug Report Template (`bug_report.md`)

```yaml
---
name: Bug report
about: Create a report to help us improve
title: "[BUG] "
labels: ["type: bug", "status: triage"]
assignees: []
---

## Description
A clear and concise description of what the bug is.

## Steps to Reproduce
1. Go to '...'
2. Click on '...'
3. Scroll down to '...'
4. See error

## Expected Behavior
A clear and concise description of what you expected to happen.

## Actual Behavior
What actually happened (include screenshots/logs).

## Environment
- OS: [e.g. macOS]
- Browser: [e.g. Chrome 98]
- Version: [e.g. 1.2.0]

## Possible Solution
(Optional) If you have a suggestion on how to fix the bug.
```

### 2. Feature Request Template (`feature_request.md`)

```yaml
---
name: Feature request
about: Suggest an idea for this project
title: "[FEAT] "
labels: ["type: enhancement", "status: triage"]
assignees: []
---

## Problem Statement
Is your feature request related to a problem? Please describe.
(e.g., "I'm always frustrated when...")

## Proposed Solution
Describe the solution you'd like.

## Alternatives Considered
Describe alternatives you've considered.

## Acceptance Criteria
- [ ] User can...
- [ ] System handles...
```

## Label Taxonomy

Use a consistent `prefix: value` format for clear filtering.

### Type (`type: *`)
| Label | Color | Description |
|-------|-------|-------------|
| `type: bug` | #d73a4a | Something isn't working |
| `type: feature` | #a2eeef | New feature or request |
| `type: chore` | #d4c5f9 | Maintenance, dependencies, tooling |
| `type: docs` | #0075ca | Documentation only changes |
| `type: refactor` | #f29513 | Code change that neither fixes a bug nor adds a feature |

### Priority (`priority: *`)
| Label | Color | Description |
|-------|-------|-------------|
| `priority: critical` | #b60205 | Must fix ASAP, blocks release/production |
| `priority: high` | #d93f0b | Important, fix in next release |
| `priority: medium` | #fbca04 | Normal priority |
| `priority: low` | #0e8a16 | Nice to have, can wait |

### Status (`status: *`)
| Label | Color | Description |
|-------|-------|-------------|
| `status: triage` | #f9d0c4 | New issue waiting for review |
| `status: backlog` | #c2e0c6 | Accepted, waiting for scheduling |
| `status: in-progress` | #bfdadc | Currently being worked on |
| `status: in-review` | #e99695 | PR open, waiting for review |
| `status: blocked` | #e11d21 | Waiting on external dependency |
| `status: done` | #0e8a16 | Completed |

## Issue Lifecycle

### 1. Triage
- New issues get `status: triage`.
- PM/Tech Lead reviews:
    - **Valid?** If no, close with comment.
    - **Duplicate?** Close as duplicate of #ID.
    - **Clear?** Ask for more info if needed.
    - **Action:** Assign `type:*` and `priority:*`. Move to `status: backlog`.

### 2. Implementation
- Developer picks up issue.
- Assigns self.
- Changes status to `status: in-progress`.
- Links a draft PR early.

### 3. Review & Close
- PR submitted -> `status: in-review`.
- PR merged -> Issue automatically closed (using "Fixes #123").
- Verify fix in staging/prod.

## Project Boards (GitHub Projects)

Use V2 Projects (memex) for sprint planning.

- **Views:**
    - "Sprint Board" (Kanban: Todo, In Progress, Done)
    - "Backlog" (List: Priority sorted)
    - "My Tasks" (Table: Filtered by assignee=@me)

- **Automation:**
    - Item added to project -> Status: Todo
    - PR merged -> Status: Done
