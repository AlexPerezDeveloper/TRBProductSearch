# Security Architecture Checklist

## Authentication

### Identity Providers

| Type             | Use Case               | Examples             |
| ---------------- | ---------------------- | -------------------- |
| OAuth 2.0 / OIDC | Web/Mobile apps, SSO   | Auth0, Okta, Cognito |
| SAML             | Enterprise SSO         | Azure AD, Okta       |
| API Keys         | Service-to-service     | UUID, random strings |
| mTLS             | High-security services | Certificate-based    |

### Token Best Practices

```yaml
access_token:
  expires: 15 minutes # Short-lived
  storage: memory # Never localStorage

refresh_token:
  expires: 7 days
  storage: httpOnly cookie
  rotation: true # One-time use

jwt_claims:
  required: [sub, exp, iat, iss, aud]
  avoid: [sensitive data, PII]
```

### MFA Implementation

```
Risk-Based MFA:
┌─────────────┬───────────────────────────────┐
│ Risk Level  │ Action                        │
├─────────────┼───────────────────────────────┤
│ Low         │ Password only                 │
│ Medium      │ + Soft token (TOTP)           │
│ High        │ + Hard token (FIDO2/WebAuthn) │
│ Critical    │ + Biometric + Admin approval  │
└─────────────┴───────────────────────────────┘

Risk Signals:
- New device/location
- Unusual time
- Suspicious IP
- Sensitive operation
```

## Authorization

### RBAC (Role-Based Access Control)

```yaml
roles:
  admin:
    permissions: [read, write, delete, manage_users]
  editor:
    permissions: [read, write]
  viewer:
    permissions: [read]

assignment:
  user_123: [editor, viewer] # Additive
```

### ABAC (Attribute-Based Access Control)

```yaml
policy:
  name: "Document Access"
  condition: |
    user.department == resource.department AND
    user.clearance_level >= resource.classification AND
    request.time.hour BETWEEN 9 AND 18

attributes:
  user: [department, role, clearance_level]
  resource: [owner, department, classification]
  environment: [time, ip_address, device_type]
```

### Policy as Code

```rego
# Open Policy Agent (OPA) example
package authz

default allow = false

allow {
  input.user.role == "admin"
}

allow {
  input.action == "read"
  input.resource.owner == input.user.id
}

allow {
  input.action == "read"
  input.resource.visibility == "public"
}
```

## Data Protection

### Encryption Standards

| Data State | Method            | Standard         |
| ---------- | ----------------- | ---------------- |
| At Rest    | AES-256           | AES-GCM          |
| In Transit | TLS 1.3           | Minimum TLS 1.2  |
| In Use     | Application-level | Field encryption |

### Key Management

```yaml
key_hierarchy:
  master_key:
    storage: HSM / Cloud KMS
    rotation: Never (or annually with ceremony)

  data_encryption_key:
    encrypted_by: master_key
    rotation: Quarterly

  envelope_encryption:
    - DEK encrypts data
    - KEK encrypts DEK
    - Master key encrypts KEK
```

### Secrets Management

```
❌ Never:
   - Hardcoded in code
   - Environment variables in code
   - Git history
   - Logs

✅ Always:
   - Vault / Cloud Secrets Manager
   - Encrypted at rest
   - Audit logging
   - Automatic rotation
   - Just-in-time access
```

## Input Validation

### Validation Layers

```
Layer 1: Edge (API Gateway)
  - Rate limiting
  - Basic format validation
  - Size limits

Layer 2: Application
  - Schema validation
  - Business rules
  - Sanitization

Layer 3: Database
  - Constraints
  - Parameterized queries
  - Type checking
```

### Common Vulnerabilities

| Vulnerability  | Prevention                        |
| -------------- | --------------------------------- |
| SQL Injection  | Parameterized queries, ORM        |
| XSS            | Output encoding, CSP              |
| CSRF           | CSRF tokens, SameSite cookies     |
| Path Traversal | Whitelist paths, sanitize         |
| SSRF           | Allowlist URLs, disable redirects |
| XXE            | Disable external entities         |

### Content Security Policy

```http
Content-Security-Policy:
  default-src 'self';
  script-src 'self' 'nonce-{random}';
  style-src 'self' 'unsafe-inline';
  img-src 'self' data: https:;
  connect-src 'self' api.example.com;
  frame-ancestors 'none';
  form-action 'self';
```

## Network Security

### Defense in Depth

```
┌─────────────────────────────────────────────────────────┐
│ WAF: SQL injection, XSS, rate limiting                   │
├─────────────────────────────────────────────────────────┤
│ DDoS Protection: L3/L4/L7                               │
├─────────────────────────────────────────────────────────┤
│ API Gateway: Auth, throttling, validation               │
├─────────────────────────────────────────────────────────┤
│ Service Mesh: mTLS, network policies                    │
├─────────────────────────────────────────────────────────┤
│ Application: Input validation, auth                      │
├─────────────────────────────────────────────────────────┤
│ Database: Least privilege, encryption                    │
└─────────────────────────────────────────────────────────┘
```

### Zero Trust Principles

```yaml
principles:
  verify_explicitly:
    - Always authenticate
    - Always authorize
    - Use all available signals

  least_privilege:
    - Just-in-time access
    - Just-enough access
    - Risk-based adaptive policies

  assume_breach:
    - Minimize blast radius
    - Segment access
    - Encrypt all traffic
    - Continuous monitoring
```

## Audit & Compliance

### Logging Requirements

```yaml
security_events:
  mandatory:
    - Authentication (success/failure)
    - Authorization decisions
    - Privilege changes
    - Data access (especially PII)
    - Admin actions

  format:
    timestamp: ISO 8601
    user_id: hashed or pseudonymized
    action: enum
    resource: identifier
    outcome: success/failure
    ip_address: included

  retention:
    security_logs: 1 year minimum
    access_logs: 90 days
    audit_logs: 7 years (regulatory)
```

### Compliance Mapping

| Requirement         | GDPR | SOC 2 | HIPAA | PCI DSS |
| ------------------- | ---- | ----- | ----- | ------- |
| Data encryption     | ✓    | ✓     | ✓     | ✓       |
| Access control      | ✓    | ✓     | ✓     | ✓       |
| Audit logging       | ✓    | ✓     | ✓     | ✓       |
| Incident response   | ✓    | ✓     | ✓     | ✓       |
| Data retention      | ✓    |       | ✓     | ✓       |
| Right to erasure    | ✓    |       |       |         |
| Breach notification | ✓    |       | ✓     | ✓       |

## Review Checklist

Before deployment, verify:

- [ ] **Authentication** - Strong, multi-factor where needed
- [ ] **Authorization** - Least privilege, policy-based
- [ ] **Encryption** - At rest and in transit
- [ ] **Secrets** - Managed securely, rotated
- [ ] **Input validation** - All entry points covered
- [ ] **Logging** - Security events captured
- [ ] **Dependencies** - Scanned, updated
- [ ] **Network** - Segmented, firewalled
- [ ] **Incident response** - Plan documented, tested
- [ ] **Compliance** - Requirements mapped and met
