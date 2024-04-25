<?php

namespace Opencart\Catalog\Controller\Extension\OcPaymentPaysoncheckout\Onecheckout;

class Onecheckout extends \Opencart\System\Engine\Controller {

    private $test_mode;
    public $data = array();

    const MODULE_VERSION = '1.0.1.127';

    function __construct($registry) {
        parent::__construct($registry);
        $this->load->model('extension/oc_payment_paysoncheckout/paysoncheckout/order');
        $this->test_mode = ($this->config->get('payment_paysoncheckout_method_mode') == 0);
    }

    public function index() {
        $this->load->model('localisation/country');
        $this->load->model('checkout/cart');

        $country_info = $this->model_localisation_country->getCountry((int) $this->config->get('config_country_id'));

        $this->load->language('extension/oc_payment_paysoncheckout/payment/paysoncheckout');

        $data['error_checkout_id'] = $this->language->get('error_checkout_id');
        $data['info_checkout'] = $this->language->get('info_checkout');
        $data['customerIsLogged'] = $this->customer->isLogged() == 1 ? true : false;

        if ($this->config->get('payment_paysoncheckout_one_page_checkout')) {

            $data['country_code'] = $this->session->data['shipping_address']['iso_code_2'] = (isset($this->session->data['shipping_address']['iso_code_2']) AND $this->session->data['shipping_address']['iso_code_2'] != null) ? $this->session->data['shipping_address']['iso_code_2'] : $country_info['iso_code_2'];
            $this->data['zone_id'] = $data['shipping_zone_id'] = $this->session->data['shipping_address']['zone_id'] = (isset($this->session->data['shipping_address']['zone_id']) AND $this->session->data['shipping_address']['zone_id'] != null) ? $this->session->data['shipping_address']['zone_id'] : $this->config->get('config_zone_id');
            $this->data['country_id'] = $data['shipping_country_id'] = $this->session->data['shipping_address']['country_id'] = (isset($this->session->data['shipping_address']['country_id']) AND $this->session->data['shipping_address']['country_id'] != null) ? $this->session->data['shipping_address']['country_id'] : $this->config->get('config_country_id');

            unset($this->session->data['payment_address']);

            $data['language'] = $this->config->get('config_code');
            $data['affiliate_id'] = '';
            $data['commission'] = '';
            $data['forwarded_ip'] = '';

            $this->setupPurchaseDataOnePage($data);

            $shipping_address = [
                'zone_id' => $this->data['zone_id'],
                'country_id' => $this->data['country_id']
            ];

            $this->data['shipping_methods'] = $this->model_checkout_shipping_method->getMethods($shipping_address);

            $this->session->data['shipping_methods'] = $this->data['shipping_methods'];
            $this->data['header'] = $this->load->controller('common/header');
            $this->data['column_right'] = $this->load->controller('common/column_right');
            $this->data['content_top'] = $this->load->controller('common/content_top');
            $this->data['content_bottom'] = $this->load->controller('common/content_bottom');
            $this->data['footer'] = $this->load->controller('common/footer');
        } else {
            //The customer return from Payson with status 'readyToPay' or 'denied'
            if (isset($this->request->get['snippet']) and $this->request->get['snippet'] !== Null) {
                $this->load->model('checkout/order');
            } else {
                $this->setupPurchaseData();
            }
        }

        return $this->data;
    }

    public function getSnippet() {
        return $this->getSnippetUrl($this->data['snippet']);
    }

    public function getSnippetUrl($snippet) {
        $url = explode("url='", $snippet);
        $checkoutUrl = explode("'", $url[1]);
        return $checkoutUrl[0];
    }

    public function confirm() {
        if ($this->session->data['payment_method']['code'] == 'paysoncheckout') {
            $this->setupPurchaseDataOnePage();
        }
    }

    private function setupPurchaseDataOnePage($data) {
        $this->load->language('extension/oc_payment_paysoncheckout/payment/paysoncheckout');
        $this->load->model('checkout/order');
        $this->load->model('extension/oc_payment_paysoncheckout/paysoncheckout/order');
        $this->load->model('localisation/country');

        $country_info = $this->model_localisation_country->getCountry((int) $this->config->get('config_country_id'));

        $data['payment_method'] = 'Payson Checkout';

        if (isset($this->session->data['order_id']) and $this->session->data['order_id'] != null) {
            $order_id = $this->session->data['order_id'];

            $onecheckoutTotal = null;
            foreach ($this->getOnecheckoutTotals() as $key => $value) {
                if (isset($value['code']) AND $value['code'] == 'total') {
                    $onecheckoutTotal = $value['value'];
                }
                if (isset($value['code']) AND $value['code'] == 'shipping') {
                    $data['shipping_method'] = $value['title'];
                    $data['shipping_code'] = $value['code'];
                }
            }

            if ($this->cart->getTotal() == $onecheckoutTotal) {
                $data['total'] = $onecheckoutTotal;
                $this->db->query("DELETE FROM `" . DB_PREFIX . "order_product` WHERE `order_id` = '" . (int) $order_id . "'");
                $data['products'] = $this->model_checkout_cart->getProducts();
                $this->model_extension_oc_payment_paysoncheckout_paysoncheckout_order->editOrder($order_id, $data);
            } else {
                $data['total'] = $onecheckoutTotal;
                $data['products'] = $this->model_checkout_cart->getProducts();
                $data['totals'] = $this->getOnecheckoutTotals();
                $this->db->query("DELETE FROM `" . DB_PREFIX . "order_product` WHERE `order_id` = '" . (int) $order_id . "'");
                $this->model_extension_oc_payment_paysoncheckout_paysoncheckout_order->editOrder($order_id, $data);
            }
        } else {
            $data['total'] = $this->cart->getTotal();
            $data['products'] = $this->model_checkout_cart->getProducts();
            $data['totals'] = $this->getOnecheckoutTotals();

            $this->session->data['order_id'] = $order_id = $this->model_extension_oc_payment_paysoncheckout_paysoncheckout_order->addOrder($data);
        }
        $order_data = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        $this->data['store_name'] = html_entity_decode($order_data['store_name'], ENT_QUOTES, 'UTF-8');
        $this->data['payson_comment'] = html_entity_decode($order_data['comment'], ENT_QUOTES, 'UTF-8');
        // URL:s
        $this->merchantUri($order_id);

        // Order 
        $this->data['order_id'] = $order_data['order_id'];
        $this->data['amount'] = $this->currency->format($order_data['total'] * 100, $order_data['currency_code'], $order_data['currency_value'], false) / 100;
        $this->data['currency_code'] = $order_data['currency_code'];
        $this->data['language_code'] = $order_data['language_code'];

        // Customer info
        $this->data['sender_email'] = isset($order_data['customer_id']) ? $order_data['email'] : '';
        $this->data['sender_email'] = (int) $order_data['customer_id'] > 0 ? $order_data['email'] : '';
        $this->data['sender_first_name'] = $this->customer->isLogged() ? html_entity_decode($order_data['payment_firstname'], ENT_QUOTES, 'UTF-8') : (isset($this->session->data['payment_address']['firstname']) ? $this->session->data['payment_address']['firstname'] : '');
        $this->data['sender_last_name'] = $this->customer->isLogged() ? html_entity_decode($order_data['payment_lastname'], ENT_QUOTES, 'UTF-8') : (isset($this->session->data['payment_address']['lastname']) ? $this->session->data['payment_address']['lastname'] : '');
        $this->data['sender_telephone'] = html_entity_decode($order_data['telephone'], ENT_QUOTES, 'UTF-8');
        $this->data['sender_address'] = $this->customer->isLogged() ? html_entity_decode($order_data['payment_address_1'], ENT_QUOTES, 'UTF-8') : (isset($this->session->data['payment_address']['address_1']) ? $this->session->data['payment_address']['address_1'] : '');
        $this->data['sender_postcode'] = $this->customer->isLogged() ? html_entity_decode($order_data['payment_postcode'], ENT_QUOTES, 'UTF-8') : (isset($this->session->data['payment_address']['postcode']) ? $this->session->data['payment_address']['address_1'] : '');
        $this->data['sender_city'] = $this->customer->isLogged() ? html_entity_decode($order_data['payment_city'], ENT_QUOTES, 'UTF-8') : (isset($this->session->data['payment_address']['city']) ? $this->session->data['payment_address']['city'] : '');
        $this->data['sender_countrycode'] = $this->customer->isLogged() ? html_entity_decode($order_data['payment_iso_code_2'], ENT_QUOTES, 'UTF-8') : (isset($this->session->data['payment_address']['iso_code_2']) ? $this->session->data['payment_address']['iso_code_2'] : $country_info['iso_code_2']);

        $result = $this->getPaysoncheckout();

        $returnData = array();

        if ($result != NULL AND $result['status'] == ("created" || "readyToPay")) {
            $this->data['checkoutId'] = $result['id'];
            $this->data['test_mode'] = !$this->test_mode ? TRUE : FALSE;
            $this->data['snippet'] = $result['snippet'];
            $this->data['status'] = $result['status'];
        } else {
            $returnData["error"] = $this->language->get("text_payson_payment_error");
        }
    }

