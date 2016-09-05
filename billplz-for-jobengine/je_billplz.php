<?php
/**
 * Plugin Name: Billplz for JobEngine
 * Plugin URI: http://www.facebook.com/billplzplugin
 * Description: Billplz Payment Gateway | Accept Payment using all participating FPX Banking Channels. <a href="https://www.billplz.com/join/8ant7x743awpuaqcxtqufg" target="_blank">Sign up Now</a>.
 * Author: Wanzul Hosting Enterprise
 * Author URI: http://www.wanzul-hosting.com/
 * Version: 3.0
 * License: GPLv3
 */

/**
 * render paymill settings form
 */
class JE_BILLPLZ {

    function __construct() {
        $this->add_action();
        //register_deactivation_hook(__FILE__, array($this,'deactivation'));
    }

    private function add_action() {
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
        add_action('et_mobile_footer', array($this, 'paymill_mobile_footer'));
    }

    function frontend_css() {
        if (is_page_template('page-post-a-job.php') || is_page_template('page-upgrade-account.php')) {
            wp_enqueue_style('paymill_css', plugin_dir_url(__FILE__) . 'assets/billplz.css');
            $paypill = $this->get_api();
            ?>
            <script type="text/javascript"> var PAYMILL_PUBLIC_KEY = '<?php echo $paypill["public_key"]; ?>';</script>
            <?php
        }
    }

