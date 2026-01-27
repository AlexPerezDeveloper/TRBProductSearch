# WordPress Hooks and Filters Reference

Comprehensive reference for commonly used WordPress action hooks and filters.

## Action Hooks

Action hooks allow you to execute custom code at specific points during WordPress execution.

### Initialization Hooks

**`init`**
- Fires after WordPress has finished loading but before any headers are sent
- Use for: Registering post types, taxonomies, shortcodes, rewrite rules
```php
add_action( 'init', 'my_custom_init' );
function my_custom_init() {
    // Register custom post types, taxonomies, etc.
}
```

**`plugins_loaded`**
- Fires after all active plugins have been loaded
- Use for: Plugin initialization, loading translations
```php
add_action( 'plugins_loaded', 'my_plugin_init' );
```

**`after_setup_theme`**
- Fires after the theme is loaded
- Use for: Theme features, custom image sizes
```php
add_action( 'after_setup_theme', 'my_theme_setup' );
function my_theme_setup() {
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'custom-logo' );
}
```

### Admin Hooks

**`admin_init`**
- Fires on every admin page load
- Use for: Registering settings, admin-only initialization
```php
add_action( 'admin_init', 'my_admin_init' );
```

**`admin_menu`**
- Fires when admin menu is being generated
- Use for: Adding custom admin pages
```php
add_action( 'admin_menu', 'add_my_admin_menu' );
function add_my_admin_menu() {
    add_menu_page( 'My Plugin', 'My Plugin', 'manage_options', 'my-plugin', 'my_plugin_page' );
}
```

**`admin_enqueue_scripts`**
- Fires when admin scripts should be enqueued
- Use for: Loading admin-specific CSS/JS
```php
add_action( 'admin_enqueue_scripts', 'my_admin_scripts' );
function my_admin_scripts( $hook ) {
    if ( 'settings_page_my-plugin' !== $hook ) {
        return;
    }
    wp_enqueue_script( 'my-admin-script', plugin_dir_url( __FILE__ ) . 'js/admin.js' );
}
```

**`admin_notices`**
- Fires when admin notices should be displayed
- Use for: Showing admin messages and notifications
```php
add_action( 'admin_notices', 'my_admin_notice' );
function my_admin_notice() {
    ?>
    <div class="notice notice-success is-dismissible">
        <p><?php _e( 'Settings saved successfully!', 'textdomain' ); ?></p>
    </div>
    <?php
}
```

### Frontend Hooks

**`wp_enqueue_scripts`**
- Fires when frontend scripts and styles should be enqueued
- Use for: Loading CSS/JS on the frontend
```php
add_action( 'wp_enqueue_scripts', 'my_scripts' );
function my_scripts() {
    wp_enqueue_style( 'my-style', get_stylesheet_uri() );
    wp_enqueue_script( 'my-script', get_template_directory_uri() . '/js/script.js', array( 'jquery' ) );
}
```

**`wp_head`**
- Fires in the `<head>` section of the site
- Use for: Adding meta tags, custom CSS, analytics code
```php
add_action( 'wp_head', 'my_custom_head_code' );
function my_custom_head_code() {
    echo '<meta name="description" content="My site description">';
}
```

**`wp_footer`**
- Fires before the closing `</body>` tag
- Use for: Adding scripts that should load at page end
```php
add_action( 'wp_footer', 'my_footer_code' );
```

**`wp_body_open`**
- Fires immediately after opening `<body>` tag
- Use for: Adding tracking pixels, body-top content
```php
add_action( 'wp_body_open', 'my_body_code' );
```

### Post/Page Hooks

**`save_post`**
- Fires when a post is created or updated
- Use for: Custom meta data processing
```php
add_action( 'save_post', 'my_save_post', 10, 3 );
function my_save_post( $post_id, $post, $update ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    // Save custom meta
}
```

**`save_post_{post_type}`**
- Fires when a specific post type is saved
- Use for: Post-type-specific processing
```php
add_action( 'save_post_product', 'save_product_meta', 10, 3 );
```

