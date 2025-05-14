<?php
/**
 * Plugin Name: WooCommerce Auto Stock Distributor by Weight
 * Plugin URI:  https://www.linkedin.com/in/haris-maqsood-ahmad/
 * Description: Auto-distributes variation stock based on main product quantity in pounds and weight in grams.
 * Version:     1.0.1
 * Author:      Haris Maqsood
 * Text Domain: wc-auto-stock-weight
 */

// Exit early if this file is accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add "Main Stock (lbs)" input to the Inventory tab of variable products.
 */
add_action('woocommerce_product_options_inventory_product_data', 'wc_asw_add_main_stock_field');
function wc_asw_add_main_stock_field()
{
    woocommerce_wp_text_input(array(
        'id' => '_main_stock_lbs',           // Meta key for saved value
        'label' => __('Main Stock (lbs)', 'wc-auto-stock-weight'),
        'type' => 'number',                    // Numeric input
        'custom_attributes' => array('step' => '0.01', 'min' => '0'), // Allow decimals
        'desc_tip' => true,
        'description' => __('Enter total stock in pounds (lbs).', 'wc-auto-stock-weight'),
    ));
}

/**
 * When a product is saved, distribute stock to variations based on weight.
 * Hooked into save_post to cover all save contexts in admin.
 *
 * @param int $post_id ID of the post being saved.
 * @param WP_Post $post Post object.
 * @param bool $update Whether this is an existing post update.
 */
add_action('save_post', 'wc_asw_handle_stock_distribution', 20, 3);
function wc_asw_handle_stock_distribution($post_id, $post, $update)
{
    // Bail if not a product post type, autosave, revision, or user cannot edit
    if (
        'product' !== $post->post_type ||                      // Only run on products
        (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) ||   // Skip autosaves
        wp_is_post_revision($post_id) ||                    // Skip revisions
        !current_user_can('edit_post', $post_id)           // Check user permissions
    ) {
        return;
    }

    // Verify WooCommerce nonce for product data
    if (empty($_POST['woocommerce_meta_nonce']) || !wp_verify_nonce(wp_unslash($_POST['woocommerce_meta_nonce']), 'woocommerce_save_data')) {
        return;
    }

    // Get the WC_Product object
    $product = wc_get_product($post_id);
    // Only proceed if this is a variable product
    if (!$product || 'variable' !== $product->get_type()) {
        return;
    }

    // Sanitize and save the main stock in pounds
    $main_stock_lbs = isset($_POST['_main_stock_lbs'])
        ? floatval(wp_unslash($_POST['_main_stock_lbs']))
        : 0;
    update_post_meta($post_id, '_main_stock_lbs', $main_stock_lbs);

    // Convert pounds to grams (1 lb = 450 g)
    $total_grams = $main_stock_lbs * 450;

    /**
     * Loop through each variation, parse its weight, calculate stock,
     * then enable/manage stock on the variation.
     */
    foreach ($product->get_children() as $variation_id) {
        $variation = wc_get_product($variation_id);     // Get variation object
        $attributes = $variation->get_attributes();       // Get variation attributes
        $weight_str = reset($attributes);               // Assume weight is first attribute

        // Extract numeric weight (e.g. "3.5g" => 3.5)
        if (preg_match('/([\d\.]+)/', $weight_str, $matches)) {
            $variation_weight = floatval($matches[1]);
            if ($variation_weight > 0) {
                // Calculate how many units can be made
                $calculated_stock = floor($total_grams / $variation_weight);

                // Enable stock management and set the quantity
                $variation->set_manage_stock(true);
                $variation->set_stock_quantity($calculated_stock);
                $variation->save();  // Persist changes
            }
        }
    }
}

/**
 * Output a container in the single product summary to display variation stock.
 */
add_action('woocommerce_single_product_summary', 'wc_asw_display_variation_stock', 25);
function wc_asw_display_variation_stock()
{
    global $product;

    // Only show for variable products
    if ($product && $product->is_type('variable')) {
        echo '<div id="wc-asw-variation-stock" class="woocommerce-variation-stock"></div>';
    }
}

/**
 * Enqueue inline script to update the stock message when a variation is chosen.
 */
add_action('wp_footer', 'wc_asw_variation_stock_script');
function wc_asw_variation_stock_script()
{
    // Only load on single product pages
    if (!is_product()) {
        return;
    }
    ?>
    <script type="text/javascript">
        jQuery(function ($) {
            // Listen for variation change
            $('form.variations_form').on('show_variation', function (event, variation) {
                var $container = $('#wc-asw-variation-stock');
                if (variation.max_qty > 0) {
                    $container.text('Available in stock: ' + variation.max_qty + ' units');
                } else {
                    $container.text('');
                }
            });
        });
    </script>
    <?php
}
