# Progress: Optimize SKU Search Query

> **Issue**: #14 - Optimizar query de SKU_Search (2→1 query)
> **Branch**: `feature/14-optimize-sku-search-query`
> **Started**: 2025-01-29

---

## Task Status

| Phase | Task | Status | PR |
|-------|------|--------|-----|
| **Phase 1** | Implementation | | |
| | Task 1.1: Optimize with CASE | ⏳ Pending | - |
| **Phase 2** | Testing | | |
| | Task 2.1: Unit tests | ⏳ Pending | - |
| | Task 2.2: Integration tests | ⏳ Pending | - |
| **Phase 3** | Validation | | |
| | Task 3.1: Performance validation | ⏳ Pending | - |

**Legend**: ⏳ Pending | 🚧 In Progress | ✅ Complete | ❌ Blocked

---

## Current Task

### 🚧 None - Ready to start

**Next Task**: Task 1.1 - Optimize get_matching_product_ids() with CASE

---

## Completed Work

*None yet - implementation not started*

---

## Blockers

*No blockers identified*

---

## Session Notes

### Session 1 (2025-01-29)
- Created branch `feature/14-optimize-sku-search-query`
- Analyzed issue #14 requirements
- Reviewed existing code in `includes/class-sku-search.php`
- Identified 2 sequential queries in `get_matching_product_ids()`:
  1. Query 1 (lines 72-77): Busca post_ids en wp_postmeta donde meta_key='_sku'
  2. Query 2 (lines 93-94): Busca post_type y post_parent en wp_posts
  3. PHP foreach (lines 96-102): Filtra variaciones para usar post_parent
  4. array_unique (line 104): Elimina duplicados
- Created SDD documentation:
  - `spec.md` - User story, requirements, test scenarios
  - `plan.md` - Architecture, SQL optimization strategy, code comparison
  - `tasks.md` - 4 tasks broken down by phase
  - `progress.md` - This file

### Key Design Decisions Made
1. Use CASE statement in SQL (instead of PHP processing)
2. DISTINCT for duplicate handling (replaces array_unique)
3. Maintain backward compatibility - same method signature and return type
4. Product variations return parent ID when post_parent > 0

### Target SQL Query
```sql
SELECT DISTINCT CASE
    WHEN p.post_type = 'product_variation' AND p.post_parent > 0
    THEN p.post_parent
    ELSE p.ID
END as product_id
FROM wp_postmeta pm
INNER JOIN wp_posts p ON pm.post_id = p.ID
WHERE pm.meta_key = '_sku'
AND pm.meta_value LIKE '%term%'
```

### WooCommerce Product Types
- **product**: Producto simple → retorna su propio ID
- **product_variation**: Variación → retorna ID del padre (post_parent)

### Next Steps
1. Start with Task 1.1 (optimize get_matching_product_ids method)
2. Follow TDD approach
3. Use `wordpress-dev` agent for implementation
4. Run `composer test` after each task

---

## Files to Modify

| File | Changes | Task |
|------|---------|------|
| `includes/class-sku-search.php` | Replace 2 queries + PHP processing with 1 CASE query | 1.1 |
| `tests/unit/SkuSearchTest.php` | Add/update unit tests | 2.1 |
| `tests/integration/SkuSearchTest.php` | Verify existing tests pass | 2.2 |

---

## Expected Performance Improvement

| Metric | Before | After | Improvement |
|--------|--------|-------|-------------|
| Database queries | 2 | 1 | 50% reduction |
| PHP processing | foreach loop | None | Eliminated |
| Execution time | Baseline | 30-40% less | Target |

---

## Reminders for Next Session

1. Start implementation with **Task 1.1**
2. Use **wordpress-dev** agent via Task tool
3. Follow **TDD**: write test first, then implement
4. Run `composer test` after each task
5. Update this progress file as tasks complete

---

## Commands Reference

```bash
# Run all tests
composer test

# Run specific test file
composer test -- tests/integration/SkuSearchTest.php

# Run tests with coverage
composer test-coverage
```
