# WordPress Plugin Development Patterns

Comprehensive patterns and best practices for WordPress plugin development.

## Plugin Architecture Patterns

### 1. Singleton Pattern (Main Plugin Class)

```php
<?php
/**
 * Plugin Name: My Custom Plugin
 * Plugin URI: https://example.com/my-plugin
 * Description: A brief description of the plugin
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: my-custom-plugin
 * Domain Path: /languages
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'MCP_VERSION', '1.0.0' );
define( 'MCP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MCP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MCP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

class My_Custom_Plugin {
    /**
     * Single instance of the class
     */
    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load required dependencies
     */
    private function load_dependencies() {
        require_once MCP_PLUGIN_DIR . 'includes/class-loader.php';
        require_once MCP_PLUGIN_DIR . 'includes/class-i18n.php';
        require_once MCP_PLUGIN_DIR . 'admin/class-admin.php';
        require_once MCP_PLUGIN_DIR . 'public/class-public.php';
    }

    /**
     * Set plugin locale for translations
     */
    private function set_locale() {
        $plugin_i18n = new My_Custom_Plugin_i18n();
        add_action( 'plugins_loaded', array( $plugin_i18n, 'load_plugin_textdomain' ) );
    }

    /**
     * Register admin-specific hooks
     */
    private function define_admin_hooks() {
        $plugin_admin = new My_Custom_Plugin_Admin();
        add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_scripts' ) );
        add_action( 'admin_menu', array( $plugin_admin, 'add_admin_menu' ) );
    }

    /**
     * Register public-facing hooks
     */
    private function define_public_hooks() {
        $plugin_public = new My_Custom_Plugin_Public();
        add_action( 'wp_enqueue_scripts', array( $plugin_public, 'enqueue_styles' ) );
        add_action( 'wp_enqueue_scripts', array( $plugin_public, 'enqueue_scripts' ) );
    }
}

// Initialize plugin
function run_my_custom_plugin() {
    return My_Custom_Plugin::get_instance();
}
run_my_custom_plugin();

// Activation hook
register_activation_hook( __FILE__, 'mcp_activate_plugin' );
function mcp_activate_plugin() {
    // Setup default options
    if ( ! get_option( 'mcp_settings' ) ) {
        add_option( 'mcp_settings', array(
            'option1' => 'default_value',
            'option2' => true,
        ));
    }

    // Create custom database tables if needed
    global $wpdb;
    $table_name = $wpdb->prefix . 'mcp_custom_table';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        data text NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    // Flush rewrite rules if adding custom post types
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook( __FILE__, 'mcp_deactivate_plugin' );
function mcp_deactivate_plugin() {
    // Clean up temporary data
    delete_transient( 'mcp_cache_key' );

    // Flush rewrite rules
    flush_rewrite_rules();
}

// Uninstall hook (in separate uninstall.php file)
```

### 2. Settings API Pattern

```php
class My_Plugin_Settings {
    private $option_group = 'mcp_settings_group';
    private $option_name = 'mcp_settings';

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function add_settings_page() {
        add_options_page(
            __( 'My Plugin Settings', 'my-custom-plugin' ),
            __( 'My Plugin', 'my-custom-plugin' ),
            'manage_options',
            'my-plugin-settings',
            array( $this, 'render_settings_page' )
        );
    }

    public function register_settings() {
        register_setting(
            $this->option_group,
            $this->option_name,
            array( $this, 'sanitize_settings' )
        );

        add_settings_section(
            'mcp_main_section',
            __( 'Main Settings', 'my-custom-plugin' ),
            array( $this, 'render_section_description' ),
            'my-plugin-settings'
        );

        add_settings_field(
            'mcp_text_field',
            __( 'Text Field', 'my-custom-plugin' ),
            array( $this, 'render_text_field' ),
            'my-plugin-settings',
            'mcp_main_section',
            array( 'label_for' => 'mcp_text_field' )
        );

        add_settings_field(
            'mcp_checkbox_field',
            __( 'Enable Feature', 'my-custom-plugin' ),
            array( $this, 'render_checkbox_field' ),
            'my-plugin-settings',
            'mcp_main_section',
            array( 'label_for' => 'mcp_checkbox_field' )
        );
    }

    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields( $this->option_group );
                do_settings_sections( 'my-plugin-settings' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function render_section_description() {
        echo '<p>' . esc_html__( 'Configure your plugin settings below.', 'my-custom-plugin' ) . '</p>';
    }

    public function render_text_field( $args ) {
        $options = get_option( $this->option_name );
        $value = isset( $options['text_field'] ) ? $options['text_field'] : '';
        ?>
        <input type="text"
               id="<?php echo esc_attr( $args['label_for'] ); ?>"
               name="<?php echo esc_attr( $this->option_name . '[text_field]' ); ?>"
               value="<?php echo esc_attr( $value ); ?>"
               class="regular-text">
        <?php
    }

    public function render_checkbox_field( $args ) {
        $options = get_option( $this->option_name );
        $checked = isset( $options['checkbox_field'] ) && $options['checkbox_field'];
        ?>
        <label>
            <input type="checkbox"
                   id="<?php echo esc_attr( $args['label_for'] ); ?>"
                   name="<?php echo esc_attr( $this->option_name . '[checkbox_field]' ); ?>"
                   value="1"
                   <?php checked( $checked, true ); ?>>
            <?php esc_html_e( 'Enable this feature', 'my-custom-plugin' ); ?>
        </label>
        <?php
    }

    public function sanitize_settings( $input ) {
        $sanitized = array();

        if ( isset( $input['text_field'] ) ) {
            $sanitized['text_field'] = sanitize_text_field( $input['text_field'] );
        }

        if ( isset( $input['checkbox_field'] ) ) {
            $sanitized['checkbox_field'] = (bool) $input['checkbox_field'];
        }

        return $sanitized;
    }
}
```

