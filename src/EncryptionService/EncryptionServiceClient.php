<?php

namespace Tokenly\CopayClient\EncryptionService;

use Exception;
use Requests;
use Tokenly\CopayClient\CopayException;

/*
* EncryptionServiceClient
*/
class EncryptionServiceClient
{

    protected $api_base_url   = 'http://127.0.0.1:8088';
    protected $encryption_key = null;

    function __construct($api_base_url=null, $encryption_key=null) {
        if ($api_base_url != null) {
            $this->api_base_url = rtrim($api_base_url, '/');;
        }
        if ($encryption_key !== null) { $this->encryption_key = $encryption_key; }
    }

    public function withEncryptionKey($encryption_key) {
        $this->encryption_key = $encryption_key; 
        return $this;
    }


    public function encrypt($message, $key=null) {
        if ($key === null) { $key = $this->encryption_key; }
        if (!strlen($key)) { throw new Exception("Encryption key was not provided", 1); }
        $args = [
            'message' => $message,
            'key'     => $key,
        ];

        $result = $this->post('/encrypt', $args);
        return $result['result'];
    }

    public function decrypt($message, $key=null) {
        if ($key === null) { $key = $this->encryption_key; }
        if (!strlen($key)) { throw new Exception("Encryption key was not provided", 1); }
        $args = [
            'message' => $message,
            'key'     => $key,
        ];

        $result = $this->post('/decrypt', $args);
        return $result['result'];
    }



    public function post($url, $parameters, $options=[]) {
        return $this->call('POST', $url, $parameters, $options);
    }

    public function call($method, $url, $parameters, $options=[]) {
        $full_url = $this->api_base_url.'/'.trim($url, '/');
        return $this->fetchFromAPI($method, $full_url, $parameters, $options);
    }

    // ------------------------------------------------------------------------
    
    protected function fetchFromAPI($method, $url, $parameters=[], $options=[], $request_options=[]) {
        $options = array_merge([
            'post_type' => 'json',
        ], $options);

        $request_options = array_merge([
            'timeout'   => 30,
        ], $request_options);

        // send request
        $headers = [];
        $headers['Content-Type'] = 'application/json';
        $headers['Accept']       = 'application/json';
        $request_params = json_encode($parameters);
        $response = $this->callRequest($url, $headers, $request_params, $method, $request_options);

        // decode json
        $json = $this->decodeJsonFromResponse($response->body);

        // look for 400 - 500 errors
        $this->checkForErrorsInResponse($response, $json);

        return $json;
    }


    protected function decodeJsonFromResponse($response_body) {
        try {
            $json = json_decode($response_body, true);
        } catch (Exception $parse_json_exception) {
            // could not parse json
            throw new CopayException("Unexpected response", 1);
        }

        return $json;
    }

    protected function checkForErrorsInResponse($response, $json) {
        $is_bad_status_code = ($response->status_code >= 400 AND $response->status_code < 600);

        $copay_status_code = null;
        $error_message = null;
        $error_code = 1;
        // echo "\$json: ".json_encode($json, 192)."\n";
        if ($json) {
            // check for error
            if (isset($json['code'])) {
                $copay_status_code = $json['code'];
            }
            if (isset($json['message'])) {
                if (is_array($json['message'])) {
                    $message_arr = $json['message'];
                    if (isset($message_arr['code'])) {
                        $error_message = $message_arr['code'];
                    } else {
                        $error_message = json_encode($message_arr);
                    }
                } else {
                    $error_message = $json['message'];
                }
            }
        }
        if ($is_bad_status_code) {
            if ($error_message === null) {
                $error_message = "Received bad status code: {$response->status_code}";
            }
            $error_code = $response->status_code;
        }

        // for any errors, throw an exception
        if ($error_message !== null) {
            $e = new CopayException($error_message, $error_code);
            $e->setCopayStatusCode($copay_status_code);
            throw $e;
        }
    }

    // for testing
    protected function callRequest($url, $headers, $request_params, $method, $request_options) {
        return Requests::request($url, $headers, $request_params, $method, $request_options);
    }
}

