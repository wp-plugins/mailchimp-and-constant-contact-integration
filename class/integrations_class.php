<?php

require 'api/ct/src/Ctct/autoload.php';

use Ctct\ConstantContact;
use Ctct\Components\Contacts\Contact;
use Ctct\Components\Contacts\ContactList;
use Ctct\Components\Contacts\EmailAddress;
use Ctct\Exceptions\CtctException;
use Ctct\Auth\SessionDataStore;
use Ctct\Auth\CtctDataStore;
use Ctct\Services;

class Integrations extends WC_Integration {

    function __construct() {
        if (!class_exists('MCAPI')) {
            include_once( 'api/class-MCAPI.php' );
        }

        include_once 'api/ct/src/Ctct/ConstantContact.php';

        // register Javascript for form_fields
        add_action('admin_enqueue_scripts', array($this, 'enqueue_js'));

        // MailChimp Value
        $this->method_title = __('MailChimp and Constant Contact', 'cc_mailchimp');
        $this->method_description = __('MailChimp and Constant Contact are a popular email marketing services. Select MailChimp or Constant Contanct', 'cc_mailchimp');
        $this->id = 'mailchimp';
        $this->mc_api_key = $this->get_option('mc_api_key'); // api key for mailchimp
        $this->enabled_mc = $this->get_option('enabled_mc');
        $this->mc_label = $this->get_option('mc_label');
        $this->mc_list = $this->get_option('mc_list');

        // Constant Contact Value
        $this->cc_access_token = $this->get_option('cc_access_token'); // 
        $this->cc_api_key = $this->get_option('cc_api_key'); // constant contact key
        $this->cc_user_name = $this->get_option('cc_user_name'); // constant contact user name
        $this->cc_password = $this->get_option('cc_password'); // constant contact password        
        $this->enabled_cc = $this->get_option('enabled_cc');
        $this->cc_label = $this->get_option('cc_label');
        $this->cc_list = $this->get_option('cc_list');

        $this->init_settings();
        add_action('woocommerce_update_options_integration', array($this, 'process_admin_options'));
        add_action('woocommerce_update_options_integration_' . $this->id, array($this, 'process_admin_options'));

        if ($this->enabled_cc == 'yes') {
            add_filter('woocommerce_checkout_fields', array(&$this, 'add_cc_checkbox_to_checkout'));
        } elseif ($this->enabled_mc == 'yes') {
            add_filter('woocommerce_checkout_fields', array(&$this, 'add_mc_checkbox_to_checkout'));
        }

        add_action('woocommerce_checkout_update_order_meta', array(&$this, 'order_submit'), 1000, 1);
        add_action('woocommerce_order_status_changed', array(&$this, 'order_submit'), 10, 3);
        add_action('woocommerce_checkout_order_processed', array(&$this, 'subscribe', 10, 2));
        $this->form_fields();
    }

// Add user when order submit
    public function order_submit($id) {
        if ($this->valid_mailchimp() || $this->valid_cc()) {
            $order = new WC_Order($id);
            $mailchimp_checkout = get_post_meta($id, 'mailchimp_checkout', true);
            $cc_checkout = get_post_meta($id, 'cc_checkout', true);

            if (!isset($mailchimp_checkout) || empty($mailchimp_checkout) || 'yes' == $mailchimp_checkout) {

                $this->subscribe($id, $order->billing_first_name, $order->billing_last_name, $order->billing_email, $this->mc_list);
            }
            if (!isset($cc_checkout) || empty($cc_checkout) || 'yes' == $cc_checkout) {
                $this->subscribe($id, $order->billing_first_name, $order->billing_last_name, $order->billing_email, $this->cc_list);
            }
        }
    }

// Check if enabled/disabled mailchim checkbox on Settings->Integrations
    public function valid_mailchimp() {
        if ($this->enabled_mc == 'yes' && $this->check_mailchimp_api_key()) {
            return true;
        }
        return false;
    }

// form fields on Settings-> Integration
    public function form_fields() {
        $lists_for_mc = $this->get_mc_lists();
        $lists_for_cc = $this->get_cc_lists();
        if ($lists === false) {
            $lists = array();
        }
        if ($lists_for_cc === false) {
            $lists_for_cc = array();
        }
        $this->form_fields = array(
            'enabled_mc' => array(
                'title' => __('Enable/Disable Mailchimp'),
                'label' => __('Enable MailChimp'),
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no'
            ),
            'mc_api_key' => array(
                'title' => __('MailChimp Api key'),
                'description' => __('Enter your Api key'),
                'type' => 'text',
                'default' => ''
            ),
            'mc_list' => array(
                'title' => __('MailChimp List'),
                'description' => __('Select your MailChimp List'),
                'css' => 'min-width:300px;',
                'type' => 'select',
                'options' => $lists_for_mc
            ),
            'mc_label' => array(
                'title' => __('Checkout Mailchimp label', 'fans'),
                'description' => __('This text will be displayed in the checkbox at checkout'),
                'type' => 'text',
                'css' => 'min-width:300px;'
            ),
            'enabled_cc' => array(
                'title' => __('Enable/Disable Constant Contact'),
                'label' => __('Enable Constant Contact'),
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no'
            ),
            'cc_list' => array(
                'title' => __('Constant Contact List'),
                'description' => __('Enter your Contact List'),
                'type' => 'select',
                'options' => $lists_for_cc,
                'css' => 'min-width:300px;'
            ),
            'cc_api_key' => array(
                'title' => __('Constant Contact Key'),
                'description' => __('Enter your Key'),
                'type' => 'text',
                'default' => ''
            ),
            'cc_access_token' => array(
                'title' => __('Access Token'),
                'description' => __('Enter your Access Token'),
                'type' => 'text',
                'default' => ''
            ),
            'cc_label' => array(
                'title' => __('Checkout CC Email label', 'fans'),
                'description' => __('This text will be displayed in the checkbox at checkout'),
                'type' => 'text',
                'css' => 'min-width:300px;'
            ),
        );
    }

// Add JavaScript to woocommerce
    public function enqueue_js() {
        wp_enqueue_script('admin_form_js', plugin_dir_url(__FILE__) . 'js/admin_form_js.js', array('jquery'), 1, true);
    }

// Check mailchimp api key
    public function check_mailchimp_api_key() {
        if ($this->mc_api_key) {
            return true;
        }
    }

// Add Mailchimp checkbox to checkout
    public function add_mc_checkbox_to_checkout($checkout_fields) {
        if ($this->get_option('mc_api_key')) {
            $checkout_fields['billing']['mailchimp_checkout'] = array(
                'type' => 'checkbox',
                'label' => esc_attr($this->mc_label),
                'default' => 1,
            );
        }

        return $checkout_fields;
    }

// Save value of mailchimp checkout checkbox
    public function save_mailchimp_checkout_checbox($order_id) {
        $opt_in = isset($_POST['mailchimp_checkout']) ? 'yes' : 'no';
        update_post_meta($order_id, 'mailchimp_checkout', $opt_in);
    }

// Get all lists from mailchimp
    public function get_mc_lists() {
        if ($this->valid_mailchimp()) {
            $lists = unserialize(get_transient('mailchimp_list'));
            if (empty($lists)) {
                include_once('api/class-MCAPI.php');
                $key = $this->mc_api_key;
                $api = new MCAPI($key);
                $retval = $api->lists();
                if ($api->errorCode) {
                    echo '<div class="error"><p>' . sprintf(__('Unable to load lists() from MailChimp: (%s) %s'), $api->errorCode, $api->errorMessage) . '</p></div>';
                } else {
                    if ($retval['total'] == 0) {
                        echo '<div class="error"><p>' . sprintf(__('You haven\'t created lists at MailChimp : (%s) %s'), $api->errorCode, $api->errorMessage) . '</p></div>';
                    }
                    foreach ($retval['data'] as $mail_list) {
                        $lists[$mail_list['id']] = $mail_list['name'];
                    }
                    set_transient('mailchimp_list', serialize($lists), 60 * 60 * 1);
                }
            }
        }
        return $lists;
    }

// Subscribe user to mailchimp and Constant Contact list
    public function subscribe($order_id, $first_name, $last_name, $email, $listid = 'false') {
        if ($this->valid_mailchimp()) {
            if ($listid == 'false') {
                $listid = $this->mc_list;
            }
            $api = new MCAPI($this->mc_api_key);
            $merge_vars = array('FNAME' => $first_name, 'LNAME' => $last_name);
            $vars = apply_filters('subscribe_merge_vars', $merge_vars, $order_id);
            $email_type = 'html';
            $update_existing = true;
            $replace_interests = false;
            $send_welcome = false;
            $retval = $api->listSubscribe($listid, $email, $vars, $email_type, $update_existing, $replace_interests, $send_welcome);
        }
        $access_token = $this->cc_access_token;
        $apikey = $this->cc_api_key;

        $ConstantContact = new ConstantContact($apikey);
        if ($this->valid_cc()) {
            $list_id = $this->cc_list;
            if (isset($email) && strlen($email) > 1) {
                $response = $ConstantContact->getContactByEmail($access_token, $email);
                if (empty($response->results)) {
                    $Contact = new Contact();
                    $Contact->addEmail($email);
                    $Contact->first_name = $first_name;
                    $Contact->last_name = $last_name;
                    $Contact->addList($list_id);
                    $NewContact = $ConstantContact->addContact($access_token, $Contact, false);
                    //var_dump($NewContact);
                } else {
                    $Contact = $response->results[0];
                    $Contact->first_name = $first_name;
                    $Contact->last_name = $last_name;
                    $Contact->addList($list_id);
                    $new_contact = $ConstantContact->updateContact($access_token, $Contact, false);
                }
            }
        }
    }

//Add Constant Contact to checkout
    public function add_cc_checkbox_to_checkout($checkout_fields) {
        if ($this->get_option('cc_access_token')) {
            $checkout_fields['billing']['cc_checkout'] = array(
                'type' => 'checkbox',
                'label' => esc_attr($this->cc_label),
                'default' => 1,
            );
        }
        return $checkout_fields;
    }

// Save value of Constant Contact checkout checkbox
    public function save_cc_checkout_checbox($order_id) {
        $opt_in = isset($_POST['cc_checkout']) ? 'yes' : 'no';
        update_post_meta($order_id, 'cc_checkout', $opt_in);
    }

// Check cc api key
    public function check_cc_api_key() {
        if ($this->cc_api_key)
            return true;
    }

// Check if enabled/disabled Constant Contact checkbox on Settings->Integrations
    public function valid_cc() {
        if ($this->enabled_cc == 'yes' && $this->check_cc_api_key()) {
            return true;
        }
        return false;
    }

// Get Constant Contact lists
    public function get_cc_lists() {
        if ($this->valid_cc()) {
            include_once 'api/ct/src/Ctct/ConstantContact.php';
            $apikey = $this->cc_api_key;
            $access_token = $this->cc_access_token;
            $cc_list = array();
            $ConstantContact = new ConstantContact($apikey);
            $ContactList = $ConstantContact->getLists($access_token);
            foreach ($ContactList as $k => $v) {
                $cc_list[$v->id] = $v->name;
            }
        }
        return $cc_list;
    }

}