    private function setupPurchaseData() {
        $this->load->language('extension/oc_payment_paysoncheckout/payment/paysoncheckout');
        $this->load->model('checkout/order');

        $order_data = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        $order_id = $this->session->data['order_id'];

        $this->data['store_name'] = html_entity_decode($order_data['store_name'], ENT_QUOTES, 'UTF-8');
        $this->data['payson_comment'] = html_entity_decode($order_data['comment'], ENT_QUOTES, 'UTF-8');
        // URL:s
        $this->merchantUri($order_id);

        // Order 
        $this->data['order_id'] = $order_data['order_id'];
        $this->data['amount'] = $this->currency->format($order_data['total'] * 100, $order_data['currency_code'], $order_data['currency_value'], false) / 100;
        $this->data['currency_code'] = $order_data['currency_code'];
        $this->data['language_code'] = $order_data['language_code'];

        $this->data['sender_email'] = isset($order_data['customer_id']) ? $order_data['email'] : '';
        $this->data['sender_first_name'] = $this->customer->isLogged() ? html_entity_decode($order_data['payment_firstname'], ENT_QUOTES, 'UTF-8') : $this->session->data['payment_address']['firstname'];
        $this->data['sender_last_name'] = $this->customer->isLogged() ? html_entity_decode($order_data['payment_lastname'], ENT_QUOTES, 'UTF-8') : $this->session->data['payment_address']['lastname'];
        $this->data['sender_telephone'] = html_entity_decode($order_data['telephone'], ENT_QUOTES, 'UTF-8');
        $this->data['sender_address'] = $this->customer->isLogged() ? html_entity_decode($order_data['payment_address_1'], ENT_QUOTES, 'UTF-8') : $this->session->data['payment_address']['address_1'];
        $this->data['sender_postcode'] = $this->customer->isLogged() ? html_entity_decode($order_data['payment_postcode'], ENT_QUOTES, 'UTF-8') : $this->session->data['payment_address']['postcode'];
        $this->data['sender_city'] = $this->customer->isLogged() ? html_entity_decode($order_data['payment_city'], ENT_QUOTES, 'UTF-8') : $this->session->data['payment_address']['city'];
        $this->data['sender_countrycode'] = $this->customer->isLogged() ? html_entity_decode($order_data['payment_iso_code_2'], ENT_QUOTES, 'UTF-8') : $this->session->data['payment_address']['iso_code_2'];

        $result = $this->getPaysoncheckout();

        $returnData = array();

        if ($result != NULL AND $result['status'] == ("created" || "readyToPay")) {
            $this->data['checkoutId'] = $result['id'];
            $this->data['test_mode'] = !$this->test_mode ? TRUE : FALSE;
            $this->data['snippet'] = $result['snippet'];
            $this->data['status'] = $result['status'];
        } else {
            $returnData["error"] = $this->language->get("text_payson_payment_error");
        }
    }

    public function getOnecheckoutTotals() {
        $this->load->model('setting/extension');
        $this->load->model('checkout/cart');
        $totals = [];
        $taxes = $this->cart->getTaxes();
        $total = 0;

        ($this->model_checkout_cart->getTotals)($totals, $taxes, $total);

        return $totals;
    }

    public function getOpencartTotalIncludingTax() {
        $this->load->model('setting/extension');
        $this->load->model('checkout/cart');
        $totals = [];
        $taxes = $this->cart->getTaxes();
        $total = 0;

        ($this->model_checkout_cart->getTotals)($totals, $taxes, $total);

        return $total;
    }

