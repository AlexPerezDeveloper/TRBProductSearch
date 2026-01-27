# WooCommerce Hooks and Customization Patterns

Comprehensive reference for WooCommerce development, hooks, and common customization patterns.

## Product Hooks

### Product Data Hooks

**`woocommerce_product_options_general_product_data`**
- Add custom fields to General product tab
```php
add_action( 'woocommerce_product_options_general_product_data', 'add_custom_general_fields' );
function add_custom_general_fields() {
    woocommerce_wp_text_input( array(
        'id'          => '_custom_field',
        'label'       => __( 'Custom Field', 'textdomain' ),
        'placeholder' => '',
        'desc_tip'    => true,
        'description' => __( 'Enter custom value', 'textdomain' ),
    ));
}
```

**`woocommerce_product_options_inventory_product_data`**
- Add fields to Inventory tab
```php
add_action( 'woocommerce_product_options_inventory_product_data', 'add_inventory_fields' );
```

**`woocommerce_product_options_advanced`**
- Add fields to Advanced tab
```php
add_action( 'woocommerce_product_options_advanced', 'add_advanced_fields' );
```

**`woocommerce_process_product_meta`**
- Save product custom fields
```php
add_action( 'woocommerce_process_product_meta', 'save_custom_fields' );
function save_custom_fields( $post_id ) {
    $custom_field = isset( $_POST['_custom_field'] ) ? sanitize_text_field( $_POST['_custom_field'] ) : '';
    update_post_meta( $post_id, '_custom_field', $custom_field );
}
```

### Product Display Hooks

**`woocommerce_before_single_product`**
- Before single product page content
```php
add_action( 'woocommerce_before_single_product', 'custom_before_product' );
```

**`woocommerce_before_single_product_summary`**
- Before product summary (image area)
```php
add_action( 'woocommerce_before_single_product_summary', 'custom_product_banner', 5 );
```

**`woocommerce_single_product_summary`**
- Inside product summary (title, price, add to cart)
- Priority determines position:
  - 5: Title
  - 10: Price
  - 15: Excerpt
  - 20: Add to cart
  - 25: Meta
  - 30: Sharing
```php
add_action( 'woocommerce_single_product_summary', 'custom_product_info', 25 );
```

**`woocommerce_after_single_product_summary`**
- After product summary (tabs area)
```php
add_action( 'woocommerce_after_single_product_summary', 'custom_related_content', 15 );
```

**`woocommerce_product_thumbnails`**
- Product gallery thumbnails
```php
add_action( 'woocommerce_product_thumbnails', 'custom_gallery' );
```

### Product Loop Hooks (Archive/Shop)

**`woocommerce_before_shop_loop`**
- Before product loop on shop/archive pages
```php
add_action( 'woocommerce_before_shop_loop', 'custom_shop_notice' );
```

**`woocommerce_before_shop_loop_item`**
- Before each product in loop
```php
add_action( 'woocommerce_before_shop_loop_item', 'custom_product_badge' );
```

**`woocommerce_shop_loop_item_title`**
- Product title in loop
```php
add_action( 'woocommerce_shop_loop_item_title', 'custom_title_addon', 15 );
```

**`woocommerce_after_shop_loop_item`**
- After each product in loop
```php
add_action( 'woocommerce_after_shop_loop_item', 'custom_product_footer', 15 );
```

**`woocommerce_after_shop_loop`**
- After product loop
```php
add_action( 'woocommerce_after_shop_loop', 'custom_shop_footer' );
```

## Cart Hooks

### Cart Page Hooks

**`woocommerce_before_cart`**
- Before cart table
```php
add_action( 'woocommerce_before_cart', 'custom_cart_notice' );
```

**`woocommerce_before_cart_table`**
- Before cart table contents
```php
add_action( 'woocommerce_before_cart_table', 'custom_cart_header' );
```

**`woocommerce_cart_contents`**
- Inside cart table (each item)
```php
add_action( 'woocommerce_cart_contents', 'custom_cart_item_data' );
```

**`woocommerce_cart_coupon`**
- Coupon form area
```php
add_action( 'woocommerce_cart_coupon', 'custom_coupon_message' );
```

**`woocommerce_after_cart_table`**
- After cart table
```php
add_action( 'woocommerce_after_cart_table', 'custom_cart_suggestions' );
```

