<?php
/*
Plugin Name: WooCommerce Auto Stock Distributor by Weight
Description: Auto-distributes WooCommerce variation stock based on main product quantity in pounds and variation weight in grams.
Version: 1.0
Author: Haris Maqsood
Text Domain: wc-auto-stock-weight
*/

if (!defined('ABSPATH')) exit;

// Add custom field for main product stock in pounds
add_action('woocommerce_product_options_inventory_product_data', 'wc_add_main_stock_field');
function wc_add_main_stock_field()
{
    woocommerce_wp_text_input([
        'id' => '_main_stock_lbs',
        'label' => __('Main Stock (lbs)', 'wc-auto-stock-weight'),
        'type' => 'number',
        'custom_attributes' => ['step' => '0.01', 'min' => '0'],
        'desc_tip' => true,
        'description' => __('Enter total product quantity in pounds (lbs).', 'wc-auto-stock-weight')
    ]);
}

// Save custom field and distribute stock
add_action('woocommerce_process_product_meta_variable', 'wc_save_and_distribute_stock');
function wc_save_and_distribute_stock($product_id)
{

    // Verify nonce
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'woocommerce-save-data')) {
        return;
    }

    if (isset($_POST['_main_stock_lbs'])) {
        $main_stock_lbs = floatval($_POST['_main_stock_lbs']);
        update_post_meta($product_id, '_main_stock_lbs', $main_stock_lbs);

        // Convert pounds to grams
        $total_grams = $main_stock_lbs * 450;

        // Get variations
        $variations = get_posts([
            'post_type' => 'product_variation',
            'post_status' => ['publish', 'private'],
            'numberposts' => -1,
            'post_parent' => $product_id,
        ]);

        foreach ($variations as $variation) {
            $variation_id = $variation->ID;

            // Extract variation weight from attributes (e.g., "3.5g")
            $attributes = wc_get_product_variation_attributes($variation_id);
            $variation_weight_str = reset($attributes);
            preg_match('/([\d\.]+)/', $variation_weight_str, $matches);

            if (!empty($matches[1])) {
                $variation_weight = floatval($matches[1]);
                if ($variation_weight > 0) {
                    // Calculate stock
                    $stock = floor($total_grams / $variation_weight);

                    // Update variation stock
                    update_post_meta($variation_id, '_manage_stock', 'yes');
                    update_post_meta($variation_id, '_stock', $stock);

                    // Optionally update total_sales to reset count (Optional)
                    // update_post_meta($variation_id, 'total_sales', 0);
                }
            }
        }
    }
}

// Optional: Display available stock on frontend
add_action('woocommerce_single_product_summary', 'wc_display_variation_stock', 25);
function wc_display_variation_stock()
{
    global $product;
    if ($product->is_type('variable')) {
        echo '<div id="wc-variation-stock"></div>';
    }
}

add_action('wp_footer', 'wc_variation_stock_script');
function wc_variation_stock_script()
{
    if (is_product()) {
        ?>
        <script>
            jQuery(document).ready(function ($) {
                $('form.variations_form').on('show_variation', function (event, variation) {
                    $('#wc-variation-stock').html('Available in stock: ' + variation.max_qty + ' units');
                });
            });
        </script>
        <?php
    }
}