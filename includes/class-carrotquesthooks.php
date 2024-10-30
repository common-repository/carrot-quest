<?php
/**
 * Carrot quest hooks
 *
 * Handles all triggers and data sending
 *
 * @package Carrotquest
 */

/**
 * Class CarrotquestHooks
 */
class CarrotquestHooks {

	/**
	 * Send event with product info
	 *
	 * @param string|integer $product_id manipulated product.
	 *
	 * @return mixed
	 */
	private static function get_product_info( $product_id ) {
		/*
			Getting product info:
				- Product name
				- Product page URL
				- Product image
				- Product price
		*/
		$_product = wc_get_product( $product_id );
		if ( $_product ) {
			$image_id = $_product->get_image_id();
			if ( $image_id ) {
				$image = wp_get_attachment_image_src( $image_id, 'full' );
			}

			$params = array(
				'$url'    => $_product->get_permalink(),
				'$amount' => round( $_product->get_price() ),
				'$name'   => $_product->get_title(),
			);
			if ( isset( $image[0] ) ) {
				$params['$img'] = $image[0];
			}

			return $params;
		}
		return false;
	}

	/**
	 * User viewed product
	 * woocommerce_after_single_product hook callback
	 */
	public static function product_viewed() {
		global $product;
		if ( isset( $_COOKIE['carrotquest_uid'] ) ) {
			$carrotquest_uid = sanitize_text_field( wp_unslash( $_COOKIE['carrotquest_uid'] ) );
			if ( $product && $product->get_id() ) {
				$params = self::get_product_info( $product->get_id() );
				if ( $params ) {
					self::carrotquest_send_event( $carrotquest_uid, '$product_viewed', $params );
					/* Adding item to users list of viewed products */
					self::carrotquest_send_operations(
						$carrotquest_uid,
						array(
							array(
								'op'    => 'union',
								'key'   => '$viewed_products',
								'value' => $params['$name'],
							),
						)
					);
				}
			}
		}
	}

	/**
	 * Added product to cart
	 * woocommerce_add_to_cart_redirect, woocommerce_ajax_added_to_cart hook callback
	 *
	 * @param mixed $arg contains product id, if callback called via ajax.
	 *
	 * @return mixed
	 */
	public static function product_added( $arg ) {
		$is_ajax    = ( isset( $_REQUEST['wc-ajax'] ) && 'add_to_cart' === $_REQUEST['wc-ajax'] ); // Checking if product was added via ajax.
		$product_id = 0;
		if ( $is_ajax ) {
			$product_id = $arg;
		} else {
			if ( isset( $_REQUEST['add-to-cart'] ) ) {
				$product_id = (int) apply_filters( 'woocommerce_add_to_cart_product_id', absint( $_REQUEST['add-to-cart'] ) ); // Getting added product id, by applying appropriate filter.
			}
		}

		if ( isset( $_COOKIE['carrotquest_uid'] ) && isset( $product_id ) && $product_id > 0 && ( $is_ajax && isset( $arg ) || ! $is_ajax && isset( $_REQUEST['add-to-cart'] ) ) ) {
			$carrotquest_uid = sanitize_text_field( wp_unslash( $_COOKIE['carrotquest_uid'] ) );

			if ( $product_id ) {
				$params = self::get_product_info( $product_id );
				if ( $params ) {
					self::carrotquest_send_event( $carrotquest_uid, '$cart_added', $params );
				}
			}

			$cart_info = self::cart_info(); // Getting current cart condition.
			self::carrotquest_send_operations( $carrotquest_uid, $cart_info['stand_alone_properties'] ); // Setting users properties - content of the cart, total of the cart.
		}
		if ( ! $is_ajax ) {
			return $arg;
		}
	}

	/**
	 * User viewed cart
	 * woocommerce_after_cart hook callback
	 */
	public static function cart_viewed() {
		if ( isset( $_COOKIE['carrotquest_uid'] ) ) {
			$carrotquest_uid = sanitize_text_field( wp_unslash( $_COOKIE['carrotquest_uid'] ) );
			$cart_info       = self::cart_info();
			self::carrotquest_send_event( $carrotquest_uid, '$cart_viewed', $cart_info['event_properties'] ); // Adding "Viewed cart" event to users chronology. List of products is sended as events properties.
			self::carrotquest_send_operations( $carrotquest_uid, $cart_info['stand_alone_properties'] ); // Setting users properties - content of the cart, total of the cart.
		}
	}