**`woocommerce_cart_totals_before_order_total`**
- Before order total in cart totals
```php
add_action( 'woocommerce_cart_totals_before_order_total', 'custom_fee_display' );
```

### Cart Actions

**`woocommerce_add_to_cart`**
- When product is added to cart
```php
add_action( 'woocommerce_add_to_cart', 'custom_add_to_cart_action', 10, 6 );
function custom_add_to_cart_action( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
    // Custom logic after adding to cart
}
```

**`woocommerce_cart_item_removed`**
- When item is removed from cart
```php
add_action( 'woocommerce_cart_item_removed', 'custom_item_removed', 10, 2 );
```

**`woocommerce_update_cart_action_cart_updated`**
- After cart is updated
```php
add_action( 'woocommerce_update_cart_action_cart_updated', 'custom_cart_updated' );
```

## Checkout Hooks

### Checkout Page Hooks

**`woocommerce_before_checkout_form`**
- Before checkout form
```php
add_action( 'woocommerce_before_checkout_form', 'custom_checkout_notice' );
```

**`woocommerce_checkout_before_customer_details`**
- Before customer details section
```php
add_action( 'woocommerce_checkout_before_customer_details', 'custom_checkout_header' );
```

**`woocommerce_checkout_billing`**
- Billing fields section
```php
add_action( 'woocommerce_checkout_billing', 'custom_billing_fields' );
```

**`woocommerce_checkout_shipping`**
- Shipping fields section
```php
add_action( 'woocommerce_checkout_shipping', 'custom_shipping_fields' );
```

**`woocommerce_checkout_before_order_review`**
- Before order review section
```php
add_action( 'woocommerce_checkout_before_order_review', 'custom_order_review_header' );
```

**`woocommerce_review_order_before_payment`**
- Before payment section
```php
add_action( 'woocommerce_review_order_before_payment', 'custom_payment_notice' );
```

**`woocommerce_checkout_after_order_review`**
- After order review
```php
add_action( 'woocommerce_checkout_after_order_review', 'custom_checkout_footer' );
```

### Checkout Field Customization

**`woocommerce_checkout_fields`**
- Modify checkout fields
```php
add_filter( 'woocommerce_checkout_fields', 'custom_checkout_fields' );
function custom_checkout_fields( $fields ) {
    // Add custom field
    $fields['billing']['billing_custom_field'] = array(
        'type'        => 'text',
        'label'       => __( 'Custom Field', 'textdomain' ),
        'placeholder' => __( 'Enter value', 'textdomain' ),
        'required'    => true,
        'class'       => array( 'form-row-wide' ),
        'priority'    => 25,
    );

    // Remove field
    unset( $fields['billing']['billing_company'] );

    // Modify field
    $fields['billing']['billing_phone']['required'] = false;

    return $fields;
}
```

**`woocommerce_checkout_update_order_meta`**
- Save custom checkout field
```php
add_action( 'woocommerce_checkout_update_order_meta', 'save_custom_checkout_field' );
function save_custom_checkout_field( $order_id ) {
    if ( ! empty( $_POST['billing_custom_field'] ) ) {
        update_post_meta( $order_id, '_billing_custom_field', sanitize_text_field( $_POST['billing_custom_field'] ) );
    }
}
```

### Checkout Validation

**`woocommerce_checkout_process`**
- Validate checkout fields
```php
add_action( 'woocommerce_checkout_process', 'custom_checkout_validation' );
function custom_checkout_validation() {
    if ( empty( $_POST['billing_custom_field'] ) ) {
        wc_add_notice( __( 'Custom field is required.', 'textdomain' ), 'error' );
    }
}
```

### Checkout Order Created

**`woocommerce_checkout_order_processed`**
- After order is created
```php
add_action( 'woocommerce_checkout_order_processed', 'custom_order_processing', 10, 3 );
function custom_order_processing( $order_id, $posted_data, $order ) {
    // Custom logic after order creation
}
```

**`woocommerce_thankyou`**
- Thank you page
```php
add_action( 'woocommerce_thankyou', 'custom_thankyou_page', 10, 1 );
function custom_thankyou_page( $order_id ) {
    $order = wc_get_order( $order_id );
    // Display custom thank you message
}
```

## Order Hooks

### Order Status Hooks

