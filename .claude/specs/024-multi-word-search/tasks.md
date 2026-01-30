# Implementation Tasks: Multi-Word Search Support

> **Based on Spec**: 024-multi-word-search v1.0.0
> **Based on Plan**: v1.0.0
> **Last Updated**: 2026-01-30

---

## Task Overview

| Task ID | Title | Estimated | Dependencies | Status |
|---------|-------|-----------|--------------|--------|
| T1 | Create tokenization utility | 2h | None | Pending |
| T2 | Extend SKU_Search for multi-word | 3h | T1 | Pending |
| T3 | Extend Attributes_Search for multi-word | 3h | T1 | Pending |
| T4 | Implement AND logic in Search_Query | 4h | T2, T3 | Pending |
| T5 | Add relevance scoring | 3h | T4 | Pending |
| T6 | Add settings for AND/OR logic | 2h | T4 | Pending |
| T7 | Update Ajax_Handler validation | 1h | T1 | Pending |
| T8 | Write integration tests | 4h | T4, T5, T6 | Pending |
| T9 | Performance testing | 2h | All above | Pending |

---

## Task 1: Create Tokenization Utility

**Spec Reference**: FR-1, SC-1
**Dependencies**: None
**Estimated**: 2 hours

### Description
Create a utility method in `Search_Query` class to parse and tokenize multi-word search terms. This includes normalization, stop word removal, and length filtering.

### Implementation Details
- Add `parse_search_terms($term)` method to `Search_Query` class
- Normalize: lowercase, trim whitespace
- Split on whitespace using `preg_split('/\s+/', ...)`
- Filter: minimum 2 characters
- Remove Spanish stop words: ['el', 'la', 'de', 'en', 'y', 'a', 'los', 'las', 'un', 'una', 'del', 'al', 'con', 'por', 'para']
- Limit to 5 words maximum
- Return array of clean tokens

### Definition of Done
- [ ] Method implemented in `includes/class-search-query.php`
- [ ] Unit tests for tokenization in `tests/unit/SearchQueryTest.php`:
  - Empty string returns empty array
  - Single word returns array with one element
  - Multiple words split correctly
  - Extra whitespace handled
  - Special characters preserved
  - Stop words removed
  - >5 words truncated to 5
  - Words <2 characters filtered out
- [ ] All tests passing

---

## Task 2: Extend SKU_Search for Multi-Word

**Spec Reference**: FR-4
**Dependencies**: T1
**Estimated**: 3 hours

### Description
Extend `SKU_Search` class to support multi-word matching with intersection logic. Products must match ALL terms to be included.

### Implementation Details
- Modify `get_matching_product_ids($term)` to accept string or array
- If array passed, delegate to new `get_matching_product_ids_for_terms($terms)` method
- New method:
  - For each term, call `get_matching_product_ids($term)` (single word)
  - Collect results in array of arrays
  - Return intersection of all arrays (products matching ALL terms)
- Maintain backward compatibility for single-word calls

### Definition of Done
- [ ] `SKU_Search::get_matching_product_ids()` accepts string or array
- [ ] New `get_matching_product_ids_for_terms()` method implemented
- [ ] Intersection logic working correctly
- [ ] Unit tests in `tests/unit/SKUSearchTest.php`:
  - Single word still works (backward compatibility)
  - Two words returns intersection
  - Three words returns intersection
  - No common results returns empty array
  - Empty terms returns empty array
- [ ] All tests passing

---

## Task 3: Extend Attributes_Search for Multi-Word

**Spec Reference**: FR-5
**Dependencies**: T1
**Estimated**: 3 hours

### Description
Extend `Attributes_Search` class to support multi-word matching with intersection logic, similar to SKU_Search.

### Implementation Details
- Modify `get_matching_product_ids($term)` to accept string or array
- If array passed, delegate to new `get_matching_product_ids_for_terms($terms)` method
- New method:
  - For each term, call `get_matching_product_ids($term)` (single word)
  - Collect results in array of arrays
  - Return intersection of all arrays (products matching ALL terms)
- Maintain backward compatibility for single-word calls

### Definition of Done
- [ ] `Attributes_Search::get_matching_product_ids()` accepts string or array
- [ ] New `get_matching_product_ids_for_terms()` method implemented
- [ ] Intersection logic working correctly
- [ ] Unit tests in `tests/unit/AttributesSearchTest.php`:
  - Single word still works (backward compatibility)
  - Two words returns intersection
  - Three words returns intersection
  - No common results returns empty array
  - Empty terms returns empty array
