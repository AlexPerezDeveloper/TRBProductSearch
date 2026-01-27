# Cloud Architecture Patterns

## Provider Comparison

### Compute

| Service Type | AWS        | GCP             | Azure               |
| ------------ | ---------- | --------------- | ------------------- |
| VMs          | EC2        | Compute Engine  | Virtual Machines    |
| Containers   | ECS, EKS   | GKE, Cloud Run  | AKS, Container Apps |
| Serverless   | Lambda     | Cloud Functions | Functions           |
| App Platform | App Runner | App Engine      | App Service         |

### Storage

| Service Type | AWS     | GCP             | Azure           |
| ------------ | ------- | --------------- | --------------- |
| Object       | S3      | Cloud Storage   | Blob Storage    |
| Block        | EBS     | Persistent Disk | Managed Disks   |
| File         | EFS     | Filestore       | Files           |
| Archive      | Glacier | Archive Storage | Archive Storage |

### Database

| Service Type   | AWS         | GCP                 | Azure           |
| -------------- | ----------- | ------------------- | --------------- |
| Relational     | RDS, Aurora | Cloud SQL, AlloyDB  | SQL Database    |
| NoSQL          | DynamoDB    | Firestore, Bigtable | Cosmos DB       |
| Cache          | ElastiCache | Memorystore         | Cache for Redis |
| Data Warehouse | Redshift    | BigQuery            | Synapse         |

## Reliability Patterns

### Load Balancing

```
Layer 4 (TCP/UDP):
- Faster, protocol-agnostic
- Use: Database connections, gaming

Layer 7 (HTTP/HTTPS):
- Content-based routing
- SSL termination
- Use: Web applications

Algorithms:
- Round Robin: Simple, equal distribution
- Least Connections: Best for varying request times
- IP Hash: Session affinity
- Weighted: Different capacity servers
```

### Auto Scaling

```yaml
# Target-based scaling
target_tracking:
  metric: CPUUtilization
  target: 70%

# Step scaling
step_scaling:
  - threshold: 70%
    adjustment: +2
  - threshold: 90%
    adjustment: +4

# Predictive scaling
predictive:
  mode: forecast_and_scale
  max_capacity_buffer: 20%
```

### Health Checks

```yaml
health_check:
  path: /health
  interval: 30s
  timeout: 5s
  healthy_threshold: 2
  unhealthy_threshold: 3

# Deep health check response
{
  "status": "healthy",
  "checks": {
    "database": "ok",
    "cache": "ok",
    "external_api": "degraded"
  }
}
```

## Networking

### VPC Design

```
┌─────────────────────────────────────────────────────────────┐
│ VPC: 10.0.0.0/16                                            │
├───────────────────────────────┬─────────────────────────────┤
│ Availability Zone A           │ Availability Zone B         │
├───────────────────────────────┼─────────────────────────────┤
│ Public Subnet: 10.0.1.0/24    │ Public Subnet: 10.0.2.0/24  │
│ - Load Balancers              │ - Load Balancers            │
│ - NAT Gateway                 │ - NAT Gateway               │
├───────────────────────────────┼─────────────────────────────┤
│ Private Subnet: 10.0.10.0/24  │ Private Subnet: 10.0.20.0/24│
│ - Application Servers         │ - Application Servers       │
├───────────────────────────────┼─────────────────────────────┤
│ Data Subnet: 10.0.100.0/24    │ Data Subnet: 10.0.200.0/24  │
│ - Databases                   │ - Databases                 │
│ - Cache                       │ - Cache                     │
└───────────────────────────────┴─────────────────────────────┘
```

### Security Groups

```yaml
# Application tier
app_security_group:
  inbound:
    - source: load_balancer_sg
      port: 8080
      protocol: tcp
  outbound:
    - destination: database_sg
      port: 5432
    - destination: cache_sg
      port: 6379
    - destination: 0.0.0.0/0
      port: 443 # External APIs

# Database tier
database_security_group:
  inbound:
    - source: app_security_group
      port: 5432
  outbound:
    - destination: 0.0.0.0/0
      port: 443 # Backups, updates
```

## Serverless Patterns

### Event-Driven Architecture

```
┌─────────────┐     ┌──────────────┐     ┌─────────────┐
│ API Gateway │────→│ Lambda/Func  │────→│ Event Bus   │
└─────────────┘     └──────────────┘     └──────┬──────┘
                                                │
                    ┌───────────────────────────┼───────────────────────────┐
                    ↓                           ↓                           ↓
            ┌───────────────┐           ┌───────────────┐           ┌───────────────┐
            │ Order Service │           │ Email Service │           │ Analytics     │
            └───────────────┘           └───────────────┘           └───────────────┘
```

### Cold Start Mitigation

```yaml
# Provisioned concurrency (AWS Lambda)
provisioned_concurrency:
  min: 5
  max: 100
  schedule:
    - cron: "0 8 * * MON-FRI"  # Business hours
      instances: 20

# Best practices
- Keep deployment package small
- Initialize outside handler
- Use connection pooling
- Consider minimum instances feature
```

### DLQ (Dead Letter Queue)

```
Normal Flow:
Request → Queue → Lambda → Success

Failure Flow:
Request → Queue → Lambda → Fail (retry 3x) → DLQ

DLQ contains:
- Original message
- Error details
- Attempt count
- Timestamp

Handle DLQ:
- Alert on threshold
- Manual investigation
- Automated retry pipeline
```

## Cost Optimization

### Right-Sizing

```
Step 1: Collect metrics (2+ weeks)
  - CPU utilization
  - Memory usage
  - Network I/O

Step 2: Analyze patterns
  - Average vs Peak
  - Time-of-day variance

Step 3: Recommend changes
  - Downsize underutilized
  - Consider burstable types
  - Use auto-scaling
```

### Reserved & Spot

| Strategy      | Discount | Commitment | Use Case                  |
| ------------- | -------- | ---------- | ------------------------- |
| On-Demand     | 0%       | None       | Unpredictable, short-term |
| Reserved      | 30-60%   | 1-3 years  | Steady baseline           |
| Savings Plans | 20-50%   | 1-3 years  | Flexible commitment       |
| Spot          | 60-90%   | None       | Fault-tolerant, flexible  |

### Cost Allocation

```yaml
tagging_strategy:
  required_tags:
    - Environment: [dev, staging, prod]
    - Team: [platform, payments, growth]
    - Service: [api, worker, web]
    - CostCenter: [product, infrastructure]

  automation:
    - Enforce via IAM policies
    - Auto-tag from Terraform
    - Schedule cleanup for untagged
```

## Multi-Region Patterns

### Active-Passive

```
Region A (Active):        Region B (Passive):
┌──────────────────┐     ┌──────────────────┐
│ Load Balancer    │     │ Load Balancer    │ (standby)
│ App Servers      │────→│ App Servers      │ (scaled down)
│ Database (RW)    │────→│ Database (RO)    │
└──────────────────┘     └──────────────────┘

Failover: DNS switch, promote replica
RPO: Minutes | RTO: Minutes to hours
```

### Active-Active

```
Region A:                 Region B:
┌──────────────────┐     ┌──────────────────┐
│ Load Balancer    │←───→│ Load Balancer    │
│ App Servers      │     │ App Servers      │
│ Database (Multi) │←───→│ Database (Multi) │
└──────────────────┘     └──────────────────┘
        ↑                        ↑
        └────── Global LB ───────┘
                (GeoDNS)

Requires: Multi-region database, conflict resolution
RPO: Near-zero | RTO: Near-zero
```
