# API Design Patterns

## RESTful API Design

### Resource Naming

```
✅ /users/{id}/orders         # Nouns, hierarchical
✅ /products?category=books   # Query params for filtering
❌ /getUser                   # No verbs in URLs
❌ /user_orders               # Avoid underscores
```

### HTTP Methods & Status Codes

| Method | Purpose        | Success        | Error             |
| ------ | -------------- | -------------- | ----------------- |
| GET    | Read           | 200 OK         | 404 Not Found     |
| POST   | Create         | 201 Created    | 400 Bad Request   |
| PUT    | Full Update    | 200 OK         | 404 Not Found     |
| PATCH  | Partial Update | 200 OK         | 422 Unprocessable |
| DELETE | Remove         | 204 No Content | 404 Not Found     |

### Pagination Patterns

**Offset-based** (simple, but slow at scale):

```json
GET /items?offset=100&limit=25
{
  "data": [...],
  "pagination": {
    "offset": 100,
    "limit": 25,
    "total": 1000
  }
}
```

**Cursor-based** (efficient, stable):

```json
GET /items?cursor=abc123&limit=25
{
  "data": [...],
  "pagination": {
    "next_cursor": "def456",
    "has_more": true
  }
}
```

### Versioning Strategies

| Strategy | Example                               | Pros             | Cons         |
| -------- | ------------------------------------- | ---------------- | ------------ |
| URL Path | `/v1/users`                           | Clear, cacheable | URL changes  |
| Header   | `Accept: application/vnd.api.v1+json` | Clean URLs       | Hidden       |
| Query    | `/users?version=1`                    | Easy to test     | Cache issues |

**Recommendation:** URL path for external APIs, headers for internal.

## GraphQL Patterns

### Schema Design

```graphql
# Prefer specific types over generic ones
type User {
  id: ID!
  email: String!
  profile: UserProfile!
  orders(first: Int, after: String): OrderConnection!
}

# Use connections for pagination
type OrderConnection {
  edges: [OrderEdge!]!
  pageInfo: PageInfo!
}

type OrderEdge {
  node: Order!
  cursor: String!
}
```

### Query Complexity

Protect against expensive queries:

```graphql
# Bad: Unbounded depth
query {
  users {
    friends {
      friends {
        friends { ... }
      }
    }
  }
}

# Good: Limit depth, use pagination
query {
  users(first: 10) {
    friends(first: 5) {
      name
    }
  }
}
```

### N+1 Problem

Use DataLoader pattern:

```javascript
// Instead of N database calls
const userLoader = new DataLoader(async (userIds) => {
  const users = await db.users.findMany({
    where: { id: { in: userIds } },
  });
  return userIds.map((id) => users.find((u) => u.id === id));
});
```

## gRPC Patterns

### Proto Definition Best Practices

```protobuf
syntax = "proto3";
package myservice.v1;

// Use wrapper types for optional fields
import "google/protobuf/wrappers.proto";

service UserService {
  // Unary RPC
  rpc GetUser(GetUserRequest) returns (User);

  // Server streaming
  rpc ListUsers(ListUsersRequest) returns (stream User);

  // Client streaming
  rpc UploadData(stream DataChunk) returns (UploadResponse);

  // Bidirectional streaming
  rpc Chat(stream Message) returns (stream Message);
}

message GetUserRequest {
  string user_id = 1;
}

message User {
  string id = 1;
  string email = 2;
  google.protobuf.StringValue nickname = 3; // Optional
}
```

### Error Handling

Use rich error details:

```protobuf
import "google/rpc/error_details.proto";

// Server-side
throw new GrpcError({
  code: Status.INVALID_ARGUMENT,
  details: [
    new BadRequest({
      fieldViolations: [
        { field: "email", description: "Invalid email format" }
      ]
    })
  ]
});
```

## API Gateway Patterns

### Rate Limiting

```yaml
# Token bucket algorithm
rate_limit:
  requests_per_second: 100
  burst_size: 200

# Tiered limits
tiers:
  free: { rpm: 60 }
  pro: { rpm: 1000 }
  enterprise: { rpm: 10000 }
```

### Circuit Breaker

```
States: CLOSED → OPEN → HALF_OPEN → CLOSED

CLOSED: Normal operation, count failures
  → After N failures in window: OPEN

OPEN: Fail fast, no requests to downstream
  → After timeout: HALF_OPEN

HALF_OPEN: Allow limited requests
  → If success: CLOSED
  → If failure: OPEN
```

### Request/Response Transformation

```yaml
# Add headers
add_headers:
  - name: X-Request-ID
    value: $uuid
  - name: X-Forwarded-For
    value: $client_ip

# Transform body
transform:
  request:
    rename: { "userName": "user_name" }
  response:
    remove: ["internal_id", "debug_info"]
```
