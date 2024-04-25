<?php

namespace Opencart\Catalog\Controller\Extension\OcPaymentPaysoncheckout\Payment;

class Paysoncheckout extends \Opencart\System\Engine\Controller {

    private $one_page_checkout = 0;

    public function index() {
        $this->load->language('extension/oc_payment_paysoncheckout/payment/paysoncheckout');
        $this->one_page_checkout = $this->config->get('payment_paysoncheckout_one_page_checkout');

        $data['language'] = $this->config->get('config_language');

        if (!$this->one_page_checkout) {

            $this->load->model('extension/oc_payment_paysoncheckout/payment/paysoncheckout');
            $shipping_address_temp = [];
            $shipping_address_temp['zone_id'] = isset($this->session->data['shipping_address']['zone_id']) ? $this->session->data['shipping_address']['zone_id'] : $this->config->get('config_zone_id');
            $shipping_address_temp['country_id'] = isset($this->session->data['shipping_address']['country_id']) ? $this->session->data['shipping_address']['country_id'] : $this->config->get('config_country_id');
            // Shipping methods
            $this->load->model('checkout/shipping_method');
            $data['shipping_method'] = $this->model_checkout_shipping_method->getMethods($shipping_address_temp);

            $onecheckoutInfo = $this->load->controller('extension/oc_payment_paysoncheckout/onecheckout/onecheckout');    //testa
            $data['snippet'] = $onecheckoutInfo['snippet'];

            return $this->load->view('extension/oc_payment_paysoncheckout/payment/paysoncheckout', $data);
        }
    }

    public function confirm(): void {
        $this->load->language('extension/oc_payment_paysoncheckout/payment/paysoncheckout');

        $json = [];

        if (!isset($this->session->data['order_id'])) {
            $json['error'] = $this->language->get('error_order');
        }

        if (!isset($this->session->data['payment_method']) || $this->session->data['payment_method']['code'] != 'paysoncheckout.paysoncheckout') {
            $json['error'] = $this->language->get('error_payment_method');
        }

        if (!$json) {
            $this->load->model('checkout/order');

            $this->model_checkout_order->addHistory($this->session->data['order_id'], $this->config->get('payment_paysoncheckout_order_status_id'));

            $json['redirect'] = $this->url->link('checkout/success', 'language=' . $this->config->get('config_language'), true);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function viewCheckoutBefore($route, &$data) {
        $moduleStatus = $this->config->get('payment_paysoncheckout_status');
        $this->one_page_checkout = $this->config->get('payment_paysoncheckout_one_page_checkout');
        $this->load->model('extension/oc_payment_paysoncheckout/payment/paysoncheckout');

        $shipping_address = [
            'zone_id' => isset($this->session->data['shipping_address']['zone_id']) ? $this->session->data['shipping_address']['zone_id'] : $this->config->get('config_zone_id'),
            'country_id' => isset($this->session->data['shipping_address']['country_id']) ? $this->session->data['shipping_address']['country_id'] : $this->config->get('config_country_id')
        ];
        $is_one_page = $this->model_extension_oc_payment_paysoncheckout_payment_paysoncheckout->getMethods($shipping_address);

        $checkFlag = 0;
        if ($this->one_page_checkout AND $moduleStatus AND isset($is_one_page['status_one_page']) AND $is_one_page['status_one_page']) {
            $status = true;
        } elseif ($moduleStatus AND $checkFlag AND isset($is_one_page['status_one_page']) AND $is_one_page['status_one_page']) {
            $status = true;
        } else {
            $status = false;
        }

        if ($status) {
            $data['one_page'] = 1;

            $data['shipping_method'] = $this->load->controller('extension/oc_payment_paysoncheckout/onecheckout/shipping_method');
        }
    }

    public function viewCheckoutAfter($route, $data, &$output) {

        if (isset($data['one_page']) and $data['one_page']) {
            $onecheckoutInfo = $this->load->controller('extension/oc_payment_paysoncheckout/onecheckout/onecheckout');
            try {
                if (isset($onecheckoutInfo['snippet'])) {
                    $data['snippet'] = $onecheckoutInfo['snippet'];
                    $data['isShipping'] = ((isset($onecheckoutInfo['isShipping']) AND $onecheckoutInfo['isShipping'] == 1) ? $onecheckoutInfo['isShipping'] : 0);
                    $data['orderId'] = $onecheckoutInfo['order_id'];
                    $data['checkoutId'] = $onecheckoutInfo['checkoutId'];
                    $data['shipping_methods'] = $onecheckoutInfo['shipping_methods'];
                    $data['confirm'] = $this->load->controller('checkout/confirm');
                    $data['preferred_shipping_method'] = $this->language->get('text_preferred_shipping_method');
                } else {
                    throw new \Exception('No snippet found! ');
                }
            } catch (\Exception $e) {
                $this->log->write($e->getMessage() . '&#10;' . $e->getCode() . ' - viewCheckoutAfter - line:' . __LINE__);
            }

            $output = $this->load->view('extension/oc_payment_paysoncheckout/onecheckout/onecheckout', $data);
        }
    }

}
