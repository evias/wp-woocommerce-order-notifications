<?php
/*
 * Plugin Name: WooCommerce Order Notifications
 * Plugin URI: https://evias.be/plugins/wp-woocommerce-order-notifications/
 * Description: Sound Alerts and Notifications on incoming WooCommerce Orders
 * Author: Grégory Saive (eVias)
 * Author URI: https://evias.be/
 * Version: 0.1.0
 * Requires at least: 4.4
 * Tested up to: 5.0
 * WC requires at least: 2.6
 * WC tested up to: 3.5
 * Text Domain: wp-woocommerce-order-notifications
 * Domain Path: /languages
 */
/**
 * Copyright 2019 Grégory Saive (greg@evias.be - eVias Services)
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *     http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

// Security Notice: https://codex.wordpress.org/Writing_a_Plugin#Plugin_Files
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @brief   Method wpwon_add_orders_ajax_call
 * @detail  Registers the JS script for Pulling WooCommerce Orders 
 */
function wpwon_add_orders_ajax_call(){
    echo <<<EJS
<script>
// globalize the counter to each page load
var current_count_orders = -1;

/**
 * @brief   Method process_api_response
 */
var process_api_response = function(data) {
    if (typeof data == 'string') {
        try {ndata = JSON.parse(data); }
        catch(e) { console.log("Error in JSON: ", e); return false; }
    }

    let cnt = parseInt(data.count);

    if (current_count_orders < 0) {
        current_count_orders = cnt;
        return false; // fresh reload
    }

    if (cnt > current_count_orders) {
        console.log("Time to bell!!! RIIINNGG");
        //XXX ring <audio>

        current_count_orders = cnt;
    }

    return false;
};

/**
 * @brief   Method api_check_orders
 */
var api_check_orders = function() {
   $.ajax({
        url: ajaxurl, // Since WP 2.8 ajaxurl is always defined and points to admin-ajax.php
        data: {
            'action': 'wpwon_check_orders'
        },
        success:function(data) {
            return process_api_response(data);
        },
        error: function(errorThrown){
            console.log("Error: ", errorThrown);
        }
    });
};

jQuery(document).ready(function($) {
 
    setInterval(api_check_orders, 20000);

    // open the dance..
    api_check_orders();
});
</script>
EJS;
}

/**
 * @brief   Method wpwon_check_orders
 * @detail  Check for available WooCommerce Orders
 */
function wpwon_check_orders() {

    global $wpdb;

    // Read total number of orders
    $results = $wpdb->get_results( "SELECT count(*) as cnt_orders FROM {$wpdb->prefix}posts WHERE post_type = 'shop_order'", OBJECT );

    $json = "{}";
    if (!empty($results)) {
        $count   = $results[0]->cnt_orders;
        $json    = json_encode(['count' => $count]); 
    }

    // Return JSON
    header("Content-Type: application/json");
    header("Content-Length: " . strlen($json));
    echo $json; 
    exit;
}

// register actions in wordpress loop
add_action('in_admin_footer', 'wpwon_add_orders_ajax_call');
add_action('wp_ajax_wpwon_check_orders', 'wpwon_check_orders'); // wp_ajax hook
?>
