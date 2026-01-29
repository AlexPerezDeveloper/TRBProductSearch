# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

TRB Product Search is a WordPress plugin that enhances WooCommerce product search with AJAX results, SKU search, attributes search, synonyms support, and typo correction.

## Commands

### Testing
```bash
# Run all tests
composer test

# Run only integration tests
composer test-integration

# Run tests with coverage report
composer test-coverage
```

### Composer Autoloader
```bash
# Regenerate autoloader after adding new classes
composer dump-autoload
```

## Architecture

### Entry Point
- `trb-product-search.php` - Main plugin file, defines constants and initializes `Plugin_Init`

### Core Classes (in `includes/`)
All classes use the `TRB_Product_Search` namespace and follow the singleton pattern with `get_instance()`.

| Class | Purpose |
|-------|---------|
| `Plugin_Init` | Orchestrates initialization, checks WooCommerce dependency, enqueues assets |
| `Search_Query` | Main search logic, combines title/content search with SKU, attributes, and synonyms |
| `SKU_Search` | Builds meta_query for `_sku` field when enabled |
| `Attributes_Search` | Builds tax_query for product attributes (e.g., `pa_color`, `pa_size`) |
| `Typo_Corrector` | Builds word index from products, provides Levenshtein-based typo correction |
| `Ajax_Handler` | Handles `wp_ajax_trb_search` requests, returns JSON with dropdown HTML |
| `Settings` | Admin settings page at Settings > TRB Search, manages options via WordPress Settings API |
| `Search_Form` | Shortcode `[trb_product_search]` for rendering search input |
| `Search_Results` | Template rendering for search results |

### Key Options
- `trb_search_synonyms` - Textarea, one synonym group per line (comma-separated)
- `trb_search_sku_enabled` - Checkbox for SKU search
- `trb_search_attributes_enabled` - Checkbox for attributes search
- `trb_search_selected_attributes` - Array of attribute slugs to search
- `trb_search_word_index` - Auto-built index for typo correction (titles, SKUs, attributes)

### Frontend Assets
- `assets/js/search.js` - AJAX search, debouncing, dropdown UI
- `assets/css/search.css` - Dropdown styling

## Testing

Integration tests are in `tests/integration/`. The `tests/bootstrap.php` provides extensive mocks for WordPress and WooCommerce core functions.

Tests do NOT use WordPress test suite; they use lightweight function mocks for faster execution.

## Gotchas

1. **Namespace**: All classes are namespaced `TRB_Product_Search`. When referencing WordPress globals, use `\WP_Query`, `\wpdb`, etc.

2. **Dependency Check**: Plugin only initializes if `class_exists('WooCommerce')`. Admin notice is shown if missing.

3. **Singleton Pattern**: Every class has private `__construct()` and static `get_instance()`. Do not instantiate directly with `new`.

4. **Search Priority**: Exact SKU matches are ordered first via `priority_orderby()` filter in `Search_Query`.

5. **Typo Correction Index**: Auto-rebuilt on `save_post_product`. For manual rebuild, call `Typo_Corrector::get_instance()->build_index()`.

6. **Attributes Taxonomy**: WooCommerce attributes are prefixed with `pa_` (e.g., `pa_color`, `pa_size`).
