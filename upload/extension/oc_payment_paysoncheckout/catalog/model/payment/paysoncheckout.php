<?php

namespace Opencart\Catalog\Model\Extension\OcPaymentPaysoncheckout\Payment;

class PaysonCheckout extends \Opencart\System\Engine\Model {

    private $currency_supported_by_p_direct = array('SEK', 'EUR');
    private $minimumAmountSEK = 10;
    private $minimumAmountEUR = 1;
    private $maxAmountSEK = 100000;
    private $maxAmountEUR = 8500;

    public function getMethods(array $address = []): array {
        $this->load->language('extension/oc_payment_paysoncheckout/payment/paysoncheckout');

        $zone_id = isset($this->session->data['shipping_address']['zone_id']) ? $this->session->data['shipping_address']['zone_id'] : $this->config->get('config_zone_id');
        $country_id = isset($this->session->data['shipping_address']['country_id']) ? $this->session->data['shipping_address']['country_id'] : $this->config->get('config_country_id');

        if ($this->cart->hasSubscription()) {
            $status = false;
        } elseif ($this->config->get('payment_paysoncheckout_total') > $this->cart->getTotal()) {
            $status = false;
        } elseif (!$this->config->get('payment_paysoncheckout_geo_zone_id')) {
            $status = true;
        } else {
            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int) $this->config->get('payment_paysoncheckout_geo_zone_id') . "' AND country_id = '" . (int) $country_id . "' AND (zone_id = '" . (int) $zone_id . "' OR zone_id = '0')");
            if ($query->num_rows) {
                $status = true;
            } else {
                $status = false;
            }
        }

        if (!$this->isMinimumOrMaxAmount()) {
            $status = false;
        }

        if (!in_array(strtoupper($this->session->data['currency']), $this->currency_supported_by_p_direct)) {
            $status = false;
        }

        $method_data = [];

        if ($status) {
            $option_data['paysoncheckout'] = [
                'code' => 'paysoncheckout.paysoncheckout',
                'name' => $this->language->get('text_title'),
            ];

            $method_data = [
                'code' => 'paysoncheckout',
                'name' => $this->language->get('text_title'),
                'option' => $option_data,
                'sort_order' => $this->config->get('payment_paysoncheckout_sort_order'),
                'status_one_page' => $status
            ];
        }
        return $method_data;
    }

    public function isMinimumOrMaxAmount() {

        if (strtoupper($this->config->get('config_currency')) == 'SEK' && ($this->getTotalAmountCart() < $this->minimumAmountSEK || $this->getTotalAmountCart() > $this->maxAmountSEK)) {
            return 0;
        }

        if (strtoupper($this->config->get('config_currency')) == 'EUR' && ($this->getTotalAmountCart() < $this->minimumAmountEUR || $this->getTotalAmountCart() > $this->maxAmountEUR)) {
            return 0;
        }

        return 1;
    }

    public function getTotalAmountCart() {
        $this->load->model('checkout/cart');

        $totals = [];
        $taxes = $this->cart->getTaxes();
        $total = 0;

        ($this->model_checkout_cart->getTotals)($totals, $taxes, $total);

        return $total;
    }

}
