# Architecture Decision Record Template

## ADR-[NUMBER]: [Title]

**Date:** YYYY-MM-DD

**Status:** [Proposed | Accepted | Deprecated | Superseded by ADR-XXX]

**Deciders:** [List of people involved in the decision]

---

## Context

Describe the situation and problem. Be specific about:

- What is the issue that we're seeing that is motivating this decision?
- What are the constraints? (technical, business, time, team)
- What is the current state?

_Keep this factual and neutral—no proposed solutions yet._

---

## Decision Drivers

List the key factors influencing this decision:

- **[Driver 1]**: Description
- **[Driver 2]**: Description
- **[Driver 3]**: Description

---

## Considered Options

### Option 1: [Name]

**Description:** Brief explanation of the approach.

**Pros:**

- Advantage 1
- Advantage 2

**Cons:**

- Disadvantage 1
- Disadvantage 2

**Estimated Effort:** [Low | Medium | High]

---

### Option 2: [Name]

**Description:** Brief explanation of the approach.

**Pros:**

- Advantage 1
- Advantage 2

**Cons:**

- Disadvantage 1
- Disadvantage 2

**Estimated Effort:** [Low | Medium | High]

---

### Option 3: [Name] (if applicable)

...

---

## Decision

**Chosen Option:** [Option Name]

**Rationale:**
Explain why this option was selected over the others. Reference the decision drivers:

- Driver 1 → This option addresses it because...
- Driver 2 → Trade-off accepted because...

---

## Consequences

### Positive

- What becomes easier or more straightforward as a result?
- What new capabilities does this enable?

### Negative

- What becomes harder or more complex?
- What technical debt is incurred?
- What risks remain?

### Neutral

- What changes without being clearly positive or negative?

---

## Implementation Notes

_Optional: Include any guidance for implementing this decision._

- Key milestones
- Dependencies
- Migration steps
- Rollback plan

---

## Related Decisions

- **ADR-XXX:** [Related decision and relationship]
- **ADR-YYY:** [Related decision and relationship]

---

## References

- [Link to relevant documentation]
- [Link to design doc or RFC]
- [Link to meeting notes or discussions]

---

# ADR Process Guidelines

## When to Write an ADR

Write an ADR when you're making a decision that:

- Is **hard to reverse** (architectural, technology choice)
- Has **significant impact** (affects multiple teams or components)
- Is **non-obvious** (reasonable people could disagree)
- Will be **questioned later** ("why did we do this?")

## Naming Convention

```
adr/
├── 0001-use-postgresql-for-primary-database.md
├── 0002-adopt-microservices-for-checkout.md
├── 0003-implement-cqrs-for-reporting.md
└── template.md
```

## Status Lifecycle

```
Proposed → Accepted → [Deprecated | Superseded]
    ↓
  Rejected
```

- **Proposed:** Open for discussion
- **Accepted:** Decision made, proceed with implementation
- **Deprecated:** No longer applies (context changed)
- **Superseded:** Replaced by a newer decision
- **Rejected:** Decision was not adopted

## Tips for Good ADRs

1. **Keep it concise** - One page ideally, two pages maximum
2. **Be specific** - Name technologies, patterns, and trade-offs
3. **Include context** - Future readers need to understand the "why"
4. **List alternatives** - Show that options were considered
5. **Accept uncertainty** - It's okay to document unknowns
6. **Update status** - Keep decisions current as things change