    function frontend_js() {

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

    function is_enable() {
        $billplz_api = $this->get_api();

        if ($billplz_api['merchan_id'] == '')
            return false;
        if ($billplz_api['collection_id'] == '')
            return false;
        return true;
    }

    function et_enable_billplz($available, $gateway) {
        // echo $this->alert($gateway);
        if ($gateway == 'billplz') {
            if ($this->is_enable())
                return true;
            return false;
        }
        return $available;
    }

    /**
     * get paymill api setting
     */
    function get_api() {
        return get_option('et_billplz_api', array('merchan_id' => '', 'collection_id' => ''));
    }

    /**
     * update billplz api setting
     */
    function set_api($api) {
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
    function billplz_update_settings($msg, $name, $value) {
        $billplz_api = $this->get_api();
        switch ($name) {
            case 'BILLPLZ-MERCHAN-ID':
                $billplz_api['merchan_id'] = trim($value);
                $msg = $this->set_api($billplz_api);
                break;
            case 'BILLPLZ-SALT':
                $billplz_api['collection_id'] = trim($value);
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
     * @author QuangDat
     */
    function billplz_process_payment($payment_return, $order, $payment_type) {
        $billplz_api = $this->get_api();

        require_once 'billplz.php';

        if ($payment_type == 'billplz') {
            $order_data = $order->get_order_data();
            $test_mode = ET_Payment::get_payment_test_mode();
            $mode = $test_mode ? 'Staging' : 'Production';
            $id = isset($_GET['billplz']['id']) ? $_GET['billplz']['id'] : $_POST['id'];
            $obj = new billplz;
            $data = $obj->check_bill($billplz_api['merchan_id'], $id, $mode);
            unset($obj);

            $amount = $data['amount'] / 100;

            if ($data['paid']) {
                
                $payment_return = array(
                    'ACK' => true,
                    'payment' => 'billplz',
                    'payment_status' => 'Completed'
                );
                $order->set_status('publish');
                $order->update_order();
            } else {
                $payment_return = array(
                    'ACK' => false,
                    'payment' => 'billplz',
                    'payment_status' => 'fail',
                    'msg' => __('Billplz Payment Cancelled.', ET_DOMAIN)
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
     * @author QuangDat
     */
    function billplz_setup_payment($response, $paymentType, $order) {
        //$this->alert('ok');

        if ($paymentType == 'BILLPLZ') {
            $billplz_api = $this->get_api();

            $order_pay = $order->generate_data_to_pay();
            $order_id = $order_pay['ID'];
            //$billplz_info = ae_get_option('billplz');
            $productinfo = array_values($order_pay['products']);
            /*
             * If $test_mode is true
             * @return true
             * Else
             * $return false
             */
            $test_mode = ET_Payment::get_payment_test_mode();
            $mode = $test_mode ? 'Staging' : 'Production';

            $hash_data['txnid'] = substr(hash('sha256', mt_rand() . microtime()), 0, 20);
            // Unique alphanumeric Transaction ID
            $hash_data['amount'] = $productinfo[0]['AMT'];
            $hash_data['productinfo'] = $productinfo[0]['NAME'];
            $hash_data['firstname'] = $_POST['billplz_firstname'];
            $hash_data['email'] = $_POST['billplz_email'];
            $hash_data['phone'] = $_POST['billplz_phone'];

            require_once 'billplz.php';
            $obj = new billplz;
            $obj->setEmail($hash_data['email'])
                    ->setMobile($hash_data['phone'])
                    ->setAutoSubmit('fpx')
                    ->setCollection($billplz_api['collection_id'])
                    ->setName($hash_data['firstname'])
                    ->setAmount($hash_data['amount'])
                    ->setDeliver(false)
                    ->setReference_1($order_id)
                    ->setDescription($hash_data['productinfo'] . '.')
                    ->setPassbackURL(et_get_page_link('process-payment', array('paymentType' => 'billplz')), et_get_page_link('process-payment', array('paymentType' => 'billplz')))
                    ->create_bill($billplz_api['merchan_id'], $mode);

            if ($hash_data['email'] != "") {
                $response = array(
                    'success' => true,
                    'data' => array(
                        'url' => $obj->getURL(),
                        'ACK' => true,
                        'value' => $hash_data,
                        'surl' => et_get_page_link('process-payment', array(
                            'paymentType' => 'billplz'
                        )),
                        'furl' => et_get_page_link('process-payment', array(
                            'paymentType' => 'billplz'
                        )),
                    ),
                    'paymentType' => 'BILLPLZ'
                );
            } else {
                $response = array(
                    'success' => false,
                    'data' => array(
                        'url' => site_url('post-place'),
                        'ACK' => false
                    )
                );
            }
        }
        return $response;
    }

    /**
     * render paymill checkout button
     */
    function billplz_render_button($payment_gateways) {
        if (!isset($payment_gateways['billplz']))
            return;
        $stripe = $payment_gateways['billplz'];
        if (!isset($stripe['active']) || $stripe['active'] == -1)
            return;
        ?>
        <li class="clearfix">
            <div class="f-left">
                <div class="title"><?php _e('Billplz Malaysia Payment Gateway', ET_DOMAIN) ?></div>
                <div class="desc"><?php _e('Pay using Malaysia Internet Banking Account (Maybank, CIMB Clicks, Bank Islam, HLB, PBe, Affin Bank, FPX).', ET_DOMAIN) ?></div>
            </div>
            <div class="btn-select f-right">
                <button id="btn_billplz" class="bg-btn-hyperlink border-radius" data-gateway="paymill" ><?php _e('Select', ET_DOMAIN); ?></button>
            </div>
        </li>
        <?php
    }

    function add_billplz_button_mobile($payment_gateways) {
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

            <?php _e('Pay using your Internet Banking through Billplz.', ET_DOMAIN) ?>
            <a href="#billplz-modal" data-rel="popup" data-position-to="window" class="ui-btn ui-corner-all ui-shadow ui-btn-inline" >
                <?php _e('Billplz', ET_DOMAIN); ?>
            </a>
        </div>

        <div data-role="popup" id="billplz-modal">
            <?php include_once dirname(__FILE__) . '/form-template.php'; ?>
        </div>
        <?php
    }

    function billplz_mobile_header() {

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

    function paymill_mobile_footer() {
        if (is_page_template('page-post-a-job.php') || is_page_template('page-upgrade-account.php')) {
            ?>
            <script type="text/javascript" src="<?php echo plugin_dir_url(__FILE__) . 'assets/je_billplz_mobile.js'; ?>"></script>
            <?php
        }
    }

    // add stripe to je support payment
    function et_support_payment_gateway($gateway) {
        $gateway['billplz'] = array(
            'label' => __("Billplz", ET_DOMAIN),
            'description' => __("Send your payment through Billplz", ET_DOMAIN),
            'active' => -1
        );
        return $gateway;
    }

    /**
     * render billplz settings form in backend
     */
    function billplz_setting() {
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
                        <?php _e("API Secret Key", ET_DOMAIN); ?> 
                    </div>
                    <input class="bg-grey-input <?php if ($billplz_api['merchan_id'] == '') echo 'color-error' ?>" name="billplz-merchan-id" type="text" value="<?php echo $billplz_api['merchan_id'] ?> " />
                    <span class="icon <?php if ($billplz_api['merchan_id'] == '') echo 'color-error' ?>" data-icon="<?php data_icon($billplz_api['merchan_id']) ?>"></span>
                </div>
                <div class="form-item">
                    <div class="label">
                        <?php _e("Collection ID", ET_DOMAIN); ?>

                    </div>
                    <input class="bg-grey-input <?php if ($billplz_api['collection_id'] == '') echo 'color-error' ?>" type="text" name="billplz-salt" value="<?php echo $billplz_api['collection_id'] ?> " />
                    <span class="icon <?php if ($billplz_api['collection_id'] == '') echo 'color-error' ?>" data-icon="<?php data_icon($billplz_api['collection_id']) ?>"></span>
                </div>
            </div>
        </div>
        <?php
    }

}

new JE_BILLPLZ();
