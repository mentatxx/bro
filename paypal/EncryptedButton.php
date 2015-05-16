<?php
namespace Bro\paypal;


class EncryptedButton {
    // List of button options for button
    public $options;
    // Certificate to sign
    public $cert;
    //
    public $privateKey;
    // Password for
    public $privateKeyPassword;

    public function getHtmlCode() {

    }

    public function fillOptions($certId, $businessEmail, $item_name, $item_number, $custom, $amount, $currency_code, $tax, $shipping) {

    }
}