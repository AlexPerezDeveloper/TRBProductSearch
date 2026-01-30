# Tasks: Global Typo Tolerance Implementation

> **Issue**: #7 - Feature: Implement Global Typo Tolerance and Fuzzy Search Results
> **Spec Version**: 1.0.0
> **Plan Version**: 1.0.0

---

## Task Breakdown Summary

| Phase | Tasks | Estimated |
|-------|-------|-----------|
| Phase 1: Core Logic | 3 tasks | 4 hours |
| Phase 2: UI Components | 3 tasks | 3 hours |
| Phase 3: Integration | 2 tasks | 2 hours |
| Phase 4: Testing | 3 tasks | 3 hours |
| **Total** | **11 tasks** | **12 hours** |

---

## Phase 1: Core Logic

### Task 1.1: Add Correction Properties to Search_Query
- **Spec Reference**: FR-1, FR-2
- **Dependencies**: None
- **Estimated**: 1 hour

#### Description
Add private properties to `Search_Query` class to store original and corrected search terms.

#### Changes
**File**: `includes/class-search-query.php`

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

#### Definition of Done
- [ ] Properties added to class
- [ ] Properties initialized to null
- [ ] No existing functionality broken
- [ ] File passes PHP syntax check

---

### Task 1.2: Add Getter Methods to Search_Query
- **Spec Reference**: FR-4
- **Dependencies**: Task 1.1
- **Estimated**: 1 hour

#### Description
Add public getter methods to retrieve correction metadata.

#### Changes
**File**: `includes/class-search-query.php`

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

#### Definition of Done
- [ ] All getter methods implemented
- [ ] Return types documented
- [ ] `has_correction()` returns correct boolean
- [ ] `get_correction_info()` returns array with both keys

---

### Task 1.3: Implement Auto-Correction in Search_Query::search()
- **Spec Reference**: FR-1, FR-2, FR-5
- **Dependencies**: Task 1.2
- **Estimated**: 2 hours

#### Description
Modify the `search()` method to automatically attempt typo correction when no results are found.

#### Changes
**File**: `includes/class-search-query.php`

```php
public function search($term)
{
    // Store original term
    $this->original_term = $term;

    // Initialize search instances
    $this->sku_search = SKU_Search::get_instance();
    $this->attributes_search = Attributes_Search::get_instance();

    // ... existing args setup ...

    // Perform initial search
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

    // Cleanup filters
    // ... existing cleanup ...

    return $query;
}
```

#### Definition of Done
- [ ] Original term stored before search
- [ ] Correction only attempted for 4+ character terms
- [ ] Typo_Corrector consulted for suggestions
- [ ] Search re-run with corrected term
- [ ] All existing tests still pass
- [ ] New tests for correction scenario pass

---

## Phase 2: UI Components

### Task 2.1: Create Correction Notice Template
- **Spec Reference**: FR-3
- **Dependencies**: None
- **Estimated**: 1 hour

#### Description
Create a new template file for rendering the correction notice.

#### Changes
**File**: `templates/correction-notice.php` (NEW)

```php
<?php
/**
 * Correction Notice Template
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

#### Definition of Done
- [ ] Template file created
- [ ] Proper escaping for security
- [ ] i18n ready with translation strings
- [ ] ARIA attributes for accessibility
- [ ] Variables properly documented

---

### Task 2.2: Add Correction Notice Rendering to Search_Results
- **Spec Reference**: FR-3
- **Dependencies**: Task 2.1
- **Estimated**: 1 hour

#### Description
Modify `Search_Results` class to accept correction info and render the notice.

#### Changes
**File**: `includes/class-search-results.php`

```php
/**
 * Render the results with optional correction notice.
 *
 * @param \WP_Query $query The search query object.
 * @param array     $correction_info Correction metadata from Search_Query.
 */
public function render($query, $correction_info = array())
{
    // Render correction notice if applicable
    if (!empty($correction_info['corrected'])) {
        $this->render_correction_notice(
            $correction_info['original'],
            $correction_info['corrected']
        );
    }

    // ... existing no results check ...
    // ... existing results rendering ...
}

