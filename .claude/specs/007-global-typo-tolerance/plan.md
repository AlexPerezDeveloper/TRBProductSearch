# Plan: Global Typo Tolerance Implementation

> **Issue**: #7 - Feature: Implement Global Typo Tolerance and Fuzzy Search Results
> **Spec Version**: 1.0.0
> **Status**: Draft

---

## Architecture Overview

### Current Architecture

```
┌─────────────────┐     ┌─────────────────┐
│  Ajax_Handler   │────▶│  Search_Query   │────▶ WP_Query
│                 │     │                 │
│  (dropdown)     │     │  (main search)  │
└─────────────────┘     └─────────────────┘
                                │
                                ▼
                        ┌──────────────────┐
                        │  Typo_Corrector  │
                        │  (isolated)      │
                        └──────────────────┘

┌─────────────────┐     ┌─────────────────┐
│  Search_Form    │────▶│  Search_Query   │────▶ WP_Query
│                 │     │                 │
│  (full page)    │     │  (main search)  │
└─────────────────┘     └─────────────────┘
                                │
                                ▼
                         (no typo correction!)
```

### Target Architecture

```
┌─────────────────┐     ┌─────────────────┐
│  Ajax_Handler   │────▶│  Search_Query   │────▶ WP_Query
│                 │     │  + correction() │
└─────────────────┘     └─────────────────┘
                                │
                                ▼
                        ┌──────────────────┐
                        │  Typo_Corrector  │
                        └──────────────────┘

┌─────────────────┐     ┌─────────────────┐
│  Search_Form    │────▶│  Search_Query   │────▶ WP_Query
│                 │     │  + correction() │
└─────────────────┘     └─────────────────┘
                                │
                                ▼
                        ┌──────────────────┐
                        │  Search_Results  │
                        │  + notice HTML   │
                        └──────────────────┘
```

---

## Design Decisions

### Decision 1: Search Result Metadata
**Problem**: How to communicate correction info from Search_Query to renderers?

**Options**:
| Option | Pros | Cons |
|--------|------|------|
| A. Return array with `query` and `correction` | Explicit, clear data | Breaks existing return type (WP_Query) |
| B. Add getters to Search_Query | Non-breaking | Adds state to class |
| C. Use WordPress `set_transient()` | Decoupled | Adds I/O overhead |
| D. Filter with `trb_search_correction` | WordPress native | Requires filter handling |

**Selected**: **Option B** - Add getters to Search_Query

**Rationale**:
- Non-breaking change
- Simple to implement
- Clear ownership of data
- No performance overhead

```php
class Search_Query {
    private $original_term = null;
    private $corrected_term = null;

    public function get_original_term() { /* ... */ }
    public function get_corrected_term() { /* ... */ }
    public function has_correction() { /* ... */ }
}
```

---

### Decision 2: Correction Logic Location
**Problem**: Where to put the shared correction logic?

**Options**:
| Option | Pros | Cons |
|--------|------|------|
| A. In Search_Query::search() | Centralized | Mixes concerns |
| B. New method Search_Query::search_with_correction() | Explicit | Duplicate method |
| C. Separate Correction_Orchestrator class | SRP compliant | Over-engineering |
| D. Make Search_Query::search() handle it internally | Simple | Search_Query becomes smarter |

**Selected**: **Option D** - Enhance Search_Query internally

**Rationale**:
- Single entry point for search
- Correction is a search concern
- Minimizes changes to consumers
- Easy to test

---

### Decision 3: Correction Notice Rendering
**Problem**: How to render the correction notice?

**Options**:
| Option | Pros | Cons |
|--------|------|------|
| A. Add method to Search_Results | Co-located | Search_Results becomes UI-heavy |
| B. Separate template file | Theme overrideable | Another file to maintain |
| C. WordPress `wp_notice()` function | Native UI | Not persistent across page load |
| D. Inline in Search_Results::render() | Simple | Mixing concerns |

**Selected**: **Option B** - Separate template file

**Rationale**:
- Follows existing pattern (results.php)
- Theme overrideable
- Clear separation of concerns
- Easy to test

---

## Component Changes

### 1. Search_Query Class Enhancement

**File**: `includes/class-search-query.php`

**New Properties**:
```php
/**
 * Original search term before correction.
 *
 * @var string|null
 */
private $original_term = null;

/**
 * Corrected search term (if correction was applied).
 *
 * @var string|null
 */
private $corrected_term = null;
```

