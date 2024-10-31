<?php
/**
 * WooCommerce Cost Price
 */

defined( 'ABSPATH' ) or exit;

/**
 *Profit by Product report
 */
class Product_cost_Report_profit_by_product extends WC_Admin_Report {

		protected $report_data;

        public function output_report() {

            $current_range = $this->get_current_range();

            if ( ! in_array( $current_range, array( 'custom', 'year', 'last_month', 'month', '7day' ), true ) ) {
                $current_range = '7day';
            }

            $this->calculate_current_range( $current_range );

            // used in view
            $ranges = array(
                'year'         => __( 'Year', 'cost_price' ),
                'last_month'   => __( 'Last Month', 'cost_price' ),
                'month'        => __( 'This Month', 'cost_price' ),
                '7day'         => __( 'Last 7 Days', 'cost_price' )
            );

            include( WC()->plugin_path() . '/includes/admin/views/html-report-by-date.php');
        }

        protected function get_current_range() {

            return ! empty( $_GET['range'] ) ? sanitize_text_field( $_GET['range'] ) : '7day';
        }

	/** array product IDs for the report */
	protected $product_ids;

	/** array define the chart colors for this report */
	protected $chart_colors = array(
		'total_sales'  => '#3498db',
		'total_cogs'   => '#b1d4ea',
		'total_profit' => '#dbe1e3',
		'total_items'  => '#5cc488',
	);


	/**
	 * Bootstrap class
	 */
	public function __construct() {

		$this->set_product_ids();
	}


	/**
	 * Set the product IDs for the report
	 */
	protected function set_product_ids() {

		// get the products selected for the report
		$this->product_ids = isset( $_GET['product_ids'] ) ? array_filter( array_map( 'absint', (array) $_GET['product_ids'] ) ) : array();
	}

	public function output_export_button( $args = array() ) {

		$defaults = array(
			'filename'       => sprintf( '%1$s-report-%2$s-%3$s.csv',
					strtolower( str_replace( array( 'WC_COG_Admin_Report_', '_' ), array( '', '-' ), get_class( $this ) ) ),
					$this->get_current_range(), date_i18n( 'Y-m-d', current_time( 'timestamp' ) ) ),
			'xaxes'          => __( 'Date', 'cost_price' ),
			'exclude_series' => '',
			'groupby'        => $this->chart_groupby,
		);

		$args = wp_parse_args( $args, $defaults );

		?>
		<a
			href="#"
			download="<?php echo esc_attr( $args['filename'] ); ?>"
			class="export_csv"
			data-export="chart"
			data-xaxes="<?php echo esc_attr( $args['xaxes'] ); ?>"
			data-exclude_series="<?php echo esc_attr( $args['exclude_series'] ); ?>"
			data-groupby="<?php echo esc_attr( $args['groupby'] ); ?>"
		>
			<?php esc_html_e( 'Export CSV', 'cost_price' ); ?>
		</a>
		<?php
	}

	/** Chart legend methods  *************************************************/


