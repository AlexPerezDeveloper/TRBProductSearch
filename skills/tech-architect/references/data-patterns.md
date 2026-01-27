# Data Architecture Patterns

## Database Selection Guide

### Relational (PostgreSQL, MySQL)

**Use when:**

- Complex queries with JOINs
- ACID transactions required
- Data relationships are central
- Strong consistency needed

**Avoid when:**

- Schema changes frequently
- Horizontal scaling is primary concern
- Unstructured data

### Document (MongoDB, DynamoDB)

**Use when:**

- Schema flexibility needed
- Hierarchical data structures
- Horizontal scaling priority
- Rapid development cycles

**Avoid when:**

- Complex relationships between entities
- Strong consistency required across documents
- Complex aggregations

### Key-Value (Redis, Memcached)

**Use when:**

- Session storage
- Caching layer
- Rate limiting
- Real-time leaderboards

**Avoid when:**

- Complex queries needed
- Data relationships exist
- Large value sizes

### Time Series (TimescaleDB, InfluxDB)

**Use when:**

- Metrics and monitoring
- IoT sensor data
- Financial market data
- Log aggregation

### Graph (Neo4j, Neptune)

**Use when:**

- Social networks
- Recommendation engines
- Fraud detection
- Knowledge graphs

## Data Modeling Patterns

### Normalization vs Denormalization

```
Normalized (3NF):
┌─────────┐     ┌─────────────┐     ┌─────────┐
│ Users   │────→│ User_Orders │←────│ Orders  │
└─────────┘     └─────────────┘     └─────────┘

Denormalized:
┌────────────────────────────────────────────────┐
│ Orders (with embedded user_name, user_email)   │
└────────────────────────────────────────────────┘

Trade-offs:
- Normalized: Less storage, easier updates, slower reads
- Denormalized: Faster reads, storage overhead, update complexity
```

### Sharding Strategies

**Hash-based:**

```
shard_id = hash(user_id) % num_shards

✅ Even distribution
❌ Range queries across shards
```

**Range-based:**

```
shard_1: A-M
shard_2: N-Z

✅ Range queries efficient
❌ Hotspots possible
```

**Directory-based:**

```
lookup_table[entity_id] → shard_id

✅ Flexible placement
❌ Lookup table is SPOF
```

### Replication Patterns

**Single Leader:**

```
Write → Leader → Followers (async)
Read  → Leader or Followers

Use: Most applications
Risk: Leader failure = write downtime
```

**Multi-Leader:**

```
Write → Any Leader → Sync to other Leaders
Read  → Any node

Use: Multi-datacenter, offline clients
Risk: Conflict resolution complexity
```

**Leaderless:**

```
Write → Quorum of nodes (W)
Read  → Quorum of nodes (R)
Rule: W + R > N for consistency

Use: High availability priority
Risk: Complex conflict handling
```

## Event Sourcing

### Core Concepts

```
Traditional: Store current state
Event Sourcing: Store all changes as events

Events:
┌─────────────────────────────────────────┐
│ 1. AccountCreated { id: 123, name: X }  │
│ 2. MoneyDeposited { id: 123, amt: 100 } │
│ 3. MoneyWithdrawn { id: 123, amt: 30 }  │
│ 4. MoneyDeposited { id: 123, amt: 50 }  │
└─────────────────────────────────────────┘
            ↓ (replay)
Current State: { id: 123, balance: 120 }
```

### When to Use

✅ Audit trail required (finance, healthcare)
✅ Temporal queries ("state at time X")
✅ Complex domain with business rules
✅ Event-driven architecture fits

❌ Simple CRUD applications
❌ Team unfamiliar with pattern
❌ Strong consistency requirements per-read

### Implementation Tips

```
1. Events are immutable - never modify, only append
2. Event names should be past tense (OrderPlaced, not PlaceOrder)
3. Snapshots for performance (every N events)
4. Separate write model (events) from read model (projections)
```

## CQRS (Command Query Responsibility Segregation)

### Architecture

```
         ┌─────────────────┐
         │   Commands      │
         │ (Write Model)   │
         └────────┬────────┘
                  ↓
         ┌─────────────────┐
         │  Event Store /  │
         │  Write Database │
         └────────┬────────┘
                  ↓ (async projection)
         ┌─────────────────┐
         │  Read Database  │
         │  (Denormalized) │
         └────────┬────────┘
                  ↓
         ┌─────────────────┐
         │    Queries      │
         │  (Read Model)   │
         └─────────────────┘
```

### Trade-offs

| Aspect      | Benefit                          | Cost                      |
| ----------- | -------------------------------- | ------------------------- |
| Performance | Optimized read models            | Sync delay                |
| Scalability | Scale reads/writes independently | Infrastructure complexity |
| Flexibility | Multiple read models             | More code to maintain     |
| Consistency | Eventual by default              | Complex if strong needed  |

## Migration Strategies

### Blue-Green Deployment

```
Before:
  Traffic → [Blue v1] ← Active
            [Green v2] ← Idle

After:
  Traffic → [Blue v1] ← Idle (rollback ready)
            [Green v2] ← Active
```

### Strangler Fig Pattern

```
Phase 1: New system handles new features
         Old system handles existing features

Phase 2: Gradually migrate features to new system

Phase 3: Decommission old system

Key: Use facade/proxy to route traffic
```

### Database Migration

```
1. Add new column (nullable)
2. Deploy code that writes to both
3. Backfill old data
4. Deploy code that reads from new
5. Remove old column

Never: Big bang schema changes
```
