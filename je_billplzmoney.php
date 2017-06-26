<?php
/**
 * Plugin Name: Billplz for JobEngine
 * Plugin URI: https://github.com/wzul/billplz-for-jobengine/
 * Description: Billplz Payment Gateway | Accept Payment using all participating FPX Banking Channels. <a href="https://www.billplz.com/join/8ant7x743awpuaqcxtqufg" target="_blank">Sign up Now</a>.
 * Author: Wanzul Hosting Enterprise
 * Author URI: http://www.wanzul-hosting.com/
 * Version: 3.1
 * License: GPLv3
 * Text Domain: enginetheme
 */

/**
 * render paymill settings form
 */
class JE_BILLPLZ
{

    function __construct()
    {
        $this->add_action();
        //register_deactivation_hook(__FILE__, array($this,'deactivation'));
    }

    private function add_action()
    {
        add_action('je_payment_settings', array($this, 'billplz_setting'));
        add_action('after_je_payment_button', array($this, 'billplz_render_button'));
        add_filter('et_support_payment_gateway', array($this, 'et_support_payment_gateway'));
        add_action('wp_footer', array($this, 'frontend_js'));
        add_action('wp_head', array($this, 'frontend_css'));
        //add_action('wp_head' , array($this, 'add_paymill_bublic_key'));
        add_filter('et_update_payment_setting', array($this, 'billplz_update_settings'), 10, 3);

        add_filter('je_payment_setup', array($this, 'billplz_setup_payment'), 10, 3);

        add_filter('je_payment_process', array($this, 'billplz_process_payment'), 10, 3);

        add_filter('et_enable_gateway', array($this, 'et_enable_billplz'), 10, 2);

        // update for mobile version 04/04/2014
        add_action('after_je_mobile_payment_button', array($this, 'add_billplz_button_mobile'));
        add_action('et_mobile_head', array($this, 'billplz_mobile_header'));
        add_action('et_mobile_footer', array($this, 'billplz_mobile_footer'));
    }

    function billplz_mobile_footer()
    {
        if (is_page_template('page-post-a-job.php') || is_page_template('page-upgrade-account.php')) {

            ?>
            <script type="text/javascript" src="<?php echo plugin_dir_url(__FILE__) . 'assets/je_billplz_mobile.js'; ?>"></script>
            <?php
        }
    }

    function frontend_css()
    {
        if (is_page_template('page-post-a-job.php') || is_page_template('page-upgrade-account.php')) {
            wp_enqueue_style('paymill_css', plugin_dir_url(__FILE__) . 'assets/billplz.css');
        }
    }

    function frontend_js()
    {

        /* $general_opts	= new ET_GeneralOptions();
          $website_logo	= $general_opts->get_website_logo();
          $paypill		= $this->get_api(); */

        if (is_page_template('page-post-a-job.php') || is_page_template('page-upgrade-account.php')) {
            wp_enqueue_script('je_billplz', plugin_dir_url(__FILE__) . 'assets/billplz.js', array('jquery'), '1.0', true);

            wp_localize_script('je_billplz', 'je_billplz', array(
                'currency' => ET_Payment::get_currency(),
                )
            );

            include_once dirname(__FILE__) . '/form-template.php';
        }
    }

    /**
     * check payment setting is enable or not
     */
    //et_enable_gateway();

    function is_enable()
    {
        $billplz_api = $this->get_api();

        if ($billplz_api['api_key'] == '')
            return false;
        if ($billplz_api['x_signature'] == '')
            return false;
        if ($billplz_api['reminder'] == '')
            return false;

        return true;
    }

    function et_enable_billplz($available, $gateway)
    {
        // echo $this->alert($gateway);
        if ($gateway == 'billplz') {
            if ($this->is_enable())
                return true;
            return false;
        }
        return $available;
    }

    protected function using_supported_currency($current)
    {
        return in_array(($current), array('MYR', 'RM'));
    }

    /**
     * get paymill api setting
     */
    function get_api()
    {
        return get_option('et_billplz_api', array('api_key' => '', 'x_signature' => '', 'collection_id' => '', 'reminder' => ''));
    }

    /**
     * update billplz api setting
     */
    function set_api($api)
    {
        update_option('et_billplz_api', $api);
        if (!$this->is_enable()) {
            $gateways = ET_Payment::get_gateways();
            if (isset($gateways['billplz']['active']) && $gateways['billplz']['active'] != -1) {
                ET_Payment::disable_gateway('billplz');
                return __('Api setting invalid', ET_DOMAIN);
            }
        }
        return true;
    }

