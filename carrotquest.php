<?php
/**
 * Plugin Name: Carrot quest
 * Description: Carrot quest is a customer service, combining all instruments for marketing automation, sales and communications for your web app. Goal is to increase first and second sales.
 * Version: 2.1.1
 * Author: Carrot quest
 * Author URI: https://www.carrotquest.io
 * Text Domain: carrotquest
 * Domain Path: /languages
 *
 * @package Carrotquest
 */

define( 'CARROTQUEST_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CARROTQUEST_PLUGIN_BASE', plugin_basename( __FILE__ ) );

$plugin_data    = get_file_data( __FILE__, array( 'Version' => 'Version' ), false );
$plugin_version = $plugin_data['Version'];
define( 'CARROTQUEST_PLUGIN_VERSION', $plugin_version );

require_once CARROTQUEST_PLUGIN_DIR . 'includes/class-carrotquestbase.php';

// Plugin initialization.
add_action( 'init', array( 'CarrotquestBase', 'init' ) );
// Localization files.
load_plugin_textdomain( 'carrotquest', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