	/**
	 * User started the checkout process
	 * woocommerce_before_checkout_form hook callback
	 */
	public static function order_started() {
		if ( isset( $_COOKIE['carrotquest_uid'] ) ) {
			$carrotquest_uid = sanitize_text_field( wp_unslash( $_COOKIE['carrotquest_uid'] ) );
			self::carrotquest_send_event( $carrotquest_uid, '$order_started' ); // Adding "Started order" event to users chronology.
		}
	}

	/**
	 * Get order info
	 *
	 * @param string|integer $order_id order id.
	 *
	 * @return mixed
	 */
	private static function order_info( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return false;
		}

		$total = $order->get_subtotal();
		$name  = trim( implode( ' ', array( trim( $order->get_billing_first_name() ), trim( $order->get_billing_last_name() ) ) ) );
		if ( ! isset( $name ) || 0 === strlen( $name ) ) {
			$name = trim( implode( ' ', array( trim( $order->get_shipping_first_name() ), trim( $order->get_shipping_last_name() ) ) ) );
		}
		$phone = $order->get_billing_phone();
		$email = $order->get_billing_email();
		$items = $order->get_items();

		return array(
			'total' => $total,
			'name'  => $name,
			'phone' => $phone,
			'email' => $email,
			'items' => $items,
		);
	}

	/**
	 * User completed the checkout process
	 * woocommerce_checkout_order_processed hook callback
	 *
	 * @param string|integer $order_id - number of resulting order.
	 *
	 * @return mixed
	 */
	public static function order_completed( $order_id ) {
		if ( isset( $_COOKIE['carrotquest_uid'] ) ) {
			$carrotquest_uid = sanitize_text_field( wp_unslash( $_COOKIE['carrotquest_uid'] ) );

			$order = self::order_info( $order_id );
			if ( $order ) {
				$operations   = array();
				$operations[] = array(
					'op'    => 'update_or_create',
					'key'   => '$last_payment',
					'value' => round( $order['total'] ),
				); // User property "Last payment" - total of the current order.
				$operations[] = array(
					'op'    => 'add',
					'key'   => '$revenue',
					'value' => round( $order['total'] ),
				); // User property "Revenue" - total of the current order added to previous revenue value.
				$operations[] = array(
					'op'    => 'delete',
					'key'   => '$cart_items',
					'value' => 0,
				); // Clearing content of the cart.
				$operations[] = array(
					'op'    => 'delete',
					'key'   => '$cart_amount',
					'value' => 0,
				); // Clearing total of the cart.

				$order_items = $order['items'];
				$items       = array();
				foreach ( $order_items as $product ) {
					$items[]      = $product['name'];
					$operations[] = array( // Adding item to the content of the cart.
						'op'    => 'union',
						'key'   => '$ordered_items',
						'value' => $product['name'],
					);
				}
				if ( isset( $order['name'] ) && 0 < strlen( $order['name'] ) ) {
					$operations[] = array(
						'op'    => 'update_or_create',
						'key'   => '$name',
						'value' => $order['name'],
					); // User name from the order.
				}

				if ( isset( $order['email'] ) && 0 < strlen( $order['email'] ) ) {
					$operations[] = array(
						'op'    => 'update_or_create',
						'key'   => '$email',
						'value' => $order['email'],
					); // User email from the order.
				}

				if ( isset( $order['phone'] ) && strlen( $order['phone'] ) > 0 ) {
					$operations[] = array(
						'op'    => 'update_or_create',
						'key'   => '$phone',
						'value' => $order['phone'],
					); // User phone number from the order.
				}

				self::carrotquest_send_event(
					$carrotquest_uid,
					'$order_completed',
					array(
						'$order_id'     => $order_id,
						'$order_amount' => $order['total'],
						'$items'        => $items,
					)
				); // Adding "Completed order" event to users chronology. Order number and total of the order are sended as events properties.
				self::carrotquest_send_operations( $carrotquest_uid, $operations ); // Sending user properties collected earlier.
			}
		}

		return $order_id;
	}

	/**
	 * Save user id to CQ if user was authenticated when order was made
	 *
	 * @param string|integer $order_id order id.
	 */
	public static function order_authentication( $order_id ) {
		$settings = self::get_settings();
		if ( isset( $settings['auth'] ) && $settings['auth'] && isset( $settings['auth_key'] ) && 0 < strlen( $settings['auth_key'] ) ) {
			$order = wc_get_order( $order_id );
			if ( $order ) {
				$user_id = $order->get_user_id();
				if ( ! $user_id ) {
					return;
				}
				?>
				<script>
					carrotquest.auth('<?php echo esc_textarea( $user_id ); ?>', '<?php echo esc_textarea( hash_hmac( 'sha256', $user_id, $settings['auth_key'] ) ); ?>');
				</script>
				<?php
			}
		}
	}

	/**
	 * Event, triggered when order status changes
	 *
	 * @param string|integer $order_id order id.
	 * @param string|integer $old_status previous order status.
	 * @param string|integer $new_status new order status.
	 */
	public static function order_status_changed( $order_id, $old_status, $new_status ) {
		$settings = self::get_settings();
		if ( isset( $settings['auth'] ) ) {
			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				return;
			}
			$user_id = $order->get_user_id();
			if ( isset( $user_id ) && $user_id ) {
				$new_status_name = wc_get_order_status_name( $new_status );

				self::carrotquest_send_operations(
					$user_id,
					array(
						array(
							'op'    => 'update_or_create',
							'key'   => '$last_order_status',
							'value' => $new_status_name,
						),
					),
					true
				);

				if ( 'completed' === $new_status ) {
					$order_items = $order->get_items();
					$items       = array();
					foreach ( $order_items as $product ) {
						$items[] = $product['name'];
					}

					self::carrotquest_send_event(
						$user_id,
						'$order_paid',
						array(
							'$order_id'     => $order_id,
							'$items'        => $items,
							'$order_amount' => $order->total,
						),
						true
					);
				}

				if ( 'refunded' === $new_status ) {
					$order_items = $order->get_items();
					$items       = array();
					foreach ( $order_items as $product ) {
						$items[] = $product['name'];
					}

					self::carrotquest_send_event(
						$user_id,
						'$order_refunded',
						array(
							'$order_id' => $order_id,
							'$items'    => $items,
						),
						true
					);
				}

				if ( 'cancelled' === $new_status ) {
					$order_items = $order->get_items();
					$items       = array();
					foreach ( $order_items as $product ) {
						$items[] = $product['name'];
					}

					self::carrotquest_send_event(
						$user_id,
						'$order_cancelled',
						array(
							'$order_id' => $order_id,
							'$items'    => $items,
						),
						true
					);
				}
			}
		}
	}

	/**
	 * Flag user login to run carrotquest.auth only once
	 *
	 * @param string $user_login current user login.
	 */
	public static function user_login( $user_login ) {
		set_transient( 'carrotquest_' . $user_login, '1', 0 );
	}

	/**
	 * Send user data to carrotquest on login (if auth option is turned on)
	 */
	public static function login_authentication() {
		global $current_user;
		wp_get_current_user();

		if ( ! is_user_logged_in() ) {
			return;
		}

		if ( ! get_transient( 'carrotquest_' . $current_user->user_login ) ) {
			return;
		}

		if ( isset( $_COOKIE['carrotquest_uid'] ) ) {
			$carrotquest_uid = sanitize_text_field( wp_unslash( $_COOKIE['carrotquest_uid'] ) );
			$settings        = self::get_settings();
			$name            = trim( implode( ' ', array( trim( get_user_meta( $current_user->ID, 'billing_first_name', true ) ), trim( get_user_meta( $current_user->ID, 'billing_last_name', true ) ) ) ) );
			if ( ! isset( $name ) || 0 === strlen( $name ) ) {
				$name = trim( implode( ' ', array( trim( get_user_meta( $current_user->ID, 'shipping_first_name', true ) ), trim( get_user_meta( $current_user->ID, 'shipping_last_name', true ) ) ) ) );
			}
			$phone      = get_user_meta( $current_user->ID, 'billing_phone', true );
			$email      = get_user_meta( $current_user->ID, 'billing_email', true );
			$operations = array();
			if ( isset( $name ) && strlen( $name ) > 0 ) {
				$operations[] = array(
					'op'    => 'update_or_create',
					'key'   => '$name',
					'value' => $name,
				); // User name from the order.
			}

			if ( isset( $email ) && 0 < strlen( $email ) ) {
				$operations[] = array(
					'op'    => 'update_or_create',
					'key'   => '$email',
					'value' => $email,
				); // User email from the order.
			}

			if ( isset( $phone ) && 0 < strlen( $phone ) ) {
				$operations[] = array(
					'op'    => 'update_or_create',
					'key'   => '$phone',
					'value' => $phone,
				); // User phone number from the order.
			}

			self::carrotquest_send_operations( $carrotquest_uid, $operations ); // Sending user properties collected earlier.

			if ( isset( $settings['auth'] ) && $settings['auth'] && isset( $settings['auth_key'] ) && 0 < strlen( $settings['auth_key'] ) ) {
				?>
				<script>
					carrotquest.auth('<?php echo esc_textarea( $current_user->ID ); ?>', '<?php echo esc_textarea( hash_hmac( 'sha256', $current_user->ID, $settings['auth_key'] ) ); ?>');
				</script>
				<?php
			}
		}
		delete_transient( 'carrotquest_' . $current_user->user_login );
	}

	/**
	 * Current cart condition
	 */
	private static function cart_info() {
		if ( function_exists( 'WC' ) ) {
			$wc = WC();
		} else {
			global $woocommerce;
			$wc = $woocommerce;
		}
		$cart         = $wc->cart->get_cart(); // Getting current cart.
		$e_cart_items = array();
		$cart_items   = array();
		$properties   = array();

		$cart_amount = 0;
		if ( count( $cart ) ) { // If cart isn't empty, then collecting cart info and preparing it for sending.
			foreach ( $cart as $key => $value ) {
				/*
					Collecting products info in 4 lists - products names, URLs, images and costs
				*/
				$product_id = ( ! empty( $value['variation_id'] ) ) ? $value['variation_id'] : $value['product_id'];

				$product_info = self::get_product_info( $product_id );
				if ( ! $product_info ) {
					continue;
				}
				$price    = $product_info['$amount'];
				$quantity = $value['quantity'];
				$name = $product_info['$name'];

				$e_cart_items['$name'][]   = $name;
				$e_cart_items['$url'][]    = $product_info['$url'];
				$e_cart_items['$amount'][] = round( $price * $quantity );
				if ( isset( $product_info['$img'] ) ) {
					$e_cart_items['$img'][] = $product_info['$img'];
				} else {
					$e_cart_items['$img'][] = '<Нет изображения>';
				}

				$cart_items[] = $name;
				$cart_amount += $price * $quantity;
			}

			$properties[] = array( // Adding item to the content of the cart.
				'op'    => 'update_or_create',
				'key'   => '$cart_items',
				'value' => $cart_items,
			);

			$properties[] = array( // Total of the cart.
				'op'    => 'update_or_create',
				'key'   => '$cart_amount',
				'value' => round( $cart_amount ),
			);
		} else { // Clearing properties with content and total of the cart, if cart is empty.
			$properties[] = array(
				'op'    => 'delete',
				'key'   => '$cart_amount',
				'value' => 0,
			);

			$properties[] = array(
				'op'    => 'delete',
				'key'   => '$cart_items',
				'value' => 0,
			);
		}

		return array(
			'stand_alone_properties' => $properties,
			'event_properties'       => $e_cart_items,
		);
	}

	/**
	 * Get plugin settings
	 *
	 * @return array
	 */
	public static function get_settings() {
		static $settings;
		if ( empty( $settings ) ) {
			$settings['api_key']    = get_option( 'carrotquest_api_key' );
			$settings['api_secret'] = get_option( 'carrotquest_api_secret' );
			$settings['auth_key']   = get_option( 'carrotquest_auth_key' );
			$settings['auth']       = get_option( 'carrotquest_auth' );
		}

		return $settings;
	}

	/**
	 * Sends event to Carrot quest
	 *
	 * @param string|integer $carrotquest_uid contains either carrotquest uid, or inner WP user id.
	 * @param string         $event name of user event. Standard events start with $, list of standard events can be found in service API documentation.
	 * @param array          $params properties that can be sent alongside with event, e.g. product name, with event "Product viewed".
	 * @param bool           $by_user_id if true, parameter $carrotquest_uid contains inner WP user id. For usage requires Carrot quest authentication to be added.
	 */
	public static function carrotquest_send_event(
		$carrotquest_uid, $event, $params = array(), $by_user_id = false
	) {
		$settings = self::get_settings();
		$send_uid = $carrotquest_uid;
		if ( $send_uid && $settings && isset( $settings['api_key'] ) && isset( $settings['api_secret'] ) ) {
			$url     = 'https://api.carrotquest.io/v1/users/' . $send_uid . '/events';
			$headers = array(
				'Content-type' => 'application/x-www-form-urlencoded',
				'User-Agent'   => 'CarrotQuestWP/' . CARROTQUEST_PLUGIN_VERSION . ' (Windows NT 6.1; Win64; x64; rv:47.0) Gecko/20100101 Firefox/47.0',
			);
			$data    = array(
				'auth_token' => 'app.' . $settings['api_key'] . '.' . $settings['api_secret'],
				'event'      => $event,
				'by_user_id' => $by_user_id ? 'true' : 'false',
			);

			if ( count( $params ) > 0 ) {
				$data['params'] = wp_json_encode( $params );
			}
			$options = array(
				'headers' => $headers,
				'method'  => 'POST',
				'body'    => $data,
			);

			wp_remote_request( $url, $options );
		}
	}

	/**
	 * Sends properties to Carrot quest
	 *
	 * @param string|integer $carrotquest_uid contains either carrotquest uid, or inner WP user id.
	 * @param array          $operations array of arrays. Each first level item contains description of operation (how, where and what you sending). Used to set properties of the user. Standard properties start with $, list of standard properties can be found in service API documentation.
	 * @param bool           $by_user_id if true, parameter $carrotquestUID contains inner WP user id. For usage requires Carrot quest authentication to be added.
	 * @param bool           $log_data log to browser console sended data.
	 */
	public static function carrotquest_send_operations(
		$carrotquest_uid, $operations, $by_user_id = false, $log_data = false
	) {
		$settings = self::get_settings();
		$send_uid = $carrotquest_uid;
		if ( $send_uid && $settings && isset( $settings['api_key'] ) && isset( $settings['api_secret'] ) ) {
			$url     = 'https://api.carrotquest.io/v1/users/' . $send_uid . '/props';
			$headers = array(
				'Content-type' => 'application/x-www-form-urlencoded',
				'User-Agent'   => 'CarrotQuestWP/' . CARROTQUEST_PLUGIN_VERSION . ' (Windows NT 6.1; Win64; x64; rv:47.0) Gecko/20100101 Firefox/47.0',
			);
			$data    = array(
				'auth_token' => 'app.' . $settings['api_key'] . '.' . $settings['api_secret'],
				'operations' => wp_json_encode( $operations ),
				'by_user_id' => $by_user_id ? 'true' : 'false',
			);
			if ( $log_data ) {
				?>
				console.log('carrotquest_send_operations', <?php echo wp_json_encode( $data ); ?>);
				<?php
			}
			$options = array(
				'headers' => $headers,
				'method'  => 'POST',
				'body'    => $data,
			);
			wp_remote_request( $url, $options );
		}
	}

	/**
	 * Write string to log file in root folder
	 *
	 * @param string      $title Title mark for log string.
	 * @param string null $text Log line.
	 */
	public static function write_to_log(
		$title, $text = ''
	) {
		$message = $title;
		if ( '' !== $text && 0 < strlen( $text ) ) {
			$message .= ': \r\n' . (string) $text;
		}
		$file_result = '[' . gmdate( 'Y-m-d H:i:s' ) . '] ' . $message . '\r\n';

		$uploads  = wp_upload_dir( null, false );
		$logs_dir = $uploads['basedir'] . '/cq-logs';

		if ( ! is_dir( $logs_dir ) ) {
			mkdir( $logs_dir, 0755, true );
		}

		$file_path = $logs_dir . '/cq_integr_log.txt';
		$open      = fopen( $file_path, "a" );
		$write     = fwrite( $open, $file_result );
		fclose( $open );
	}
}
