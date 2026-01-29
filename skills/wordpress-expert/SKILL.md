---
name: wordpress-expert
description: Expert WordPress development with plugin creation, theme customization, MCP integration, and ecosystem mastery. Desarrollo experto en WordPress con creación de plugins, personalización de temas, integración MCP y dominio del ecosistema.

  **Use when / Usar cuando:** Creating plugins (crear plugins), building themes (construir temas), customizing WordPress sites (personalizar sitios WordPress), integrating MCP for WordPress operations (integrar MCP para operaciones WordPress), developing custom post types (desarrollar custom post types), creating REST API endpoints (crear endpoints REST API), extending WooCommerce (extender WooCommerce), working with WordPress hooks/filters (trabajar con hooks/filtros WordPress), database operations (operaciones de base de datos), WordPress configuration (configuración WordPress), multisite setup (configuración multisite), performance optimization (optimización de rendimiento WordPress).

  **WordPress PHP / PHP WordPress:** WordPress uses PHP with specific patterns and coding standards. For general PHP expertise (PHP 8.3+ features, types, OOP), see [php-expert](/home/raskardev/.claude/skills/php-expert/SKILL.md). For PHP backend patterns (APIs, security, databases), see [backend-expert](/home/raskardev/.claude/skills/backend-expert/SKILL.md).

  **PHP Skills Integration / Integración Skills PHP:**
  - php-expert: Modern PHP 8.3+, types, OOP, SOLID, Clean Code principles
  - backend-expert: PHP security, input validation, output escaping, prepared statements
  - testing-expert: PHPUnit for testing WordPress plugins/themes

  **WordPress-Specific / Específico WordPress:** Hooks (actions/filters), WP REST API, Custom Post Types, Meta boxes, Transients API, wpdb, $wpdb->prepare(), WP_Query, WP Cron, Shortcodes, Widgets, Sidebars, Theme Customizer, Block Editor (Gutenberg), Full Site Editing (FSE).
---

# WordPress Expert

## Overview

This skill provides expert-level WordPress development capabilities, focusing on the WordPress ecosystem, architecture, and best practices. It covers plugin development, theme customization, MCP (Model Context Protocol) integration for WordPress operations, custom post types, REST API development, WooCommerce extensions, and database operations.

## PHP Foundation for WordPress

WordPress is built on PHP. This skill focuses on **WordPress-specific patterns**. For general PHP expertise, the following skills provide the foundation:

| PHP Skill | WordPress Application |
|-----------|----------------------|
| **[php-expert](/home/raskardev/.claude/skills/php-expert/SKILL.md)** | PHP 8.3+ features (union types, readonly, match), OOP, SOLID, error handling, design patterns |
| **[backend-expert](/home/raskardev/.claude/skills/backend-expert/SKILL.md)** | Security (OWASP), input validation, output escaping, prepared statements, JWT auth, logging |
| **[testing-expert](/home/raskardev/.claude/skills/testing-expert/SKILL.md)** | PHPUnit tests for plugins/themes, mocking WordPress functions |

**WordPress PHP Version Support:**
- WordPress 6.4+ recommends PHP 8.0+
- WordPress 6.7+ recommends PHP 8.1+
- Use modern PHP features where backward compatibility allows

## Core Capabilities

### 1. MCP Integration for WordPress Operations

The WordPress MCP server provides programmatic access to WordPress sites for content management, plugin operations, and site administration.

**Key operations:**
- **Site connection**: Connect to WordPress sites via REST API credentials
- **Post/Page management**: Create, read, update, delete posts and pages
- **Media handling**: Upload, manage, and attach media files
- **Plugin operations**: List, activate, deactivate, install plugins
- **Theme management**: Switch themes, customize theme settings
- **User management**: Create users, manage roles and capabilities
- **Site settings**: Update site configuration, permalinks, and options

**MCP workflow pattern:**
1. Establish connection with site credentials (URL, username, application password)
2. Verify connection and permissions
3. Execute operations using WordPress REST API endpoints
4. Handle responses and error states
5. Log operations for audit trail

