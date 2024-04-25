<?php

namespace Opencart\Catalog\Controller\Extension\OcPaymentPaysoncheckout\Onecheckout;

class Confirm extends \Opencart\System\Engine\Controller {

    public function index(): string {
        $this->load->language('checkout/confirm');
        $this->load->model('extension/oc_payment_paysoncheckout/paysoncheckout/order');

        //Order Totals
        $totals = [];
        $taxes = $this->cart->getTaxes();
        $total = 0;

        $this->load->model('checkout/cart');

        ($this->model_checkout_cart->getTotals)($totals, $taxes, $total);

        $status = true;

        if (!isset($this->session->data['customer'])) {
            $status = false;
        }

        // Validate cart has products and has stock.
        if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
            $status = false;
        }

        // Validate minimum quantity requirements.
        $products = $this->model_checkout_cart->getProducts();

        foreach ($products as $product) {
            if (!$product['minimum']) {
                $status = false;

                break;
            }
        }

        // Shipping
        if ($this->cart->hasShipping()) {
            
            // Validate shipping address
            if (!isset($this->session->data['shipping_address'])) {
                $status = false;
            }

            // Validate shipping method
            if (!isset($this->session->data['shipping_method'])) {
                $status = false;
            }
        } else {
            unset($this->session->data['shipping_address']);
            unset($this->session->data['shipping_method']);
            unset($this->session->data['shipping_methods']);
        }

        // Validate has payment address if required
        if ($this->config->get('config_checkout_payment_address') && !isset($this->session->data['payment_address'])) {
            $status = false;
        }

        // Validate payment methods
        if (!isset($this->session->data['payment_method'])) {
            $status = false;
        }

        // Validate checkout terms
        if ($this->config->get('config_checkout_id') && empty($this->session->data['agree'])) {
            $status = false;
        }
        


        $this->load->model('tool/upload');

        $data['products'] = [];
        foreach ($products as $product) {
            $description = '';

            if ($product['subscription']) {
                if ($product['subscription']['trial_status']) {
                    $trial_price = $this->currency->format($this->tax->calculate($product['subscription']['trial_price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                    $trial_cycle = $product['subscription']['trial_cycle'];
                    $trial_frequency = $this->language->get('text_' . $product['subscription']['trial_frequency']);
                    $trial_duration = $product['subscription']['trial_duration'];

                    $description .= sprintf($this->language->get('text_subscription_trial'), $trial_price, $trial_cycle, $trial_frequency, $trial_duration);
                }

                $price = $this->currency->format($this->tax->calculate($product['subscription']['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                $cycle = $product['subscription']['cycle'];
                $frequency = $this->language->get('text_' . $product['subscription']['frequency']);
                $duration = $product['subscription']['duration'];

                if ($duration) {
                    $description .= sprintf($this->language->get('text_subscription_duration'), $price, $cycle, $frequency, $duration);
                } else {
                    $description .= sprintf($this->language->get('text_subscription_cancel'), $price, $cycle, $frequency);
                }
            }

            $data['products'][] = [
                'cart_id' => $product['cart_id'],
                'product_id' => $product['product_id'],
                'name' => $product['name'],
                'model' => $product['model'],
                'option' => $product['option'],
                'subscription' => $description,
                'quantity' => $product['quantity'],
                'price' => $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']),
                'total' => $this->currency->format($this->tax->calculate($product['price'] * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']),
                'reward' => $product['reward'],
                'href' => $this->url->link('product/product', 'language=' . $this->config->get('config_language') . '&product_id=' . $product['product_id'])
            ];
        }

        // Gift Voucher
        $data['vouchers'] = [];

        $vouchers = $this->model_checkout_cart->getVouchers();

        foreach ($vouchers as $voucher) {
            $data['vouchers'][] = [
                'description' => $voucher['description'],
                'amount' => $this->currency->format($voucher['amount'], $this->session->data['currency'])
            ];
        }

        $data['totals'] = [];

        foreach ($totals as $total) {
            $data['totals'][] = [
                'title' => $total['title'],
                'text' => $this->currency->format($total['value'], $this->session->data['currency'])
            ];
        }
        
        // Validate if payment method has been set.
        if (isset($this->session->data['payment_method'])) {
            $code = oc_substr($this->session->data['payment_method']['code'], 0, strpos($this->session->data['payment_method']['code'], '.'));
        } else {
            $code = '';
        }

        $extension_info = $this->model_setting_extension->getExtensionByCode('payment', $code);

        if ($status && $extension_info) {

            $data['payment'] = $this->load->controller('extension/' . $extension_info['extension'] . '/payment/' . $extension_info['code']);
        } else {
            $data['payment'] = '';
        }

        return $this->load->view('extension/oc_payment_paysoncheckout/onecheckout/confirm', $data);
    }

    public function confirm(): void {
        $this->response->setOutput($this->index());
    }
}