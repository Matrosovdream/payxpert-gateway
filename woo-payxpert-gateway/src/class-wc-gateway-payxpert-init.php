<?php
class WC_Gateway_PayXpert_init {

    protected $plugin_slug = 'WC_Gateway_payxpert';

    public function __construct() {

        add_action('plugins_loaded', array($this, 'woocommerce_payxpert_init'), 0);

        // Include classes
        $this->include_classes();

    }

    public function woocommerce_payxpert_init() {

        if (!class_exists('WC_Payment_Gateway')) {
            return;
        }

        // Gateway classes
        require_once PAYX_ABSPATH.'/src/gateways/class-wc-gateway-payxpert.php';

        // Settings classes
        require_once PAYX_ABSPATH.'/src/helpers/class-payxpert-settings.php';


        add_filter('woocommerce_payment_gateways', array($this, 'woocommerce_payxpert_add_gateways'));
    }

    public function woocommerce_payxpert_add_gateways($methods) {

        // Default gateway
        $methods[] = 'WC_Gateway_PayXpert';
        
        return $methods;

    }

    public function include_classes() {

        require_once PAYX_ABSPATH.'/src/admin/class-payxpert-admin-settings.php';

    }

}

new WC_Gateway_PayXpert_init();