---
name: jira-project-manager
description: |
  Expert project management with Jira integration via MCP for task tracking, sprint planning, and team coordination. Gestión experta de proyectos con integración de Jira vía MCP para seguimiento de tareas, planificación de sprints y coordinación de equipos.

  **Use when / Usar cuando:**
  - Creating, updating, or managing Jira issues (crear, actualizar, o gestionar issues de Jira)
  - Planning sprints and managing backlogs (planificar sprints y gestionar backlogs)
  - Tracking progress and generating status reports (seguir progreso y generar reportes de estado)
  - Breaking down epics into stories and tasks (dividir épicas en historias y tareas)
  - Moving issues through workflows (mover issues por flujos de trabajo)
  - Searching and filtering issues with JQL (buscar y filtrar issues con JQL)
  - Linking related issues and managing dependencies (vincular issues relacionados y gestionar dependencias)
  - Assigning work and managing team workload (asignar trabajo y gestionar carga de equipo)
  - Creating burndown reports and velocity tracking (crear reportes burndown y seguimiento de velocidad)
  - Managing Jira boards and filters (gestionar tableros y filtros de Jira)

  **Jira concepts / Conceptos Jira:** Epic (Épica), Story (Historia), Task (Tarea), Bug (Error), Sub-task (Subtarea), Sprint, Backlog, Board (Tablero), Filter (Filtro), JQL, Workflow (Flujo de trabajo), Component (Componente), Version (Versión), Label (Etiqueta).

  **MCP operations / Operaciones MCP:** Create issue (crear issue), update issue (actualizar issue), transition issue (transicionar issue), search issues (buscar issues), get issue details (obtener detalles de issue), add comments (agregar comentarios), manage sprints (gestionar sprints).
---

# Jira Project Manager

## MCP Integration

### Checking MCP Availability

Before performing Jira operations, verify the MCP is configured:

```
If Jira MCP is available:
  → Use MCP tools for all Jira operations
  → Leverage real-time data and updates

If Jira MCP is NOT available:
  → Inform the user:
    "Jira MCP is not configured. To enable Jira integration:
    1. Configure the Jira MCP server (see references/mcp-setup.md)
    2. Provide your Jira credentials

    I can still help plan tasks and write JQL queries."
```

### Core MCP Operations

| Operation          | Purpose                 | When to Use                       |
| ------------------ | ----------------------- | --------------------------------- |
| `get_issue`        | Fetch issue details     | View issue, check status          |
| `create_issue`     | Create new issue        | New tasks, stories, bugs          |
| `update_issue`     | Modify issue fields     | Change priority, assignee, labels |
| `transition_issue` | Move through workflow   | Start, complete, close issues     |
| `search_issues`    | Query with JQL          | Find issues, generate reports     |
| `add_comment`      | Add issue comment       | Updates, questions, notes         |
| `list_projects`    | List available projects | Project discovery                 |

See [references/mcp-operations.md](references/mcp-operations.md) for detailed usage.

## Issue Management

### Creating Issues

When asked to create an issue, gather:

1. **Project** - Which project to create in
2. **Issue Type** - Epic, Story, Task, Bug, Sub-task
3. **Summary** - Clear, concise title
4. **Description** - Detailed context with acceptance criteria
5. **Priority** - Highest, High, Medium, Low, Lowest
6. **Assignee** - Who will work on it (optional)
7. **Labels/Components** - Categorization (optional)

### Issue Type Guidelines

| Type         | Use For                     | Contains                               |
| ------------ | --------------------------- | -------------------------------------- |
| **Epic**     | Large feature or initiative | Stories, deliverables                  |
| **Story**    | User-facing functionality   | Acceptance criteria, value             |
| **Task**     | Technical work item         | Implementation details                 |
| **Bug**      | Defect or issue             | Reproduction steps, expected vs actual |
| **Sub-task** | Breakdown of parent         | Granular work item                     |