    private function getPaysoncheckout() {
        require_once(DIR_EXTENSION . 'oc_payment_paysoncheckout/system/library/paysonpayments/include.php');
        $this->load->language('extension/oc_payment_paysoncheckout/payment/paysoncheckout');

        $checkoutClient = $this->getAPIInstanceMultiShop();

        $paysonMerchant = array(
            'termsUri' => $this->data['terms_url'],
            'checkoutUri' => $this->data['checkout_url'],
            'confirmationUri' => $this->data['ok_url'],
            'notificationUri' => $this->data['ipn_url'],
            'validationUri' => $this->data['validation_url'],
            'integrationInfo' => ('PCO:' . self::MODULE_VERSION . '|OC:' . VERSION),
            'reference' => $this->session->data['order_id'],
            'partnerId' => null,
        );

        $paysonOrder = array(
            'currency' => $this->currencypaysoncheckout(),
            'items' => $this->getOrderItems($this->session->data['order_id']),
        );

        $paysonGui = array(
            'colorScheme' => $this->config->get('payment_paysoncheckout_color_scheme'),
            'locale' => $this->languagepaysoncheckout(),
            'verification' => $this->config->get('payment_paysoncheckout_gui_verification'),
            'requestPhone' => (int) $this->config->get('config_telephone_display'),
            'phoneOptional' => (!((int) $this->config->get('config_telephone_required')) ? 1 : 0),
            'countries' => [$this->config->get('payment_paysoncheckout_countries')],
        );

        if (!$this->test_mode) {
            $paysonCustomer = array(
                'firstName' => $this->data['sender_first_name'],
                'lastName' => $this->data['sender_last_name'],
                'email' => $this->data['sender_email'],
                'phone' => $this->data['sender_telephone'],
                'identityNumber' => '',
                'city' => $this->data['sender_city'],
                'countryCode' => $this->data['sender_countrycode'],
                'postalCode' => $this->data['sender_postcode'],
                'street' => $this->data['sender_address']
            );
        } else {
            $paysonCustomer = array(
                'firstName' => 'Name',
                'lastName' => 'Last name',
                'email' => 'test@payson.se',
                'phone' => '11111111',
                'identityNumber' => '4605092222',
                'city' => 'Stockholm',
                'countryCode' => 'SE',
                'postalCode' => '99999',
                'street' => 'Test address'
            );
        }
        $checkoutData = array('merchant' => $paysonMerchant, 'order' => $paysonOrder, 'gui' => $paysonGui, 'customer' => $paysonCustomer);

        try {

            //The function that updates checkout
            if ($this->getCheckoutIdPayson($this->session->data['order_id']) != Null) {
                $checkoutTemp = $checkoutClient->get(array('id' => $this->getCheckoutIdPayson($this->session->data['order_id'])));

                if ($checkoutTemp['status'] == 'expired' || (!$this->canUpdate($checkoutTemp['status']))) {
                    $checkout = $checkoutClient->create($checkoutData);
                    $this->session->data['$checkout_id'] = $checkout['id'];
                } else {

                    $checkoutTemp['order']['items'] = $this->getOrderItems($this->session->data['order_id']);
                    $checkout = $checkoutClient->update($checkoutTemp);
                }
            } else {
                $checkout = $checkoutClient->create($checkoutData);
                $this->session->data['checkout_id'] = $checkout['id'];
            }

            if ($checkout['id'] != null) {
                $this->storePaymentResponseDatabase($checkout['id'], $checkout ['status'], $this->session->data['order_id']);
            }

            return $checkout;
        } catch (\Exception $e) {
            $this->log->write($e->getMessage() . '&#10;' . $e->getCode() . ' - getPaysoncheckout - line: ' . __LINE__ . ' Function: ' . __FUNCTION__);

            $this->response->redirect($this->url->link('checkout/checkout', 'language=' . $this->config->get('config_language')));
        }
    }

    //Returns from Payson after the transaction has ended.
    public function returnFromPayson() {

        $this->load->model('checkout/order');
        $this->load->language('extension/oc_payment_paysoncheckout/payment/paysoncheckout');

        $checkoutClient = $this->getAPIInstanceMultiShop();

        try {
            //Check if the checkoutid exist in the database.
            if (isset($this->request->get['order_id']) AND $this->request->get['order_id'] !== Null) {
                $orderId = $this->request->get['order_id'];
                $checkout = $checkoutClient->get(array('id' => $this->getCheckoutIdPayson($orderId)));

                //This row update database with info from the return object.
                $this->updatePaymentResponseDatabase($checkout, $this->getCheckoutIdPayson($orderId), 'returnCall');
                //Create the order order

                $this->handlePaymentDetails($checkout, $orderId, 'returnCall');
            } else {
                $this->response->redirect($this->url->link('checkout/checkout'));
            }
        } catch (\Exception $e) {
            $this->log->write($e->getMessage() . '&#10;' . $e->getCode() . ' - returnFromPayson - line: ' . __LINE__ . ' Function: ' . __FUNCTION__);
        }
    }

    public function validation() {
        $this->load->language('extension/oc_payment_paysoncheckout/payment/paysoncheckout');

        if((isset($this->request->get['order_id']) AND $this->request->get['order_id'] != Null) AND (isset($this->request->get['checkout']) AND $this->request->get['checkout'] != Null)){ 
            $order_id = $this->request->get['order_id'];
            $checkout = $this->request->get['checkout'];
        } else {
            $this->log->write('OrderID/checkout is missing by validation - line: ' . __LINE__ . ' Function: ' . __FUNCTION__); 
            var_dump(http_response_code(303));
            exit;
        }
        
        if (!$this->config->get('payment_paysoncheckout_out_of_stock')) {
            $this->addOrderInit($this->checkOrderId($order_id), $this->checkCheckoutId($checkout));
            var_dump(http_response_code(200));
            exit;
        } else {
            try {
                $this->compareProductQuantity($this->checkOrderId($order_id));
                $this->addOrderInit($this->checkOrderId($order_id), $this->checkCheckoutId($checkout));
                var_dump(http_response_code(200));
                exit;
            } catch (\Exception $e) {
                $this->log->write($e->getMessage() . '&#10;' . $e->getCode() . ' - validation - line: ' . __LINE__ . ' Function: ' . __FUNCTION__);
                var_dump(http_response_code(303));
                exit;
            }
        }
    }

    public function checkOrderId($order_id) {
        if (!isset($order_id) || $order_id == null) {
            http_response_code(303);
            exit;
        } else {
            return $order_id;
        }
    }

    public function checkCheckoutId($checkout_id) {
        if (!isset($checkout_id) || $checkout_id == null) {
            http_response_code(303);
            exit;
        } else {
            return $checkout_id;
        }
    }

    private function addOrderInit($order_id, $checkout_id) {
        $this->load->model('checkout/order');
        $this->model_checkout_order->addHistory($order_id, 1, 'CheckoutID: ' . $checkout_id, false);
    }

    public function compareProductQuantity($order_id) {
        $compare_product_quantity = $this->db->query("SELECT " . DB_PREFIX . "order_product.product_id as id, " . DB_PREFIX . "order_product.quantity as o_quantity, " . DB_PREFIX . "product.quantity as p_quantity FROM "
                . "" . DB_PREFIX . "order_product INNER JOIN " . DB_PREFIX . "product ON " . DB_PREFIX . "order_product.product_id = " . DB_PREFIX . "product.product_id WHERE " . DB_PREFIX . "order_product.order_id = '" . (int) $order_id . "'");

        foreach ($compare_product_quantity->rows as $product_quantity) {
            if ($product_quantity['p_quantity'] >= $product_quantity['o_quantity']) {
                var_dump(http_response_code(200));
            } else {
                $this->writeToLog("One or more products are not in stock before payment is made. ProductId: " . $product_quantity['id']);
                var_dump(http_response_code(303));
                exit;
            }
        }
    }