	/**
	 * Get the chart legend data
	 */
	public function get_chart_legend() {

		if ( empty( $this->product_ids ) ) {
			return array();
		}

		$data = $this->get_report_data();

		$legend = array(

			// total product sales
			array(
				/* translators: Placeholders: %1$s is the formatted total product sales with surrounding <strong> tags, e.g. <strong>$7.77</strong> */
				'title'            => sprintf( __( '%1$s sales for the selected items', 'cost_price' ), '<strong>' . wc_price( $data->total_sales ) . '</strong>' ),
				'color'            => $this->chart_colors['total_sales'],
				'highlight_series' => 1
			),

			// total product cost price
			array(
				/* translators: Placeholders: %1$s is the formatted total product cost price with surrounding <strong> tags, e.g. <strong>$4.77</strong> */
				'title'            => sprintf( __( '%1$s cost price for the selected items', 'cost_price' ), '<strong>' . wc_price( $data->total_cogs ) . '</strong>' ),
				'color'            => $this->chart_colors['total_cogs'],
				'highlight_series' => 2
			),

			// total product profit
			array(
				/* translators: Placeholders: %1$s is the formatted total product profit with surrounding <strong> tags, e.g. <strong>$3.00</strong> */
				'title'            => sprintf( __( '%1$s profit for the selected items', 'cost_price' ), '<strong>' . wc_price( $data->total_profit ) . '</strong>' ),
				'color'            => $this->chart_colors['total_profit'],
				'highlight_series' => 3
			),

			// total product items
			array(
				/* translators: Placeholders: %1$s is the the total number of purchased items with surrounding <strong> tags, e.g. <strong>5</strong> */
				'title'            => sprintf( __( '%1$s purchases for the selected items', 'cost_price' ), '<strong>' . $data->total_items . '</strong>' ),
				'color'            => $this->chart_colors['total_items'],
				'highlight_series' => 0
			),
		);

		return $legend;
	}


	/** Chart Widget methods  *************************************************/


	/**
	 * Get the widgets for this report:
	 */
	public function get_chart_widgets() {

		$widgets = array(
			array(
				'title'    => __( 'Product Search', 'cost_price' ),
				'callback' => array( $this, 'output_product_search_widget' ),
			),
			array(
				'title'    => '',
				'callback' => array( $this, 'output_profitable_sellers_widget' ),
			),
		);

		// add filter widget if filtering by product
		if ( ! empty( $this->product_ids ) ) {

			array_unshift( $widgets, array(
				'title'    => __( 'Showing reports for:', 'cost_price' ),
				'callback' => array( $this, 'output_current_filters_widget' )
			) );
		}

		return $widgets;
	}


	/**
	 * Show current product filters for the report
	 */
	public function output_current_filters_widget() {

		$product_titles = array();

		foreach ( $this->product_ids as $product_id ) {

			$product = wc_get_product( $product_id );

			$product_titles[] = $product instanceof WC_Product ? $product->get_formatted_name() : '#' . $product_id;
		}

		printf( '<p><strong>%1$s</strong></p><p><a class="button" href="%2$s">%3$s</a></p>', implode( ', ', $product_titles ), esc_url( remove_query_arg( 'product_ids' ) ), __( 'Reset', 'cost_price' ) );
	}


	/**
	 * Show the product search widget
	 */
	public function output_product_search_widget() {

		?>
		<div class="section">
			<form method="GET">
				<div>

						 <select
							name="product_ids[]"
							class="wc-product-search"
							style="width:203px;"
							data-placeholder="<?php // esc_attr_e( 'Search for a product&hellip;', 'cost_price' ); ?>"
							data-action="woocommerce_json_search_products_and_variations">
						</select> 


						<!-- <input
							type="hidden"
							name="product_ids[]"
							class="wc-product-search"
							style="width:203px;"
							data-placeholder="<?php // esc_attr_e( 'Search for a product&hellip;', 'cost_price' ); ?>"
							data-action="woocommerce_json_search_products_and_variations" /> -->

			

					<input type="submit" class="submit button" value="<?php esc_attr_e( 'Show', 'cost_price' ); ?>" />
					<input type="hidden" name="range" value="<?php if ( ! empty( $_GET['range'] ) ) echo esc_attr( $_GET['range'] ); ?>" />
					<input type="hidden" name="start_date" value="<?php if ( ! empty( $_GET['start_date'] ) ) echo esc_attr( $_GET['start_date'] ); ?>" />
					<input type="hidden" name="end_date" value="<?php if ( ! empty( $_GET['end_date'] ) ) echo esc_attr( $_GET['end_date'] ); ?>" />
					<input type="hidden" name="page" value="<?php if ( ! empty( $_GET['page'] ) ) echo esc_attr( $_GET['page'] ); ?>" />
					<input type="hidden" name="tab" value="<?php if ( ! empty( $_GET['tab'] ) ) echo esc_attr( $_GET['tab'] ); ?>" />
					<input type="hidden" name="report" value="<?php if ( ! empty( $_GET['report'] ) ) echo esc_attr( $_GET['report'] ); ?>" />
				</div>
			</form>
		</div>
		<?php
	}