### Writing Good Summaries

```
✅ Good summaries:
- [API] Add pagination to /products endpoint
- [UI] Implement dark mode toggle in settings
- [BUG] Cart total incorrect with discount codes
- [PERF] Optimize homepage load time below 2s

❌ Bad summaries:
- Fix bug
- Update code
- Issue with login
- Implement feature
```

### Writing Good Descriptions

```markdown
## Description

Brief context about why this is needed.

## Acceptance Criteria

- [ ] User can do X
- [ ] System responds with Y
- [ ] Error handling for Z

## Technical Notes

Any implementation guidance or constraints.

## Out of Scope

What this issue does NOT cover.

## Related Issues

- Blocks: PROJECT-123
- Related to: PROJECT-456
```

## JQL (Jira Query Language)

### Essential JQL Queries

```jql
# My open issues
assignee = currentUser() AND status != Done

# Sprint backlog
project = PROJ AND sprint in openSprints()

# Unassigned issues
project = PROJ AND assignee is EMPTY AND status != Done

# High priority bugs
project = PROJ AND type = Bug AND priority in (Highest, High)

# Recently updated
project = PROJ AND updated >= -7d ORDER BY updated DESC

# Blocked issues
project = PROJ AND status = Blocked

# Issues without estimates
project = PROJ AND type = Story AND "Story Points" is EMPTY
```

### JQL Operators

| Operator             | Usage              | Example                          |
| -------------------- | ------------------ | -------------------------------- |
| `=`, `!=`            | Equals, not equals | `status = "In Progress"`         |
| `IN`, `NOT IN`       | Multiple values    | `priority IN (High, Highest)`    |
| `~`, `!~`            | Contains text      | `summary ~ "login"`              |
| `IS`, `IS NOT`       | Empty/null check   | `assignee IS EMPTY`              |
| `>`, `<`, `>=`, `<=` | Comparison         | `created >= -30d`                |
| `AND`, `OR`          | Combine conditions | `type = Bug AND priority = High` |
| `ORDER BY`           | Sort results       | `ORDER BY priority DESC`         |

### Date Functions

```jql
created >= -7d           # Last 7 days
created >= startOfWeek()  # This week
created >= startOfMonth() # This month
updated >= -24h          # Last 24 hours
duedate < now()          # Overdue
```

See [references/jql-examples.md](references/jql-examples.md) for more examples.

## Sprint Management

### Sprint Planning Workflow

```
1. BACKLOG REFINEMENT
   ├─→ Review upcoming stories
   ├─→ Ensure acceptance criteria are clear
   ├─→ Estimate story points
   └─→ Prioritize by business value

2. SPRINT PLANNING
   ├─→ Set sprint goal
   ├─→ Select stories based on velocity
   ├─→ Break stories into sub-tasks
   └─→ Assign owners

3. DURING SPRINT
   ├─→ Daily standups (blockers, progress)
   ├─→ Update issue statuses
   ├─→ Track burndown
   └─→ Manage scope creep

4. SPRINT REVIEW
   ├─→ Demo completed work
   ├─→ Gather feedback
   └─→ Update backlog

5. RETROSPECTIVE
   ├─→ What went well
   ├─→ What to improve
   └─→ Action items
```

### Velocity Tracking

```
Velocity = Story points completed per sprint

Last 3 sprints:
- Sprint 10: 34 points
- Sprint 11: 28 points
- Sprint 12: 32 points
Average velocity: 31 points

Sprint capacity planning:
- Available velocity: 31 points
- Account for PTO, meetings: -5 points
- Planned capacity: 26 points
```

## Status Reports

### Daily Standup Report

```markdown
## Daily Standup - [Date]

### Completed Yesterday

- [PROJ-123] Implemented user authentication
- [PROJ-124] Fixed cart calculation bug

### Working On Today

- [PROJ-125] Adding payment integration
- [PROJ-126] Writing unit tests

### Blockers

- [PROJ-127] Waiting for API credentials from vendor
- Need design review for checkout flow
```

