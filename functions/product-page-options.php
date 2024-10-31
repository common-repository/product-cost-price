<?php

// Simple Product add Cost price textbox

function aspl_pcp_simple_product_cost_price()
{
    $label = 'Cost Price (' . get_woocommerce_currency_symbol() . ')';
    global $woocommerce, $post;
    $price = get_post_meta($post->ID, '_cost_price', true);
   // $price = sprintf("%.2f", $price);
    woocommerce_wp_text_input(array(
        'id' => '_cost_price',
        'label' => __($label, 'woocommerce'),
        'placeholder' => '',
        'desc_tip' => 'true',
        'value' => $price,
        'description' => __('Enter Product Cost Price', 'woocommerce')
    ));
}

// Save Cost Price Value to Product mera
function aspl_pcp_simple_product_cost_price_save($post_id)
{
    $woocommerce_text_field = sanitize_text_field($_POST['_cost_price']);
   // if (!empty($woocommerce_text_field)) {
        update_post_meta($post_id, '_cost_price', $woocommerce_text_field);
 //   }
}

add_action('woocommerce_product_after_variable_attributes', 'aspl_pcp_variation_product_cost_price', 10, 3);
add_action('woocommerce_save_product_variation', 'aspl_pcp_variation_product_cost_price_save', 10, 2);

function aspl_pcp_variation_product_cost_price($loop, $variation_data, $variation)
{
    $label = 'Cost Price (' . get_woocommerce_currency_symbol() . ')';
    $price = get_post_meta($variation->ID, '_number_field', true);
    $price = sprintf("%.2f", $price);
    woocommerce_wp_text_input(array(
        'id' => '_cost_price[' . $variation->ID . ']',
        'label' => __($label, 'woocommerce'),
        'desc_tip' => 'true',
        'description' => __('Enter Product Cost Price', 'woocommerce'),
        'value' => $price,
        'custom_attributes' => array(
            'step' => 'any',
            'min' => '0'
        )
    ));
}

function aspl_pcp_variation_product_cost_price_save($post_id)
{
    $number_field = sanitize_text_field($_POST['_cost_price'][$post_id]);
    if (!empty($number_field)) {
        update_post_meta($post_id, '_cost_price', $number_field);
    }
}

add_filter('manage_product_posts_columns', 'aspl_pcp_product_page_column', 11);
function aspl_pcp_product_page_column($columns)
{
    $reordered_columns = array();
    
    foreach ($columns as $key => $column) {
        $reordered_columns[$key] = $column;
        if ($key === 'price') {
            $reordered_columns['cog'] = __('Cost Price', 'theme_slug');
        }
        
    }
    return $reordered_columns;
}

add_action('manage_product_posts_custom_column', 'aspl_pcp_column_content', 10, 2);
function aspl_pcp_column_content($column, $post_id)
{
    if ('cog' == $column) {
        $my_var_one = get_post_meta($post_id, '_cost_price', true);
        if ($my_var_one != "") {
            $price = sprintf("%.2f", $my_var_one);
            echo get_woocommerce_currency_symbol() . $price;
        } else {
            echo '-';
        }        
    }
}