    /**
     * ajax callback to update payment settings
     */
    function billplz_update_settings($msg, $name, $value)
    {
        $billplz_api = $this->get_api();
        switch ($name) {
            case 'BILLPLZ-API-KEY':
                $billplz_api['api_key'] = trim($value);
                $msg = $this->set_api($billplz_api);
                break;
            case 'BILLPLZ-X-SIGNATURE':
                $billplz_api['x_signature'] = trim($value);
                $msg = $this->set_api($billplz_api);
                break;
            case 'BILLPLZ-COLLECTION-ID':
                $billplz_api['collection_id'] = trim($value);
                $msg = $this->set_api($billplz_api);
                break;
            case 'BILLPLZ-REMINDER':
                $billplz_api['reminder'] = trim($value);
                $msg = $this->set_api($billplz_api);
                break;
        }

        return $msg;
    }

    /**
     * process payment after billplz return
     * @param $payment_return, $order , $payment_type
     * @return array()
     * @since 1.0
     * @package je_payment
     */
    function billplz_process_payment($payment_return, $order, $payment_type)
    {

        if ($payment_type == 'billplz') {

            require_once __DIR__ . '/includes/billplz.php';

            $billplz_api = $this->get_api();
            $api_key = $billplz_api['api_key'];
            $signkey = $billplz_api['x_signature'];

            if (isset($_GET['billplz']['id'])) {
                $data = Billplz::getRedirectData($signkey);
            } else {
                exit;
            }

            //$callback_class = new je_billplz_callback($moreData['reference_1']);
            //$order_data = $order->get_order_data();

            if ($data['paid']) {
                $payment_return = array(
                    'ACK' => true,
                    'payment' => 'billplz',
                    'payment_status' => 'Completed'
                );
                //$callback_class->set_status('publish');
                //$callback_class->update_order();

                $order->set_status('publish');
                $order->update_order();
            } else {
                $payment_return = array(
                    'ACK' => false,
                    'payment' => 'billplz',
                    'payment_status' => 'fail',
                    'msg' => __('Billplz Bills is not paid.', ET_DOMAIN)
                );
            }
        }

        return $payment_return;
    }

    /**
     * setup payment before sent to billplz
     * @param $response , $paymentType, $order
     * @return array()
     * @since 1.0
     * @package je_payment
     */
    function billplz_setup_payment($response, $paymentType, $order)
    {

        if ($paymentType == 'BILLPLZ') {
            $billplz_api = $this->get_api();

            $order_pay = $order->generate_data_to_pay();
            $order_id = $order_pay['ID'];
            $productinfo = array_values($order_pay['products']);

            $api_key = $billplz_api['api_key'];
            $collection_id = $billplz_api['collection_id'];
            $deliver = $billplz_api['reminder'] === '0' ? '0' : '3';
            $amount = $productinfo[0]['AMT'];
            $description = $productinfo[0]['NAME'];
            $name = $_POST['billplz_firstname'];
            $email = $_POST['billplz_email'];
            $mobile = $_POST['billplz_phone'];

            /*
             * There is no callback for this plugin
             */
            $redirect_url = et_get_page_link('process-payment', array(
                'paymentType' => 'billplz'
            ));

            require_once __DIR__ . '/includes/billplz.php';

            $billplz = new Billplz($api_key);
            $billplz
                ->setAmount($amount)
                ->setCollection($collection_id)
                ->setDescription($description)
                ->setEmail($email)
                ->setMobile($mobile)
                ->setName($name)
                ->setDeliver($deliver)
                ->setPassbackURL($redirect_url, $redirect_url)
                ->setReference_1($order_id)
                ->setReference_1_Label('ID')
                ->create_bill(true);
            $bill_url = $billplz->getURL();

            $response = array(
                'success' => true,
                'data' => array(
                    'url' => $bill_url
                ),
                'paymentType' => 'BILLPLZ'
            );
        }
        return $response;
    }

    /**
     * render paymill checkout button
     */
    function billplz_render_button($payment_gateways)
    {
        if (!isset($payment_gateways['billplz']))
            return;
        $stripe = $payment_gateways['billplz'];
        if (!isset($stripe['active']) || $stripe['active'] == -1)
            return;

        ?>
        <li class="clearfix">
            <div class="f-left">
                <div class="title"><?php _e('Billplz', ET_DOMAIN) ?></div>
                <div class="desc"><?php _e('Pay using your preferred Internet Banking with participating FPX Retail Banking.', ET_DOMAIN) ?></div>
            </div>
            <div class="btn-select f-right">
                <button id="btn_billplz" class="bg-btn-hyperlink border-radius" data-gateway="paymill" ><?php _e('Select', ET_DOMAIN); ?></button>
            </div>
        </li>
        <?php
    }