	/**
	 * Show the profitable sellers widget
	 */
	public function output_profitable_sellers_widget() {

		if ( isset( $_GET['show_least_profitable'] ) ) {
			$order_by = 'ASC';
			$title = esc_html__( 'Least Profitable Sellers', 'cost_price' );
		} else {
			$order_by = 'DESC';
			$title = esc_html__( 'Most Profitable Sellers', 'cost_price' );
		}

		?>
		<h4 class="section_title"><span><?php echo $title; ?></span></h4>
		<div class="section">
			<table cellspacing="0">
				<?php
				$sellers = $this->get_order_report_data( array(
					'data' => array(
						'_line_total' => array(
							'type'            => 'order_item_meta',
							'order_item_type' => 'line_item',
							'function'        => 'SUM',
							'name'            => 'total_sales',
						),
						'_product_item_total_cost' => array(
							'type'            => 'order_item_meta',
							'order_item_type' => 'line_item',
							'function'        => 'SUM( order_item_meta__line_total.meta_value ) - SUM', // hack so we can order by the calculated profit
							'name'            => 'total_profit',
							'join_type'       => 'LEFT',
						),
						'_product_id' => array(
								'type'            => 'order_item_meta',
								'order_item_type' => 'line_item',
								'function'        => '',
								'name'            => 'product_id',
						),
					),
					'order_by'     => "total_profit {$order_by}",
					'group_by'     => 'product_id',
					'limit'        => 12,
					'query_type'   => 'get_results',
					'filter_range' => true,
				) );

				if ( $sellers ) {
					foreach ( $sellers as $product ) {
						$profit_margin = $product->total_sales > 0 ? ( ( $product->total_profit / $product->total_sales ) * 100 ) : 0;
						?>
						<tr class="<?php echo in_array( $product->product_id, $this->product_ids, false ) ? 'active' : ''; ?>">
							<td class="tips" data-tip="<?php /* translators: Placeholders: %1$s - profit margin as a percentage, e.g. 85.4% */ printf( esc_attr__( '%1$s%% profit margin', 'cost_price' ), $profit_margin ); ?>">
								<?php echo wc_price( $product->total_profit ) . ' <small>' . esc_html__( 'total profit', 'cost_price' ) . '</small>'; ?></td>
							<td class="name"><a href="<?php echo esc_url( add_query_arg( 'product_ids', $product->product_id ) ) . '">' . get_the_title( $product->product_id ); ?></a></td>
							<td class="sparkline"><?php echo $this->sales_sparkline( $product->product_id, 7, 'sales' ); ?></td>
						</tr>
						<?php
					}
				} else {
					echo '<tr><td colspan="3">' . __( 'No products found in range', 'cost_price' ) . '</td></tr>';
				}
				?><tr><td colspan="3" style="text-align: right"><small><?php
					if ( isset( $_GET['show_least_profitable'] ) ) {
						echo '<a href="' . esc_url( remove_query_arg( 'show_least_profitable' ) ) . '">' . esc_html__( 'show most profitable', 'cost_price' ) . '</a>';
					} else {
						echo '<a href="' . esc_url( add_query_arg( array( 'show_least_profitable' => 1 ) ) ) . '">' . esc_html__( 'show least profitable', 'cost_price' ) . '</a>';
					}
				?>
				</small></td></tr>
			</table>
		</div>
		<script type="text/javascript">
			jQuery('.section_title' ).click( function() {
				var section = jQuery(this).next( '.section' );

				if ( section.is( ':visible' ) ) {
					section.slideUp();
				} else {
					section.slideDown();
				}

				return false;
			});
		</script>
		<?php
	}