### 3. AJAX Handler Pattern

```php
class My_Plugin_Ajax {
    public function __construct() {
        // For logged-in users
        add_action( 'wp_ajax_mcp_custom_action', array( $this, 'handle_custom_action' ) );

        // For non-logged-in users
        add_action( 'wp_ajax_nopriv_mcp_custom_action', array( $this, 'handle_custom_action' ) );
    }

    public function handle_custom_action() {
        // Verify nonce
        if ( ! check_ajax_referer( 'mcp_ajax_nonce', 'nonce', false ) ) {
            wp_send_json_error( array(
                'message' => __( 'Security check failed', 'my-custom-plugin' )
            ));
        }

        // Check capabilities
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Insufficient permissions', 'my-custom-plugin' )
            ));
        }

        // Sanitize input
        $data = isset( $_POST['data'] ) ? sanitize_text_field( $_POST['data'] ) : '';

        if ( empty( $data ) ) {
            wp_send_json_error( array(
                'message' => __( 'No data provided', 'my-custom-plugin' )
            ));
        }

        // Process the request
        $result = $this->process_data( $data );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array(
                'message' => $result->get_error_message()
            ));
        }

        // Send success response
        wp_send_json_success( array(
            'message' => __( 'Data processed successfully', 'my-custom-plugin' ),
            'result'  => $result
        ));
    }

    private function process_data( $data ) {
        // Your processing logic here
        return $data;
    }
}

// In your enqueue scripts function:
function mcp_enqueue_ajax_script() {
    wp_enqueue_script( 'mcp-ajax', MCP_PLUGIN_URL . 'js/ajax.js', array( 'jquery' ), MCP_VERSION, true );

    wp_localize_script( 'mcp-ajax', 'mcpAjax', array(
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'mcp_ajax_nonce' )
    ));
}
```

JavaScript companion (ajax.js):
```javascript
jQuery(document).ready(function($) {
    $('#submit-button').on('click', function(e) {
        e.preventDefault();

        var data = {
            action: 'mcp_custom_action',
            nonce: mcpAjax.nonce,
            data: $('#input-field').val()
        };

        $.post(mcpAjax.ajaxurl, data, function(response) {
            if (response.success) {
                alert(response.data.message);
            } else {
                alert('Error: ' + response.data.message);
            }
        });
    });
});
```

### 4. Shortcode Pattern

```php
class My_Plugin_Shortcodes {
    public function __construct() {
        add_shortcode( 'my_shortcode', array( $this, 'render_shortcode' ) );
    }

    public function render_shortcode( $atts, $content = null ) {
        // Parse attributes with defaults
        $atts = shortcode_atts( array(
            'title'   => __( 'Default Title', 'my-custom-plugin' ),
            'type'    => 'default',
            'limit'   => 10,
            'show_date' => 'yes'
        ), $atts, 'my_shortcode' );

        // Sanitize attributes
        $title = sanitize_text_field( $atts['title'] );
        $type = sanitize_key( $atts['type'] );
        $limit = absint( $atts['limit'] );
        $show_date = ( 'yes' === $atts['show_date'] );

        // Start output buffering
        ob_start();
        ?>
        <div class="my-plugin-shortcode" data-type="<?php echo esc_attr( $type ); ?>">
            <h3><?php echo esc_html( $title ); ?></h3>
            <?php
            // Query logic here
            $args = array(
                'post_type'      => 'post',
                'posts_per_page' => $limit,
                'post_status'    => 'publish'
            );

            $query = new WP_Query( $args );

            if ( $query->have_posts() ) :
                echo '<ul class="my-plugin-list">';
                while ( $query->have_posts() ) : $query->the_post();
                    ?>
                    <li>
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        <?php if ( $show_date ) : ?>
                            <span class="date"><?php echo get_the_date(); ?></span>
                        <?php endif; ?>
                    </li>
                    <?php
                endwhile;
                echo '</ul>';
                wp_reset_postdata();
            else :
                echo '<p>' . esc_html__( 'No posts found.', 'my-custom-plugin' ) . '</p>';
            endif;
            ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
```

