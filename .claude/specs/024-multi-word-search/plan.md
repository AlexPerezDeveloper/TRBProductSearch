# Technical Implementation Plan: Multi-Word Search Support

> **Based on Spec**: 024-multi-word-search v1.0.0
> **Plan Version**: v1.0.0 | **Last Updated**: 2026-01-30

---

## 1. Executive Summary

This plan outlines the implementation of multi-word search support for the TRB Product Search WooCommerce plugin. Currently, the search only handles single-word queries effectively. This enhancement will allow shoppers to search using multiple words (e.g., "disco duro ssd") with AND logic, returning only products that contain all search terms.

**What**: Extend the existing search architecture to split multi-word queries, apply AND logic across title/content/SKU/attributes, and maintain relevance ordering with exact phrase matches prioritized.

**How**: Modify the `Search_Query` class to tokenize search terms, extend `SKU_Search` and `Attributes_Search` to support multi-word matching with intersection logic, and implement a relevance scoring system in the SQL query.

**Why this approach**: This design maintains backward compatibility by treating single-word searches exactly as before. It leverages existing patterns (singleton, hooks, `pre_get_posts`) while avoiding prohibited approaches (FULLTEXT, new tables). The AND logic with relevance scoring provides better user experience than OR logic, which would return too many irrelevant results.

---

## 2. Requirements Summary

### 2.1 Functional Requirements (from Spec)

| ID | Requirement | Technical Implication |
|----|-------------|----------------------|
| FR-1 | Split search terms - "disco duro ssd" → ["disco", "duro", "ssd"] | New tokenization utility needed; handle edge cases like extra spaces |
| FR-2 | AND logic - Products must contain ALL words | Modify SQL WHERE clause from OR to AND; handle SKU/attributes intersection |
| FR-3 | Relevance ordering - Exact phrase matches first | Implement scoring algorithm in ORDER BY clause |
| FR-4 | Works with SKU search | Extend `SKU_Search::get_matching_product_ids()` to accept array of terms |
| FR-5 | Works with attributes search | Extend `Attributes_Search::get_matching_product_ids()` to accept array of terms |
| FR-6 | Configurable logic (AND/OR) | Add settings option; default to AND logic |

### 2.2 Non-Functional Requirements (from Spec)

| Category | Requirement | Technical Approach |
|----------|-------------|-------------------|
| Performance | AJAX search < 200ms at p95 | Limit to 5 words max; optimize SQL with proper indexes; cache results |
| Compatibility | WordPress 6.4+, WooCommerce 8.0+, PHP 8.0+ | Use existing WP/WC APIs; type hints for PHP 8.0+ |
| Backward Compatibility | Single-word searches work exactly as before | Detect single-word queries and use existing code path |
| Memory | Peak memory < 64MB per request | Use efficient array operations; avoid loading full product objects |

---

## 3. Architecture Design

### 3.1 System Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                     Multi-Word Search Flow                       │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  [User Input] → [Tokenize] → [Search_Query] → [WP_Query]        │
│       ↓              ↓              ↓              ↓              │
│   "disco duro"  [disco,duro]   [SKU_Search]   [SQL Build]       │
│                                 [Attributes_Search]             │
│                                      ↓                          │
│                               [Intersection]                     │
│                                      ↓                          │
│                               [Relevance Score]                  │
│                                      ↓                          │
│                               [Results]                          │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 3.2 Component Architecture

| Component | Responsibility | Changes Required |
|-----------|---------------|------------------|
| `Search_Query` | Main orchestrator, term tokenization, AND logic coordination | Modify `search()` method; add `parse_search_terms()`; update `custom_search_filter()` |
| `SKU_Search` | Multi-word SKU matching with intersection logic | Extend `get_matching_product_ids()` to accept array; add `get_matching_product_ids_for_terms()` |
| `Attributes_Search` | Multi-word attribute matching with intersection logic | Extend `get_matching_product_ids()` to accept array; add `get_matching_product_ids_for_terms()` |
| `Ajax_Handler` | Preprocess search terms, validation | Update term validation; handle empty tokens |
| `Settings` | Add AND/OR logic configuration | New setting: `trb_search_logic` (and/or) |

