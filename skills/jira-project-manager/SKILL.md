---
name: jira-project-manager
description: |
  Expert project management with Jira and GitHub integration for task tracking, sprint planning, and team coordination. Gestión experta de proyectos con integración de Jira y GitHub para seguimiento de tareas, planificación de sprints y coordinación de equipos.

  **Use when / Usar cuando:**
  - Creating, updating, or managing Jira/GitHub issues (crear, actualizar, o gestionar issues)
  - Planning sprints and managing backlogs (planificar sprints y gestionar backlogs)
  - Tracking progress and generating status reports (seguir progreso y generar reportes de estado)
  - Breaking down epics into stories and tasks (dividir épicas en historias y tareas)
  - Moving issues through workflows (mover issues por flujos de trabajo)
  - Searching and filtering issues (JQL for Jira, filters for GitHub)
  - Linking related issues and managing dependencies
  - Assigning work and managing team workload
  - Managing boards and filters

  **Platforms:**
  - **Jira:** Enterprise planning, complex workflows, JQL power user.
  - **GitHub:** Developer-centric, issues close to code, lightweight project boards.

  **MCP operations:** Create issue, update issue, search issues, get issue details, add comments, list projects/repos.
---

# Project Manager (Jira & GitHub)

## Platform Selection

Choose the right tool based on the user's context:

| Context | Recommended Platform |
| :--- | :--- |
| **Enterprise / Large Teams** | **Jira** (Advanced reporting, strict workflows, cross-team dependencies) |
| **Open Source / Dev-Centric** | **GitHub Issues** (Closer to code, simpler, integrated with PRs) |
| **Hybrid** | Use **Jira** for product planning & **GitHub** for dev tasks (link them!) |

## MCP Integration

### Checking MCP Availability

Before performing operations, verify which MCP server is configured:

```
If Jira MCP is available:
  → Use `jira_*` tools (create_issue, search_issues...)

If GitHub MCP is available:
  → Use `github_*` tools (create_issue, list_issues...)

If NEITHER is available:
  → Guide user to set up the relevant MCP server.
  → Offer to help plan tasks using markdown or local files.
```

---

## GitHub Management

### Core GitHub Operations

| Operation | Purpose | Tool (Typical) |
| :--- | :--- | :--- |
| **Create Issue** | New bug/feature | `github_create_issue` |
| **List/Search** | Find issues | `github_list_issues` / `search_issues` |
| **Get Details** | Read thread | `github_get_issue` |
| **Comment** | Update status | `github_create_issue_comment` |
| **PRs** | Review code | `github_list_pull_requests` |

### GitHub Issue Best Practices

Refer to [references/github-best-practices.md](references/github-best-practices.md) for detailed templates and label strategies.

#### Quick Issue Template

When creating a GitHub issue via MCP, ensure you provide:
1.  **Title:** Clear and descriptive (e.g., `[FEAT] Add dark mode`).
2.  **Body:** Structured description (Context, Acceptance Criteria).
3.  **Labels:** At least one `type:*` and `priority:*` label.
4.  **Assignee:** (Optional) GitHub username.

---

## Jira Management

### Core Jira Operations

| Operation | Purpose | Tool |
| :--- | :--- | :--- |
| `get_issue` | Fetch issue details | View issue, check status |
| `create_issue` | Create new issue | New tasks, stories, bugs |
| `update_issue` | Modify issue fields | Change priority, assignee |
| `transition_issue` | Move through workflow | Start, complete, close |
| `search_issues` | Query with JQL | Find issues, reports |
| `add_comment` | Add issue comment | Updates, questions |

See [references/mcp-operations.md](references/mcp-operations.md) for detailed Jira usage.

### Issue Type Guidelines (Jira)

| Type | Use For | Contains |
| :--- | :--- | :--- |
| **Epic** | Large feature or initiative | Stories, deliverables |
| **Story** | User-facing functionality | Acceptance criteria, value |
| **Task** | Technical work item | Implementation details |
| **Bug** | Defect or issue | Reproduction steps |
| **Sub-task** | Breakdown of parent | Granular work item |

## Issue Hygiene (Universal)

Whether using Jira or GitHub:

1.  **Clear Summaries:** `[Component] Action or Result` (e.g., `[API] Fix 500 error on login`)
2.  **Acceptance Criteria:** Definition of Done.
3.  **Status Updates:** Keep the ticket status in sync with reality.
4.  **Linking:** Link PRs to Issues (`Fixes #123` or Jira Links).

## JQL (Jira Query Language)

### Essential Queries

```jql
# My open issues
assignee = currentUser() AND status != Done

# Sprint backlog
project = PROJ AND sprint in openSprints()

# High priority bugs
project = PROJ AND type = Bug AND priority in (Highest, High)
```

See [references/jql-examples.md](references/jql-examples.md) for more JQL.

## Status Reports & Planning

### Daily Standup Report

```markdown
## Daily Standup - [Date]

### Completed
- [PROJ-123] Implemented user auth

### Working On
- [PROJ-125] Payment integration

### Blockers
- Waiting for API keys
```

### Sprint Review / Status

Use the `document-summarizer` skill to help generate these reports from issue lists if needed.