### 5. Widget Pattern

```php
class My_Custom_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'my_custom_widget',
            __( 'My Custom Widget', 'my-custom-plugin' ),
            array(
                'description' => __( 'A custom widget description', 'my-custom-plugin' ),
                'classname'   => 'my-custom-widget',
            )
        );
    }

    public function widget( $args, $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : '';
        $title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

        $number = ! empty( $instance['number'] ) ? absint( $instance['number'] ) : 5;

        echo $args['before_widget'];

        if ( $title ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }

        // Widget content
        echo '<div class="my-widget-content">';
        // Your widget logic here
        echo '</div>';

        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : '';
        $number = ! empty( $instance['number'] ) ? absint( $instance['number'] ) : 5;
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
                <?php esc_html_e( 'Title:', 'my-custom-plugin' ); ?>
            </label>
            <input class="widefat"
                   id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
                   type="text"
                   value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>">
                <?php esc_html_e( 'Number to show:', 'my-custom-plugin' ); ?>
            </label>
            <input class="tiny-text"
                   id="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'number' ) ); ?>"
                   type="number"
                   step="1"
                   min="1"
                   value="<?php echo esc_attr( $number ); ?>"
                   size="3">
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = sanitize_text_field( $new_instance['title'] );
        $instance['number'] = absint( $new_instance['number'] );
        return $instance;
    }
}

// Register widget
function register_my_custom_widget() {
    register_widget( 'My_Custom_Widget' );
}
add_action( 'widgets_init', 'register_my_custom_widget' );
```

### 6. Gutenberg Block Pattern

```php
function register_my_custom_block() {
    if ( ! function_exists( 'register_block_type' ) ) {
        return;
    }

    wp_register_script(
        'my-custom-block',
        MCP_PLUGIN_URL . 'blocks/my-block/build/index.js',
        array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n' ),
        MCP_VERSION
    );

    wp_register_style(
        'my-custom-block-editor',
        MCP_PLUGIN_URL . 'blocks/my-block/editor.css',
        array( 'wp-edit-blocks' ),
        MCP_VERSION
    );

    wp_register_style(
        'my-custom-block',
        MCP_PLUGIN_URL . 'blocks/my-block/style.css',
        array(),
        MCP_VERSION
    );

    register_block_type( 'my-plugin/my-block', array(
        'editor_script'   => 'my-custom-block',
        'editor_style'    => 'my-custom-block-editor',
        'style'           => 'my-custom-block',
        'render_callback' => 'render_my_custom_block',
        'attributes'      => array(
            'title' => array(
                'type'    => 'string',
                'default' => 'Default Title',
            ),
            'content' => array(
                'type'    => 'string',
                'default' => '',
            ),
        ),
    ));
}
add_action( 'init', 'register_my_custom_block' );

function render_my_custom_block( $attributes, $content ) {
    $title = isset( $attributes['title'] ) ? esc_html( $attributes['title'] ) : '';
    $block_content = isset( $attributes['content'] ) ? wp_kses_post( $attributes['content'] ) : '';

    ob_start();
    ?>
    <div class="wp-block-my-plugin-my-block">
        <?php if ( $title ) : ?>
            <h3><?php echo $title; ?></h3>
        <?php endif; ?>
        <div class="block-content">
            <?php echo $block_content; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
```

## Best Practices Summary

1. **Always prefix functions, classes, and database tables** to avoid conflicts
2. **Use nonces for security** on all form submissions
3. **Sanitize input, escape output** without exception
4. **Check user capabilities** before performing privileged operations
5. **Use WordPress APIs** instead of direct database queries when possible
6. **Make plugins translation-ready** from the start
7. **Follow WordPress coding standards** (WPCS)
8. **Version control your assets** for proper cache busting
9. **Provide uninstall cleanup** to remove all plugin data
10. **Test across multiple WordPress versions** and common themes/plugins