### 3.3 Data Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                        Data Flow                                │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  1. User enters: "disco duro ssd"                               │
│     ↓                                                            │
│  2. Tokenize: ["disco", "duro", "ssd"]                          │
│     ↓                                                            │
│  3. Check synonyms for each term                                │
│     ↓                                                            │
│  4. For each term, get SKU matches → array of IDs               │
│     ↓                                                            │
│  5. For each term, get Attribute matches → array of IDs         │
│     ↓                                                            │
│  6. INTERSECTION: IDs present in ALL term results               │
│     ↓                                                            │
│  7. Build SQL with AND conditions for each term                 │
│     ↓                                                            │
│  8. Add relevance scoring (exact phrase = highest)              │
│     ↓                                                            │
│  9. Execute WP_Query with modified filters                      │
│     ↓                                                            │
│  10. Return ordered results                                      │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 4. Algorithm Design

### 4.1 Tokenization Strategy

```php
/**
 * Parse search terms into array of tokens.
 *
 * @param string $term Raw search term.
 * @return array Array of tokens (min 2 chars, stop words removed).
 */
private function parse_search_terms($term)
{
    // Normalize: lowercase, trim
    $term = mb_strtolower(trim($term));

    // Split on whitespace
    $tokens = preg_split('/\s+/', $term, -1, PREG_SPLIT_NO_EMPTY);

    // Filter: minimum 2 characters
    $tokens = array_filter($tokens, function($t) {
        return mb_strlen($t) >= 2;
    });

    // Remove stop words (Spanish)
    $stop_words = ['el', 'la', 'de', 'en', 'y', 'a', 'los', 'las', 'un', 'una'];
    $tokens = array_diff($tokens, $stop_words);

    // Limit to 5 words max (performance)
    $tokens = array_slice($tokens, 0, 5);

    return array_values($tokens);
}
```

### 4.2 AND Logic Implementation

**For Title/Content Search:**
```sql
-- Current (single word OR):
AND (
    (post_title LIKE '%term1%') OR
    (post_content LIKE '%term1%') OR
    (ID IN (sku_matches))
)

-- New (multi-word AND):
AND (
    (
        (post_title LIKE '%term1%' OR post_content LIKE '%term1%')
        AND (post_title LIKE '%term2%' OR post_content LIKE '%term2%')
        AND (post_title LIKE '%term3%' OR post_content LIKE '%term3%')
    )
    OR (ID IN (intersection_of_sku_and_attr_matches))
)
```

**For SKU/Attributes Intersection:**
```php
// For each term, get matching IDs
$term_results = [];
foreach ($terms as $term) {
    $sku_ids = $this->sku_search->get_matching_product_ids($term);
    $attr_ids = $this->attributes_search->get_matching_product_ids($term);
    $term_results[] = array_unique(array_merge($sku_ids, $attr_ids));
}

// Intersection: IDs present in ALL term results
$intersection = $term_results[0];
for ($i = 1; $i < count($term_results); $i++) {
    $intersection = array_intersect($intersection, $term_results[$i]);
}
```

### 4.3 Relevance Scoring Algorithm

```sql
ORDER BY
    -- Priority 1: Exact phrase match in title
    CASE WHEN post_title LIKE '%disco duro ssd%' THEN 3 ELSE 0 END DESC,

    -- Priority 2: All words in title (any order)
    CASE WHEN (
        post_title LIKE '%disco%' AND
        post_title LIKE '%duro%' AND
        post_title LIKE '%ssd%'
    ) THEN 2 ELSE 0 END DESC,

    -- Priority 3: Some words in title
    CASE WHEN post_title LIKE '%disco%' THEN 1 ELSE 0 END +
    CASE WHEN post_title LIKE '%duro%' THEN 1 ELSE 0 END +
    CASE WHEN post_title LIKE '%ssd%' THEN 1 ELSE 0 END DESC,

    -- Priority 4: SKU exact match
    CASE WHEN mt_sku.meta_value = 'disco duro ssd' THEN 1 ELSE 0 END DESC,

    -- Final tie-breaker: alphabetical
    post_title ASC
```

