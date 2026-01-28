# TRB Product Search

A lightweight, efficient, and user-friendly product search plugin for WooCommerce. enhancing the default search experience with AJAX results, synonyms support, and intelligent typo correction.

## Features

*   **Real-time AJAX Search**: Instant dropdown results as you type.
*   **Synonyms Support**: Map synonymous terms (e.g., "cellphone" -> "smartphone") so customers always find what they need.
*   **Typo Correction**: Automatically suggests corrections for misspelled words (e.g., "samsng" -> "Samsung").
*   **Performance**: Optimized for speed, compatible with large catalogs.
*   **Minimalist Design**: Clean UI that fits any theme.

## Screenshots

### Instant Search Dropdown
Fast results with thumbnails and prices directly in the search bar.
![Search Dropdown](assets/screenshots/search-dropdown.png)

### Synonyms Configuration
Easily manage synonymous terms in the admin panel.
![Synonyms Settings](assets/screenshots/synonyms-settings.png)

### Intelligent Typo Correction
Helps users find products even when they make spelling mistakes.
![Typo Correction](assets/screenshots/typo-correction.png)

## Installation

1.  Upload the plugin files to the `/wp-content/plugins/trb-product-search` directory, or install the plugin through the WordPress plugins screen.
2.  Activate the plugin through the 'Plugins' screen in WordPress.
3.  Ensure WooCommerce is installed and active.
4.  Go to **Settings > TRB Search** to configure synonyms.

## Configuration

### Synonyms
To add synonyms, navigate to **Settings > TRB Search**. Enter groups of synonyms, one group per line. separated by commas.
Example:
```
sneakers, trainers, running shoes
tv, television, telly
```

### Typo Correction
The typo correction index is built automatically when you save products. To ensure it's up to date, simply update a product in your store.

## Requirements
*   WordPress 5.0+
*   WooCommerce 3.0+
*   PHP 7.4+