    function paysonIpn() {
        sleep(5);
        $this->load->model('checkout/order');
        $this->load->language('extension/oc_payment_paysoncheckout/payment/paysoncheckout');

        $checkoutClient = $this->getAPIInstanceMultiShop();
        try {
            //Check if the checkoutid exist in the database.
            if (isset($this->request->get['checkout'])) {
                $checkoutID = $this->request->get['checkout'];
                $checkout = $checkoutClient->get(array('id' => $checkoutID));
                //This row update database with info from the return object.
                $this->updatePaymentResponseDatabase($checkout, $checkoutID, 'ipnCall');
                //Create, canceled or dinaid the order.
                $this->handlePaymentDetails($checkout, $this->request->get['order_id'], 'ipnCall');
            }
        } catch (\Exception $e) {
            $this->log->write($e->getMessage() . '&#10;' . $e->getCode() . ' - paysonIpn - line: ' . __LINE__ . ' Function: ' . __FUNCTION__);
        }
    }

    /**
     * 
     * @param Checkout $checkout
     */
    private function handlePaymentDetails($checkout, $orderId = 0, $ReturnCallUrl = Null) {
        $this->load->language('extension/oc_payment_paysoncheckout/payment/paysoncheckout');
        $this->load->model('checkout/order');

        $orderIdTemp = $orderId ? $orderId : $this->session->data['order_id'];

        $paymentStatus = $checkout['status'];
        $paymentCheckoutId = $checkout['id'];

        $order_info = $this->model_checkout_order->getOrder($orderIdTemp);
        if (!$order_info) {
            return false;
        }

        $succesfullStatus = null;

        switch ($paymentStatus) {
            case "readyToShip":

                $succesfullStatus = $this->config->get('payment_paysoncheckout_order_status_id');
                $comment = "";

                $opencart_total_including_tax = $ReturnCallUrl == 'ipnCall' ? $this->getOrderTotalOC($orderIdTemp) : $this->getOpencartTotalIncludingTax();

                if (!$this->compareTotalPriceIncludingTax(round($checkout['order']['totalPriceIncludingTax']), $opencart_total_including_tax, $order_info)) {

                    $comment .= "OBS! THE PRICE DOES NO MATCH, PLEASE CHECK THE VALUE OF THE ORDER." . "\n\n";
                    $comment .= "Total Opencart: " . round($this->currency->format($opencart_total_including_tax * 100, $order_info['currency_code'], $order_info['currency_value'], false) / 100) . "\n";
                    $comment .= "PaysonTotalIncludingTax: " . $checkout['order']['totalPriceIncludingTax'] . "\n";

                    if ($this->config->get('payment_paysoncheckout_price_mismatch')) {
                        $succesfullStatus = 1;
                    }
                }

                if ($ReturnCallUrl == 'ipnCall') {
                    $comment .= "Paid Order: " . $orderIdTemp . "\n";
                    $comment .= 'Payson-ref:  ' . $checkout['purchaseId'] . "\n";
                    $comment .= "Checkout ID: " . $paymentCheckoutId . "\n";
                    $comment .= "Payson status: " . $paymentStatus . "\n";
                    $comment .= "CallUrl: " . $ReturnCallUrl . "\n";
                    $this->test_mode ? $comment .= "\n\nPayment mode: " . 'TEST MODE' : '';
                }

                $this->db->query("UPDATE `" . DB_PREFIX . "order` SET 
                    
                                firstname  = '" . $this->db->escape($checkout['customer']['firstName']) . "',
                                lastname  = '" . $this->db->escape($checkout['customer']['lastName']) . "',
                                telephone  = '" . (isset($checkout['customer']['phone']) ? $this->db->escape($checkout['customer']['phone']) : '') . "',
                                email               = '" . $this->db->escape($checkout['customer']['email']) . "',
                                    
                                payment_firstname  = '" . $this->db->escape($checkout['customer']['firstName']) . "',
                                payment_lastname   = '" . $this->db->escape($checkout['customer']['lastName']) . "',
                                payment_address_1  = '" . $this->db->escape($checkout['customer']['street']) . "',
                                payment_city       = '" . $this->db->escape($checkout['customer']['city']) . "', 
                                payment_country    = '" . $this->db->escape($checkout['customer']['countryCode']) . "',
                                payment_postcode   = '" . $this->db->escape($checkout['customer']['postalCode']) . "',
                                    
                                shipping_firstname  = '" . $this->db->escape($checkout['customer']['firstName']) . "',
                                shipping_lastname   = '" . $this->db->escape($checkout['customer']['lastName']) . "',
                                shipping_address_1  = '" . $this->db->escape($checkout['customer']['street']) . "',
                                shipping_city       = '" . $this->db->escape($checkout['customer']['city']) . "', 
                                shipping_country    = '" . $this->db->escape($checkout['customer']['countryCode']) . "', 
                                shipping_postcode   = '" . $this->db->escape($checkout['customer']['postalCode']) . "'
                                WHERE order_id      = '" . (int) $orderIdTemp . "'");

                if ($this->config->get('payment_paysoncheckout_logg') == 1) {
                    $this->writeArrayToLog($comment);
                    $this->writeArrayToLog($comment, 'line: ' . __LINE__ . ' Function: ' . __FUNCTION__ . 'Comment: ');
                }

                $this->model_checkout_order->addHistory($orderIdTemp, $succesfullStatus, $comment, true);

                $showReceiptPage = $this->config->get('payment_paysoncheckout_receipt');

                if ($showReceiptPage == 1) {
                    $this->response->redirect($this->url->link('extension/oc_payment_paysoncheckout/onecheckout/success', 'paysonStatus=readyToShip' . '&snippet=' . $this->getSnippetUrl($checkout['snippet']), true));
                } else {
                    $this->response->redirect($this->url->link('checkout/success'));
                }
                break;
            case "readyToPay":
                if ($checkout['id'] != Null) {
                    $this->response->redirect($this->url->link('extension/oc_payment_paysoncheckout/onecheckout/success', 'paysonStatus=readyToPay' . '&snippet=' . $this->getSnippetUrl($checkout['snippet']), true));
                }

                break;
            case "shipped":
                $succesfullStatus = $this->config->get('payment_paysoncheckout_order_status_id');
                $comment = "";

                if ($ReturnCallUrl == 'ipnCall') {
                    $comment = "Paid Order: " . $orderIdTemp . "\n";
                    $comment .= 'Payson-ref:  ' . $checkout['purchaseId'] . "\n";
                    $comment .= "Checkout ID: " . $paymentCheckoutId . "\n";
                    $comment .= "Payson status: " . $paymentStatus . "\n";
                    $comment .= "CallUrl: " . $ReturnCallUrl . "\n";
                    $this->test_mode ? $comment .= "\n\nPayment mode: " . 'TEST MODE' : '';
                }

                $this->paysonCommentsOrderHistory($orderIdTemp, $this->config->get('payment_paysoncheckout_order_status_shipped_id'), $comment, $ReturnCallUrl);
                break;
            case "paidToAccount":
                $this->updatePaymentResponseDatabase($checkout, $orderId, $ReturnCallUrl);
                $this->response->redirect($this->url->link('checkout/cart'));
                break;
            case "denied":
                $this->paysonApiError($this->language->get('text_denied'));
                $this->updatePaymentResponseDatabase($checkout, $orderId, $ReturnCallUrl);
                $this->response->redirect($this->url->link('checkout/cart'));
                break;
            case "canceled":
                $this->updatePaymentResponseDatabase($checkout, $orderId, $ReturnCallUrl);
                $this->response->redirect($this->url->link('checkout/cart'));
                break;
            case "Expired":
                $this->writeToLog('Order was Expired by payson.&#10;Checkout status:&#9;&#9;' . $paymentStatus . '&#10;Checkout id:&#9;&#9;&#9;&#9;' . $paymentCheckoutId, $checkout);
                break;
            default:
                $this->response->redirect($this->url->link('checkout/cart'));
        }
    }

    private function compareTotalPriceIncludingTax($pco_total_including_tax, $oc_total_including_tax, $order_info) {
        $totals_opencart = round($this->currency->format($oc_total_including_tax * 100, $order_info['currency_code'], $order_info['currency_value'], false) / 100);

        if (($totals_opencart + 1 < $pco_total_including_tax) || ($totals_opencart - 1 > $pco_total_including_tax)) {
            return 0;
        }
        return 1;
    }

    private function getCredentials() {
        $storesInShop = $this->db->query("SELECT store_id FROM `" . DB_PREFIX . "store`");

        $numberOfStores = $storesInShop->rows;

        $keys = array_keys($numberOfStores);
        //Since the store table do not contain the fist storeID this must be entered manualy in the $shopArray below
        $shopArray = array(0 => 0);
        for ($i = 0; $i < count($numberOfStores); $i++) {
            foreach ($numberOfStores[$keys[$i]] as $value) {
                array_push($shopArray, $value);
            }
        }
        return $shopArray;
    }

    private function getAPIInstanceMultiShop() {
        require_once(DIR_EXTENSION . 'oc_payment_paysoncheckout/system/library/paysonpayments/include.php');

        $apiUrl = !$this->test_mode ? \Payson\Payments\Transport\Connector::PROD_BASE_URL : \Payson\Payments\Transport\Connector::TEST_BASE_URL;

        $merchant = explode('##', $this->config->get('payment_paysoncheckout_merchant_id'));
        $key = explode('##', $this->config->get('payment_paysoncheckout_api_key'));

        $storeID = $this->config->get('config_store_id');
        $shopArray = $this->getCredentials();
        $multiStore = array_search($storeID, $shopArray);
        $agentId = $merchant[$multiStore];
        $apiKey = $key[$multiStore];

        try {
            // Init the connector
            $connector = \Payson\Payments\Transport\Connector::init($agentId, $apiKey, $apiUrl);
            // Create the client
            $checkoutClient = new \Payson\Payments\CheckoutClient($connector);

            return $checkoutClient;
        } catch (\Exception $e) {
            $this->log->write($e->getMessage() . '&#10;' . $e->getCode() . ' - getAPIInstanceMultiShop - line: ' . __LINE__ . ' Function: ' . __FUNCTION__);
        }
    }

    private function getOrderItems($order_id) {
        $orderitemslist = array();
        $this->load->language('extension/oc_payment_paysoncheckout/payment/paysoncheckout');
        $this->load->model('checkout/order');
        $orderId = $order_id;

        $order_data = $this->model_checkout_order->getOrder($order_id);

        $query = "SELECT `product_id`, `name`, `model`, `price`, `quantity`, `tax` / `price` as 'tax_rate' FROM `" . DB_PREFIX . "order_product` WHERE `order_id` = " . (int) $orderId . " UNION ALL SELECT 0, '" . $this->db->escape($this->language->get('text_gift_card')) . "', `code`, `amount`, '1', 0.00 FROM `" . DB_PREFIX . "order_voucher` WHERE `order_id` = " . (int) $orderId;
        $product_query = $this->db->query($query)->rows;

        foreach ($this->cart->getProducts() as $product) {
            $optionsArray = array();

            foreach ($product['option'] as $option) {
                $optionsArray[] = $option['name'] . ': ' . $option['value'];
            }

            $tax_rate_product = '';
            foreach ($product_query as $product1) {
                if ($product['product_id'] == $product1['product_id']) {
                    $tax_rate_product = $product1['tax_rate'];
                }
            }

            $productTitle = $product['name'];

            if (!empty($optionsArray))
                $productTitle .= ' | ' . join('; ', $optionsArray);

            $productTitle = (strlen($productTitle) > 80 ? substr($productTitle, 0, strpos($productTitle, ' ', 80)) : $productTitle);
            $product_price = $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('total_tax_status')), $this->session->data['currency'], 0, false);

            $orderitemslist[] = array(
                'name' => html_entity_decode($productTitle, ENT_QUOTES, 'UTF-8'),
                'unitPrice' => $product_price,
                'quantity' => $product['quantity'],
                'taxrate' => $tax_rate_product,
                'reference' => $product['model']
            );
        }

