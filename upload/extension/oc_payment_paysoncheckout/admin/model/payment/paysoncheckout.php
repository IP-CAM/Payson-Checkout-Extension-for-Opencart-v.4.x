<?phpnamespace Opencart\Admin\Model\Extension\OcPaymentPaysoncheckout\Payment;class PaysonCheckout extends \Opencart\System\Engine\Model {    public function install(): void {        $query = $this->db->query("                    CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "payson_embedded_order` (                    `payson_embedded_id` int(11) AUTO_INCREMENT,                    `order_id` int(15) NOT NULL,                    `checkout_id` varchar(40) DEFAULT NULL,                    `purchase_id` varchar(50) DEFAULT NULL,                    `payment_status` varchar(20) DEFAULT NULL,                    `added` datetime DEFAULT NULL,                    `updated` datetime DEFAULT NULL,                    `sender_email` varchar(50) DEFAULT NULL,                    `currency_code` varchar(5) DEFAULT NULL,                    `tracking_id` varchar(100) DEFAULT NULL,                    `type` varchar(50) DEFAULT NULL,                    `shippingAddress_name` varchar(50) DEFAULT NULL,                    `shippingAddress_lastname` varchar(50) DEFAULT NULL,                    `shippingAddress_street_ddress` varchar(60) DEFAULT NULL,                    `shippingAddress_postal_code` varchar(20) DEFAULT NULL,                    `shippingAddress_city` varchar(60) DEFAULT NULL,                    `shippingAddress_country` varchar(60) DEFAULT NULL,                    PRIMARY KEY (`payson_embedded_id`)                    )  ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;"        );    }    public function uninstall(): void {            }}