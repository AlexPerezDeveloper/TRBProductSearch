# JQL Examples

## Basic Queries

### By Status

```jql
# Open issues
project = PROJ AND status != Done

# In progress
project = PROJ AND status = "In Progress"

# Multiple statuses
project = PROJ AND status IN ("In Progress", "In Review")

# Not in status
project = PROJ AND status NOT IN (Done, Cancelled)
```

### By Assignee

```jql
# My issues
assignee = currentUser()

# Specific person
assignee = "john.doe@company.com"

# Unassigned
assignee IS EMPTY

# Assigned to anyone
assignee IS NOT EMPTY

# My team
assignee IN (membersOf("developers"))
```

### By Type

```jql
# Only bugs
type = Bug

# Stories and tasks
type IN (Story, Task)

# Not sub-tasks
type != Sub-task
```

### By Priority

```jql
# Critical issues
priority = Highest

# High priority and above
priority IN (Highest, High)

# Order by priority
project = PROJ ORDER BY priority DESC
```

## Date-Based Queries

### Created Date

```jql
# Last 7 days
created >= -7d

# Last 24 hours
created >= -24h

# This week
created >= startOfWeek()

# This month
created >= startOfMonth()

# This year
created >= startOfYear()

# Specific date range
created >= "2024-01-01" AND created <= "2024-01-31"
```

### Updated Date

```jql
# Recently updated
updated >= -3d

# Not updated in 30 days (stale)
updated <= -30d AND status != Done
```

### Due Date

```jql
# Overdue
duedate < now() AND status != Done

# Due this week
duedate >= startOfWeek() AND duedate <= endOfWeek()

# Due in next 7 days
duedate >= now() AND duedate <= 7d

# No due date
duedate IS EMPTY
```

### Resolved Date

```jql
# Resolved this sprint
resolved >= -14d

# Resolved this month
resolved >= startOfMonth()
```

## Sprint Queries

### Current Sprint

```jql
# In current sprint
sprint IN openSprints()

# In any active sprint
sprint IN openSprints() AND project = PROJ

# Not in any sprint
sprint IS EMPTY

# In specific sprint
sprint = "Sprint 10"
```

### Sprint Planning

```jql
# Backlog (not in sprint, not done)
project = PROJ AND sprint IS EMPTY AND status != Done

# Ready for sprint (refined, estimated)
sprint IS EMPTY AND "Story Points" IS NOT EMPTY AND status = "Ready for Dev"

# Unestimated stories
type = Story AND "Story Points" IS EMPTY
```

## Text Search

### Summary and Description

```jql
# Contains word in summary
summary ~ "authentication"

# Contains phrase
summary ~ "user login"

# Contains in summary OR description
text ~ "payment"

# Exact match (case sensitive)
summary = "Implement user authentication"
```

### Using Wildcards

```jql
# Starts with
summary ~ "API*"

# Contains
summary ~ "*login*"

# Single character wildcard
summary ~ "v?.0"
```

## Labels and Components

### Labels

```jql
# Has specific label
labels = "backend"

# Has any of these labels
labels IN ("frontend", "backend")

# Has all of these labels (AND)
labels = "urgent" AND labels = "production"

# Has no labels
labels IS EMPTY
```

### Components

```jql
# In specific component
component = "Authentication"

# In any of these components
component IN ("API", "Database")

# No component assigned
component IS EMPTY
```

## Linked Issues

### Issue Links

```jql
# Has any linked issues
issueLink IS NOT EMPTY

# Blocks other issues
issueLinkType = "Blocks"

# Is blocked by
issueLinkType = "is blocked by"

# Related to specific issue
issue IN linkedIssues("PROJ-123")
```

### Parent/Child

```jql
# Sub-tasks of issue
parent = "PROJ-123"

# All issues in epic
"Epic Link" = "PROJ-100"

# Issues without epic
"Epic Link" IS EMPTY AND type = Story
```

## Custom Fields

### Story Points

```jql
# Has story points
"Story Points" IS NOT EMPTY

# Specific points
"Story Points" = 5

# Range
"Story Points" >= 3 AND "Story Points" <= 8

# Large stories (may need splitting)
"Story Points" > 8
```

### Other Custom Fields

```jql
# Custom dropdown
"Environment" = "Production"

# Custom text field
"Customer" ~ "Acme*"
```

## Complex Queries

### Sprint Readiness

```jql
# Stories ready for sprint (complete criteria)
project = PROJ
  AND type = Story
  AND status = "Ready for Dev"
  AND "Story Points" IS NOT EMPTY
  AND description IS NOT EMPTY
  AND sprint IS EMPTY
ORDER BY priority DESC, created ASC
```

### Bug Triage

```jql
# Untriaged bugs
project = PROJ
  AND type = Bug
  AND priority = Medium
  AND created >= -7d
  AND labels IS EMPTY
ORDER BY created DESC
```

### Stale Issues

```jql
# Issues not updated in 14 days, still open
project = PROJ
  AND status NOT IN (Done, Cancelled)
  AND updated <= -14d
ORDER BY updated ASC
```

### At Risk for Sprint

```jql
# In sprint but not started
sprint IN openSprints()
  AND status = Backlog
  AND created <= -7d
```

### Velocity Calculation

```jql
# Completed in sprint
project = PROJ
  AND sprint = "Sprint 10"
  AND status = Done
  AND resolutiondate >= startOfMonth()
```

## Saved Filters

### Recommended Filters

| Filter Name      | JQL                                                    | Purpose           |
| ---------------- | ------------------------------------------------------ | ----------------- |
| My Open Issues   | `assignee = currentUser() AND status != Done`          | Daily work        |
| Sprint Backlog   | `sprint IN openSprints() AND project = PROJ`           | Sprint tracking   |
| Untriaged Bugs   | `type = Bug AND priority = Medium AND labels IS EMPTY` | Bug triage        |
| Blocked          | `status = Blocked`                                     | Blocker review    |
| Ready for Review | `status = "In Review"`                                 | Code review queue |
| Stale Issues     | `updated <= -14d AND status != Done`                   | Hygiene           |

## Query Optimization

### Performance Tips

1. **Use project filter first**

   ```jql
   # Fast
   project = PROJ AND status = Open

   # Slow (scans all projects)
   status = Open
   ```

2. **Limit date ranges**

   ```jql
   # Better
   created >= -30d AND created <= now()

   # Avoid open-ended
   created IS NOT EMPTY
   ```

3. **Use specific fields**

   ```jql
   # Better (indexed)
   summary ~ "login"

   # Slower (full text)
   text ~ "login"
   ```

4. **Avoid NOT with text search**

   ```jql
   # Slow
   summary !~ "test"

   # Consider alternative approach
   ```
