<?php
/*
 * Copyright 2015-2024 PayXpert
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @author Regis Vidal
 */
if (!defined('ABSPATH')) {
  exit();
}
/**
 * PayXpert Standard Payment Gateway
 *
 * Provides a PayXpert Standard Payment Gateway.
 */


class WC_Gateway_PayXpert extends WC_Payment_Gateway {

  /** @var boolean Whether or not logging is enabled */
  public static $log_enabled = false;

  /** @var WC_Logger Logger instance */
  public static $log = false;

  // PayXpert Originator ID
  private $originator_id;
  // PayXpert password
  private $password;
  // PayXpert url to call the payment page
  private $connect2_url;
  // PayXpert url to process refund
  private $api_url;

  // Merchant notifications settings
  private $merchant_notifications;
  private $merchant_notifications_to;
  private $merchant_notifications_lang;

  // PayXpert API client
  private $apiClient;
  
  /**
   * Constructor for the gateway.
   */
  public function __construct() {
    
    // Setup general properties
    $this->setup_variables();

    // Setup hooks
    $this->setup_hooks(); 

  }

  private function setup_hooks() {

    add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

    /*if ($this->is_iframe_on()) {
      add_action('woocommerce_receipt_payxpert', array($this, 'receipt_page'));
    }*/

    if ($this->is_seamless_on()) {
      add_action('woocommerce_receipt_payxpert', array($this, 'receipt_page'));
    }

    if (!$this->is_valid_for_use()) {
      $this->enabled = 'no';
    } else {
      add_action('woocommerce_api_wc_gateway_payxpert', array($this, 'handle_callback'));
    }

  }

  private function setup_variables() {

    $this->id = 'payxpert';
    $this->has_fields = false;
    $this->method_title = __('PayXpert', 'payxpert');
    $this->method_description = '';
    $this->supports = array('products', 'refunds');

    // Load the settings.
    $this->init_form_fields();
    $this->init_settings();

    // Define user set variables
    $this->title = $this->get_option('title');
    $this->description = $this->get_option('description');
    $this->testmode = 'no';
    $this->order_button_text = $this->get_option('pay_button');
    $this->debug = 'yes' === $this->get_option('debug', 'no');
    $this->originator_id = $this->get_option('originator_id');
    $this->password = $this->get_option('password');
    $this->connect2_url = $this->get_option('connect2_url', 'https://connect2.payxpert.com');
    $this->connect2_url .= (substr($this->connect2_url, -1) == '/' ? '' : '/');
    $this->api_url = $this->get_option('api_url', 'https://api.payxpert.com');
    $this->api_url .= (substr($this->api_url, -1) == '/' ? '' : '/');
    $this->home_url = is_ssl() ? home_url('/', 'https') : home_url('/'); //set the urls (cancel or return) based on SSL
    $this->relay_response_url = add_query_arg('wc-api', 'WC_Gateway_PayXpert', $this->home_url);

    $this->merchant_notifications = $this->get_option('merchant_notifications');
    $this->merchant_notifications_to = $this->get_option('merchant_notifications_to');
    $this->merchant_notifications_lang = $this->get_option('merchant_notifications_lang');

    self::$log_enabled = $this->debug;

  }

  /**
   * Logging method
   *
   * @param string $message
   */
  public static function log($message) {
    if (self::$log_enabled) {
      if (empty(self::$log)) {
        self::$log = new WC_Logger();
      }
      self::$log->add('PayXpert', $message);
    }
  }

  /**
   * get_icon function.
   *
   * @return string
   */
  public function get_icon() {
    $icon_html = '';

    return apply_filters('woocommerce_gateway_icon', $icon_html, $this->id);
  }

  /**
   * Check if this gateway is enabled and available in the user's country
   *
   * @return bool
   */
  public function is_valid_for_use() {
    // We allow to use the gateway from any where
    return true;
  }

  /**
   * Check if iframe mode is on
   *
   * @return bool
   */
  public function is_iframe_on() {
    // We allow to use the gateway from any where
    if ($this->get_option('iframe_mode') == 'yes') {
      return true;
    }
    return false;
  }

