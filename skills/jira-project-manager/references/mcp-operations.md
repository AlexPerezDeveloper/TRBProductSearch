# Jira MCP Operations

## MCP Configuration

### Setup

Add the Jira MCP server to your configuration:

```json
{
  "mcpServers": {
    "jira": {
      "command": "npx",
      "args": ["-y", "@anthropic/mcp-jira"],
      "env": {
        "JIRA_HOST": "https://yourcompany.atlassian.net",
        "JIRA_EMAIL": "your-email@company.com",
        "JIRA_API_TOKEN": "your-api-token"
      }
    }
  }
}
```

### Getting API Token

1. Go to https://id.atlassian.com/manage-profile/security/api-tokens
2. Click "Create API token"
3. Give it a descriptive name (e.g., "Claude Jira Integration")
4. Copy the token immediately (shown only once)
5. Store securely in environment variables

## Core Operations

### Get Issue

Fetch complete details for a specific issue.

**Use for:**

- Viewing issue details before updating
- Getting current status and assignee
- Checking acceptance criteria
- Reviewing comments and history

**Parameters:**

```
issue_key: "PROJECT-123"
```

**Returns:**

- Key, summary, description
- Status, priority, type
- Assignee, reporter
- Labels, components
- Custom fields (story points, sprint, etc.)
- Comments, attachments
- Linked issues

### Create Issue

Create a new issue in Jira.

**Parameters:**

```
project_key: "PROJECT"
issue_type: "Story" | "Task" | "Bug" | "Epic" | "Sub-task"
summary: "Clear, concise title"
description: "Detailed description with context"
priority: "Highest" | "High" | "Medium" | "Low" | "Lowest"
assignee: "account-id" (optional)
labels: ["frontend", "urgent"] (optional)
components: ["API", "Auth"] (optional)
parent: "PROJECT-100" (for sub-tasks)
custom_fields: { "customfield_10001": "value" } (optional)
```

**Example:**

```
Create Issue:
  project_key: "PROJ"
  issue_type: "Story"
  summary: "[API] Add pagination to products endpoint"
  description: |
    ## Description
    Implement pagination for the products API to improve performance.

    ## Acceptance Criteria
    - [ ] Add page and perPage query parameters
    - [ ] Return total count in response
    - [ ] Default to 20 items per page
    - [ ] Maximum 100 items per page
  priority: "Medium"
  labels: ["backend", "api"]
```

### Update Issue

Modify existing issue fields.

**Parameters:**

```
issue_key: "PROJECT-123"
fields: {
  summary: "Updated summary",
  description: "Updated description",
  priority: "High",
  assignee: "account-id",
  labels: ["updated", "labels"]
}
```

**Common Updates:**

- Change priority when requirements change
- Update assignee when reassigning
- Add/remove labels for categorization
- Update description with new information
- Modify custom fields (story points, etc.)

### Transition Issue

Move issue through workflow states.

**Parameters:**

```
issue_key: "PROJECT-123"
transition: "In Progress" | "In Review" | "Done" | etc.
comment: "Optional comment explaining transition"
resolution: "Done" | "Won't Do" | etc. (for closing transitions)
```

**Common Transitions:**

```
Backlog → In Progress
  Comment: "Starting work on this issue"

In Progress → In Review
  Comment: "Ready for review. PR: [link]"

In Review → Done
  Comment: "Approved and merged. Deployed to staging."
  Resolution: "Done"

Any → Blocked
  Comment: "Blocked by [PROJ-456]. Waiting for API access."
```

### Search Issues

Query issues using JQL.

**Parameters:**

```
jql: "project = PROJ AND status = 'In Progress'"
max_results: 50 (optional, default varies)
fields: ["summary", "status", "assignee"] (optional)
```

**Returns:**

- List of matching issues
- Basic fields (key, summary, status)
- Requested additional fields

### Add Comment

Add a comment to an issue.

**Parameters:**

```
issue_key: "PROJECT-123"
body: "Comment content with @mentions and formatting"
```

**Good Comments:**

```
✅ Progress update:
"Completed API implementation. Starting on frontend integration.
Estimated completion: Tomorrow EOD."

✅ Asking for input:
"@john.doe Need clarification on the error handling requirement.
Should we return 404 or 204 for empty results?"

✅ Linking resources:
"PR ready for review: github.com/org/repo/pull/123
Demo video: [link]"
```

### List Projects

Get available projects.

**Returns:**

- Project keys and names
- Project leads
- Categories

**Use for:**

- Finding correct project key
- Discovering available projects
- Validating project access

## Sprint Operations

### Get Active Sprints

```
Get sprints for board:
  board_id: 123
  state: "active" | "future" | "closed"
```

### Move to Sprint

```
Move issue to sprint:
  issues: ["PROJ-123", "PROJ-124"]
  sprint_id: 456
```

### Get Sprint Report

```
Get sprint report:
  sprint_id: 456

Returns:
  - Completed issues
  - Incomplete issues
  - Issues removed from sprint
  - Burndown data
```

## Bulk Operations

### Create Multiple Issues

When creating related issues:

```
1. Create Epic first → Get epic key
2. Create Stories linked to Epic
3. Create Sub-tasks for Stories

Example:
Epic: "Payment System" → PROJ-100
├── Story: "Credit card payments" → PROJ-101 (parent: PROJ-100)
│   ├── Sub-task: "Add Stripe SDK" → PROJ-104 (parent: PROJ-101)
│   └── Sub-task: "Implement checkout flow" → PROJ-105
├── Story: "PayPal integration" → PROJ-102
└── Story: "Payment history" → PROJ-103
```

### Bulk Update

Update multiple issues at once:

```
For each issue in [PROJ-101, PROJ-102, PROJ-103]:
  Update:
    labels: ADD ["sprint-5"]
    priority: "High"
```

### Bulk Transition

Move multiple issues through workflow:

```
For each issue in search results:
  If status = "In Review" AND approved:
    Transition to "Done"
    Add comment: "Completed in Sprint 5"
```

## Error Handling

### Common Errors

| Error            | Cause               | Solution                 |
| ---------------- | ------------------- | ------------------------ |
| 401 Unauthorized | Invalid token       | Regenerate API token     |
| 403 Forbidden    | No permission       | Check project access     |
| 404 Not Found    | Invalid issue key   | Verify issue exists      |
| 400 Bad Request  | Invalid field value | Check field names/values |

### Validation Before Operations

```
Before creating issue:
  ✓ Project exists and accessible
  ✓ Issue type valid for project
  ✓ Required fields provided
  ✓ Assignee has project access

Before transitioning:
  ✓ Transition is valid from current state
  ✓ Required fields for transition are set
  ✓ User has permission to transition
```

## Rate Limiting

Jira Cloud has rate limits. Best practices:

- Batch operations when possible
- Cache frequently accessed data
- Use webhooks for real-time updates
- Implement exponential backoff
- Monitor API usage

```
Rate limit headers:
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1699999999
```
