<?php

/*
 * Plugin Name: Mailchimp and Constant Contact Integration
 * Description: Mailchimp and Constant Contact
 * Version: 1.0
 * Author: WEB4PRO_co
 * Author URI: http://web4pro.net
 */

add_action('plugins_loaded', 'woocommerce_mailchimp_cc_init', 0);

function woocommerce_mailchimp_cc_init() {

    if (!class_exists('WC_Integration'))
        return;
    include_once( 'class/integrations_class.php' );

    /**
     * Add the Integration to WooCommerce
     * */
    function add_mailchimp_cc_integration($methods) {
        $methods[] = 'Integrations';
        return $methods;
    }

    add_filter('woocommerce_integrations', 'add_mailchimp_cc_integration');

    function action_links($links) {

        global $woocommerce;

        $settings_url = admin_url('admin.php?page=woocommerce_settings&tab=integration&section=mailchimp');

        if ($woocommerce->version >= '2.1') {
            $settings_url = admin_url('admin.php?page=wc-settings&tab=integration&section=mailchimp');
        }

        $plugin_links = array(
            '<a href="' . $settings_url . '">' . __('Settings', 'cc_mailchimp') . '</a>',
        );

        return array_merge($plugin_links, $links);
    }

    // Add the "Settings" links on the Plugins administration screen
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'action_links');
}
