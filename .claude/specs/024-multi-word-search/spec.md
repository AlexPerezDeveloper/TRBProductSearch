# Feature Specification: Multi-Word Search Support

> **Spec Version**: v1.0.0 | **Status**: Draft | **Last Updated**: 2026-01-30

---

## Metadata

| Field | Value |
|-------|-------|
| **Spec ID** | 024-multi-word-search |
| **Version** | v1.0.0 |
| **Status** | Draft |
| **Last Updated** | 2026-01-30 |
| **Author** | TRB Development Team |
| **Related Epic/Story** | GitHub Issue #24 |
| **Estimated Complexity** | Medium |
| **Target Completion** | 2026-02-03 |

---

## Version History

| Version | Date | Changes | Author |
|---------|------|---------|--------|
| v1.0.0 | 2026-01-30 | Initial specification | TRB Dev Team |

---

## 1. User Story

**As a** shopper on the WooCommerce store
**I want** to search for products using multiple words (e.g., "disco duro ssd")
**So that** I can find products that match all the terms I'm looking for, improving my shopping experience

---

## 2. Stakeholders

| Role | Description | Impact/Scale | Success For Them |
|------|-------------|--------------|------------------|
| **Primary** | Online shoppers | 100% of search users | Find products with descriptive searches |
| **Secondary** | Store owners | All WooCommerce stores | Higher conversion rates, better UX |
| **Tertiary** | Development team | Maintainers | Maintainable, tested code |

---

## 3. Success Criteria

| ID | Criterion | Metric | Target | How to Measure |
|----|-----------|--------|--------|----------------|
| SC-1 | Multi-word search returns results | Success rate | > 95% | Integration tests |
| SC-2 | Search performance | Query time | < 200ms | Performance tests |
| SC-3 | Backward compatibility | Single-word searches | 100% pass | Regression tests |
| SC-4 | Result relevance | Exact matches first | Top 3 positions | Manual verification |

---

## 4. Functional Requirements

### 4.1 Core Features

| ID | Requirement | Acceptance Criteria | Priority |
|----|-------------|---------------------|----------|
| FR-1 | Split search terms | Search query "disco duro ssd" splits into ["disco", "duro", "ssd"] | Must |
| FR-2 | AND logic search | Products must contain ALL words to appear in results | Must |
| FR-3 | Relevance ordering | Exact phrase matches appear first, then partial matches | Should |
| FR-4 | Works with SKU search | Multi-word search also checks SKU field | Must |
| FR-5 | Works with attributes | Multi-word search also checks product attributes | Must |
| FR-6 | Configurable logic | Admin can choose AND or OR logic in settings | Could |

### 4.2 Edge Cases

| ID | Edge Case | Expected Behavior |
|----|-----------|-------------------|
| EC-1 | Single word search | Works exactly as before (backward compatible) |
| EC-2 | Empty search term | Returns no results gracefully |
| EC-3 | Special characters | Handles quotes, hyphens, apostrophes correctly |
| EC-4 | Very long queries (10+ words) | Truncates or limits to prevent performance issues |
| EC-5 | Stop words ("el", "la", "de") | Ignores common Spanish stop words |

---

## 5. Non-Functional Requirements

### 5.1 Performance
- **Response Time**: AJAX search < 200ms at p95
- **Database**: No more than 3 queries per search
- **Memory**: Peak memory < 64MB per request

### 5.2 Compatibility
- **WordPress**: 6.4+
- **WooCommerce**: 8.0+
- **PHP**: 8.0+

---

## 6. Explicit Constraints (DO NOT)

- ❌ **DO NOT** implement full-text search with MySQL MATCH/AGAINST (requires schema changes)
- ❌ **DO NOT** build a separate search index table
- ❌ **DO NOT** modify existing database tables
- ❌ **DO NOT** break existing single-word search functionality
- ❌ **DO NOT** implement fuzzy matching or typo correction in this feature

---

## 7. Technical Context

### 7.1 Current Stack

| Layer | Technology | Notes |
|-------|------------|-------|
| **CMS** | WordPress 6.4+ | Must use WP_Query |
| **E-commerce** | WooCommerce 8.0+ | Product CPT, meta tables |
| **Language** | PHP 8.0+ | Namespaced classes |
| **Frontend** | jQuery | AJAX search handler |

### 7.2 Integration Points

| Integration | Type | Existing Pattern | Notes |
|-------------|------|------------------|-------|
| SKU_Search | Class | Singleton with `get_matching_product_ids()` | Extend to support multi-word |
| Attributes_Search | Class | Singleton with `get_matching_product_ids()` | Extend to support multi-word |
| Search_Query | Class | Main search orchestrator | Modify term splitting logic |
| Ajax_Handler | Class | Processes search requests | May need term preprocessing |

### 7.3 Architecture Constraints

- Must use existing `pre_get_posts` hook pattern
- Must follow singleton pattern for new classes
- Must maintain backward compatibility with existing filters
- Must work with existing cache system

---

## 8. Test Scenarios (Given/When/Then)

### Happy Path

**Scenario 1: Three-word search**
```
Given products exist with titles "Disco Duro SSD 1TB", "Disco SSD Externo", "Duro Funda"
When user searches for "disco duro ssd"
Then results include "Disco Duro SSD 1TB" and "Disco SSD Externo"
And "Disco Duro SSD 1TB" appears first (exact match)
```

**Scenario 2: Two-word search**
```
Given products exist with titles "Zapatillas Running", "Zapatillas Casual", "Running Medias"
When user searches for "zapatillas running"
Then only "Zapatillas Running" appears in results
```

**Scenario 3: Single word still works**
```
Given products exist with titles "Camiseta Roja", "Camiseta Azul"
When user searches for "camiseta"
Then both products appear in results
```

### Edge Cases

**Scenario: Search with stop words**
```
Given products exist with titles "Camiseta de Algodón"
When user searches for "camiseta de algodón"
Then product appears (ignoring "de")
```

**Scenario: Empty search**
```
Given user enters empty search
When search is submitted
Then no results are returned gracefully
```

---

## 9. Open Questions / Assumptions

| ID | Question | Impact | Assumption (if unresolved) | Owner |
|----|----------|--------|---------------------------|-------|
| OQ-1 | Should we support OR logic as alternative? | Feature scope | Start with AND only, add OR later if requested | Product |
| OQ-2 | Minimum word length to consider? | Search quality | 2 characters minimum | Dev Team |

---

## 10. Dependencies

| Dependency | Type | Status | Blocker? |
|------------|------|--------|----------|
| Issue #27 (redirect to native search) | Internal | Completed | No |
| SKU search implementation | Internal | Completed | No |
| Attributes search implementation | Internal | Completed | No |

---

## 11. Risks & Mitigation

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Performance degradation with many words | Medium | High | Limit to 5 words max, add caching |
| Breaking existing search | Low | High | Comprehensive regression tests |
| Conflicts with synonym feature | Medium | Medium | Test integration with synonyms |

---

## 12. Progress Tracking

- **Current Phase**: Specify
- **Overall Progress**: 10%
- **Active Task**: Creating specification
- **Blockers**: None

---

## Appendix: Reference Materials

- [GitHub Issue #24](https://github.com/AlexPerezDeveloper/TRBProductSearch/issues/24)
- [WordPress WP_Query Documentation](https://developer.wordpress.org/reference/classes/wp_query/)
- [WooCommerce Product Search](https://woocommerce.com/document/shop-page-search/)