  public function is_seamless_on()
  {
    // We allow to use the gateway from any where
    if ($this->get_option('seamless_checkout') == 'yes') {
      return true;
    }
    return false;
  }

  /**
   * Admin Panel Options
   *
   * @since 1.0.0
   */
  public function admin_options() {
    if ($this->is_valid_for_use()) {
      parent::admin_options();
    } else {
      include PAYX_ABSPATH.'/views/admin.error.php';
    }
  }

  /**
   * Initialize Gateway Settings Form Fields
   */
  public function init_form_fields() {

    $fields = PXSettings::get_admin_fields();
    $this->form_fields = $fields;

  }

  /**
   * Process the payment and return the result
   *
   * @param int $order_id
   * @return array
   */
  public function process_payment($order_id) {

    $order = new WC_Order($order_id);

    // init api
    $this->init_api_client();

    // customer informations
    $this->set_customer_info($order);

    // Shipping information
    if ('yes' == $this->get_option('send_shipping')) {
      $this->set_shipping_info($order);
    }

    // Order informations
    $this->set_order_info($order);

    // Totals
    $this->set_order_totals($order);

    // URLs
    $this->set_order_urls($order);

    // Merchant notifications
    $this->set_order_notifications($order);

    // prepare API
    if ($this->apiClient->prepareTransaction() == false) {
      $message = "can't prepare transaction - " . $this->apiClient->getClientErrorMessage();
      $this->log($message);
      return array('result' => 'fail', 'redirect' => '');
    }
    
    // Save the merchant token for callback verification
    update_post_meta($order_id, '_payxpert_merchant_token', $this->apiClient->getMerchantToken());
    update_post_meta($order_id, '_payxpert_customer_url', $this->apiClient->getCustomerRedirectURL());

    $url = $this->apiClient->getCustomerRedirectURL();

    if($this->is_iframe_on()) $url = $order->get_checkout_payment_url(true);

    return array('result' => 'success', 'redirect' => $url);
  }

  /**
   * Can the order be refunded via PayPal?
   *
   * @param WC_Order $order
   * @return bool
   */
  public function can_refund_order($order) {
    return $order && $order->get_transaction_id();
  }

  /**
   * Set order URLs
   *
   * @param WC_Order $order
   */
  private function set_order_urls($order) {

    $this->apiClient->setCtrlCallbackURL(WC()->api_request_url('WC_Gateway_PayXpert'));
    $this->apiClient->setCtrlRedirectURL($this->relay_response_url . '&order_id=' . $order->get_id());

  }

  /**
   * Set merchant notifications
   *
   * @param WC_Order $order
   */

  private function set_order_notifications($order) {

    if (isset($this->merchant_notifications) && $this->merchant_notifications != null) {
      if ($this->merchant_notifications == 'enabled') {
        $this->apiClient->setMerchantNotification(true);
        $this->apiClient->setMerchantNotificationTo($this->merchant_notifications_to);
        $this->apiClient->setMerchantNotificationLang($this->merchant_notifications_lang);
      } else if ($this->merchant_notifications == 'disabled') {
        $this->apiClient->setMerchantNotification(false);
      }
    }

  }  

  /**
   * Set order totals
   *
   * @param WC_Order $order
   */
  private function set_order_totals($order) {

    $total = number_format($order->order_total * 100, 0, '.', '');
    $this->apiClient->setAmount($total);
    $this->apiClient->setPaymentMode(Connect2PayClient::_PAYMENT_MODE_SINGLE);
    $this->apiClient->setPaymentType(Connect2PayClient::_PAYMENT_TYPE_CREDITCARD);

  }

