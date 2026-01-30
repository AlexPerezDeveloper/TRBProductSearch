# Spec: Global Typo Tolerance and Fuzzy Search Results

> **Issue**: #7 - Feature: Implement Global Typo Tolerance and Fuzzy Search Results
> **Version**: 1.0.0
> **Status**: Draft

---

## User Story

**As a** tienda online con productos en español (e.g., "camiseta", "pantalón")
**I want** que el sistema detecte y corrija automáticamente errores tipográficos en las búsquedas
**So that** los usuarios encuentren productos incluso cuando escriben mal los términos (e.g., "stich" → "stitch", "camisa" → "camiseta")

---

## Stakeholders

| Role | Description | Impact |
|------|-------------|--------|
| **Primary** | Clientes de la tienda | Encuentran productos sin necesidad de re-escribir la búsqueda |
| **Secondary** | Dueño de la tienda | Menor tasa de rebote, mayor conversión |
| **Tertiary** | Equipo de soporte | Menos tickets de "no encuentro el producto" |

---

## Current State Analysis

### Existing Implementation
1. **Typo_Corrector class** (`class-typo-corrector.php`)
   - Uses Levenshtein distance for word correction
   - Indexes: titles, SKUs, attributes
   - Token-by-token correction for multi-word phrases

2. **Ajax_Handler** (`class-ajax-handler.php`)
   - ✅ Uses typo correction (lines 76-89)
   - ✅ Shows correction message in dropdown
   - ❌ Only triggers when 0 results found

3. **Search_Form** (`class-search-form.php`)
   - ❌ Does NOT use typo correction at all
   - ❌ No correction notice displayed

4. **Search_Results** (`class-search-results.php`)
   - ❌ No correction notice component

### Problems Identified
| Problem | Impact |
|---------|--------|
| Inconsistent behavior between AJAX and full-page search | Users confused why one works and the other doesn't |
| Typo correction is reactive (only after 0 results) | Users must wait for failure before correction |
| No visual feedback on main results page | Users don't know correction was applied |

---

## Functional Requirements

### FR-1: Unified Search Correction
Both AJAX dropdown and full-page search MUST use the same typo correction logic.

### FR-2: Automatic Fallback
When a search yields 0 results, the system MUST automatically attempt a search with the corrected term.

### FR-3: Correction Notice UX
When a correction is applied, a notice MUST be displayed:
- Spanish: *"No se encontraron resultados para '{original_term}'. Mostrando resultados para '{corrected_term}'"*
- English: *"No results found for '{original_term}'. Showing results for '{corrected_term}'"*

### FR-4: Shared Correction Logic
Ajax_Handler and Search_Form MUST share the same correction mechanism (no code duplication).

### FR-5: Minimum Length Threshold
Typo correction only applies for terms with 4+ characters (prevents over-correction of short partial searches).

---

## Success Criteria

| ID | Criterion | Metric | Target | How to Measure |
|----|-----------|--------|--------|----------------|
| SC-1 | Correction accuracy | True positives / Total corrections | > 85% | Integration tests with common typos |
| SC-2 | Zero-result reduction | Zero results before vs after | < 10% | Compare search logs |
| SC-3 | Correction notice display | Notice shown when correction applied | 100% | Visual regression tests |
| SC-4 | Code reuse | Shared correction logic | Single source | Code review (no duplication) |
| SC-5 | Test coverage | New code covered by tests | > 90% | PHPUnit coverage report |

---

## Test Scenarios (Given/When/Then)

### Happy Path: Single Word Typo
**Given** A product exists with title "Camiseta de algodón"
**When** User searches for "camisetta"
**Then** System shows results for "Camiseta de algodón"
**And** Notice displays: "No se encontraron resultados para 'camisetta'. Mostrando resultados para 'camiseta'"

### Happy Path: Multi-Word Typo
**Given** A product exists with title "Portátil Gaming 15 pulgadas"
**When** User searches for "portatil gamng"
**Then** System shows results for "Portátil Gaming 15 pulgadas"
**And** Notice displays: "No se encontraron resultados para 'portatil gamng'. Mostrando resultados para 'portátil gaming'"

### Edge Case: Short Search Term
**Given** Typo correction is enabled
**When** User searches for "cam" (3 characters)
**Then** Typo correction is NOT triggered
**And** System shows partial matches or "No results"

### Edge Case: No Correction Available
**Given** User searches for "xyz123invalid"
**When** No correction is found in dictionary
**Then** System shows "No products found"
**And** No correction notice is displayed

### Edge Case: Exact Match Exists
**Given** User searches for "camiseta" (exact match)
**When** Products exist with "camiseta"
**Then** Results are shown immediately
**And** No correction notice is displayed

### Edge Case: Accent Correction
**Given** User searches for "atletico" (without accent)
**When** Product exists with "atlético"
**Then** System shows results for "atlético"
**And** Correction notice may show accent correction

### Integration: AJAX Dropdown
**Given** User types "stich" in search box
**When** AJAX dropdown triggers
**Then** Dropdown shows products for "stitch"
**And** Notice appears in dropdown

### Integration: Full Page Results
**Given** User submits "stich" in search form
**When** Full page loads
**Then** Results page shows products for "stitch"
**And** Notice appears above results

---

## Explicit Constraints (DO NOT)

❌ **DO NOT** modify the existing Levenshtein algorithm (it works well)
❌ **DO NOT** add new settings to admin panel (use existing typo correction infrastructure)
❌ **DO NOT** implement "fuzzy/proactive" suggestions when results exist (out of scope for Phase 1)
❌ **DO NOT** change the index building process (current index is sufficient)
❌ **DO NOT** add external dependencies for typo correction

---

## Technical Context

### Existing Classes to Modify
| Class | Changes Required |
|-------|------------------|
| `Search_Query` | Add correction method, return correction metadata |
| `Search_Form` | Integrate typo correction, pass correction data to renderer |
| `Search_Results` | Add correction notice rendering |
| `Ajax_Handler` | Refactor to use shared correction logic |

### Existing Classes to Reference
| Class | Purpose |
|-------|---------|
| `Typo_Corrector` | Provides `correct()` method - use as-is |
| `Search_Query` | Main search logic - extend for correction |

### Test Files to Update
| File | Purpose |
|------|---------|
| `tests/integration/RealSearchTest.php` | Add typo correction scenarios |

---

## Non-Functional Requirements

| Requirement | Specification |
|-------------|----------------|
| **Performance** | Correction lookup < 50ms |
| **Backward Compatibility** | Existing functionality unchanged |
| **i18n** | Notice messages translatable via WordPress i18n |
| **Accessibility** | Correction notice uses proper ARIA attributes |

---

## Dependencies

| Dependency | Type | Status |
|------------|------|--------|
| WordPress `WP_Query` | External | ✅ Available |
| WooCommerce `wc_get_product()` | External | ✅ Available |
| `Typo_Corrector::correct()` | Internal | ✅ Existing |
| WordPress i18n functions | External | ✅ Available |

---

## Out of Scope (Future Enhancements)

- "Did you mean?" suggestions when results exist (proactive fuzzy matching)
- Machine learning-based typo correction
- User typo feedback loop
- Per-product custom corrections
- Typo correction analytics dashboard

---

## Open Questions

| Question | Answer | Decision Date |
|----------|--------|---------------|
| Should correction be case-sensitive? | No, already handled by `mb_strtolower()` | Resolved |
| Minimum term length for correction? | 4 characters (existing behavior) | Resolved |
| Should we correct accents only? | Yes, `Typo_Corrector` already handles | Resolved |
