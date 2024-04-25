<?php

$_['paysoncheckout_example'] = 'Example Extra Text';

// Heading Goes here:
$_['heading_title'] = 'Payson Checkout 2.0';

// Text
$_['text_modul_name'] = 'Payson Checkout 2.0';
$_['text_modul_version'] = '1.0.1.127';
$_['text_payment'] = 'Payment';
$_['text_extension'] = 'Extensions';
// Text
$_['text_description'] = 'Payment methods for Payson Checkout offer your customers simple and secure payments online. In Payson Checkout all payment methods are included as standard! The last used payment method of the customer is preselected for a fast and simple shopping experience.';

$_['text_success'] = 'Success: You have modified Payson Checkout 2.0 module!';
$_['text_paysoncheckout'] = '<a onclick="window.open(\'https://www.payson.se/tj%C3%A4nster/ta-betalt\');"><img src="view/image/payment/paysoncheckout.png" alt="payson Checkout 2.0" title="payson Checkout 2.0" /></a>';
$_['text_edit'] = 'Payson Checkout';

// Entry
$_['entry_button_create_a_paysonaccount'] = 'Create a PaysonAccount';
$_['entry_button_create_a_test_paysonaccount'] = 'Create a test PaysonAccount';
$_['entry_method_mode'] = 'Mode';
$_['text_method_mode_live'] = 'Production';
$_['text_method_mode_sandbox'] = 'Test';

$_['merchant_id'] = 'Merchant id';
$_['api_key'] = 'API-key';

//Tabs
$_['tab_api'] = 'API Details';
$_['tab_general'] = 'General';
$_['tab_order_status'] = 'Order status';
$_['tab_version'] = 'Modul version';
$_['tab_checkout_scheme'] = 'Checkout scheme';

$_['entry_total'] = 'Total';
$_['entry_order_status'] = 'Order Status';
$_['entry_order_status_shipped'] = 'Order Status Shipped';
$_['entry_order_status_canceled'] = 'Order Status Canceled';
$_['entry_order_status_refunded'] = 'Order Status Refunded';

$_['entry_geo_zone'] = 'Geo Zone';
$_['entry_status'] = 'Status';
$_['entry_sort_order'] = 'Sort Order';
$_['entry_logg'] = 'Logs';
$_['entry_totals_to_ignore'] = 'Order totals to ignore';
$_['entry_countries'] = 'List of countries';

$_['text_logotype'] = 'Enable logotype';
$_['entry_logotype'] = 'Logotype';
$_['text_logotype_yes_right'] = 'yes right';
$_['text_logotype_yes_left'] = 'yes left';
$_['text_logotype_no'] = 'no';

$_['entry_verification'] = 'Verification';
$_['text_verification_none'] = 'None';
$_['text_verification_bankid'] = 'BankId';
$_['entry_color_scheme'] = 'Color scheme';
$_['text_color_scheme_blue'] = 'blue';
$_['text_color_scheme_gray'] = 'gray';
$_['text_color_scheme_white'] = 'white';

$_['entry_iframe_size_width'] = 'Size of iframe (Width)';
$_['entry_iframe_size_height'] = 'Size of iframe (height)';
$_['entry_iframe_size_width_type'] = 'Percent or px';
$_['text_iframe_size_width_percent'] = '%';
$_['text_iframe_size_width_px'] = 'px';
$_['entry_iframe_size_height_type'] = 'Percent or px';
$_['text_iframe_size_height_percent'] = '%';
$_['text_iframe_size_height_px'] = 'px';
$_['entry_order_item_details_to_ignore'] = 'Order Item Details to ignore by Payson Checkout 2.0';
$_['entry_show_receipt_page'] = 'Show Receipt Page';
$_['entry_show_receipt_page_yes'] = 'yes';
$_['entry_show_receipt_page_no'] = 'no';

$_['entry_product_out_of_stock'] = 'Product out of stock';
$_['entry_product_out_of_stock_yes'] = 'yes';
$_['entry_product_out_of_stock_no'] = 'no';

$_['entry_price_mismatch'] = 'Order price mismatch';
$_['entry_price_mismatch_yes'] = 'yes';
$_['entry_price_mismatch_no'] = 'no';

$_['entry_enable_one_page_checkout'] = 'One page checkout';
$_['terms_and_conditions'] = 'Terms & Conditions';
// Error
$_['error_permission'] = 'Warning: You do not have permission to modify payment Payson module!';
$_['error_merchant_id'] = 'Agent ID Required!';
$_['error_merchant_id_format'] = 'You try to send the wrong format of the string or send a blank agent ID';
$_['error_api_key'] = 'API-key Required!';
$_['error_api_key_format'] = 'You try to send the wrong format of the string or send a blank API-key';
$_['error_ignored_order_totals'] = 'Enter a comma separated list with order totals not to send to payson';

//help
$_['help_button_create_a_test_paysonaccount'] = 'Create a test PaysonAccount.';
$_['help_button_create_a_paysonaccount'] = 'Create a PaysonAccount here.';
$_['help_method_mode'] = 'Enable environment to production.';
$_['help_status_extension'] = 'Enable extension.';
$_['help_merchant_id'] = 'Enter your merchant ID for Payson.';
$_['help_api_key'] = 'Enter your API-key for Payson.';
$_['help_logg'] = 'You can find your logs in Admin | System -> Maintenance -> Error Log.';
$_['help_logotype'] = 'Enable logotype  1:no | 2:left | 3:right.';
$_['help_verification'] = 'Enable BANKID. Can be used to add extra customer verification.';
$_['help_color_scheme'] = 'Select color scheme.';
$_['help_iframe_size_height'] = 'Select the height of iframe.';
$_['help_iframe_size_width'] = 'Select the width of iframe.';
$_['help_iframe_size_height_type'] = 'Select the height of iframe.';
$_['help_iframe_size_width_type'] = 'Select the width of iframe.';
$_['help_total'] = 'The checkout total the order must reach before this payment method becomes active.';
$_['help_receipt'] = 'Enable Payson receipt.';
$_['help_product_out_of_stock'] = 'Decline payment when a product is out of stock';
$_['help_price_mismatch'] = 'Pending payment when a price mismatch between OpenCart and Payson';
$_['help_enable_one_page_checkout'] = 'Enable one page checkout.';
$_['help_totals_to_ignore'] = 'Comma separated list with order totals not to send to payson.';
$_['help_countries'] = 'List of countries a customer can choose in the checkout snippet. Case sensitive, e.g use: SE,NO,DK.';
$_['help_order_status'] = 'Set by OpenCart after the customer has completed a payment or when an invoice can be sent.<br /> Please check under Order | History | Comment that the text “Notification is sent, and the order has been readyToShip” is included.';
$_['help_order_status_shipped'] = 'Notify Payson that the order has been shipped. <br />Please check under Order | History | Comment that the text “Notification is sent, and the order has been shipped” is included.';
$_['help_order_status_canceled'] = 'Notify Payson that the order has been canceled.<br />Please check under Order | History | Comment that the text “Notification is sent, and the order has been canceled” is included';
$_['help_order_status_refunded'] = 'Notify Payson that the order has been refunded.<br />Please check under Order | History | Comment that the text “Notification is sent, and the order has been paidToAccount/refunded” is included.';
$_['help_terms_and_conditions'] = 'Select the key to the term and conditions from Design -> SEO URL.';

