<?php
/**
 * Plugin Name:       Bai Paga Payment Gateway
 * Plugin URI:        https://albreis.com.br/pix-pagseguro
 * Description:       O BAI PAGA é uma solução inovadora de pagamentos do Banco BAI
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Albreis - Design & Programação
 * Author URI:        https://albreis.com.br
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://albreis.com.br/
 * Text Domain:       bai-paga
 */

if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	return;
}

register_activation_hook( __FILE__, function() {
  /**
   * Add webhook key
   */
  if(!get_option('b_p__webhook_key')) {
    update_option('b_p__webhook_key', md5(microtime()));
  }
  if(!get_option('b_p__certfile')) {
    update_option('b_p__certfile', md5(microtime()));
  }
} );

add_action( 'woocommerce_api_' . get_option('b_p__webhook_key'), function() { 
  global $wpdb;
  $logger = wc_get_logger();
  $gateway = new WC_BaiPaga_Gateway;
  $tokens = get_option('bai_paga');
  $res = json_decode(file_get_contents('php://input'));
  if($res->status == 'CONCLUIDA') {
    $order_id = $wpdb->get_var("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_ppst_txid' AND meta_value ='{$res->txid}' LIMIT 1");
    if($order_id) {
      $order = wc_get_order($order_id);
      $order->set_status('processing');
      $order->save();
    }
  }
  exit;
} );

add_action( 'wp_enqueue_scripts', function() {
	wp_enqueue_style( 'bai-paga', plugin_dir_url(__FILE__) . 'assets/css/style.css', [], time() );
	wp_enqueue_script( 'main', plugin_dir_url(__FILE__) . 'assets/js/app.js', array(), time(), false );
  wp_enqueue_script( 'vue', plugin_dir_url(__FILE__) . 'assets/js/vue.min.js', array(), time(), false );
} );
add_action( 'admin_enqueue_scripts', function() {
	wp_enqueue_style( 'admin-bai-paga', plugin_dir_url(__FILE__) . 'assets/css/admin-style.css', [], time() );
	wp_enqueue_script( 'admin-main', plugin_dir_url(__FILE__) . 'assets/js/admin-app.js', array(), time(), false );
	wp_enqueue_script( 'admin-vue', plugin_dir_url(__FILE__) . 'assets/js/vue.js', array(), time(), false );
} );

/**
 * Add gateway class and register with woocommerce
 */
add_action( 'plugins_loaded', function() {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
  }
  
	include_once plugin_dir_path( __FILE__ ) . 'classes/WC_BaiPaga_Gateway.class.php';
	add_filter( 'woocommerce_payment_gateways', function( $methods ) {
		$methods[] = 'WC_BaiPaga_Gateway';
		return $methods;
	}, 1000 );
	
} , 0 );   

/**
 * Thank You Page Content
 *
 * @param int $order_id Order Id.
 */
add_action( 'woocommerce_thankyou', function( $order_id ) {
  $order = new WC_Order( $order_id );
  $tokens = get_option('bai_paga');
  $gateway = new WC_BaiPaga_Gateway;  					
	if ( $order->get_payment_method() === 'bai_paga' ) {
	}
} );

add_action('rest_api_init', function() {
});