---

## 5. API/Interface Changes

### 5.1 Modified Methods

| Class | Method | Change |
|-------|--------|--------|
| `Search_Query` | `search($term)` | Add tokenization; handle multi-word logic; pass array to SKU/Attributes classes |
| `Search_Query` | `custom_search_filter($search, $wp_query)` | Build AND conditions for each term; add relevance scoring |
| `Search_Query` | `priority_orderby($orderby, $wp_query)` | Enhance with multi-word relevance scoring |
| `SKU_Search` | `get_matching_product_ids($term)` | Accept string or array; maintain backward compatibility |
| `Attributes_Search` | `get_matching_product_ids($term)` | Accept string or array; maintain backward compatibility |

### 5.2 New Methods

| Class | Method | Purpose |
|-------|--------|---------|
| `Search_Query` | `parse_search_terms($term)` | Tokenize and clean search terms |
| `Search_Query` | `get_intersecting_product_ids($terms)` | Get IDs that match ALL terms across SKU/attributes |
| `Search_Query` | `build_relevance_orderby($terms)` | Build relevance scoring SQL |
| `SKU_Search` | `get_matching_product_ids_for_terms($terms)` | Get intersection of SKU matches for all terms |
| `Attributes_Search` | `get_matching_product_ids_for_terms($terms)` | Get intersection of attribute matches for all terms |

### 5.3 Backward Compatibility

```php
// SKU_Search::get_matching_product_ids() maintains BC
public function get_matching_product_ids($term)
{
    // If array passed, delegate to new method
    if (is_array($term)) {
        return $this->get_matching_product_ids_for_terms($term);
    }

    // Existing single-term logic unchanged
    // ...
}
```

---

## 6. Implementation Phases

### Phase 1: Foundation (Day 1)

- [ ] Create `parse_search_terms()` utility method in `Search_Query`
- [ ] Add unit tests for tokenization (edge cases, stop words, limits)
- [ ] Extend `SKU_Search` with `get_matching_product_ids_for_terms()`
- [ ] Extend `Attributes_Search` with `get_matching_product_ids_for_terms()`

### Phase 2: Core AND Logic (Day 1-2)

- [ ] Modify `Search_Query::search()` to detect multi-word queries
- [ ] Implement intersection logic for SKU/attributes matches
- [ ] Update `custom_search_filter()` to build AND conditions
- [ ] Add backward compatibility check for single-word queries

### Phase 3: Relevance Scoring (Day 2)

- [ ] Implement `build_relevance_orderby()` method
- [ ] Update `priority_orderby()` to use relevance scoring
- [ ] Test exact phrase matching priority
- [ ] Test partial word matching ordering

### Phase 4: Settings & Configuration (Day 2-3)

- [ ] Add `trb_search_logic` option (and/or) to Settings class
- [ ] Create admin UI for search logic selection
- [ ] Update `Search_Query` to respect logic setting
- [ ] Default to AND logic for new installations

### Phase 5: Integration & Testing (Day 3)

- [ ] Update `Ajax_Handler` validation for multi-word queries
- [ ] Integration tests for full search flow
- [ ] Performance testing with 5+ word queries
- [ ] Regression testing for single-word searches

---

## 7. Testing Strategy

### 7.1 Unit Tests

| Module | Coverage Target | Key Tests |
|--------|----------------|-----------|
| `parse_search_terms()` | 100% | Empty string, single word, multiple words, extra spaces, special chars, stop words, >5 words limit |
| `get_intersecting_product_ids()` | 90% | Empty terms, single term, multiple terms with overlap, no overlap |
| `build_relevance_orderby()` | 90% | SQL generation, injection prevention, term escaping |

### 7.2 Integration Tests