**`wp_insert_post`**
- Fires after a post is inserted into the database
- Use for: Post-creation side effects
```php
add_action( 'wp_insert_post', 'my_post_inserted', 10, 3 );
```

**`before_delete_post`**
- Fires before a post is deleted
- Use for: Cleanup operations before deletion
```php
add_action( 'before_delete_post', 'my_before_delete', 10, 2 );
```

**`trashed_post`**
- Fires after a post is moved to trash
```php
add_action( 'trashed_post', 'my_post_trashed' );
```

### Comment Hooks

**`comment_post`**
- Fires after a comment is posted
- Use for: Comment notifications, custom processing
```php
add_action( 'comment_post', 'my_comment_notification', 10, 3 );
```

**`wp_insert_comment`**
- Fires after a comment is inserted into the database
```php
add_action( 'wp_insert_comment', 'my_comment_inserted', 10, 2 );
```

### User Hooks

**`user_register`**
- Fires after a new user is registered
- Use for: Welcome emails, default user meta
```php
add_action( 'user_register', 'my_user_registered' );
function my_user_registered( $user_id ) {
    // Send welcome email
    // Set default user meta
}
```

**`wp_login`**
- Fires after a user logs in
- Use for: Login tracking, redirects
```php
add_action( 'wp_login', 'my_login_handler', 10, 2 );
function my_login_handler( $user_login, $user ) {
    // Track login
}
```

**`profile_update`**
- Fires after a user profile is updated
```php
add_action( 'profile_update', 'my_profile_update', 10, 2 );
```

### Template Hooks

**`template_redirect`**
- Fires before determining which template to load
- Use for: Custom redirects, template logic
```php
add_action( 'template_redirect', 'my_redirect_logic' );
function my_redirect_logic() {
    if ( is_page( 'old-page' ) ) {
        wp_redirect( home_url( '/new-page' ), 301 );
        exit;
    }
}
```

**`loop_start`**
- Fires at the start of the WordPress loop
```php
add_action( 'loop_start', 'my_loop_start' );
```

**`loop_end`**
- Fires at the end of the WordPress loop
```php
add_action( 'loop_end', 'my_loop_end' );
```

### AJAX Hooks

**`wp_ajax_{action}`**
- Fires for logged-in AJAX requests
```php
add_action( 'wp_ajax_my_action', 'my_ajax_handler' );
```

**`wp_ajax_nopriv_{action}`**
- Fires for non-logged-in AJAX requests
```php
add_action( 'wp_ajax_nopriv_my_action', 'my_public_ajax_handler' );
```

### REST API Hooks

**`rest_api_init`**
- Fires when REST API is initialized
- Use for: Registering custom REST routes and fields
```php
add_action( 'rest_api_init', 'my_rest_routes' );
function my_rest_routes() {
    register_rest_route( 'myplugin/v1', '/endpoint', array(
        'methods'  => 'GET',
        'callback' => 'my_rest_callback',
    ));
}
```

### Widget Hooks

**`widgets_init`**
- Fires when widgets are initialized
- Use for: Registering sidebars and widgets
```php
add_action( 'widgets_init', 'my_widgets_init' );
function my_widgets_init() {
    register_sidebar( array(
        'name'          => __( 'Sidebar', 'textdomain' ),
        'id'            => 'sidebar-1',
        'before_widget' => '<div class="widget">',
        'after_widget'  => '</div>',
    ));
}
```

## Filter Hooks

Filter hooks allow you to modify data before it's displayed or saved.

### Content Filters

**`the_content`**
- Filters the post content
- Use for: Adding content, modifying display
```php
add_filter( 'the_content', 'my_content_filter' );
function my_content_filter( $content ) {
    if ( is_single() ) {
        $content .= '<p>Additional content</p>';
    }
    return $content;
}
```