See [references/mcp-operations.md](references/mcp-operations.md) for detailed MCP integration patterns.

### 2. Plugin Development

Create robust, secure, and performant WordPress plugins following WordPress coding standards.

**Plugin structure:**
```
plugin-name/
├── plugin-name.php          # Main plugin file with header
├── includes/                # Core functionality
│   ├── class-plugin.php     # Main plugin class
│   ├── class-admin.php      # Admin functionality
│   └── class-public.php     # Public-facing functionality
├── admin/                   # Admin-specific files
│   ├── css/
│   ├── js/
│   └── partials/           # Admin templates
├── public/                  # Public-facing files
│   ├── css/
│   ├── js/
│   └── partials/           # Public templates
├── languages/               # Translation files
└── assets/                  # Icons, banners
```

**Essential plugin patterns:**
- Activation/deactivation hooks for setup/cleanup
- Uninstall hooks for complete data removal
- Proper enqueuing of scripts and styles
- Nonce verification for security
- Data sanitization and validation
- Capability checks for permissions
- Hooks and filters for extensibility
- Settings API integration
- Custom database tables (when needed)

**Best practices:**
- Prefix all functions, classes, and global variables
- Use WordPress coding standards (WPCS)
- Implement proper error handling
- Follow plugin security best practices
- Make plugins translation-ready
- Version assets for cache busting
- Use WordPress APIs instead of direct database queries

See [references/plugin-patterns.md](references/plugin-patterns.md) for comprehensive plugin development patterns.

### 3. Theme Development

Build custom WordPress themes or customize existing themes using child themes.

**Theme structure (modern block theme):**
```
theme-name/
├── style.css               # Theme header and base styles
├── functions.php           # Theme functionality
├── theme.json              # Theme configuration (FSE)
├── templates/              # Full site editing templates
│   ├── index.html
│   ├── single.html
│   ├── page.html
│   └── archive.html
├── parts/                  # Template parts
│   ├── header.html
│   ├── footer.html
│   └── sidebar.html
├── patterns/               # Block patterns
└── assets/                 # Images, fonts, etc.
```

**Classic theme essentials:**
- Template hierarchy understanding
- Template tags and conditional tags
- The Loop implementation
- Custom page templates
- Widget areas (sidebars)
- Navigation menus
- Customizer API integration
- Theme support features

**Block theme (FSE) essentials:**
- theme.json configuration
- Block templates and template parts
- Block patterns
- Global styles
- Block variations

**Child theme pattern:**
```php
// functions.php in child theme
function child_theme_enqueue_styles() {
    wp_enqueue_style( 'parent-style',
        get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array( 'parent-style' )
    );
}
add_action( 'wp_enqueue_scripts', 'child_theme_enqueue_styles' );
```

### 4. Hooks and Filters System

Master WordPress's event-driven architecture through actions and filters.

**Action hooks:**
```php
// Register custom functionality
add_action( 'init', 'my_custom_init' );
add_action( 'wp_enqueue_scripts', 'my_custom_scripts' );
add_action( 'save_post', 'my_custom_save_post', 10, 2 );

// Create custom hooks for extensibility
do_action( 'my_plugin_before_process' );
do_action( 'my_plugin_after_process', $result );
```

**Filter hooks:**
```php
// Modify content
add_filter( 'the_content', 'my_custom_content_filter' );
add_filter( 'the_title', 'my_custom_title_filter', 10, 2 );

// Create custom filters
$value = apply_filters( 'my_plugin_filter_value', $value, $context );
```

**Priority and parameters:**
- Priority: Lower numbers run first (default: 10)
- Accepted args: Number of parameters your callback accepts

See [references/hooks-filters.md](references/hooks-filters.md) for comprehensive hook reference.

### 5. Custom Post Types and Taxonomies

Extend WordPress content types beyond posts and pages.