- [ ] All tests passing

---

## Task 4: Implement AND Logic in Search_Query

**Spec Reference**: FR-2, SC-1
**Dependencies**: T2, T3
**Estimated**: 4 hours

### Description
Modify `Search_Query` to detect multi-word queries and apply AND logic across title, content, SKU, and attributes.

### Implementation Details
- Modify `search($term)` method:
  - Detect if term contains multiple words using `parse_search_terms()`
  - If single word, use existing code path (backward compatibility)
  - If multiple words, use new multi-word logic
- Modify `custom_search_filter($search, $wp_query)`:
  - For multi-word: build SQL with AND conditions for each term
  - Example: `(title LIKE '%term1%') AND (title LIKE '%term2%')`
  - Include intersection of SKU and attribute matches with OR
- Add `get_intersecting_product_ids($terms)` method:
  - Get SKU matches for all terms (intersection)
  - Get attribute matches for all terms (intersection)
  - Merge and return unique IDs

### Definition of Done
- [ ] Multi-word detection in `search()` method
- [ ] AND logic SQL construction in `custom_search_filter()`
- [ ] `get_intersecting_product_ids()` method implemented
- [ ] Backward compatibility maintained for single-word searches
- [ ] Integration tests in `tests/integration/MultiWordSearchTest.php`:
  - "disco duro ssd" returns products with all three words
  - Product with only "disco" does NOT appear
  - Product with "disco" and "duro" but not "ssd" does NOT appear
  - Single word search still works as before
- [ ] All tests passing

---

## Task 5: Add Relevance Scoring

**Spec Reference**: FR-3, SC-4
**Dependencies**: T4
**Estimated**: 3 hours

### Description
Implement relevance scoring to prioritize exact phrase matches, then all words in title, then partial matches.

### Implementation Details
- Add `build_relevance_orderby($terms)` method:
  - Build SQL CASE statements for scoring
  - Priority 1: Exact phrase match in title (highest score)
  - Priority 2: All words in title (any order)
  - Priority 3: Count of matching words in title
  - Priority 4: SKU exact match
  - Final: Alphabetical tie-breaker
- Modify `priority_orderby($orderby, $wp_query)`:
  - If multi-word search, use `build_relevance_orderby()`
  - Otherwise, use existing logic

### SQL Structure
```sql
ORDER BY
    CASE WHEN post_title LIKE '%disco duro ssd%' THEN 100 ELSE 0 END DESC,
    CASE WHEN (post_title LIKE '%disco%' AND post_title LIKE '%duro%' AND post_title LIKE '%ssd%') THEN 50 ELSE 0 END DESC,
    (CASE WHEN post_title LIKE '%disco%' THEN 10 ELSE 0 END +
     CASE WHEN post_title LIKE '%duro%' THEN 10 ELSE 0 END +
     CASE WHEN post_title LIKE '%ssd%' THEN 10 ELSE 0 END) DESC,
    post_title ASC
```

### Definition of Done
- [ ] `build_relevance_orderby()` method implemented
- [ ] `priority_orderby()` updated to use relevance for multi-word
- [ ] Integration tests:
  - Exact phrase match appears first
  - All words in title appears before partial matches
  - Partial matches ordered by word count
- [ ] All tests passing

---

## Task 6: Add Settings for AND/OR Logic

**Spec Reference**: FR-6
**Dependencies**: T4
**Estimated**: 2 hours

### Description
Add admin setting to allow store owners to choose between AND and OR logic for multi-word searches.

### Implementation Details
- Add `trb_search_logic` option to `Settings` class
- Default value: 'and'
- Valid values: 'and', 'or'
- Add UI in admin settings page:
  - Radio buttons or select dropdown
  - Label: "Logic for multi-word searches"
  - Options: "AND (all words required)" / "OR (any word sufficient)"
  - Description explaining the difference
- Modify `Search_Query` to respect setting:
  - If 'or', use OR logic instead of AND
  - If 'and', use existing AND logic

### Definition of Done
- [ ] Setting registered and saved correctly
- [ ] Admin UI implemented
- [ ] `Search_Query` respects the setting
- [ ] OR logic implementation (alternative to AND)
- [ ] Tests for both logic modes
- [ ] All tests passing