/**
 * Render the correction notice.
 *
 * @param string $original_term  The original search term.
 * @param string $corrected_term The corrected search term.
 */
private function render_correction_notice($original_term, $corrected_term)
{
    $template_name = 'correction-notice.php';

    $theme_template = locate_template(array('trb-product-search/' . $template_name));

    if ($theme_template) {
        include $theme_template;
    } else {
        include TRB_PRODUCT_SEARCH_PATH . 'templates/' . $template_name;
    }
}
```

#### Definition of Done
- [ ] `render()` signature updated (backward compatible)
- [ ] `render_correction_notice()` method added
- [ ] Template loading follows existing pattern
- [ ] Theme override capability maintained

---

### Task 2.3: Add CSS Styling for Correction Notice
- **Spec Reference**: FR-3
- **Dependencies**: Task 2.1
- **Estimated**: 1 hour

#### Description
Add CSS styles for the correction notice component.

#### Changes
**File**: `assets/css/search.css`

```css
/* Correction Notice Styles */
.trb-search-correction-notice {
    padding: 12px 16px;
    margin-bottom: 16px;
    background-color: #fff3cd;
    border-left: 4px solid #ffc107;
    border-radius: 4px;
}

.trb-correction-message {
    margin: 0;
    font-size: 14px;
    line-height: 1.5;
    color: #856404;
}

.trb-original-term {
    font-style: italic;
}

.trb-corrected-term {
    color: #d63384;
    font-weight: 600;
}

/* Dropdown correction notice */
.trb-search-suggestion {
    padding: 8px 12px;
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    font-size: 13px;
    color: #495057;
}
```

#### Definition of Done
- [ ] CSS added to search.css
- [ ] Notice visually distinct from results
- [ ] Responsive on mobile devices
- [ ] Matches existing design language

---

## Phase 3: Integration

### Task 3.1: Update Search_Form to Use Correction Info
- **Spec Reference**: FR-1, FR-2
- **Dependencies**: Task 1.3, Task 2.2
- **Estimated**: 1 hour

#### Description
Modify `Search_Form::handle_search()` to pass correction info to results renderer.

#### Changes
**File**: `includes/class-search-form.php`

```php
/**
 * Handle the search request and display results.
 *
 * @param string $term Search term.
 */
