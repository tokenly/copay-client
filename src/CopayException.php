<?php

namespace Tokenly\CopayClient;

use Exception;

class CopayException extends Exception {

    protected $copay_status_code = null;

    public function setCopayStatusCode($copay_status_code) {
        $this->copay_status_code = $copay_status_code;
    }

    public function getCopayStatusCode() {
        return $this->copay_status_code;
    }

}