### Sprint Status Report

```markdown
## Sprint [N] Status Report

**Sprint Goal:** [Goal statement]
**Duration:** [Start] - [End]
**Days Remaining:** [X]

### Progress

- Stories completed: 5/8
- Story points: 21/34 (62%)
- Tasks completed: 18/25

### Burndown

[Include burndown chart or summary]

### At Risk

- [PROJ-130] Payment integration - blocked on vendor
- [PROJ-132] May not complete - complex edge cases

### Completed

- [PROJ-128] User login flow ✅
- [PROJ-129] Cart persistence ✅

### Carry-over Risk

Stories that may not complete:

- [PROJ-131] Performance optimization
```

### Weekly Status Report

```markdown
## Weekly Status Report - [Week of Date]

### Summary

[1-2 sentence executive summary]

### Key Accomplishments

- Completed feature X (PROJ-100-105)
- Fixed critical bug affecting Y users
- Deployed version Z to production

### In Progress

- [Epic] Payment System (60% complete)
- [Epic] User Dashboard (40% complete)

### Blockers & Risks

| Issue    | Impact | Mitigation          |
| -------- | ------ | ------------------- |
| PROJ-150 | High   | Escalated to vendor |

### Metrics

- Velocity: 32 pts (avg: 30)
- Bug escape rate: 2%
- Sprint completion: 85%

### Next Week Focus

- Complete payment integration
- Begin dashboard development
- Release 2.1.0
```

## Workflow Management

### Common Workflow States

```
┌─────────┐     ┌─────────────┐     ┌───────────┐     ┌──────┐
│ Backlog │ ──▶ │ In Progress │ ──▶ │ In Review │ ──▶ │ Done │
└─────────┘     └─────────────┘     └───────────┘     └──────┘
                      │                    │
                      ▼                    ▼
                ┌─────────┐          ┌─────────┐
                │ Blocked │          │ Reopened│
                └─────────┘          └─────────┘
```

### Transitioning Issues

When transitioning issues, include:

1. **Comment** explaining the transition
2. **Updated fields** (assignee, labels, etc.)
3. **Linked issues** if blocked

```
Transition: "In Progress" → "In Review"
Comment: "Ready for code review. PR: github.com/org/repo/pull/123"
```

## Team Management

### Workload Distribution

```markdown
## Team Workload - Sprint [N]

| Team Member | Assigned | Story Points | Capacity |
| ----------- | -------- | ------------ | -------- |
| Alice       | 4 issues | 13 pts       | 80%      |
| Bob         | 3 issues | 8 pts        | 60%      |
| Carol       | 5 issues | 15 pts       | 90%      |

### Notes

- Alice: PTO Thursday-Friday
- Bob: Supporting production incident
```

### Issue Assignment

When assigning issues, consider:

1. **Skills match** - Right person for the work
2. **Capacity** - Not overloaded
3. **Context** - Related work they've done
4. **Growth** - Learning opportunities
5. **Availability** - PTO, other commitments

## Best Practices

### Issue Hygiene

- [ ] Every issue has clear acceptance criteria
- [ ] Issues are properly sized (1-8 story points)
- [ ] No issues older than 30 days without updates
- [ ] Labels and components are consistent
- [ ] Blocked issues have linked blockers
- [ ] Done issues are verified complete

### Communication

- [ ] Comment on issues with meaningful updates
- [ ] Tag stakeholders when needing input
- [ ] Link related issues and PRs
- [ ] Update status promptly after changes
- [ ] Use @mentions sparingly but effectively

### Sprint Health

- [ ] Sprint goal is clear and achievable
- [ ] Work is evenly distributed
- [ ] Buffer for unexpected work (10-20%)
- [ ] No scope creep without removal
- [ ] Retro action items are tracked
