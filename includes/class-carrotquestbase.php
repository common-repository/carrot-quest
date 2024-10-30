<?php
/**
 * Carrotquest plugin setup
 *
 * Sets all the dependencies for plugin to work
 *
 * @package Carrotquest
 */

// Require file with callbacks.
require_once 'class-carrotquesthooks.php';

/**
 * Class CarrotquestBase
 *
 * Plugin init class
 */
class CarrotquestBase {

	/**
	 * Plugin was initiated
	 *
	 * @var bool
	 */
	private static $initiated = false;

	/**
	 * Initializing required hooks if they not yet initiated.
	 */
	public static function init() {
		if ( ! self::$initiated ) {
			self::add_hooks();
			self::$initiated = true;
		}
	}


	/**
	 * Add hooks required for plugin to work correctly
	 */
	private static function add_hooks() {
		add_filter(
			'plugin_action_links_' . CARROTQUEST_PLUGIN_BASE,
			array(
				'CarrotquestBase',
				'action_links',
			)
		); // Adding action links for plugin in admin panel.
		add_action( 'admin_menu', array( 'CarrotquestBase', 'add_pages' ) ); // Adding plugin options page.

		
		add_action( 'wp_enqueue_scripts', array('CarrotquestBase', 'plugin_main' ) ); // Adding Carrot quest widget

		add_action( 'wp_login', array( 'CarrotquestHooks', 'user_login' ) );
		add_action( 'wp_footer', array( 'CarrotquestHooks', 'login_authentication' ) );

		add_action(
			'woocommerce_after_single_product',
			array(
				'CarrotquestHooks',
				'product_viewed',
			)
		); // User viewed product.
		add_action( 'woocommerce_after_cart', array( 'CarrotquestHooks', 'cart_viewed' ) ); // User viewed cart.

		add_action(
			'woocommerce_add_to_cart',
			array(
				'CarrotquestHooks',
				'product_added',
			)
		);
		add_action(
			'woocommerce_ajax_added_to_cart',
			array(
				'CarrotquestHooks',
				'product_added',
			)
		); // User added product to cart via ajax.

		add_action(
			'woocommerce_before_checkout_form',
			array(
				'CarrotquestHooks',
				'order_started',
			)
		); // User opened checkout page.
		add_action(
			'woocommerce_checkout_order_processed',
			array(
				'CarrotquestHooks',
				'order_completed',
			)
		); // User completed checkout and order was created.

		add_action( 'woocommerce_order_status_changed', array( 'CarrotquestHooks', 'order_status_changed' ), 10, 3 );
		add_action( 'woocommerce_thankyou', array( 'CarrotquestHooks', 'order_authentication' ) );

		register_uninstall_hook( __FILE__, array( 'carrotquest', 'delete_options' ) );
	}

	/**
	 *  Plugin action links.
	 *
	 * @param array $actions action links array.
	 *
	 * @return mixed
	 */
	public static function action_links( $actions ) {
		$actions[] = '<a href="plugins.php?page=carrotquest">' . __( 'Settings', 'carrotquest' ) . '</a>';

		return $actions;
	}

	/**
	 * Show plugin Options page in menu
	 */
	public static function add_pages() {
		add_submenu_page(
			'plugins.php',
			__( 'Carrot quest', 'carrotquest' ),
			__( 'Carrot quest', 'carrotquest' ),
			'manage_options',
			'carrotquest',
			array( 'CarrotquestBase', 'settings_page' )
		);
		add_action( 'admin_init', array( 'CarrotquestBase', 'add_options' ) ); // Adding plugins options.
	}

	/**
	 * Creating plugin options in wp
	 */
	public static function add_options() {
		if ( ! get_option( 'carrotquest_api_key' ) ) {
			add_option( 'carrotquest_api_key' );
		}
		if ( ! get_option( 'carrotquest_api_secret' ) ) {
			add_option( 'carrotquest_api_secret' );
		}
		if ( ! get_option( 'carrotquest_auth_key' ) ) {
			add_option( 'carrotquest_auth_key' );
		}
		if ( ! get_option( 'carrotquest_auth' ) ) {
			add_option( 'carrotquest_auth' );
		}
	}

	/**
	 * Delete plugin options when plugin deleted
	 */
	public static function delete_options() {
		delete_option( 'carrotquest_api_key' );
		delete_option( 'carrotquest_api_secret' );
		delete_option( 'carrotquest_auth_key' );
		delete_option( 'carrotquest_auth' );
	}


	/**
	 * Form and initialize options page
	 */
	public static function settings_page() {
		if ( isset( $_REQUEST['carrotquest_plugin_form_submit'] )
		     && check_admin_referer( 'carrotquest_plugin_settings', 'carrotquest_plugin_nonce' ) // Updating options if options form was submited.
		) {
			if ( isset( $_REQUEST['carrotquest_api_key'] ) ) {
				update_option( 'carrotquest_api_key', wp_unslash( $_REQUEST['carrotquest_api_key'] ) );
			} else {
				update_option( 'carrotquest_api_key', '' );
			}

			if ( isset( $_REQUEST['carrotquest_api_secret'] ) ) {
				update_option( 'carrotquest_api_secret', sanitize_text_field( wp_unslash( $_REQUEST['carrotquest_api_secret'] ) ) );
			} else {
				update_option( 'carrotquest_api_secret', '' );
			}

			if ( isset( $_REQUEST['carrotquest_auth_key'] ) ) {
				update_option( 'carrotquest_auth_key', sanitize_text_field( wp_unslash( $_REQUEST['carrotquest_auth_key'] ) ) );
			} else {
				update_option( 'carrotquest_auth_key', '' );
			}

			if ( isset( $_REQUEST['carrotquest_auth'] ) ) {
				update_option( 'carrotquest_auth', sanitize_text_field( wp_unslash( $_REQUEST['carrotquest_auth'] ) ) );
			} else {
				update_option( 'carrotquest_auth', '' );
			}

			$message = __( 'Settings saved', 'carrotquest' ); // Everything's OK.
		}
		if ( ! isset( $message ) ) {
			$message = __( 'Failed', 'carrotquest' ); // Something went wrong.
		}
		$page = CARROTQUEST_PLUGIN_DIR . 'options.php';
		ob_start();
		include $page; // Including options page template.
		echo ob_get_clean();
	}


	/**
	 * Adding Carrot quest script to every page if it's not admin panel
	 */
	public static function plugin_main() {
		// Skip our script if it's admin panel
		if ( ! is_admin() ) {
			$settings = CarrotquestHooks::get_settings();
			if ( ! empty( $settings['api_key'] ) ) { // If api_key option is set add code to each non-admin page.
				// @formatter:off
				?>
				<!-- Carrot quest BEGIN -->
				<script type="text/javascript">
					!function(){function t(t,e){return function(){window.carrotquestasync.push(t,arguments)}}if("undefined"==typeof carrotquest){var e=document.createElement("script");e.type="text/javascript",e.async=!0,e.src="//cdn.carrotquest.app/api.min.js",document.getElementsByTagName("head")[0].appendChild(e),window.carrotquest={},window.carrotquestasync=[],carrotquest.settings={};for(var n=["connect","track","identify","auth","oth","onReady","addCallback","removeCallback","trackMessageInteraction"],a=0;a<n.length;a++)carrotquest[n[a]]=t(n[a])}}(),carrotquest.connect('<?php echo esc_textarea( $settings['api_key'] ); ?>');
				</script>
				<!-- Carrot quest END -->
				<?php
				// @formatter:on
			}
		}
	}
}