---

## Task 7: Update Ajax_Handler Validation

**Spec Reference**: EC-1, EC-2
**Dependencies**: T1
**Estimated**: 1 hour

### Description
Update Ajax_Handler to properly validate and handle multi-word search terms.

### Implementation Details
- Review `Ajax_Handler::handle_search()` method
- Ensure `sanitize_text_field()` is applied to search term
- Handle edge case: search with only stop words (return empty)
- Handle edge case: search with only short words (<2 chars)
- Ensure proper error messages for edge cases

### Definition of Done
- [ ] Validation handles multi-word terms correctly
- [ ] Edge cases handled gracefully
- [ ] Error messages are user-friendly
- [ ] Tests for Ajax_Handler edge cases
- [ ] All tests passing

---

## Task 8: Write Integration Tests

**Spec Reference**: All SCs
**Dependencies**: T4, T5, T6
**Estimated**: 4 hours

### Description
Create comprehensive integration tests covering all scenarios for multi-word search.

### Test Scenarios

#### Happy Path Tests
1. **Three-word search in title**
   - Given: Products with titles containing "Disco Duro SSD", "Disco SSD", "Duro Funda"
   - Search: "disco duro ssd"
   - Expected: "Disco Duro SSD" appears, "Disco SSD" does NOT appear

2. **Two-word search across title and content**
   - Given: Product with "Disco" in title, "Duro" in content
   - Search: "disco duro"
   - Expected: Product appears

3. **Search in SKU**
   - Given: Product with SKU "DISCO-DURO-001"
   - Search: "disco duro"
   - Expected: Product appears

4. **Search in attributes**
   - Given: Product with color attribute "Rojo Intenso"
   - Search: "rojo intenso"
   - Expected: Product appears

#### Edge Cases
5. **Single word (regression)**
   - Search: "camiseta"
   - Expected: Works exactly as before

6. **Empty search**
   - Search: ""
   - Expected: No results, no errors

7. **Only stop words**
   - Search: "el la de"
   - Expected: No results (all words filtered)

8. **Very long query (7 words)**
   - Search: "a b c d e f g"
   - Expected: Only first 5 words used

9. **Special characters**
   - Search: "camiseta "roja""
   - Expected: Handles quotes correctly

#### Relevance Tests
10. **Exact phrase priority**
    - Given: "Disco Duro SSD 1TB", "Disco SSD Externo", "Duro SSD Rápido"
    - Search: "disco duro ssd"
    - Expected: "Disco Duro SSD 1TB" first (exact phrase)

### Definition of Done
- [ ] Test file created: `tests/integration/MultiWordSearchTest.php`
- [ ] All happy path scenarios covered
- [ ] All edge cases covered
- [ ] All relevance scenarios covered
- [ ] Both AND and OR logic tested
- [ ] All tests passing

---

## Task 9: Performance Testing

**Spec Reference**: SC-2
**Dependencies**: All above
**Estimated**: 2 hours

### Description
Verify performance requirements are met with multi-word search implementation.

### Performance Targets
| Metric | Target | Measurement |
|--------|--------|-------------|
| 3-word search | < 100ms | Query execution time |
| 5-word search | < 150ms | Query execution time |
| Memory usage | < 32MB | Peak memory per request |

### Testing Approach
- Create test with mock database of 1000+ products
- Measure query execution time for:
  - 2-word search
  - 3-word search
  - 5-word search
- Measure memory usage
- Document results

### Definition of Done
- [ ] Performance test file created: `tests/performance/MultiWordSearchPerformanceTest.php`
- [ ] All targets met or documented with mitigation plan
- [ ] No regressions in single-word search performance
- [ ] Results documented in `tests/performance/results.md`

---

## Summary

### Critical Path
```
T1 (Tokenization) → T2 (SKU) + T3 (Attributes) → T4 (AND Logic) → T5 (Relevance) → T8 (Tests)
```

### Parallel Work
- T6 (Settings) can be done in parallel with T5
- T7 (Ajax) can be done in parallel with T4
- T9 (Performance) must be last

### Total Estimated Time
**~24 hours** of development work

### Definition of Project Done
- [ ] All tasks T1-T9 completed
- [ ] All tests passing (unit + integration + performance)
- [ ] Code review approved
- [ ] Documentation updated
- [ ] PR created and merged