**New Public Methods**:
```php
/**
 * Get the original search term.
 *
 * @return string|null
 */
public function get_original_term() {
    return $this->original_term;
}

/**
 * Get the corrected search term.
 *
 * @return string|null
 */
public function get_corrected_term() {
    return $this->corrected_term;
}

/**
 * Check if a correction was applied.
 *
 * @return bool
 */
public function has_correction() {
    return $this->corrected_term !== null;
}

/**
 * Get correction metadata (for template use).
 *
 * @return array{original: string|null, corrected: string|null}
 */
public function get_correction_info() {
    return array(
        'original' => $this->original_term,
        'corrected' => $this->corrected_term,
    );
}
```

**Modified Method**:
```php
/**
 * Execute the search with automatic typo correction.
 *
 * @param string $term Search term.
 * @return \WP_Query The query result.
 */
public function search($term) {
    // Store original term
    $this->original_term = $term;

    // Perform initial search
    $args = /* ... existing args ... */;
    $query = new \WP_Query($args);

    // If no results and term is eligible for correction
    if (!$query->have_posts() && strlen($term) >= 4) {
        $corrector = \TRB_Product_Search\Typo_Corrector::get_instance();
        $suggestion = $corrector->correct($term);

        if ($suggestion) {
            $this->corrected_term = $suggestion;
            // Re-run search with corrected term
            $args['s'] = $suggestion;
            $query = new \WP_Query($args);
        }
    }

    return $query;
}
```

---

### 2. Search_Results Class Enhancement

**File**: `includes/class-search-results.php`

**Modified Constructor/Render**:
```php
/**
 * Render the results with optional correction notice.
 *
 * @param \WP_Query $query The search query object.
 * @param array     $correction_info Correction metadata from Search_Query.
 */
public function render($query, $correction_info = array()) {
    // Render correction notice if applicable
    if (!empty($correction_info['corrected'])) {
        $this->render_correction_notice(
            $correction_info['original'],
            $correction_info['corrected']
        );
    }

    if (!$query->have_posts()) {
        echo '<p class="trb_product_search_no_results">' .
             esc_html__('No products found.', 'trb-product-search') .
             '</p>';
        return;
    }

    // ... existing results rendering ...
}

/**
 * Render the correction notice.
 *
 * @param string $original_term  The original search term.
 * @param string $corrected_term The corrected search term.
 */
private function render_correction_notice($original_term, $corrected_term) {
    $template_name = 'correction-notice.php';

    $theme_template = locate_template(array('trb-product-search/' . $template_name));

    if ($theme_template) {
        include $theme_template;
    } else {
        include TRB_PRODUCT_SEARCH_PATH . 'templates/' . $template_name;
    }
}
```

---

### 3. Search_Form Class Update

**File**: `includes/class-search-form.php`

**Modified handle_search Method**:
```php
/**
 * Handle the search request and display results.
 *
 * @param string $term Search term.
 */
private function handle_search($term) {
    if (class_exists('\TRB_Product_Search\Search_Query') &&
        class_exists('\TRB_Product_Search\Search_Results')) {

        $query_handler = new Search_Query();
        $results = $query_handler->search($term);

        // Get correction info if any
        $correction_info = $query_handler->get_correction_info();

        $results_renderer = new Search_Results();
        $results_renderer->render($results, $correction_info);
    }
}
```

---

### 4. Ajax_Handler Refactoring

**File**: `includes/class-ajax-handler.php`

**Simplified handle_search Method**:
```php
/**
 * Handle the AJAX search request.
 */
public function handle_search() {
    check_ajax_referer('trb_search_nonce', 'security');

    $term = isset($_GET['term']) ? sanitize_text_field($_GET['term']) : '';

    if (empty($term) || strlen($term) < 3) {
        wp_send_json_error(array('message' => __('Term too short', 'trb-product-search')));
    }

    $query_handler = new Search_Query();
    $query = $query_handler->search($term);

    // Check if correction was applied
    $is_correction = $query_handler->has_correction();
    $correction_info = $query_handler->get_correction_info();

    if (!$query->have_posts()) {
        wp_send_json_error(array('message' => __('No products found', 'trb-product-search')));
    }

    ob_start();
    echo '<ul class="trb-search-dropdown-list">';

    if ($is_correction) {
        echo '<li class="trb-search-suggestion">';
        printf(
            esc_html__('No results for "%s". Showing results for "%s"', 'trb-product-search'),
            esc_html($correction_info['original']),
            '<strong>' . esc_html($correction_info['corrected']) . '</strong>'
        );
        echo '</li>';
    }

    // ... existing product list rendering ...
}
```