**`the_title`**
- Filters the post title
```php
add_filter( 'the_title', 'my_title_filter', 10, 2 );
function my_title_filter( $title, $post_id ) {
    if ( is_admin() ) return $title;
    return '[Custom] ' . $title;
}
```

**`the_excerpt`**
- Filters the post excerpt
```php
add_filter( 'the_excerpt', 'my_excerpt_filter' );
```

**`excerpt_length`**
- Filters the excerpt word count
```php
add_filter( 'excerpt_length', 'my_excerpt_length' );
function my_excerpt_length( $length ) {
    return 30;
}
```

**`excerpt_more`**
- Filters the excerpt "read more" text
```php
add_filter( 'excerpt_more', 'my_excerpt_more' );
function my_excerpt_more( $more ) {
    return '... <a href="' . get_permalink() . '">Read More</a>';
}
```

### Query Filters

**`pre_get_posts`**
- Filters the query before it runs
- Use for: Modifying main query, custom query logic
```php
add_action( 'pre_get_posts', 'my_modify_query' );
function my_modify_query( $query ) {
    if ( ! is_admin() && $query->is_main_query() && is_home() ) {
        $query->set( 'posts_per_page', 20 );
    }
}
```

**`posts_where`**
- Filters the WHERE clause of the query
```php
add_filter( 'posts_where', 'my_custom_where' );
```

**`posts_join`**
- Filters the JOIN clause of the query
```php
add_filter( 'posts_join', 'my_custom_join' );
```

### Template Filters

**`template_include`**
- Filters the path of the template file to load
- Use for: Custom template loading logic
```php
add_filter( 'template_include', 'my_template_include' );
function my_template_include( $template ) {
    if ( is_page( 'custom' ) ) {
        return plugin_dir_path( __FILE__ ) . 'templates/custom-page.php';
    }
    return $template;
}
```

**`body_class`**
- Filters the body CSS classes
```php
add_filter( 'body_class', 'my_body_classes' );
function my_body_classes( $classes ) {
    if ( is_page( 'about' ) ) {
        $classes[] = 'custom-about-page';
    }
    return $classes;
}
```

**`post_class`**
- Filters the post CSS classes
```php
add_filter( 'post_class', 'my_post_classes', 10, 3 );
```

### Authentication Filters

**`authenticate`**
- Filters user authentication
- Use for: Custom login logic, two-factor auth
```php
add_filter( 'authenticate', 'my_custom_auth', 30, 3 );
function my_custom_auth( $user, $username, $password ) {
    // Custom authentication logic
    return $user;
}
```

**`login_redirect`**
- Filters the login redirect URL
```php
add_filter( 'login_redirect', 'my_login_redirect', 10, 3 );
function my_login_redirect( $redirect_to, $request, $user ) {
    if ( isset( $user->roles ) && is_array( $user->roles ) ) {
        if ( in_array( 'subscriber', $user->roles ) ) {
            return home_url( '/my-account' );
        }
    }
    return $redirect_to;
}
```

### Upload Filters

**`upload_mimes`**
- Filters allowed upload file types
```php
add_filter( 'upload_mimes', 'my_custom_upload_mimes' );
function my_custom_upload_mimes( $mimes ) {
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
}
```

**`wp_handle_upload_prefilter`**
- Filters file upload data before upload
```php
add_filter( 'wp_handle_upload_prefilter', 'my_upload_filter' );
function my_upload_filter( $file ) {
    // Validate or modify file before upload
    return $file;
}
```

### Admin Filters

**`admin_footer_text`**
- Filters the admin footer text
```php
add_filter( 'admin_footer_text', 'my_admin_footer_text' );
function my_admin_footer_text( $text ) {
    return 'Custom admin footer text';
}
```

**`plugin_action_links_{plugin_basename}`**
- Filters plugin action links
- Use for: Adding settings link to plugins page
```php
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'my_plugin_action_links' );
function my_plugin_action_links( $links ) {
    $settings_link = '<a href="options-general.php?page=my-plugin">' . __( 'Settings' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
```

