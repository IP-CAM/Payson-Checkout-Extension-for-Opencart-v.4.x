<?php

namespace Opencart\Catalog\Controller\Extension\OcPaymentPaysoncheckout\Onecheckout;

class ShippingMethod extends \Opencart\System\Engine\Controller {

    public $payson_one_page_checkout = true;

    public function index(): string {
        $this->load->language('checkout/shipping_method');
        $this->load->model('localisation/country');

        $data['language'] = $this->config->get('config_language');

        $country_info = $this->model_localisation_country->getCountry((int) $this->config->get('config_country_id'));

        $data['iso_code_2'] = $this->session->data['shipping_address']['iso_code_2'] = (isset($this->session->data['shipping_address']['iso_code_2']) AND $this->session->data['shipping_address']['iso_code_2'] != null) ? $this->session->data['shipping_address']['iso_code_2'] : $country_info['iso_code_2'];
        $shipping_address['postcode'] = $this->session->data['shipping_address']['postcode'] = '';
        $shipping_address['zone_id'] = isset($this->session->data['shipping_address']['zone_id']) ? $this->session->data['shipping_address']['zone_id'] : $this->config->get('config_zone_id');
        $shipping_address['country_id'] = isset($this->session->data['shipping_address']['country_id']) ? $this->session->data['shipping_address']['country_id'] : $this->config->get('config_country_id');

        // Shipping methods
        $this->load->model('checkout/shipping_method');

        $data['shipping_methods'] = $this->model_checkout_shipping_method->getMethods($shipping_address);

        if (isset($this->session->data['shipping_method'])) {
            $data['shipping_method'] = $this->session->data['shipping_method']['name'];
            $data['code'] = $this->session->data['shipping_method']['code'];
        } else {
            $data['shipping_method'] = '';
            $data['code'] = '';
        }

        // Store shipping methods in session
        $this->session->data['shipping_methods'] = $data['shipping_methods'];
        $data['shipping_title'] = $this->language->get('shipping_title');

        return $this->load->view('extension/oc_payment_paysoncheckout/onecheckout/shipping_method', $data);
    }

    public function quote(): void {
        $this->load->language('checkout/shipping_method');

        $json = [];

        // Validate cart has products and has stock.
        if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
            $json['redirect'] = $this->url->link('checkout/cart', 'language=' . $this->config->get('config_language'), true);  //XXX
        }

        // Validate minimum quantity requirements.
        $products = $this->cart->getProducts();

        foreach ($products as $product) {
            if (!$product['minimum']) {
                $json['redirect'] = $this->url->link('checkout/cart', 'language=' . $this->config->get('config_language'), true);

                break;
            }
        }

        if (!$this->payson_one_page_checkout && !$json) {
            // Validate if customer data is set
            if (!isset($this->session->data['customer'])) {
                $json['error'] = $this->language->get('error_customer');
            }

            // Validate if payment address is set if required in settings
            if ($this->config->get('config_checkout_payment_address') && !isset($this->session->data['payment_address'])) {  //XXXX
                $json['error'] = $this->language->get('error_payment_address');
            }

            // Validate if shipping not required. If not the customer should not have reached this page.
            if (!$this->cart->hasShipping() && !isset($this->session->data['shipping_address'])) {
                $json['error'] = $this->language->get('error_shipping_address');
            }
        }

        if (!$json) {
            $shipping_address['zone_id'] = isset($this->session->data['shipping_address']['zone_id']) ? $this->session->data['shipping_address']['zone_id'] : $this->config->get('config_zone_id');
            $shipping_address['country_id'] = isset($this->session->data['shipping_address']['country_id']) ? $this->session->data['shipping_address']['country_id'] : $this->config->get('config_country_id');

            // Shipping Methods
            $this->load->model('checkout/shipping_method');

            $shipping_methods = $this->model_checkout_shipping_method->getMethods($shipping_address);

            if ($shipping_methods) {
                $json['shipping_methods'] = $this->session->data['shipping_methods'] = $shipping_methods;
            } else {
                $json['error'] = sprintf($this->language->get('error_no_shipping'), $this->url->link('information/contact', 'language=' . $this->config->get('config_language')));
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function save(): void {
        //$this->document->addScript('/extension/oc_payment_paysoncheckout/catalog/view/javascript/jquery/onecheckout.js');
        $this->load->language('checkout/shipping_method');

        $json = [];

        // Validate cart has products and has stock.
        if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
            $json['redirect'] = $this->url->link('checkout/cart', 'language=' . $this->config->get('config_language'), true);
        }

        // Validate minimum quantity requirements.
        $products = $this->cart->getProducts();

        foreach ($products as $product) {
            if (!$product['minimum']) {
                $json['redirect'] = $this->url->link('checkout/cart', 'language=' . $this->config->get('config_language'), true);

                break;
            }
        }

        if (!$this->payson_one_page_checkout && !$json) {

            // Validate if customer is logged in or customer session data is not set
            if (!isset($this->session->data['customer'])) {
                //$json['redirect'] = $this->url->link('checkout/cart', 'language=' . $this->config->get('config_language'), true);
                $json['error'] = $this->language->get('error_customer');
            }

            // Validate if payment address is set if required in settings
            if ($this->config->get('config_checkout_payment_address') && !isset($this->session->data['payment_address'])) {  //XXX
                $json['error'] = $this->language->get('error_payment_address');
            }


            // Validate if shipping not required. If not the customer should not have reached this page.
            if (!$this->cart->hasShipping() || !isset($this->session->data['shipping_address'])) {   //XXX
                $json['error'] = $this->language->get('error_shipping_address');
            }
        }

        if (!$json) {
            if (isset($this->request->post['shipping_method'])) {
                $shipping = explode('.', $this->request->post['shipping_method']);

                if (!isset($shipping[0]) || !isset($shipping[1]) || !isset($this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]])) {
                    $json['error'] = $this->language->get('error_shipping_method');
                }
            } else {
                $json['error'] = $this->language->get('error_shipping_method');
            }
        }

        if (!$json) {

            $json['shipping_ext'] = $this->session->data['shipping_method'] = $this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]];
            $json['success'] = $this->language->get('text_success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

}
