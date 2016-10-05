<?php

namespace Tokenly\CopayClient;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Crypto\Random\Rfc6979;
use BitWasp\Bitcoin\MessageSigner\SignedMessage;
use BitWasp\Buffertools\Buffer;
use Exception;
use Requests;
use Tokenly\CopayClient\CopayException;
use Tokenly\CopayClient\EncryptionService\EncryptionServiceClient;

/*
* CopayClient
*/
class CopayClient
{

    protected $api_base_url = '';

    function __construct($api_base_url) {
        $this->api_base_url       = rtrim($api_base_url, '/');;
    }

    public function withSharedEncryptionService(EncryptionServiceClient $shared_encryption_service) {
        $this->shared_encryption_service = $shared_encryption_service;
        return $this;
    }

    public function withPersonalEncryptionService(EncryptionServiceClient $personal_encryption_service) {
        $this->personal_encryption_service = $personal_encryption_service;
        return $this;
    }


    // returns the wallet id
    public function createWallet($wallet_name, $args) {
        $args = array_merge([
            'm'             => 2,
            'n'             => 2,
            'network'       => 'livenet',
            'singleAddress' => true,
        ], $args);

        $encrypted_wallet_name = $this->shared_encryption_service->encrypt($wallet_name);
        $args['name'] = $encrypted_wallet_name;

        $result = $this->post('/v2/wallets/', $args);

        return $result['walletId'];
    }

    public function joinWallet($wallet_id, $wallet_priv_key, $wallet_address_public_key, $request_public_key, $copayer_name) {
        $custom_data = ['walletPrivKey' => $wallet_priv_key->getHex()];
        $encoded_custom_data = $this->personal_encryption_service->encrypt(json_encode($custom_data));
        $encoded_copayer_name = $this->shared_encryption_service->encrypt($copayer_name);

        $args = [
            'walletId'      => $wallet_id,
            'name'          => $encoded_copayer_name,
            'xPubKey'       => $wallet_address_public_key->toExtendedKey(),
            'requestPubKey' => $request_public_key->getHex(),
            'customData'    => $encoded_custom_data,
        ];

        $message = $args['name'].'|'.$args['xPubKey'].'|'.$args['requestPubKey'];
        $args['copayerSignature'] = $this->signMessage($message, $wallet_priv_key);

        $url = '/v2/wallets/'.$wallet_id.'/copayers';
        $result = $this->post($url, $args);
        return $result;
    }

    public function getWallet($copayer_id, $request_private_key) {
        $result = $this->get('/v2/wallets/', [], ['copayer_id' => $copayer_id, 'private_key' => $request_private_key]);
        return $result;
    }

    /*
    * returns:
    * {
    *   "address": "39VbaiHFKTzwCvjFJczdjVQX11cVgu29g1",
    *   "walletId": "72abff08-6414-4d91-adfc-509cbc26e53f"
    * }
    */
    public function getAddressInfo($copayer_id, $request_private_key) {
        $result = $this->post('/v3/addresses/', [], ['copayer_id' => $copayer_id, 'private_key' => $request_private_key]);
        return $result;
    }



    public function get($url, $parameters=[], $options=[]) {
        return $this->call('GET',    $url, $parameters, $options);
    }
    public function post($url, $parameters, $options=[]) {
        return $this->call('POST',   $url, $parameters, $options);
    }
    public function put($url, $parameters, $options=[]) {
        return $this->call('PUT',    $url, $parameters, $options);
    }
    public function patch($url, $parameters, $options=[]) {
        return $this->call('PATCH',  $url, $parameters, $options);
    }
    public function delete($url, $parameters=[], $options=[]) {
        return $this->call('DELETE', $url, $parameters, $options);
    }

    public function call($method, $url, $parameters, $options=[]) {
        $full_url = $this->api_base_url.'/'.trim($url, '/');
        return $this->fetchFromAPI($method, $full_url, $parameters, $options);
    }

    // ------------------------------------------------------------------------

    protected function signMessage($message, $wallet_priv_key) {
        // hash
        $hash = Hash::sha256d(new Buffer($message));

        $ec = Bitcoin::getEcAdapter();
        $signature = $ec->sign(
                $hash,
                $wallet_priv_key,
                new Rfc6979(
                    $ec,
                    $wallet_priv_key,
                    $hash,
                    'sha256'
                )
        );

        return $signature->getBuffer()->getHex();
    }

    protected function signRequest($method, $url, $parameters, $wallet_priv_key) {
        // req.method.toLowerCase() + '|' + req.url + '|' + JSON.stringify(req.body)
        $api_path = parse_url($url)['path'];
        if (substr($api_path, 0, 8) == '/bws/api') { $api_path = substr($api_path, 8); }

        $message = strtolower($method).'|'.$api_path.'|'.json_encode($parameters, JSON_UNESCAPED_SLASHES | JSON_FORCE_OBJECT);
        return $this->signMessage($message, $wallet_priv_key);
    }

    // ------------------------------------------------------------------------
    
    protected function fetchFromAPI($method, $url, $parameters=[], $options=[], $request_options=[]) {
        $options = array_merge([
            'post_type' => 'json',
        ], $options);

        $request_options = array_merge([
            'timeout'   => 30,
        ], $request_options);

        // get the headers and request params
        list($headers, $request_params) = $this->buildRequestHeadersAndParams($method, $url, $parameters, $options);

        // send request
        $response = $this->callRequest($url, $headers, $request_params, $method, $request_options);

        // decode json
        $json = $this->decodeJsonFromResponse($response->body);

        // look for 400 - 500 errors
        $this->checkForErrorsInResponse($response, $json);

        return $json;
    }

    protected function buildRequestHeadersAndParams($method, $url, $parameters, $options) {
        $headers = [];
        if (isset($options['copayer_id']) AND $options['copayer_id']) {
            if (!isset($options['private_key']) OR !$options['private_key']) { throw new Exception("private key not found for signature", 1); }
            $headers = $this->buildAuthentication($options['copayer_id'], $options['private_key'], $method, $url, $parameters, $headers);
        }

        // build body
        if ($method == 'GET') {
            $request_params = $parameters;
        }

        if ($method != 'GET') {
            // default to form fields (x-www-form-urlencoded)
            $request_params = $parameters;

            if ($options['post_type'] == 'json') {
                // override request params
                $headers['Content-Type'] = 'application/json';
                $headers['Accept'] = 'application/json';
                if ($parameters) {
                    if ($method == 'DELETE'){
                        $request_params = $parameters;
                    }

                    if ($method != 'DELETE'){
                        $request_params = json_encode($parameters);
                    }
                }

                if (!$parameters) {
                    $request_params = null;
                }
            }
        }

        $headers['User-Agent'] = 'CopayClient-php';

        return [$headers, $request_params];
    }

    protected function buildAuthentication($copayer_id, $wallet_priv_key, $method, $url, $parameters, $headers=[]) {
        $headers['x-identity'] = $copayer_id;
        $headers['x-signature'] = $this->signRequest($method, $url, $parameters, $wallet_priv_key);

        return $headers;
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

