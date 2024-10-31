<?php
/*Plugin Name: Product Cost Price
Plugin URI: https://acespritech.com/services/wordpress-extensions/
Description: Cost of Goods
Author: Acespritech Solutions Pvt. Ltd.
Author URI: https://acespritech.com/
Version: 1.1.0
Domain Path: /languages/
*/
include_once(ABSPATH . 'wp-admin/includes/plugin.php');
if (is_plugin_active('woocommerce/woocommerce.php'))
{
    add_action('admin_enqueue_scripts', 'aspl_pcp_load_admin_styles');
    add_action('woocommerce_product_options_general_product_data', 'aspl_pcp_simple_product_cost_price', 10, 3);
    add_action('woocommerce_process_product_meta', 'aspl_pcp_simple_product_cost_price_save', 10, 2);
}
else
{
	deactivate_plugins(plugin_basename(__FILE__));
    add_action('admin_notices', 'aspl_pcp_woocommerce_not_installed');
}
function aspl_pcp_woocommerce_not_installed()
{
?>
    <div class="error notice">
      	<p><?php
    		_e('You need to install and activate WooCommerce to use WooCommerce Cost Of Goods!', 'Product Cost Price');
?>		</p>
    </div>
    <?php
}

function aspl_pcp_load_admin_styles()
{
    wp_enqueue_style('admin_css_pcp', plugins_url('/css/style.css', __FILE__));
}


include('functions/add-profit-tab.php');

function product_cost_plugin_path() {
  return untrailingslashit( plugin_dir_path( __FILE__ ) );
}
include('functions/order-options.php');
include('functions/product-page-options.php');