private function handle_search($term)
{
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

#### Definition of Done
- [ ] Correction info retrieved from Search_Query
- [ ] Correction info passed to Search_Results
- [ ] Full page search shows correction notice
- [ ] No breaking changes to existing functionality

---

### Task 3.2: Refactor Ajax_Handler to Use Shared Correction Logic
- **Spec Reference**: FR-1, FR-4
- **Dependencies**: Task 1.3, Task 2.2
- **Estimated**: 1 hour

#### Description
Simplify `Ajax_Handler::handle_search()` by using the shared correction logic in Search_Query.

#### Changes
**File**: `includes/class-ajax-handler.php`

```php
public function handle_search()
{
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

    // ... rest of product list rendering ...
}
```

#### Definition of Done
- [ ] Direct typo correction code removed from Ajax_Handler
- [ ] Uses Search_Query for all search logic
- [ ] Correction notice rendered in dropdown
- [ ] Code duplication eliminated

---

## Phase 4: Testing

### Task 4.1: Add Unit Tests for Correction Logic
- **Spec Reference**: SC-4, SC-5
- **Dependencies**: Task 1.3
- **Estimated**: 1 hour

#### Description
Add unit tests for new Search_Query correction methods.

#### Changes
**File**: `tests/unit/TypoCorrectionTest.php` (NEW)

```php
class TypoCorrectionTest extends TestCase {
    public function test_has_correction_returns_true_when_corrected() {
        $query_handler = new \TRB_Product_Search\Search_Query();
        // Setup test with typo that will be corrected
        $result = $query_handler->search('stich');
        $this->assertTrue($query_handler->has_correction());
    }

    public function test_has_correction_returns_false_for_exact_match() {
        $query_handler = new \TRB_Product_Search\Search_Query();
        $result = $query_handler->search('stitch');
        $this->assertFalse($query_handler->has_correction());
    }

    public function test_get_correction_info_returns_correct_data() {
        $query_handler = new \TRB_Product_Search\Search_Query();
        $query_handler->search('stich');
        $info = $query_handler->get_correction_info();
        $this->assertArrayHasKey('original', $info);
        $this->assertArrayHasKey('corrected', $info);
        $this->assertEquals('stich', $info['original']);
        $this->assertEquals('stitch', $info['corrected']);
    }

    public function test_correction_not_triggered_for_short_terms() {
        $query_handler = new \TRB_Product_Search\Search_Query();
        $query_handler->search('cam'); // 3 characters
        $this->assertFalse($query_handler->has_correction());
    }
}
```

#### Definition of Done
- [ ] Test file created
- [ ] All getter methods tested
- [ ] Correction logic tested
- [ ] Edge cases covered (short terms, no correction)
- [ ] Tests pass

---

### Task 4.2: Add Integration Tests for Full Page Search
- **Spec Reference**: SC-1, SC-3
- **Dependencies**: Task 3.1
- **Estimated**: 1 hour

#### Description
Add integration tests for full page search with typo correction.

#### Changes
**File**: `tests/integration/RealSearchTest.php` (APPEND)

```php
/**
 * Test typo correction in full page search.
 */
public function test_typo_correction_applied_when_no_results()
{
    TRB_Product_Search_Tests_Setup::set_option('trb_search_synonyms', '');
    TRB_Product_Search_Tests_Setup::set_option('trb_search_sku_enabled', '0');

    $query_handler = new \TRB_Product_Search\Search_Query();
    $query_handler->search('stich'); // Typo

    $this->assertTrue($query_handler->has_correction());
    $info = $query_handler->get_correction_info();
    $this->assertEquals('stich', $info['original']);
    $this->assertNotEmpty($info['corrected']);
}

/**
 * Test that correction is not applied for exact matches.
 */
public function test_no_correction_for_exact_match()
{
    $query_handler = new \TRB_Product_Search\Search_Query();
    $query_handler->search('stitch');

    $this->assertFalse($query_handler->has_correction());
}
```

#### Definition of Done
- [ ] Integration tests added
- [ ] Tests verify correction behavior
- [ ] Tests verify no correction for exact matches
- [ ] All tests pass

---

### Task 4.3: Final Validation and Regression Testing
- **Spec Reference**: SC-1, SC-2, SC-3, SC-5
- **Dependencies**: All previous tasks
- **Estimated**: 1 hour

#### Description
Run full test suite and perform manual validation.

#### Checklist
- [ ] Run `composer test` - all tests pass
- [ ] Run `composer test-coverage` - coverage > 90% for new code
- [ ] Manual test: AJAX dropdown with typo
- [ ] Manual test: Full page search with typo
- [ ] Manual test: Exact match (no correction)
- [ ] Manual test: Short term (no correction)
- [ ] Visual check: Correction notice styling
- [ ] Accessibility check: ARIA attributes present

#### Definition of Done
- [ ] All automated tests pass
- [ ] Manual testing checklist complete
- [ ] No regressions detected
- [ ] Ready for code review

---

## Task Dependencies

```
Task 1.1 ──────┐
               │
Task 1.2 ──────┤
               ├──▶ Task 1.3 ──────────────────────────┐
               │                                      │
Task 2.1 ──────┤                                      │
               │                                      │
Task 2.2 ──────┼──▶ Task 3.1 ────────────────────────┤
               │                                      │
Task 2.3 ──────┤                                      │
               │                                      │
               └──▶ Task 3.2 ────────────────────────┤
                                                      │
Task 4.1 ─────────────────────────────────────────────┤
                                                      │
Task 4.2 ─────────────────────────────────────────────┤
                                                      │
                                                      ▼
                                               Task 4.3 (Final)
```

---

## Definition of Done for Entire Feature

- [ ] All tasks completed
- [ ] All tests passing (unit + integration)
- [ ] Code coverage > 90% for new code
- [ ] No regressions in existing functionality
- [ ] Documentation updated (CLAUDE.md if needed)
- [ ] Code review approved
- [ ] Ready to merge to main
