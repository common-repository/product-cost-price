<?php

// Admin order details page - order items - cost price header 

add_action( 'woocommerce_admin_order_item_headers', 'aspl_pcp_order_item_cost_column_headers');
function aspl_pcp_order_item_cost_column_headers( $order ) {
		global $pagenow;

		// Do not add for orders being created manually and not saved yet
		if ( 'post-new.php' === $pagenow ) {
			return;
		}

		?>
		<th class="item_cost_price sortable" data-sort="float">
			<?php esc_html_e( 'Product Cost', 'cost_price' ); ?>
		</th>
		<?php
}

// Admin order details page - order items - cost price value 
add_action( 'woocommerce_admin_order_item_values', 'aspl_pcp_add_order_item_cost', 10, 3 );
function aspl_pcp_add_order_item_cost( $product, $item, $item_id ) {
	
		global $pagenow;

		// do not add for orders being created manually and not saved yet
		if ( 'post-new.php' === $pagenow ) {
			return;
		}

		// empty cell for refunds or where product is null
		if ( ! $item || ! $product instanceof WC_Product ) {

			echo '<td width="1%">&nbsp;</td>';

		} else {

			if ( is_array( $item ) ) {
				echo $item_qty = isset( $item['qty'] ) ? max( 1, (int) $item['qty'] ) : 1;
			} elseif ( $item instanceof WC_Order_Item ) {
				$item_qty = $item->get_quantity();
			} else {
				return;
			}
			
			$item_cost = wc_get_order_item_meta( $item_id, '_product_item_cost', true );

			// set default cost if item cost doesn't exist
			if ( false === $item_cost ) {
				 $item_cost = (float) WC_COG_Product::get_cost( $product ) * $item_qty;
			}

			$decimals = wc_get_price_decimals();

			// number input stepper value
			$steps = $decimals > 0 ? '0.' . str_repeat( '0', $decimals - 1 ) . '1' : 1;

			$formatted_item_cost = wc_format_decimal( $item_cost * $item_qty );

			?>
			<td class="item_cost_price" width="1%">

				<div class="view">
					<?php echo wc_price( $formatted_item_cost ); ?>
					<!-- <?php //if ( $refunded_item_total_cost = $this->get_total_cost_refunded_for_item( $item_id ) ) : ?>
						<small class="refunded"><?php //echo wc_price( $refunded_item_total_cost ); ?></small>
					<?php// endif; ?> -->
				</div>

				<div class="edit edit-cog" style="display: none;">
					<div class="split-input">
						<div class="input">
							<label></label>
							<input type="number"
								   name="item_cost_price[<?php esc_attr_e( $item_id ); ?>]"
								   class="cog-total"
								   min="0"
								   step="<?php echo esc_attr( $steps ); ?>"
								   placeholder="0"
								   data-cog-total="<?php echo esc_attr( $formatted_item_cost ); ?>"
								   value="<?php echo esc_attr( $formatted_item_cost ); ?>" />
						</div>
						<div class="input">
							<label><?php esc_html_e( 'Should be:', 'cost_price' ); ?></label>
							<input type="text"
								   value="<?php esc_attr_e( $formatted_item_cost ); ?>"
								   class="cog-suggestion"
								   disabled="disabled" />
						</div>
					</div>
				</div>

			</td>
			<?php

		}
	}


// When order place create product cost meta in order_itemmeta
add_action( 'woocommerce_checkout_update_order_meta', 'aspl_pcp_set_order_cost_meta', 10, 1 );
function aspl_pcp_set_order_cost_meta( $order_id )
{
		$order = wc_get_order( $order_id );
		$total_cost = 0;

		foreach ( $order->get_items() as $item_id => $item ) {
			$product_id = ( ! empty( $item['variation_id'] ) ) ? $item['variation_id'] : $item['product_id'];
			$item_cost  = get_post_meta($product_id, '_cost_price', true  );;
			$quantity   = (float) $item['qty'];			

			$formatted_total = (float) $item_cost * $quantity;

			wc_add_order_item_meta( $item_id, '_product_item_cost', $item_cost );
			wc_add_order_item_meta( $item_id, '_product_item_total_cost', $formatted_total );
			$total_cost += ( $item_cost * $quantity );				
		}
		update_post_meta($order_id, '_item_total_cost', $total_cost);
}


// hide the Product cost meta in admin Order Items table
add_filter( 'woocommerce_hidden_order_itemmeta','aspl_pcp_hide_order_item_cost_meta');
function aspl_pcp_hide_order_item_cost_meta( $hidden_fields )
{
	return array_merge( $hidden_fields, array( '_product_item_cost', '_product_item_total_cost' ) );
}

add_action( 'woocommerce_delete_shop_order_transients','aspl_pcp_clear_report_transients');
function aspl_pcp_clear_report_transients() {

		foreach ( array( 'date', 'product', 'category' ) as $report ) {

			delete_transient( "product_cost_report_profit_by_{$report}" );
		}
	}