**`woocommerce_order_status_changed`**
- When order status changes
```php
add_action( 'woocommerce_order_status_changed', 'custom_order_status_change', 10, 4 );
function custom_order_status_change( $order_id, $old_status, $new_status, $order ) {
    if ( 'processing' === $new_status ) {
        // Order is now processing
    }
}
```

**Specific Status Hooks:**
```php
// Order completed
add_action( 'woocommerce_order_status_completed', 'custom_order_completed' );

// Order processing
add_action( 'woocommerce_order_status_processing', 'custom_order_processing' );

// Order on-hold
add_action( 'woocommerce_order_status_on-hold', 'custom_order_on_hold' );

// Order cancelled
add_action( 'woocommerce_order_status_cancelled', 'custom_order_cancelled' );

// Order refunded
add_action( 'woocommerce_order_status_refunded', 'custom_order_refunded' );

// Order failed
add_action( 'woocommerce_order_status_failed', 'custom_order_failed' );
```

### Order Email Hooks

**`woocommerce_email_before_order_table`**
- Before order table in email
```php
add_action( 'woocommerce_email_before_order_table', 'custom_email_content', 10, 4 );
function custom_email_content( $order, $sent_to_admin, $plain_text, $email ) {
    echo '<p>Custom email content</p>';
}
```

**`woocommerce_email_after_order_table`**
- After order table in email
```php
add_action( 'woocommerce_email_after_order_table', 'custom_email_footer', 10, 4 );
```

### Order Meta

**Display in Admin:**
```php
add_action( 'woocommerce_admin_order_data_after_billing_address', 'display_custom_order_meta', 10, 1 );
function display_custom_order_meta( $order ) {
    $custom_field = get_post_meta( $order->get_id(), '_billing_custom_field', true );
    if ( $custom_field ) {
        echo '<p><strong>' . __( 'Custom Field', 'textdomain' ) . ':</strong> ' . esc_html( $custom_field ) . '</p>';
    }
}
```

## Payment Gateway Hooks

### Register Custom Payment Gateway

```php
add_filter( 'woocommerce_payment_gateways', 'add_custom_gateway' );
function add_custom_gateway( $gateways ) {
    $gateways[] = 'WC_Gateway_Custom';
    return $gateways;
}

class WC_Gateway_Custom extends WC_Payment_Gateway {
    public function __construct() {
        $this->id                 = 'custom_gateway';
        $this->icon               = '';
        $this->has_fields         = true;
        $this->method_title       = __( 'Custom Gateway', 'textdomain' );
        $this->method_description = __( 'Custom payment gateway', 'textdomain' );

        $this->init_form_fields();
        $this->init_settings();

        $this->title       = $this->get_option( 'title' );
        $this->description = $this->get_option( 'description' );
        $this->enabled     = $this->get_option( 'enabled' );

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => __( 'Enable/Disable', 'textdomain' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable Custom Gateway', 'textdomain' ),
                'default' => 'yes'
            ),
            'title' => array(
                'title'       => __( 'Title', 'textdomain' ),
                'type'        => 'text',
                'description' => __( 'Payment method title', 'textdomain' ),
                'default'     => __( 'Custom Payment', 'textdomain' ),
            ),
        );
    }

    public function process_payment( $order_id ) {
        $order = wc_get_order( $order_id );

        // Process payment here

        // Mark order as processing
        $order->payment_complete();

        // Return success redirect
        return array(
            'result'   => 'success',
            'redirect' => $this->get_return_url( $order )
        );
    }
}
```

## Price Modification Hooks

**`woocommerce_product_get_price`**
- Filter product price
```php
add_filter( 'woocommerce_product_get_price', 'custom_product_price', 10, 2 );
function custom_product_price( $price, $product ) {
    // Modify price based on logic
    if ( is_user_logged_in() ) {
        $price = $price * 0.9; // 10% discount for logged-in users
    }
    return $price;
}
```

**`woocommerce_get_price_html`**
- Filter price display HTML
```php
add_filter( 'woocommerce_get_price_html', 'custom_price_html', 10, 2 );
function custom_price_html( $price_html, $product ) {
    if ( ! is_user_logged_in() ) {
        return '<span class="login-required">' . __( 'Login to see price', 'textdomain' ) . '</span>';
    }
    return $price_html;
}
```