    function add_billplz_button_mobile($payment_gateways)
    {
        if (!isset($payment_gateways['billplz']))
            return;
        $paymill = $payment_gateways['billplz'];
        if (!isset($paymill['active']) || $paymill['active'] == -1)
            return;

        ?>
        <style type="text/css">
            .post-new-classified{
                padding: 20px 15px;
            }
            .post-new-classified.paymill a {
                background: -moz-linear-gradient(center top , #FEFEFE 0%, #FAFAFA 16%, #F0F0F0 85%, #E0E0E0 100%) repeat scroll 0 0 rgba(0, 0, 0, 0);
                border: 1px solid #BABABA;
                box-shadow: 1px 0 1px 0 #E3E3E3;
                color: #777777 !important;
                text-align: center;
                text-shadow: 0 -1px 0 #EFEFEF !important;

                display: block;
                clear: both;
                overflow: hidden;						
                font-size: 16px;
                min-width: 0.75em;
                overflow: hidden;
                padding: 0.9em 20px;
                position: relative;
                text-overflow: ellipsis;
                white-space: nowrap;
                font-weight: bold;
                text-decoration: none;
                margin: 10px 0 0 0;
            }
        </style>	
        <div data-role="fieldcontain" class="post-new-classified paymill" >

                <?php _e('Pay using your credit card through Billplz.', ET_DOMAIN) ?>
            <a href="#billplz-modal" data-rel="popup" data-position-to="window" class="ui-btn ui-corner-all ui-shadow ui-btn-inline" >
        <?php _e('Billplz', ET_DOMAIN); ?>
            </a>
        </div>

        <div data-role="popup" id="billplz-modal">
        <?php include_once dirname(__FILE__) . '/form-template.php'; ?>
        </div>
        <?php
    }

    function billplz_mobile_header()
    {

        $billplz = $this->get_api();
        $currency = ET_Payment::get_currency();
        if (is_page_template('page-post-a-job.php') || is_page_template('page-upgrade-account.php')) {

            ?>

            <script type="text/javascript">
                var je_paymill = {
                    'currency': {<?php
            foreach ($currency as $key => $value) {
                echo '"' . $key . '":"' . $value . '",';
            }

            ?>},
                }

            </script>
            <?php
        }
    }

    // add Billplz to je support payment
    function et_support_payment_gateway($gateway)
    {
        $gateway['billplz'] = array(
            'label' => __("Billplz", ET_DOMAIN),
            'description' => __("Send your payment through paymill", ET_DOMAIN),
            'active' => -1
        );
        return $gateway;
    }

    /**
     * render billplz settings form in backend
     */
    function billplz_setting()
    {
        $billplz_api = $this->get_api();
        //print_r($billplz_api);

        ?>
        <div class="item">
            <div class="payment">
                <a class="icon" data-icon="y" href="#"></a>
                <div class="button-enable font-quicksand">
                <?php et_display_enable_disable_button('billplz', 'Billplz') ?>
                </div>
                <span class="message"></span>
        <?php _e("Billplz", ET_DOMAIN); ?>
            </div>
            <div class="form payment-setting">
                <div class="form-item">
                    <div class="label">
        <?php _e("API Secret Key ", ET_DOMAIN); ?> 
                    </div>
                    <input class="bg-grey-input <?php if ($billplz_api['api_key'] == '') echo 'color-error' ?>" name="billplz-api-key" type="text" value="<?php echo $billplz_api['api_key'] ?> " />
                    <span class="icon <?php if ($billplz_api['api_key'] == '') echo 'color-error'; ?>" data-icon="<?php data_icon($billplz_api['api_key']); ?>"></span>
                </div>
                <div class="form-item">
                    <div class="label">
        <?php _e("X Signature Key", ET_DOMAIN); ?>

                    </div>
                    <input class="bg-grey-input <?php if ($billplz_api['x_signature'] == '') echo 'color-error' ?>" type="text" name="billplz-x-signature" value="<?php echo $billplz_api['x_signature'] ?> " />
                    <span class="icon <?php if ($billplz_api['x_signature'] == '') echo 'color-error'; ?>" data-icon="<?php data_icon($billplz_api['x_signature']); ?>"></span>
                </div>
                <div class="form-item">
                    <div class="label">
        <?php _e("Collection ID", ET_DOMAIN); ?>

                    </div>
                    <input class="bg-grey-input <?php if ($billplz_api['collection_id'] == '') echo 'color-error' ?>" type="text" name="billplz-collection-id" value="<?php echo $billplz_api['collection_id'] ?> " />
                    <span class="icon <?php if ($billplz_api['collection_id'] == '') echo 'color-error'; ?>" data-icon="<?php data_icon($billplz_api['collection_id']); ?>"></span>
                </div>

                <div class="form-item">
                    <div class="label">
        <?php _e("Send Billls to Customer", ET_DOMAIN); ?>

                    </div>
                    <input class="bg-grey-input" type="checkbox" name="billplz-reminder" <?php
                    if (!empty($billplz_api['reminder'])) {
                        echo 'checked';
                    }

                    ?> />Charge RM0.15 per Bills sent
                    <span class="icon" data-icon="<?php data_icon($billplz_api['reminder']); ?>"></span>
                </div>

            </div>
        </div>
        <?php
    }
}

new JE_BILLPLZ();