**Register custom post type:**
```php
function register_custom_post_type() {
    $args = array(
        'labels'       => array(
            'name'          => __( 'Projects' ),
            'singular_name' => __( 'Project' )
        ),
        'public'       => true,
        'has_archive'  => true,
        'rewrite'      => array( 'slug' => 'projects' ),
        'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'show_in_rest' => true, // Enable Gutenberg
        'menu_icon'    => 'dashicons-portfolio',
    );
    register_post_type( 'project', $args );
}
add_action( 'init', 'register_custom_post_type' );
```

**Register custom taxonomy:**
```php
function register_custom_taxonomy() {
    $args = array(
        'labels'       => array(
            'name'          => __( 'Project Types' ),
            'singular_name' => __( 'Project Type' )
        ),
        'hierarchical' => true, // true = categories, false = tags
        'public'       => true,
        'show_in_rest' => true,
        'rewrite'      => array( 'slug' => 'project-type' ),
    );
    register_taxonomy( 'project_type', array( 'project' ), $args );
}
add_action( 'init', 'register_custom_taxonomy' );
```

**Meta boxes and custom fields:**
```php
function add_custom_meta_box() {
    add_meta_box(
        'project_details',
        __( 'Project Details' ),
        'render_project_details_meta_box',
        'project',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'add_custom_meta_box' );

function render_project_details_meta_box( $post ) {
    wp_nonce_field( 'project_details_nonce', 'project_details_nonce' );
    $value = get_post_meta( $post->ID, '_project_url', true );
    echo '<input type="text" name="project_url" value="' . esc_attr( $value ) . '" />';
}

function save_project_details( $post_id ) {
    if ( ! isset( $_POST['project_details_nonce'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['project_details_nonce'], 'project_details_nonce' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    if ( isset( $_POST['project_url'] ) ) {
        update_post_meta( $post_id, '_project_url',
            sanitize_text_field( $_POST['project_url'] ) );
    }
}
add_action( 'save_post_project', 'save_project_details' );
```

### 6. REST API and Custom Endpoints

Extend WordPress REST API or create custom endpoints.

**Register custom REST endpoint:**
```php
function register_custom_rest_route() {
    register_rest_route( 'myplugin/v1', '/data', array(
        'methods'             => 'GET',
        'callback'            => 'get_custom_data',
        'permission_callback' => 'custom_permissions_check',
        'args'                => array(
            'id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric( $param );
                }
            ),
        ),
    ));
}
add_action( 'rest_api_init', 'register_custom_rest_route' );

function get_custom_data( $request ) {
    $id = $request->get_param( 'id' );
    // Process and return data
    return new WP_REST_Response( array(
        'success' => true,
        'data'    => $result
    ), 200 );
}

function custom_permissions_check() {
    return current_user_can( 'edit_posts' );
}
```

**Add custom fields to existing endpoints:**
```php
function add_custom_field_to_posts() {
    register_rest_field( 'post', 'custom_field', array(
        'get_callback'    => 'get_custom_field_value',
        'update_callback' => 'update_custom_field_value',
        'schema'          => array(
            'description' => __( 'Custom field' ),
            'type'        => 'string'
        ),
    ));
}
add_action( 'rest_api_init', 'add_custom_field_to_posts' );
```

### 7. Database Operations

Work with WordPress database using wpdb class and custom tables.

**Using wpdb for custom queries:**
```php
global $wpdb;

// Prepared statements (always use for security)
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->posts} WHERE post_author = %d AND post_status = %s",
        $author_id,
        'publish'
    )
);

// Insert
$wpdb->insert(
    $wpdb->prefix . 'custom_table',
    array(
        'column1' => $value1,
        'column2' => $value2
    ),
    array( '%s', '%d' ) // Format: %s = string, %d = integer, %f = float
);

// Update
$wpdb->update(
    $wpdb->prefix . 'custom_table',
    array( 'column1' => $new_value ),
    array( 'ID' => $id ),
    array( '%s' ),
    array( '%d' )
);

// Delete
$wpdb->delete(
    $wpdb->prefix . 'custom_table',
    array( 'ID' => $id ),
    array( '%d' )
);
```