  /**
   * Set customer information
   *
   * @param WC_Order $order
   */
  private function set_customer_info($order) {

    $this->apiClient->setShopperID(substr($order->get_customer_id(), 0, 64));
    $this->apiClient->setShopperFirstName(substr($order->get_billing_first_name(), 0, 35));
    $this->apiClient->setShopperLastName(substr($order->get_billing_last_name(), 0, 35));
    $this->apiClient->setShopperCompany(substr($order->get_billing_company(), 0, 128));
    $this->apiClient->setShopperEmail(substr($order->get_billing_email(), 0, 128));
    $this->apiClient->setShopperPhone(substr($order->get_billing_phone(), 0, 20));
    $this->apiClient->setShopperAddress(substr(trim($order->get_billing_address_1() . " " . $order->get_billing_address_2()), 0, 255));
    $this->apiClient->setShopperZipcode(substr($order->get_billing_postcode(), 0, 10));
    $this->apiClient->setShopperCity(substr($order->get_billing_city(), 0, 50));
    $this->apiClient->setShopperState(substr($order->get_billing_state(), 0, 30));
    $this->apiClient->setShopperCountryCode($order->get_billing_country());

  }

  private function set_shipping_info($order) {

      $this->apiClient->setShipToFirstName(substr($order->get_shipping_first_name(), 0, 35));
      $this->apiClient->setShipToLastName(substr($order->get_shipping_last_name(), 0, 35));
      $this->apiClient->setShipToCompany(substr($order->get_shipping_company(), 0, 128));

      $this->apiClient->setShipToPhone(substr(trim(), 0, 20));

      $this->apiClient->setShipToAddress(substr(trim($order->get_shipping_address_1() . " " . $order->get_shipping_address_2()), 0, 255));
      $this->apiClient->setShipToZipcode(substr($order->get_shipping_postcode(), 0, 10));
      $this->apiClient->setShipToCity(substr($order->get_shipping_city(), 0, 50));
      $this->apiClient->setShipToState(substr($order->get_shipping_state(), 0, 30));
      $this->apiClient->setShipToCountryCode($order->get_shipping_country());
      $this->apiClient->setShippingType(Connect2PayClient::_SHIPPING_TYPE_PHYSICAL);

  }

  private function set_order_info($order) {

    $this->apiClient->setOrderID(substr($order->get_id(), 0, 100));
    $this->apiClient->setOrderDescription(substr('Invoice:' . $order->get_id(), 0, 255));
    $this->apiClient->setCurrency($order->get_currency());

  }

  private function init_api_client() {

    // Init API
    include_once PAYX_ABSPATH.'/src/API/GatewayClient.php';

    $this->apiClient = new Connect2PayClient($this->connect2_url, $this->originator_id, $this->password);

  }

  /**
   * Process a refund if supported
   *
   * @param int $order_id
   * @param float $amount
   * @param string $reason
   * @return boolean True or false based on success, or a WP_Error object
   */
  public function process_refund($order_id, $amount = null, $reason = '') {

    $order = wc_get_order($order_id);

    if (!$this->can_refund_order($order)) {
      $this->log('Refund Failed: No transaction ID');
      return false;
    }

    $transactionId = $order->get_transaction_id();

    // init api
    $this->init_api_client();

    $transaction = $this->apiClient->newTransaction('Refund');
    if ($amount <= 0) {
      $amount = $order->order_total;
    }

    $total = number_format($amount * 100, 0, '.', '');
    $transaction->setReferralInformation($transactionId, $total);

    $response = $transaction->send();
    return $this->process_refund_response($response, $order, $amount);

  }

  /**
   * Process the refund response
   *
   * @param Connect2PayClientResponse $response
   * @return boolean
   */
  private function process_refund_response($response, $order, $amount) {

    if ('000' === $response->errorCode) {
      $this->log("Refund Successful: Transaction ID {$response->transactionID}");
      $order->add_order_note(sprintf(__('Refunded %s - Refund ID: %s', 'payxpert'), $amount, $response->transactionID));
      return true;
    } else {
      $this->log(
          "Refund Failed: Transaction ID {$response->transactionID}, Error {$response->errorCode} with message {$response->errorMessage}");
      return false;
    }

  }  

  /**
   * Complete order, add transaction ID and note
   *
   * @param WC_Order $order
   * @param string $txn_id
   * @param string $note
   */
  protected function payment_complete($order, $txn_id = '', $note = '') {
    $order->add_order_note($note);
    $order->payment_complete($txn_id);
  }