### Email Filters

**`wp_mail_from`**
- Filters the "from" email address
```php
add_filter( 'wp_mail_from', 'my_mail_from' );
function my_mail_from( $email ) {
    return 'noreply@example.com';
}
```

**`wp_mail_from_name`**
- Filters the "from" name
```php
add_filter( 'wp_mail_from_name', 'my_mail_from_name' );
function my_mail_from_name( $name ) {
    return 'My Site Name';
}
```

### Sanitization Filters

**`sanitize_title`**
- Filters how titles are sanitized
```php
add_filter( 'sanitize_title', 'my_sanitize_title', 10, 3 );
```

**`sanitize_file_name`**
- Filters how filenames are sanitized
```php
add_filter( 'sanitize_file_name', 'my_sanitize_filename' );
```

### Rewrite Filters

**`rewrite_rules_array`**
- Filters the entire rewrite rules array
```php
add_filter( 'rewrite_rules_array', 'my_rewrite_rules' );
```

**`query_vars`**
- Filters the list of public query variables
```php
add_filter( 'query_vars', 'my_query_vars' );
function my_query_vars( $vars ) {
    $vars[] = 'my_custom_var';
    return $vars;
}
```

## Hook Priority and Parameters

### Priority
- Default priority is `10`
- Lower numbers run earlier
- Higher numbers run later
- Common priorities: `5` (early), `10` (default), `15`, `20` (late)

```php
// Runs early
add_action( 'init', 'my_early_function', 5 );

// Runs at default time
add_action( 'init', 'my_default_function' );

// Runs late
add_action( 'init', 'my_late_function', 20 );
```

### Accepted Arguments
Specify how many parameters your callback accepts:

```php
// Accept 1 parameter (default)
add_filter( 'the_title', 'my_title_filter' );

// Accept 2 parameters
add_filter( 'the_title', 'my_title_filter_with_id', 10, 2 );
function my_title_filter_with_id( $title, $post_id ) {
    return $title;
}

// Accept 3 parameters
add_action( 'save_post', 'my_save_handler', 10, 3 );
function my_save_handler( $post_id, $post, $update ) {
    // ...
}
```

## Custom Hooks

### Creating Custom Actions

```php
// In your plugin/theme
function my_custom_function() {
    // Before processing
    do_action( 'my_plugin_before_process' );

    // Process
    $result = process_something();

    // After processing (pass data)
    do_action( 'my_plugin_after_process', $result );
}

// Other developers can hook into this
add_action( 'my_plugin_before_process', 'my_custom_handler' );
add_action( 'my_plugin_after_process', 'my_result_handler' );
function my_result_handler( $result ) {
    // Do something with $result
}
```

### Creating Custom Filters

```php
// In your plugin/theme
function my_custom_function() {
    $value = 'default';

    // Allow filtering
    $value = apply_filters( 'my_plugin_custom_value', $value, $context );

    return $value;
}

// Other developers can filter this
add_filter( 'my_plugin_custom_value', 'my_custom_filter', 10, 2 );
function my_custom_filter( $value, $context ) {
    if ( $context === 'special' ) {
        return 'modified_value';
    }
    return $value;
}
```

## Best Practices

1. **Always check context** - Use conditional tags to ensure hooks run only when needed
2. **Return filtered values** - Filters must always return a value
3. **Use specific hooks** - Use `save_post_{post_type}` instead of `save_post` when possible
4. **Check capabilities** - Always verify user permissions in action callbacks
5. **Validate and sanitize** - Always sanitize input and validate data
6. **Document custom hooks** - Document any custom hooks you create for other developers
7. **Use appropriate priority** - Set priority based on when your code needs to run
8. **Remove hooks when needed** - Use `remove_action()` and `remove_filter()` to unhook
9. **Avoid infinite loops** - Be careful when filtering content that might trigger the same filter
10. **Use has_action/has_filter** - Check if hooks are registered before relying on them