**`woocommerce_cart_item_price`**
- Filter cart item price display
```php
add_filter( 'woocommerce_cart_item_price', 'custom_cart_item_price', 10, 3 );
```

## Custom Order Status

### Register Custom Status

```php
add_action( 'init', 'register_custom_order_status' );
function register_custom_order_status() {
    register_post_status( 'wc-custom-status', array(
        'label'                     => __( 'Custom Status', 'textdomain' ),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Custom Status <span class="count">(%s)</span>', 'Custom Status <span class="count">(%s)</span>', 'textdomain' )
    ));
}

add_filter( 'wc_order_statuses', 'add_custom_order_status' );
function add_custom_order_status( $order_statuses ) {
    $order_statuses['wc-custom-status'] = __( 'Custom Status', 'textdomain' );
    return $order_statuses;
}
```

## Stock Management Hooks

**`woocommerce_product_set_stock`**
- When product stock is updated
```php
add_action( 'woocommerce_product_set_stock', 'custom_stock_update' );
function custom_stock_update( $product ) {
    // Notify when stock is low
    if ( $product->get_stock_quantity() <= 5 ) {
        // Send notification
    }
}
```

**`woocommerce_low_stock`**
- When stock reaches low threshold
```php
add_action( 'woocommerce_low_stock', 'custom_low_stock_alert' );
```

**`woocommerce_no_stock`**
- When product is out of stock
```php
add_action( 'woocommerce_no_stock', 'custom_out_of_stock_alert' );
```

## Account & My Account Hooks

**`woocommerce_account_dashboard`**
- My Account dashboard
```php
add_action( 'woocommerce_account_dashboard', 'custom_account_dashboard' );
```

**`woocommerce_account_navigation`**
- My Account navigation
```php
add_filter( 'woocommerce_account_menu_items', 'custom_account_menu_items' );
function custom_account_menu_items( $items ) {
    $items['custom-endpoint'] = __( 'Custom Page', 'textdomain' );
    return $items;
}
```

**Add Custom Endpoint:**
```php
add_action( 'init', 'add_custom_account_endpoint' );
function add_custom_account_endpoint() {
    add_rewrite_endpoint( 'custom-endpoint', EP_ROOT | EP_PAGES );
}

add_filter( 'woocommerce_get_query_vars', 'custom_query_vars' );
function custom_query_vars( $vars ) {
    $vars['custom-endpoint'] = 'custom-endpoint';
    return $vars;
}

add_action( 'woocommerce_account_custom-endpoint_endpoint', 'custom_endpoint_content' );
function custom_endpoint_content() {
    echo '<h3>' . __( 'Custom Content', 'textdomain' ) . '</h3>';
    // Custom content here
}
```

## WooCommerce Admin Hooks

**`woocommerce_admin_order_item_headers`**
- Add column header to order items
```php
add_action( 'woocommerce_admin_order_item_headers', 'custom_order_item_header' );
function custom_order_item_header() {
    echo '<th>' . __( 'Custom Column', 'textdomain' ) . '</th>';
}
```

**`woocommerce_admin_order_item_values`**
- Add column content to order items
```php
add_action( 'woocommerce_admin_order_item_values', 'custom_order_item_values', 10, 3 );
function custom_order_item_values( $product, $item, $item_id ) {
    echo '<td>' . get_post_meta( $item_id, '_custom_field', true ) . '</td>';
}
```

## REST API Hooks

**`woocommerce_rest_prepare_product_object`**
- Modify product REST response
```php
add_filter( 'woocommerce_rest_prepare_product_object', 'custom_rest_product', 10, 3 );
function custom_rest_product( $response, $product, $request ) {
    $response->data['custom_field'] = get_post_meta( $product->get_id(), '_custom_field', true );
    return $response;
}
```

## Best Practices

1. **Check WooCommerce is active** before using WooCommerce functions
2. **Use WooCommerce APIs** instead of direct database queries
3. **Respect hook priorities** - check default WooCommerce hooks
4. **Test across product types** (simple, variable, grouped, external)
5. **Handle CRUD properly** - use WooCommerce CRUD classes
6. **Cache expensive operations** to improve performance
7. **Follow WooCommerce coding standards**
8. **Test with WooCommerce updates** - hooks can change
9. **Use child theme or plugin** - never modify WooCommerce core
10. **Document custom hooks** for other developers