	/** Chart Methods *********************************************************/


	/**
	 * Render the "Export to CSV" button
	 */
	function get_export_button() {

		$this->output_export_button();
	}


	/**
	 * Render the main chart
	 */
	public function get_main_chart() {

		if ( empty( $this->product_ids ) ) {
			?>
			<div class="chart-container">
				<p class="chart-prompt"><?php _e( '&larr; Choose a product to view stats', 'cost_price' ); ?></p>
			</div>
			<?php
			return;
		}

		$data = $this->get_report_data();

		// prep data for charting
		$sales       = $this->prepare_chart_data( $data->sales,       'post_date', 'order_item_amount',     $this->chart_interval, $this->start_date, $this->chart_groupby );
		$cogs        = $this->prepare_chart_data( $data->cogs,        'post_date', 'order_item_total_cost', $this->chart_interval, $this->start_date, $this->chart_groupby );
		$profits     = $this->prepare_chart_data( $data->profits,     'post_date', 'order_item_profit',     $this->chart_interval, $this->start_date, $this->chart_groupby );
		$item_counts = $this->prepare_chart_data( $data->item_counts, 'post_date', 'order_item_count',      $this->chart_interval, $this->start_date, $this->chart_groupby );

		$chart_data = array(
			'sales'       => array_values( $sales ),
			'cogs'        => array_values( $cogs ),
			'profits'     => array_values( $profits ),
			'item_counts' => array_values( $item_counts ),
		);

		?>
		<div class="chart-container">
			<div class="chart-placeholder main"></div>
		</div>
		<script type="text/javascript">

			var main_chart;

			jQuery(function(){
				var order_data = jQuery.parseJSON( '<?php echo json_encode( $chart_data ); ?>' );

				var drawGraph = function( highlight ) {

					var series = [
						{
							label     : "<?php echo esc_js( __( 'Number of items sold', 'cost_price' ) ) ?>",
							data      : order_data.item_counts,
							color     : '<?php echo $this->chart_colors['total_items']; ?>',
							bars      : { fillColor: '<?php echo $this->chart_colors['total_items']; ?>', fill: true, show: true, lineWidth: 0, barWidth: <?php echo $this->barwidth; ?> * 0.5, align: 'center' },
							shadowSize: 0,
							hoverable : false
						},
						{
							label     : "<?php echo esc_js( __( 'Sales amount', 'cost_price' ) ) ?>",
							data      : order_data.sales,
							yaxis     : 2,
							color     : '<?php echo $this->chart_colors['total_sales']; ?>',
							points    : { show: true, radius: 5, lineWidth: 3, fillColor: '#fff', fill: true },
							lines     : { show: true, lineWidth: 4, fill: false },
							shadowSize: 0,
							<?php echo $this->get_currency_tooltip(); ?>
						},
						{
							label     : "<?php echo esc_js( __( 'Cost of Goods Sold', 'cost_price' ) ) ?>",
							data      : order_data.cogs,
							yaxis     : 2,
							color     : '<?php echo $this->chart_colors['total_cogs']; ?>',
							points    : { show: true, radius: 5, lineWidth: 3, fillColor: '#fff', fill: true },
							lines     : { show: true, lineWidth: 4, fill: false },
							shadowSize: 0,
							<?php echo $this->get_currency_tooltip(); ?>
						},
						{
							label     : "<?php echo esc_js( __( 'Profit amount', 'cost_price' ) ) ?>",
							data      : order_data.profits,
							yaxis     : 2,
							color     : '<?php echo $this->chart_colors['total_profit']; ?>',
							points    : { show: true, radius: 5, lineWidth: 3, fillColor: '#fff', fill: true },
							lines     : { show: true, lineWidth: 4, fill: false },
							shadowSize: 0,
							<?php echo $this->get_currency_tooltip(); ?>
						}
					];

					if ( highlight !== 'undefined' && series[ highlight ] ) {
						highlight_series = series[ highlight ];

						highlight_series.color = '#9c5d90';

						if ( highlight_series.bars ) {
							highlight_series.bars.fillColor = '#9c5d90';
						}

						if ( highlight_series.lines ) {
							highlight_series.lines.lineWidth = 5;
						}
					}

					main_chart = jQuery.plot(
						jQuery( '.chart-placeholder.main' ),
						series,
						{
							legend: {
								show: false
							},
							grid: {
								color      : '#aaa',
								borderColor: 'transparent',
								borderWidth: 0,
								hoverable  : true
							},
							xaxes: [ {
								color      : '#aaa',
								position   : 'bottom',
								tickColor  : 'transparent',
								mode       : 'time',
								timeformat : "<?php if ( $this->chart_groupby == 'day' ) echo '%d %b'; else echo '%b'; ?>",
								monthNames : <?php echo json_encode( array_values( $GLOBALS['wp_locale']->month_abbrev ) ) ?>,
								tickLength : 1,
								minTickSize: [1, "<?php echo esc_js( $this->chart_groupby ); ?>"],
								font       : {
									color: '#aaa'
								}
							} ],
							yaxes: [
								{
									min         : 0,
									minTickSize : 1,
									tickDecimals: 0,
									color       : '#ecf0f1',
									font        : { color: '#aaa' }
								},
								{
									position          : 'right',
									min               : 0,
									tickDecimals      : 2,
									alignTicksWithAxis: 1,
									color             : 'transparent',
									font              : { color: '#aaa' }
								}
							],
						}
					);

					jQuery( '.chart-placeholder' ).resize();
				}

				drawGraph();

				jQuery( '.highlight_series' ).hover(
					function() {
						drawGraph( jQuery( this ).data( 'series' ) );
					},
					function() {
						drawGraph();
					}
				);
			});
		</script>
		<?php
	}

