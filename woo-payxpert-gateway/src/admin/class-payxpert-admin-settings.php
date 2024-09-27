<?php
class PayXpert_admin_settings {

    public function get_common_fields() {

        $fields = array(
            'enabled' => array(
              'title' => __('Enable/Disable', 'payxpert'),
              'type' => 'checkbox',
              'label' => __('Enable PayXpert payment gateway', 'payxpert'),
              'default' => 'yes'
            ),
            'originator_id' => array(
              'title' => __('Originator ID', 'payxpert'),
              'type' => 'text',
              'description' => __('The identifier of your Originator', 'payxpert'),
              'default' => ''
            ),
            'password' => array(
              'title' => __('Password', 'payxpert'),
              'type' => 'text',
              'description' => __('The password associated with your Originator', 'payxpert'),
              'default' => ''
            ),
            'merchant_notifications' => array(
              'title' => __('Merchant Notifications', 'payxpert'),
              'type' => 'select',
              'class' => 'wc-enhanced-select',
              'description' => __('Determine if you want or not merchant notifications after each payment attempt', 'payxpert'),
              'default' => 'default',
              'options' => array(
                'default' => __('Default value for the account', 'payxpert'),
                'enabled' => __('Enabled', 'payxpert'),
                'disabled' => __('Disabled', 'payxpert')
              )
            ),
            'merchant_notifications_to' => array(
              'title' => __('Merchant email notifications recipient', 'payxpert'),
              'type' => 'text',
              'description' => __('The email address that will receive merchant notifications', 'payxpert'),
              'default' => ''
            ),
            'merchant_notifications_lang' => array(
              'title' => __('Merchant email notifications language', 'payxpert'),
              'type' => 'select',
              'class' => 'wc-enhanced-select',
              'description' => __('The language that will be used for merchant notifications', 'payxpert'),
              'default' => 'default',
              'options' => array(
                'en' => __('English', 'payxpert'),
                'fr' => __('French', 'payxpert'),
                'es' => __('Spanish', 'payxpert'),
                'it' => __('Italian', 'payxpert'),
                'de' => __('German', 'payxpert'),
                'pl' => __('Polish', 'payxpert'),
                'zh' => __('Chinese', 'payxpert'),
                'ja' => __('Japanese', 'payxpert')
              )
            ),
            'title' => array(
              'title' => __('Title', 'payxpert'),
              'type' => 'text',
              'description' => __('This controls the title the user sees during checkout.', 'payxpert'),
              'default' => __('Credit Card Payment via PayXpert WeChat', 'payxpert'),
              'desc_tip' => true
            ),
            'pay_button' => array(
              'title' => __('Pay Button', 'payxpert'),
              'type' => 'text',
              'description' => __('"Pay Button" text', 'payxpert'),
              'default' => __('Proceed to PayXpert', 'payxpert')
            ),
            'description' => array(
              'title' => __('Description', 'payxpert'),
              'type' => 'text',
              'desc_tip' => true,
              'description' => __('This controls the description the user sees during checkout.', 'payxpert'),
              'default' => __('Pay via PayXpert: you can pay with your credit / debit card', 'payxpert')
            ),
            'connect2_url' => array(
              'title' => __('Payment Page URL', 'payxpert'),
              'type' => 'text',
              'description' => __('Do not change this field unless you have been given a specific URL', 'payxpert')
            ),
            'api_url' => array(
              'title' => __('Payment Gateway URL (refund)', 'payxpert'),
              'type' => 'text',
              'description' => __('Do not change this field unless you have been given a specific URL', 'payxpert')
            ),
            'debug' => array(
              'title' => __('Debug Log', 'payxpert'),
              'type' => 'checkbox',
              'label' => __('Enable logging', 'payxpert'),
              'default' => 'no',
              'description' => __('Log PayXpert events, such as Callback', 'payxpert')
            ),
            'iframe_mode' => array(
              'title' => __('Iframe mode', 'payxpert'),
              'type' => 'checkbox',
              'label' => __('Enable iframe mode', 'payxpert'),
              'default' => 'no',
              'description' => __('Enables iframe mode (no redirection)', 'payxpert')
            )
        );

        return $fields;

    }

}