---

### 5. New Template: Correction Notice

**File**: `templates/correction-notice.php` (NEW)

```php
<?php
/**
 * Correction Notice Template
 *
 * Displays a notice when search term was corrected.
 *
 * @package TRB_Product_Search
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="trb-search-correction-notice" role="status" aria-live="polite">
    <p class="trb-correction-message">
        <?php
        printf(
            /* translators: %1$s: original search term, %2$s: corrected search term */
            esc_html__('No se encontraron resultados para %1$s. Mostrando resultados para %2$s', 'trb-product-search'),
            '<span class="trb-original-term">' . esc_html($original_term) . '</span>',
            '<strong class="trb-corrected-term">' . esc_html($corrected_term) . '</strong>'
        );
        ?>
    </p>
</div>
```

---

## Data Flow

### Full Page Search Flow
```
User submits "stich"
        │
        ▼
Search_Form::handle_search("stich")
        │
        ▼
Search_Query::search("stich")
        │
        ├─► Store: original_term = "stich"
        │
        ├─► WP_Query("stich") → 0 results
        │
        ├─► Typo_Corrector::correct("stich") → "stitch"
        │
        ├─► Store: corrected_term = "stitch"
        │
        └─► WP_Query("stitch") → results found
        │
        ▼
Search_Results::render($query, $correction_info)
        │
        ├─► render_correction_notice() → HTML notice
        │
        └─► render product results
```

### AJAX Dropdown Flow
```
User types "stich"
        │
        ▼
Ajax_Handler::handle_search()
        │
        ▼
Search_Query::search("stich")
        │
        └─► [same as above, returns query + correction info]
        │
        ▼
Build JSON response with:
  - HTML product list
  - Correction notice embedded
```

---

## Testing Strategy

### Unit Tests
| Test | Purpose |
|------|---------|
| `Search_Query::has_correction()` | Returns true when correction applied |
| `Search_Query::get_correction_info()` | Returns correct metadata |
| `Search_Query::search()` with typo | Returns corrected results |
| `Search_Query::search()` with exact match | No correction applied |

### Integration Tests
| Test | Purpose |
|------|---------|
| Full page search with typo | Correction notice appears |
| Full page search without typo | No notice |
| AJAX search with typo | Notice in dropdown |
| Short search term (< 4 chars) | No correction triggered |
| No correction available | Standard "no results" message |

### Visual Regression Tests
| Scenario | Verify |
|----------|--------|
| Correction notice styling | Matches design specs |
| Notice accessibility attributes | ARIA roles present |
| Notice in dropdown | Proper positioning |

---

## Implementation Checklist

### Phase 1: Core Logic
- [ ] Add correction properties to `Search_Query`
- [ ] Add getter methods to `Search_Query`
- [ ] Modify `Search_Query::search()` for auto-correction
- [ ] Add unit tests for correction logic

### Phase 2: UI Components
- [ ] Create `correction-notice.php` template
- [ ] Modify `Search_Results::render()` signature
- [ ] Add `render_correction_notice()` method
- [ ] Add CSS for notice styling

### Phase 3: Integration
- [ ] Update `Search_Form::handle_search()`
- [ ] Refactor `Ajax_Handler::handle_search()`
- [ ] Add integration tests

### Phase 4: Testing & Validation
- [ ] Run all existing tests (ensure no regression)
- [ ] Run new tests
- [ ] Manual testing in browser
- [ ] Code review

---

## Risk Analysis

| Risk | Impact | Mitigation |
|------|--------|------------|
| Breaking existing `Search_Query` return type | High | Return WP_Query unchanged, add getters |
| Correction notice not themeable | Medium | Use template file like results.php |
| Performance degradation from double search | Low | Only re-search on 0 results (rare) |
| False positive corrections | Medium | Existing threshold (distance ≤ 3) works well |

---

## Rollback Plan

If issues arise:
1. Revert `Search_Query` changes (git revert)
2. Remove `correction-notice.php` template
3. Revert `Search_Form` and `Ajax_Handler` changes
4. Delete new tests

No database changes = clean rollback possible.
