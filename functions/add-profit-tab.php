<?php
// Report screen add profit report tab
add_filter( 'woocommerce_admin_reports','aspl_pcp_add_profit_reports');
function aspl_pcp_add_profit_reports( $reports )
{
        if(current_user_can('edit_others_posts'))
        {
            $reports['cost_price'] = array(
                    'title'   => __( 'Profit', 'cost_price' ),
                    'reports' => array(
                        'profit_by_date'     => array(
                            'title'       => __( 'Profit by date', 'cost_price' ),
                            'description' => '',
                            'hide_title'  => true,
                            'callback'    => 'aspl_pcp_create_report'
                        ),
                        'profit_by_product'  => array(
                            'title'       => __( 'Profit by product', 'cost_price' ),
                            'description' => '',
                            'hide_title'  => true,
                            'callback'    => 'aspl_pcp_create_report'
                        ),
                        'profit_by_category' => array(
                            'title'       => __( 'Profit by category', 'cost_price' ),
                            'description' => '',
                            'hide_title'  => true,
                            'callback'    => 'aspl_pcp_create_report'
                        ),
                    ),
            );
    
            $reports = apply_filters( 'woocommerce_reports_charts', $reports ); // Backwards compatibility.
        }
    return $reports;
}

// Call function by filters

function aspl_pcp_create_report( $name ) {
     $name  = sanitize_title( str_replace( '_', '-', $name ) );
     $class = 'Product_cost_Report_' . str_replace( '-', '_', $name );

    include_once( apply_filters( 'wc_admin_reports_path',product_cost_plugin_path().'/includes/admin/reports/class-product-cost-admin-report-' . $name . '.php', $name, $class ) );

        if ( ! class_exists( $class ) ) {
            return;
        }

        $report = new $class();
        $report->output_report();

}