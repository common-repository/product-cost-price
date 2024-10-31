<?php
/**
 * Product Cost Price
*/

defined( 'ABSPATH' ) or exit;

abstract class Product_cost_profit_report extends WC_Admin_Report 
{

	/** @var array chart colors */
	protected $chart_colors;

	/** @var stdClass|array for caching multiple calls to get_report_data() */
	protected $report_data;

	public function output_report() {

		$current_range = $this->get_current_range();

		if ( ! in_array( $current_range, array( 'custom', 'year', 'last_month', 'month', '7day' ), true ) ) {
			$current_range = '7day';
		}

		$this->calculate_current_range( $current_range );

		// used in view
		$ranges = array(
			'year'         => __( 'Year', 'woocommerce-cost-of-goods' ),
			'last_month'   => __( 'Last Month', 'woocommerce-cost-of-goods' ),
			'month'        => __( 'This Month', 'woocommerce-cost-of-goods' ),
			'7day'         => __( 'Last 7 Days', 'woocommerce-cost-of-goods' )
		);

		include( WC()->plugin_path() . '/includes/admin/views/html-report-by-date.php');
	}


	/**
	 * Render the export CSV button
	 *
	 * @since 2.0.0
	 * @param array $args optional arguments for adjusting the exported CSV
	 */
	


	/**
	 * Return the currently selected date range for the report
	 *
	 * @since 2.0.0
	 * @return string
	 */
	protected function get_current_range() {

		return ! empty( $_GET['range'] ) ? sanitize_text_field( $_GET['range'] ) : '7day';
	}


	/**
	 * Return true if fees should be excluded from net sales/profit calculations
	 *
	 * Note that taxes on fees are already included in the order tax amount.
	 *
	 * @since 2.0.0
	 * @return bool
	 */

	public function exclude_fees() {

		return 'yes' === get_option( 'wc_cog_profit_report_exclude_gateway_fees' );
	}


	/**
	 * Return true if taxes should be excluded from net sales/profit calculations
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	public function exclude_taxes() {

		return 'yes' === get_option( 'wc_cog_profit_report_exclude_taxes' );
	}


	/**
	 * Return true if shipping should be excluded from net sales/profit calculations
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	public function exclude_shipping() {

		return 'yes' === get_option( 'wc_cog_profit_report_exclude_shipping_costs' );
	}


	/**
	 * Helper to format an amount using wc_format_decimal() for both strings/floats
	 * and arrays
	 *
	 * @since 2.0.0
	 * @param string|float|array $amount
	 * @return array|string
	 */
	protected function format_decimal( $amount ) {
		if ( is_array( $amount ) ) {
			return array( $amount[0], wc_format_decimal( $amount[1], wc_get_price_decimals() ) );
		} else {
			return wc_format_decimal( $amount, wc_get_price_decimals() );
		}
	}


}