	protected function format_decimal( $amount ) {
		if ( is_array( $amount ) ) {
			return array( $amount[0], wc_format_decimal( $amount[1], wc_get_price_decimals() ) );
		} else {
			return wc_format_decimal( $amount, wc_get_price_decimals() );
		}
	}
	protected function get_report_data() {

		if ( ! empty( $this->report_data ) ) {
			return $this->report_data;
		}

		$this->report_data = new stdClass();

		// sales
		$this->report_data->sales = $this->get_order_report_data( array(
			'data' => array(
				'_line_total' => array(
					'type'            => 'order_item_meta',
					'order_item_type' => 'line_item',
					'function'        => 'SUM',
					'name'            => 'order_item_amount',
				),
				'post_date' => array(
					'type'     => 'post_data',
					'function' => '',
					'name'     => 'post_date',
				),
				'_product_id' => array(
					'type'            => 'order_item_meta',
					'order_item_type' => 'line_item',
					'function'        => '',
					'name'            => 'product_id',
				)
			),
			'where_meta' => array(
				'relation' => 'OR',
				array(
					'type'       => 'order_item_meta',
					'meta_key'   => array( '_product_id', '_variation_id' ),
					'meta_value' => $this->product_ids,
					'operator'   => 'IN',
				),
			),
			'group_by'     => 'product_id, ' . $this->group_by_query,
			'order_by'     => 'post_date ASC',
			'query_type'   => 'get_results',
			'filter_range' => true,
		) );

		$this->report_data->total_sales = $this->format_decimal( array_sum( wp_list_pluck( $this->report_data->sales, 'order_item_amount' ) ) );

		// COGS
		$this->report_data->cogs = $this->get_order_report_data( array(
			'data' => array(
				'_product_item_total_cost' => array(
					'type'            => 'order_item_meta',
					'order_item_type' => 'line_item',
					'function'        => 'SUM',
					'name'            => 'order_item_total_cost',
				),
				'post_date' => array(
					'type'     => 'post_data',
					'function' => '',
					'name'     => 'post_date',
				),
				'_product_id' => array(
					'type'            => 'order_item_meta',
					'order_item_type' => 'line_item',
					'function'        => '',
					'name'            => 'product_id',
				),
			),
			'where_meta' => array(
				'relation' => 'OR',
				array(
						'type'       => 'order_item_meta',
						'meta_key'   => array( '_product_id', '_variation_id' ),
						'meta_value' => $this->product_ids,
						'operator'   => 'IN',
				),
			),
			'group_by'     => 'product_id, ' . $this->group_by_query,
			'order_by'     => 'post_date ASC',
			'query_type'   => 'get_results',
			'filter_range' => true,
		) );

		$this->report_data->total_cogs = $this->format_decimal( array_sum( wp_list_pluck( $this->report_data->cogs, 'order_item_total_cost' ) ) );

		// profit
		$this->report_data->profits = $this->get_order_report_data( array(
			'data' => array(
				'_line_total' => array(
					'type'            => 'order_item_meta',
					'order_item_type' => 'line_item',
					'function' => 'SUM',
					'name'     => 'order_item_amount',
				),
				'_product_item_total_cost' => array(
					'type'            => 'order_item_meta',
					'order_item_type' => 'line_item',
					'function'        => 'SUM( order_item_meta__line_total.meta_value ) - SUM',
					'name'            => 'order_item_profit',
					'join_type'       => 'LEFT', 
				),
				'post_date' => array(
					'type'     => 'post_data',
					'function' => '',
					'name'     => 'post_date',
				),
				'_product_id' => array(
					'type'            => 'order_item_meta',
					'order_item_type' => 'line_item',
					'function'        => '',
					'name'            => 'product_id',
				),
			),
			'where_meta' => array(
				'relation' => 'OR',
				array(
					'type'       => 'order_item_meta',
					'meta_key'   => array( '_product_id', '_variation_id' ),
					'meta_value' => $this->product_ids,
					'operator'   => 'IN',
				),
			),
			'group_by'     => 'product_id, ' . $this->group_by_query,
			'order_by'     => 'post_date ASC',
			'query_type'   => 'get_results',
			'filter_range' => true,
		) );

		$this->report_data->total_profit = $this->format_decimal( array_sum( wp_list_pluck( $this->report_data->profits, 'order_item_profit' ) ) );

		// item counts
		$this->report_data->item_counts = $this->get_order_report_data( array(
			'data' => array(
				'_qty' => array(
					'type'            => 'order_item_meta',
					'order_item_type' => 'line_item',
					'function'        => 'SUM',
					'name'            => 'order_item_count',
				),
				'post_date' => array(
					'type'     => 'post_data',
					'function' => '',
					'name'     => 'post_date',
				),
				'_product_id' => array(
					'type'            => 'order_item_meta',
					'order_item_type' => 'line_item',
					'function'        => '',
					'name'            => 'product_id',
				),
			),
			'where_meta' => array(
				'relation' => 'OR',
				array(
						'type'       => 'order_item_meta',
						'meta_key'   => array( '_product_id', '_variation_id' ),
						'meta_value' => $this->product_ids,
						'operator'   => 'IN',
				),
			),
			'group_by'     => 'product_id,' . $this->group_by_query,
			'order_by'     => 'post_date ASC',
			'query_type'   => 'get_results',
			'order_types'  => wc_get_order_types( 'order-count' ),
			'filter_range' => true,
		) );

		$this->report_data->total_items = absint( array_sum( wp_list_pluck( $this->report_data->item_counts, 'order_item_count' ) ) );


		/**
		 * Filter for Profit by Product Report Data .
		 */
		return apply_filters( 'product_cost_profit_by_product_report_data', $this->report_data, $this->product_ids, $this );
	}


}