        //Vouchers as a product (Checkout)
        if (!empty($this->session->data['vouchers'])) {
            foreach ($this->session->data['vouchers'] as $key => $voucher) {
                $voucherDescription = (strlen($voucher['description']) > 80 ? substr($voucher['description'], 0, strpos($voucher['description'], ' ', 80)) : $voucher['description']);
                $orderitemslist[] = array(
                    'name' => 'Voucher',
                    'unitPrice' => $this->currency->format($voucher['amount'], $this->session->data['currency'], '', false),
                    'quantity' => 1,
                    'taxrate' => 0.0,
                    'reference' => html_entity_decode($voucherDescription, ENT_QUOTES, 'UTF-8')
                );
            }
        }


        $orderTotals = $this->getOrderTotals();

        if (!empty($orderTotals)) {
            foreach ($orderTotals as $orderTotal) {
                $orderTotalType = 'SERVICE';

                $orderTotalAmountTemp = 0;
                if (!(int) $this->config->get('total_tax_status')) {
                    $orderTotalAmountTemp = $orderTotal['value'];
                } elseif ((int) $orderTotal['sort_order'] >= (int) $this->config->get('total_tax_sort_order')) {
                    echo 'nn';
                    $orderTotalAmountTemp = $orderTotal['value'];
                } else {

                    $orderTotalAmountTemp = $orderTotal['value'] * (1 + ($orderTotal['lpa_tax'] > 0 ? $orderTotal['lpa_tax'] / 100 : 0));
                }

                if ($orderTotal['code'] == 'shipping') {
                    $this->data['isShipping'] = $this->session->data['isShipping'] = 1;
                }

                $orderTotalAmount = $this->currency->format($orderTotalAmountTemp, $this->session->data['currency'], 0, false);

                if ($orderTotalAmount == null || $orderTotalAmount == 0) {
                    continue;
                }

                if ($orderTotal['code'] == 'coupon') {
                    $orderTotalType = 'DISCOUNT';
                }

                if ($orderTotal['code'] == 'voucher') {
                    $orderTotalType = 'DISCOUNT';
                }

                if ($orderTotal['code'] == 'shipping') {
                    $orderTotalType = 'SERVICE';
                    $this->data['isShipping'] = $this->session->data['isShipping'] = 1;
                }

                if ($orderTotalAmount < 0) {
                    $orderTotalType = 'DISCOUNT';
                }

                $orderitemslist[] = array(
                    'name' => html_entity_decode($orderTotal['title'], ENT_QUOTES, 'UTF-8'),
                    'unitPrice' => $orderTotalAmount,
                    'quantity' => 1,
                    'taxrate' => ($orderTotal['lpa_tax']) / 100,
                    'reference' => $orderTotal['code'],
                    'type' => $orderTotalType
                );
            }
        }