**Creating custom tables:**
```php
function create_custom_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_table';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        name tinytext NOT NULL,
        text text NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
register_activation_hook( __FILE__, 'create_custom_table' );
```

See [references/database-schema.md](references/database-schema.md) for WordPress database structure.

### 8. WooCommerce Extensions

Extend WooCommerce functionality with custom plugins and modifications.

**Common WooCommerce hooks:**
```php
// Add custom field to product
add_action( 'woocommerce_product_options_general_product_data',
    'add_custom_product_field' );

// Save custom field
add_action( 'woocommerce_process_product_meta',
    'save_custom_product_field' );

// Modify price
add_filter( 'woocommerce_get_price_html', 'custom_price_html', 10, 2 );

// Add custom payment gateway
add_filter( 'woocommerce_payment_gateways', 'add_custom_gateway' );

// Customize checkout fields
add_filter( 'woocommerce_checkout_fields', 'custom_checkout_fields' );

// Custom order status
add_action( 'init', 'register_custom_order_status' );
add_filter( 'wc_order_statuses', 'add_custom_order_status_to_list' );
```

**Create custom WooCommerce product type:**
```php
class WC_Product_Custom extends WC_Product {
    public function get_type() {
        return 'custom';
    }
}

add_filter( 'woocommerce_product_class', 'custom_product_class', 10, 2 );
function custom_product_class( $classname, $product_type ) {
    if ( $product_type === 'custom' ) {
        $classname = 'WC_Product_Custom';
    }
    return $classname;
}
```

### 9. Security Best Practices

Implement WordPress security standards in all custom code.

