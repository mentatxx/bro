<?php
namespace Bro\paypal;

class IPN {
    var $options = [];

    /**
     * Parse options returned by IPN
     *
     * @param string $options
     * @return array
     */
    public function parseOptions($options) {
        $this->options = array();
        $allOptions = explode('&', $options);
        foreach($allOptions as $option) {
            list($keyCoded, $valueCoded) = explode('=', $option.'=');
            $key = urldecode($keyCoded);
            $value = urldecode($valueCoded);
            $this->options[$key] = $value;
        }
        return $this->options;
    }

    public function getValue($key) {
        if (isset($this->options[$key])) {
            return $this->options[$key];
        } else {
            return '';
        }
    }
}