        if ($this->config->get('payment_paysoncheckout_logg') == 1) {
            $this->writeArrayToLog($orderitemslist, 'line: ' . __LINE__ . ' Function: ' . __FUNCTION__ . 'Items list: ');
        }

        return $orderitemslist;
    }

    private function getOrderTotals() {
        $this->load->model('setting/extension');

        $totals = [];
        $taxes = $this->cart->getTaxes();
        $total = 0;
        $this->load->model('checkout/cart');
        // Because __call can not keep var references so we put them into an array.
        $total_data = array(
            'totals' => &$totals,
            'taxes' => &$taxes,
            'total' => &$total
        );

        $old_taxes = $taxes;

        $lpa_tax = array();

        $sort_order = array();

        $results = $this->model_setting_extension->getExtensionsByType('total');

        foreach ($results as $key => $value) {
            if (isset($value['code'])) {
                $code = $value['code'];
            } else {
                $code = $value['key'];
            }

            $sort_order[$key] = $this->config->get('total_' . $code . '_sort_order');
        }

        array_multisort($sort_order, SORT_ASC, $results);

        foreach ($results as $result) {
            if (isset($result['code'])) {
                $code = $result['code'];
            } else {
                $code = $result['key'];
            }

            if ($this->config->get('total_' . $result['code'] . '_status')) {
                $this->load->model('extension/' . $result['extension'] . '/total/' . $result['code']);
                //__call magic method cannot pass-by-reference so we get PHP to call it as an anonymous function.
                ($this->{'model_extension_' . $result['extension'] . '_total_' . $result['code']}->getTotal)($totals, $taxes, $total);

                $tax_difference = 0;
                foreach ($taxes as $tax_id => $value) {

                    if (isset($old_taxes[$tax_id])) {
                        $tax_difference += $value - $old_taxes[$tax_id];
                    } else {
                        $tax_difference += $value;
                    }
                }

                if ($tax_difference != 0) {
                    $lpa_tax[$code] = $tax_difference;
                }

                $old_taxes = $taxes;
            }
        }

        $ignoredTotals = $this->config->get('payment_paysoncheckout_ignore_order_totals');

        if ($ignoredTotals == null)
            $ignoredTotals = 'sub_total, total, tax';

        $ignoredOrderTotals = array_map('trim', explode(',', $ignoredTotals));

        foreach ($totals as $key => $orderTotal) {
            if (in_array($orderTotal['code'], $ignoredOrderTotals)) {
                unset($totals[$key]);
            }
        }

        $sort_order = array();

        foreach ($totals as $key => $value) {
            $sort_order[$key] = $value['sort_order'];

            if (isset($lpa_tax[$value['code']]) and $value['value'] != 0) {
                $total_data['totals'][$key]['lpa_tax'] = abs($lpa_tax[$value['code']] / $value['value'] * 100);
            } else {
                $total_data['totals'][$key]['lpa_tax'] = 0;
            }
        }

        return $totals;
    }

    /**
     * @param $checkout
     * @param checkout_id int $id
     */
    private function updatePaymentResponseDatabase($checkout, $id, $call = 'returnCall') {
        $this->db->query("UPDATE `" . DB_PREFIX . "payson_embedded_order` SET 
                        payment_status  = '" . $this->db->escape($checkout['status']) . "',
                        updated                       = NOW(), 
                        sender_email                  = 'sender_email', 
                        currency_code                 = 'currency_code',
                        tracking_id                   = 'tracking_id',
                        type                          = 'type',
                        shippingAddress_name          = '" . $this->db->escape($checkout['customer']['firstName']) . "', 
                        shippingAddress_lastname      = '" . $this->db->escape($checkout['customer']['lastName']) . "', 
                        shippingAddress_street_ddress = '" . $this->db->escape(str_replace(array('\'', '"', ',', ';', '<', '>', '&'), ' ', $checkout['customer']['street'])) . "',
                        shippingAddress_postal_code   = '" . $this->db->escape($checkout['customer']['postalCode']) . "',
                        shippingAddress_city          = '" . $this->db->escape($checkout['customer']['city']) . "', 
                        shippingAddress_country       = '" . $this->db->escape($checkout['customer']['countryCode']) . "'
                        WHERE  checkout_id            = '" . $this->db->escape($id) . "'"
        );
    }

    private function storePaymentResponseDatabase($checkoutId, $checkoutStatus, $orderId) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "payson_embedded_order SET 
                        payson_embedded_id  = '',
                        order_id            = '" . (int) $orderId . "', 
                        checkout_id         = '" . $this->db->escape($checkoutId) . "', 
                        purchase_id         = '" . $this->db->escape($checkoutId) . "',
                        payment_status      = '" . $this->db->escape($checkoutStatus) . "',
                        added               = NOW(), 
                        updated             = NOW()"
        );
    }

    private function getCheckoutIdPayson($order_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "payson_embedded_order` WHERE order_id = '" . (int) $order_id . "' ORDER BY `added` DESC");
        if ($query->num_rows && $query->row['checkout_id']) {
            if ($query->row['payment_status'] == ('created' || 'readyToPay')) {

                return $query->row['checkout_id'];
            } else {
                return null;
            }
        }
    }

    private function getPaysonEmbeddedOrder($order_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "payson_embedded_order` WHERE order_id = '" . (int) $order_id . "' ORDER BY `added` DESC");
        if ($query->num_rows) {
            return $query->row;
        } else {
            return null;
        }
    }

    public function languagepaysoncheckout() {
        $language = explode("-", $this->data['language_code']);
        switch (strtoupper($language[0])) {
            case "SE":
            case "SV":
                return "SV";
            case "FI":
                return "FI";
            case "DA":
            case "DK":
                return "DA";
            case "NB":
            case "NO":
                return "NO";
            case "CA":
            case "GL":
            case "ES":
                return "ES";
            case "DE":
                return "DE";
            default:
                return "EN";
        }
    }

    public function currencypaysoncheckout() {
        switch (strtoupper($this->data['currency_code'])) {
            case "SEK":
                return "SEK";
            default:
                return "EUR";
        }
    }

    public function canUpdate($checkoutStatus) {
        switch ($checkoutStatus) {
            case 'created':
                return true;
            case 'readyToPay':
                return true;
            case 'processingPayment':
                return true;
            case 'readyToShip':
                return false;
            case 'formsFiled':
                return false;
            default:
                return false;
        }
        return false;
    }

    /**
     * 
     * @param string $message
     * @param PaymentResponsObject $paymentResponsObject
     */
    function writeToLog($message, $paymentResponsObject = False) {
        $paymentDetailsFormat = "Payson reference:&#9;%s&#10;Correlation id:&#9;%s&#10;";
        if ($this->config->get('payment_paysoncheckout_logg') == 1) {
            $this->log->write('PAYSON CHECKOUT 2.0&#10;' . $message . '&#10;' . ($paymentResponsObject != false ? sprintf($paymentDetailsFormat, $paymentResponsObject->status, $paymentResponsObject->id) : '') . $this->writeModuleInfoToLog());
        }
    }

    private function writeArrayToLog($array, $additionalInfo = "") {
        if ($this->config->get('payment_paysoncheckout_logg') == 1) {
            $this->log->write('PAYSON CHECKOUT 2.0&#10;Additional information:&#9;' . $additionalInfo . '&#10;&#10;' . print_r($array, true) . '&#10;' . $this->writeModuleInfoToLog());
        }
    }

    private function writeModuleInfoToLog() {
        return 'Module version: ' . $this->config->get('payment_paysoncheckout_modul_version') . '&#10;------------------------------------------------------------------------&#10;';
    }

    private function writeTextToLog($additionalInfo = "") {
        $module_version = 'Module version: ' . $this->config->get('payment_paysoncheckout_modul_version') . '&#10;------------------------------------------------------------------------&#10;';
        $this->log->write('PAYSON CHECKOUT 2.0' . $additionalInfo . '&#10;&#10;' . $module_version);
    }

    /* public function paysonComments() {
      $this->load->model('checkout/order');
      if (isset($this->request->get['payson_comments']) && !empty($this->request->get['payson_comments'])) {
      $p_comments = $this->request->get['payson_comments'];
      if (is_string($p_comments)) {
      $this->session->data['comment'] = $p_comments;
      $this->db->query("UPDATE `" . DB_PREFIX . "order` SET
      comment  = '" . $this->db->escape(nl2br($p_comments)) . "'
      WHERE order_id      = '" . (int) $this->session->data['order_id'] . "'");
      }
      }
      } */

    public function paysonCommentsOrderHistory($order_id, $order_status, $p_comments, $ReturnCallUrl = null) {
        $this->load->model('checkout/order');

        $this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET 
                        order_history_id  = '',
                        order_id            = '" . (int) $order_id . "', 
                        order_status_id         = '" . (int) $order_status . "', 
                        notify         = '" . 0 . "',
                        comment      = '" . $this->db->escape(nl2br($p_comments)) . "',
                        date_added             = NOW()"
        );

        if ($ReturnCallUrl == 'ipnCall') {
            $this->db->query("UPDATE `" . DB_PREFIX . "order` SET `order_status_id` = '" . (int) $order_status . "' WHERE `order_id` = '" . (int) $order_id . "'");
        }
    }

    public function paysonApiError($error) {
        $this->load->language('extension/oc_payment_paysoncheckout/payment/paysoncheckout');
        $error_code = '<html>
                            <head>
                                <script type="text/javascript"> 
                                    alert("' . $error . $this->language->get('text_payson_payment_method') . '");
                                    window.location="' . (HTTPS_SERVER . 'index.php?route=checkout/cart') . '";
                                </script>
                            </head>
                    </html>';
        echo ($error_code);
        exit;
    }

    public function notifyStatusToPayson($route, &$data) {
        $getCheckoutObject = $this->getPaysonEmbeddedOrder($data[0]);

        if (isset($getCheckoutObject['checkout_id']) && ($getCheckoutObject['payment_status'] == 'readyToShip' || $getCheckoutObject['payment_status'] == 'shipped' || $getCheckoutObject['payment_status'] == 'paidToAccount')) {
            try {
                $checkoutClient = $checkoutClient = $this->getAPIInstanceMultiShop();
                $checkout = $checkoutClient->get(array('id' => $getCheckoutObject['checkout_id']));

                if ($data[1] == $this->config->get('payment_paysoncheckout_order_status_shipped_id') AND $data[1] == 3) {
                    $checkout['status'] = 'shipped';
                    $checkout = $checkoutClient->update($checkout);
                } elseif ($data[1] == $this->config->get('payment_paysoncheckout_order_status_canceled_id') AND $data[1] == 7) {
                    $checkout['status'] = 'canceled';
                    $checkout = $checkoutClient->update($checkout);
                } elseif ($data[1] == $this->config->get('payment_paysoncheckout_order_status_refunded_id') AND $data[1] == 11) {
                    if ($checkout['status'] == 'readyToShip' || $checkout['status'] == 'shipped' || $checkout['status'] == 'paidToAccount') {
                        if ($checkout['status'] == 'readyToShip') {
                            $checkout['status'] = 'shipped';
                            $checkout = $checkoutClient->update($checkout);
                        }

                        foreach ($checkout['order']['items'] as &$item) {
                            $item['creditedAmount'] = ($item['totalPriceIncludingTax']);
                        }
                        unset($item);
                        $checkout = $checkoutClient->update($checkout);
                    }
                } else {
                    // Do nothing
                }

                $comment = "Paid Order:  " . $data[0] . "\n";
                $comment .= 'Payson-ref:  ' . $checkout['purchaseId'] . "\n";
                $comment .= "Checkout ID:  " . $checkout['id'] . "\n";
                $comment .= "Notification is sent and the order has been:  " . $checkout['status'] . "\n";

                $this->paysonCommentsOrderHistory($data[0], $data[1], $comment);
            } catch (\Exception $e) {

                $this->paysonCommentsOrderHistory($data[0], $data[1], $e->getMessage());
                $this->log->write($e->getMessage() . '&#10;' . $e->getCode() . ' - notifyStatusToPayson - line: ' . __LINE__ . ' Function: ' . __FUNCTION__);
            }
        } else {
            //Do nothing
        }
    }

    public function editPaysonEmbeddedAddress() {
        require_once(DIR_EXTENSION . 'oc_payment_paysoncheckout/system/library/paysonpayments/include.php');
        $this->load->language('extension/oc_payment_paysoncheckout/payment/paysoncheckout');

        $is_payson_embedded_address_changed = false;
        $shipping_address = json_decode($_GET['shipping_address'], true);

        $countryCode = '';
        if (isset($shipping_address)) {
            $countryCode = $shipping_address['CountryCode'];
        } else {
            $countryCode = $this->config->get('config_country_id');
        }

        $json = [];

        if (isset($this->session->data['shipping_address']['iso_code_2']) AND (strtoupper($this->session->data['shipping_address']['iso_code_2']) != strtoupper($countryCode))) {
            $is_payson_embedded_address_changed = true;
            $this->load->model('localisation/country');
            $country_info = $this->model_localisation_country->getCountryByIsoCode2($countryCode);

            $this->load->model('localisation/zone');
            $zone_info = $this->model_localisation_zone->getZonesByCountryId($country_info[0]['country_id']);

            $this->editShippingAddress($zone_info[0]['zone_id'], $countryCode, $country_info[0]['country_id'], $shipping_address);
        } else {
            $json['redirect'] = null;
        }
        $json['is_payson_embedded_address_changed'] = $is_payson_embedded_address_changed;
        $json['success'] = $this->language->get('text_success');
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function editOrderItems() {
        //$this->document->addScript('/extension/oc_payment_paysoncheckout/catalog/view/javascript/jquery/onecheckout.js');
        require_once(DIR_EXTENSION . 'oc_payment_paysoncheckout/system/library/paysonpayments/include.php');
        $this->load->language('extension/oc_payment_paysoncheckout/payment/paysoncheckout');

        //$test_nada = false;

        $checkout_id = $this->session->data['checkout_id'];
        $order_id = $this->session->data['order_id'];

        try {
            $checkoutClient = $this->getAPIInstanceMultiShop();
            $currentCheckout = $checkoutClient->get(array('id' => $checkout_id));
            $json = [];
            if ((!$this->canUpdate($currentCheckout['status']))) {
                $json['success'] = $this->language->get('text_success');
            }
            if (isset($this->session->data['shipping_address']['iso_code_2']) and $this->session->data['shipping_address']['iso_code_2'] != $currentCheckout['customer']['countryCode']) {
                $countryCode = $currentCheckout['customer']['countryCode'];
                $this->load->model('localisation/country');
                $country_info = $this->model_localisation_country->getCountryByIsoCode2($countryCode);

                $this->load->model('localisation/zone');
                $zone_info = $this->model_localisation_zone->getZonesByCountryId($country_info[0]['country_id']);

                $this->editShippingAddress($zone_info[0]['zone_id'], $countryCode, $country_info[0]['country_id'], $currentCheckout['customer']);
            }

            $one_checkout_totals = $this->getOnecheckoutTotals();

            foreach ($one_checkout_totals as $key => $value) {
                if (isset($value['code'])) {
                    $data['payment_method'] = '{"code":"paysoncheckout.paysoncheckout","name":"Payson Checkout"}';
                    if ($value['code'] == 'shipping') {
                        $data['shipping_method'] = $this->session->data['shipping_method'];
                    }

                    if ($value['code'] == 'total') {
                        $this->db->query("DELETE FROM `" . DB_PREFIX . "order_total` WHERE `order_id` = '" . (int) $order_id . "'");
                        if (isset($one_checkout_totals)) {
                            foreach ($one_checkout_totals as $total) {
                                $this->model_extension_oc_payment_paysoncheckout_paysoncheckout_order->editOrderProduct($order_id);
                                // Totals
                                $this->db->query("INSERT INTO `" . DB_PREFIX . "order_total` SET `order_id` = '" . (int) $order_id . "', `extension` = '" . $this->db->escape($total['extension']) . "', `code` = '" . $this->db->escape($total['code']) . "', `title` = '" . $this->db->escape($total['title']) . "', `value` = '" . (float) $total['value'] . "', `sort_order` = '" . (int) $total['sort_order'] . "'");
                            }
                        }

                        $data['total'] = $value['value'];
                        $this->model_extension_oc_payment_paysoncheckout_paysoncheckout_order->updateTotal($order_id, $data);                      // Kontrollera här
                    }
                }
            }
            if (!$json) {
                $json['redirect'] = $this->url->link('checkout/checkout', 'language=' . $this->config->get('config_language'), true);
                $currentCheckout['order']['items'] = $this->getOrderItems($order_id);
                $updatedCheckout = $checkoutClient->update($currentCheckout);
                $json['success'] = $this->language->get('text_success');
                $json['snippet'] = $updatedCheckout['snippet'];
                $json['checkout_id'] = $checkout_id;
            }

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
        } catch (\Exception $e) {
            $this->log->write($e->getMessage() . '&#10;' . $e->getCode() . ' - editPaysonEmbeddedAddress - line: ' . __LINE__ . ' Function: ' . __FUNCTION__);
        }
    }

    private function merchantUri($order_id) {
        $this->data['ok_url'] = $this->url->link('extension/oc_payment_paysoncheckout/onecheckout/onecheckout|returnFromPayson', 'language=' . $this->config->get('config_language') . '&order_id=' . $order_id, true);
        $this->data['ipn_url'] = $this->url->link('extension/oc_payment_paysoncheckout/onecheckout/onecheckout|paysonIpn', 'language=' . $this->config->get('config_language') . '&order_id=' . $order_id, true);
        $this->data['checkout_url'] = $this->url->link('extension/oc_payment_paysoncheckout/onecheckout/onecheckout|returnFromPayson', 'language=' . $this->config->get('config_language') . '&order_id=' . $order_id, true);
        $this->data['terms_url'] = $this->url->link('information/information', 'language=' . $this->config->get('config_language') . '&information_id=' . (int) $this->config->get('payment_paysoncheckout_terms_and_conditions'), true);
        $this->data['validation_url'] = $this->url->link('extension/oc_payment_paysoncheckout/onecheckout/onecheckout|validation', 'language=' . $this->config->get('config_language') . '&order_id=' . $order_id, true);
    }

    private function editShippingAddress($zone_id, $country_code, $country_id, $customer_shipping_address) {
        $this->session->data['shipping_address']['zone_id'] = $zone_id;
        $this->session->data['shipping_address']['firstname'] = $customer_shipping_address['FirstName'];
        $this->session->data['shipping_address']['lastname'] = $customer_shipping_address['LastName'];
        $this->session->data['shipping_address']['address_1'] = $customer_shipping_address['Street'];
        $this->session->data['shipping_address']['city'] = $customer_shipping_address['City'];
        $this->session->data['shipping_address']['postcode'] = $customer_shipping_address['PostalCode'];
        $this->session->data['shipping_address']['country'] = '';
        $this->session->data['shipping_address']['country_id'] = $country_id;
        $this->session->data['shipping_address']['iso_code_2'] = $country_code;
    }

    public function getOrderTotalOC(int $order_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_total` WHERE `order_id` = '" . (int) $order_id . "' AND `code` like 'total'");

        if ($query->num_rows) {
            return $query->row['value'];
        } else {
            return null;
        }
    }

}