  /**
   * Check for PayXpert Callback Response
   */
  public function handle_callback() {

    // init api
    $this->init_api_client();

    if ($_POST["data"] != null) {

      $data = sanitize_text_field( $_POST["data"] );
      $order_id = sanitize_text_field( $_GET['order_id'] );
      $merchantToken = get_post_meta($order_id, '_payxpert_merchant_token', true);

      // Setup the client and decrypt the redirect Status
      if ($this->apiClient->handleRedirectStatus($data, $merchantToken)) {
        // Get the PaymentStatus object
        $status = $this->apiClient->getStatus();

        $errorCode = $status->getErrorCode();
        $merchantData = $status->getCtrlCustomData();
        $order = wc_get_order($order_id);

        // errorCode = 000 => payment is successful
        if ($errorCode == '000') {
          $transactionId = $status->getTransactionID();
          $message = "Successful transaction by customer redirection. Transaction Id: " . $transactionId;
          $this->payment_complete($order, $transactionId, $message, 'payxpert');
          $order->update_status('completed', $message);
          $this->log($message);
          $this->redirect_to($order->get_checkout_order_received_url());
        } else if ($errorCode == '-1'){
          $message = "Unsuccessful transaction, customer left payment flow. Retrieved data: " . print_r($data, true);
          $this->log($message);
          $this->redirect_to(wc_get_checkout_url());
          wc_add_notice(__('Payment not complete, please try again', 'payxpert'), 'notice');
        } else {
          wc_add_notice(__('Payment not complete: ' . $status->getErrorMessage(), 'payxpert'), 'error');
          $this->redirect_to(wc_get_checkout_url());
        }
      }
    } else {

      if ($this->apiClient->handleCallbackStatus()) {

        $status = $this->apiClient->getStatus();

        // get the Error code
        $errorCode = $status->getErrorCode();
        $errorMessage = $status->getErrorMessage();
        $transactionId = $status->getTransactionID();

        $order_id = $status->getOrderID();

        $order = wc_get_order($order_id);
        $merchantToken = $status->getMerchantToken();

        $amount = number_format($status->getAmount() / 100, 2, '.', '');

        $data = compact("errorCode", "errorMessage", "transactionId", "invoiceId", "amount");

        $payxpert_merchant_token = get_post_meta($order_id, '_payxpert_merchant_token', true);

        // Be sure we have the same merchant token
        if ($payxpert_merchant_token == $merchantToken) {
          // errorCode = 000 transaction is successfull
          if ($errorCode == '000') {

            $message = "Successful transaction Callback received with transaction Id: " . $transactionId;
            $this->payment_complete($order, $transactionId, $message, 'payxpert');
            $order->update_status('completed', $message);
            $this->log($message);
          } else {

            $message = "Unsuccessful transaction Callback received with the following information: " . print_r($data, true);
            $order->add_order_note($message);
            $this->log($message);
          }
        } else {
          // We do not update the status of the transaction, we just log the
          // message
          $message = "Error. Invalid token " . $merchantToken . " for order " . $order->id . " in callback from " . $_SERVER["REMOTE_ADDR"];
          $this->log($message);
        }

        // Send a response to mark this transaction as notified
        $response = array("status" => "OK", "message" => "Status recorded");
        header("Content-type: application/json");
        echo json_encode($response);
        exit();
      } else {

        $this->log("Error: Callback received an incorrect status from " . $_SERVER["REMOTE_ADDR"]);
        wp_die("PayXpert Callback Failed", "PayXpert", array('response' => 500));
      }
    }    
  }

  public function receipt_page($order_id)
  {

    // Get the customer URL
    $payxpert_customer_url = get_post_meta($order_id, '_payxpert_customer_url', true);

    // Get the customer token
    $customerToken = get_post_meta($order_id, '_payxpert_merchant_token', true);

    //display the form
    include PAYX_ABSPATH . '/views/payment.receipt.php';

  }

  public function redirect_to($redirect_url) {
      // Clean
      @ob_clean();

      // Header
      header('HTTP/1.1 200 OK');
      echo "<script>window.parent.location.href='" . $redirect_url . "';</script>";
      exit;

  }
  
}