| Scenario | Test Type | Expected Result |
|----------|-----------|-----------------|
| "disco duro ssd" with all words in title | Happy path | Product appears, scored highest |
| "disco duro ssd" with words in content only | Happy path | Product appears, lower score |
| "disco duro ssd" with words in SKU | Happy path | Product appears via SKU match |
| "disco duro ssd" with words in attributes | Happy path | Product appears via attribute match |
| "disco duro" with only "disco" present | Negative | Product does NOT appear (AND logic) |
| "camiseta" (single word) | Regression | Works exactly as before |
| Empty search | Edge case | Returns no results gracefully |
| "el la de" (only stop words) | Edge case | Returns no results |
| "a b c d e f g" (7 words) | Edge case | Only first 5 words used |

### 7.3 Performance Tests

| Test | Target | Measurement |
|------|--------|-------------|
| 3-word search | < 100ms | Query execution time |
| 5-word search | < 150ms | Query execution time |
| Large catalog (10K products) | < 200ms | Query execution time |
| Memory usage | < 32MB | Peak memory per request |

---

## 8. Security Considerations

### 8.1 Input Validation

| Input | Validation | Sanitization |
|-------|------------|--------------|
| Search term | `sanitize_text_field()` | Remove HTML, limit length to 100 chars |
| Tokenized terms | `esc_sql()` + `$wpdb->esc_like()` | Prevent SQL injection in LIKE clauses |
| Settings | `sanitize_text_field()` | Only allow 'and' or 'or' values |

### 8.2 SQL Injection Prevention

```php
// All terms must be escaped before SQL construction
$safe_term = esc_sql($wpdb->esc_like($term));

// Use $wpdb->prepare() for dynamic queries
$sql = $wpdb->prepare("... LIKE %s", '%' . $like_term . '%');
```

---

## 9. Open Technical Questions

| ID | Question | Impact | Decision Needed |
|----|----------|--------|-----------------|
| TQ-1 | Should we cache individual term results to improve multi-word performance? | Performance vs complexity | Measure after implementation; add if needed |
| TQ-2 | How to handle synonym expansion with multi-word (each word's synonyms)? | Search quality | Expand synonyms for each token, then deduplicate |
| TQ-3 | Should OR logic be implemented as fallback when AND returns no results? | UX vs performance | Start with AND only; add OR fallback if user feedback requests it |

---

## 10. Architecture Decision Records

### ADR-001: AND Logic as Default

**Date**: 2026-01-30
**Status**: Proposed

**Context**: Multi-word search can use AND (all words required) or OR (any word sufficient) logic. AND provides more relevant results but may return fewer matches. OR returns more results but with lower relevance.

**Decision**: Implement AND logic as default, with OR as configurable option.

**Consequences**:
- Positive: Better search relevance, matches user expectations for e-commerce
- Positive: Reduces noise in results
- Negative: May return empty results for overly specific queries
- Neutral: Can be changed by admin in settings

**Alternatives Considered**:
1. OR logic default - Rejected: Returns too many irrelevant results
2. Smart switching (AND first, OR if no results) - Rejected: Adds complexity; may confuse users with inconsistent behavior

### ADR-002: Intersection Approach for SKU/Attributes

**Date**: 2026-01-30
**Status**: Proposed

**Context**: For multi-word search across SKU and attributes, we need products that match ALL terms. Options: (1) Complex SQL with multiple JOINs, (2) PHP intersection of individual term results.

**Decision**: Use PHP intersection of individual term results.

**Rationale**:
- Simpler SQL reduces risk of performance issues
- Existing `get_matching_product_ids()` can be reused
- Easier to maintain and debug
- Caching can be added at term level if needed

**Consequences**:
- Positive: Simpler implementation, reusable code
- Negative: More database queries (one per term)
- Mitigation: Limit to 5 terms max; results are cached

---

## 11. References

- [Spec Document](./spec.md)
- [WordPress WP_Query Documentation](https://developer.wordpress.org/reference/classes/wp_query/)
- [WooCommerce Product Search](https://woocommerce.com/document/shop-page-search/)
- File: `/home/raskardev/proyectos/trb/TRBProductSearch/includes/class-search-query.php`
- File: `/home/raskardev/proyectos/trb/TRBProductSearch/includes/class-sku-search.php`
- File: `/home/raskardev/proyectos/trb/TRBProductSearch/includes/class-attributes-search.php`