**Foundation:** See [backend-expert Security](/home/raskardev/.claude/skills/backend-expert/SKILL.md#security) for comprehensive PHP security patterns including OWASP Top 10, SQL injection prevention, XSS prevention, and CSRF protection.

**WordPress-specific security practices:**

1. **Nonce verification:**
```php
wp_nonce_field( 'my_action', 'my_nonce_field' );

if ( ! isset( $_POST['my_nonce_field'] ) ||
     ! wp_verify_nonce( $_POST['my_nonce_field'], 'my_action' ) ) {
    die( 'Security check failed' );
}
```

2. **Capability checks:**
```php
if ( ! current_user_can( 'edit_posts' ) ) {
    wp_die( 'Unauthorized' );
}
```

3. **Data sanitization:**
```php
$safe_text = sanitize_text_field( $_POST['text'] );
$safe_email = sanitize_email( $_POST['email'] );
$safe_url = esc_url_raw( $_POST['url'] );
$safe_html = wp_kses_post( $_POST['content'] );
```

4. **Output escaping:**
```php
echo esc_html( $text );
echo esc_attr( $attribute );
echo esc_url( $url );
echo wp_kses_post( $html_content );
```

5. **Prepared statements:**
```php
$wpdb->prepare( "SELECT * FROM table WHERE id = %d", $id );
```

### 10. Performance Optimization

Optimize WordPress sites for speed and scalability.

**Key optimization techniques:**

1. **Caching:**
```php
// Transient API for caching
set_transient( 'my_cache_key', $data, DAY_IN_SECONDS );
$cached = get_transient( 'my_cache_key' );

// Object caching
wp_cache_set( 'my_key', $data, 'my_group', 3600 );
$cached = wp_cache_get( 'my_key', 'my_group' );
```

2. **Database query optimization:**
```php
// Use WP_Query efficiently
$query = new WP_Query( array(
    'post_type'      => 'post',
    'posts_per_page' => 10,
    'no_found_rows'  => true, // Skip counting for pagination
    'fields'         => 'ids', // Only return IDs if that's all you need
));
```

3. **Lazy loading and conditional loading:**
```php
// Only load scripts where needed
function conditional_scripts() {
    if ( is_page( 'contact' ) ) {
        wp_enqueue_script( 'contact-form' );
    }
}
add_action( 'wp_enqueue_scripts', 'conditional_scripts' );
```

4. **Asset optimization:**
```php
// Defer/async scripts
function add_defer_attribute( $tag, $handle ) {
    if ( 'my-script' !== $handle ) {
        return $tag;
    }
    return str_replace( ' src', ' defer src', $tag );
}
add_filter( 'script_loader_tag', 'add_defer_attribute', 10, 2 );
```

## Best Practices

1. **Follow WordPress Coding Standards**: Use WPCS and adhere to WordPress PHP, HTML, CSS, and JavaScript standards
2. **Use WordPress APIs**: Leverage built-in APIs instead of custom implementations
3. **Security first**: Always sanitize input, escape output, verify nonces, check capabilities. See [backend-expert Security](/home/raskardev/.claude/skills/backend-expert/SKILL.md#security)
4. **Make it extensible**: Use hooks and filters to allow others to extend your code
5. **Translation ready**: Use translation functions (`__()`, `_e()`, `esc_html__()`, etc.)
6. **Prefix everything**: Use unique prefixes for functions, classes, constants, database tables
7. **Documentation**: Comment complex logic, document hooks and filters
8. **Version control**: Use semantic versioning and maintain changelog
9. **Testing**: Test across WordPress versions, themes, and common plugins. See [testing-expert](/home/raskardev/.claude/skills/testing-expert/SKILL.md) for PHPUnit patterns
10. **Performance conscious**: Minimize database queries, cache when appropriate
11. **Modern PHP**: Apply PHP 8.3+ features where backward compatible. See [php-expert](/home/raskardev/.claude/skills/php-expert/SKILL.md) for modern patterns

## Testing WordPress Code

**Foundation:** See [testing-expert](/home/raskardev/.claude/skills/testing-expert/SKILL.md) for PHPUnit fundamentals, TDD workflow, and testing patterns.

**WordPress-specific testing:**

```php
// Installing WordPress test suite
composer require --dev phpunit/phpunit
composer require --dev yoast/phpunit-polyfills

// phpunit.xml
<phpunit bootstrap="tests/bootstrap.php">
    <testsuites>
        <testsuite name="Plugin Test Suite">
            <directory>tests/unit</directory>
            <directory>tests/integration</directory>
        </testsuite>
    </testsuites>
</phpunit>

// tests/bootstrap.php
$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
    $_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
    require dirname( __FILE__ ) . '/../plugin-name.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';
```

**Example unit test:**
```php
class Plugin_Test extends \PHPUnit\Framework\TestCase {
    public function test_plugin_activated() {
        $this->assertTrue( is_plugin_active( 'plugin-name/plugin-name.php' ) );
    }

    public function test_custom_post_type_registered() {
        $this->assertTrue( post_type_exists( 'project' ) );
    }
}
```

## Resources

### PHP Skills Foundation
**Core PHP skills that support WordPress development:**
- **[php-expert](/home/raskardev/.claude/skills/php-expert/SKILL.md)**: PHP 8.3+ features, OOP, SOLID, design patterns, error handling, performance optimization
- **[backend-expert](/home/raskardev/.claude/skills/backend-expert/SKILL.md)**: PHP security (OWASP), input validation, output escaping, prepared statements, JWT auth, logging
- **[testing-expert](/home/raskardev/.claude/skills/testing-expert/SKILL.md)**: PHPUnit, Pest, TDD, mocking, test coverage for WordPress plugins/themes

### WordPress-specific references/
- **plugin-patterns.md**: Comprehensive plugin development patterns and architecture
- **hooks-filters.md**: Reference guide for common WordPress hooks and filters
- **mcp-operations.md**: WordPress MCP integration patterns and operations
- **database-schema.md**: WordPress database structure and custom table patterns
- **woocommerce-hooks.md**: Essential WooCommerce hooks and customization patterns

### scripts/
Example scripts for common WordPress operations (can be adapted as needed).
