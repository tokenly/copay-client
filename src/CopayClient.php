<?php

namespace Tokenly\CopayClient;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Crypto\Random\Rfc6979;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Buffertools\Buffer;
use Exception;
use Requests;
use Tokenly\CopayClient\CopayException;
use Tokenly\CopayClient\CopayWallet;
use Tokenly\CopayClient\EncryptionService\EncryptionServiceClient;
use Tokenly\CopayClient\Transaction\CopayTransactionBuilder;
use Tokenly\CounterpartyTransactionComposer\OpReturnBuilder;
use Tokenly\CryptoQuantity\CryptoQuantity;

/*
* CopayClient
*
* Use createAndJoinWallet for a new wallet
* Use proposePublishAndSignTransaction to submit a transaction
*/
class CopayClient
{

    const SATOSHI      = 100000000;
    const CP_DUST_SIZE = 5430;

    protected $api_base_url = '';

    function __construct($api_base_url) {
        $this->api_base_url = rtrim($api_base_url, '/');;
    }

    public function withSharedEncryptionService(EncryptionServiceClient $shared_encryption_service) {
        $this->shared_encryption_service = $shared_encryption_service;
        return $this;
    }

    public function withPersonalEncryptionService(EncryptionServiceClient $personal_encryption_service) {
        $this->personal_encryption_service = $personal_encryption_service;
        return $this;
    }


    /**
     * Initiates a new shared wallet
     * Requires shared and personal encryption services
     * @param  CopayWallet $wallet       The seeded wallet
     * @param  string      $wallet_name  Wallet name displayed in Pockets
     * @param  string      $copayer_name Copayer name displayed in Pockets
     * @param  array       $args         arguments such as 'm' and 'n'
     * @return array                     new wallet info
     */
    public function createAndJoinWallet(CopayWallet $wallet, $wallet_name, $copayer_name, $args) {
        $wallet_id = $this->createWallet($wallet, $wallet_name, $args);
        return $this->joinWallet($wallet, $wallet_id, $copayer_name);
    }



    /**
     * Publishes and signs a transaction
     * 
     * args are:
     *   counterpartyType: send or issuance (optional - default: send)
     *   address: destination bitcoin address (for sends only)
     *   amountSat: The amount to send or issue in Satoshis (for indivisible assets, this is the amount of tokens to send or issue)
     *   description: for issuance, the issuance description
     *   feePerKB: fee per KB
     *   dustSize: counterparty dust size in satoshis (optional - default: 5430)
     *   token: the token to send (optional - default: BTC)
     *   divisible: set to false for indivisible tokens (optional - default: true)
     *   message: the transaction message (optional)
     * 
     * @param  CopayWallet $wallet       The seeded wallet
     * @param  array       $args         arguments: address, amountSat, feePerKB, [dustSize], [token=BTC], [message]
     * @return array                     The transaction info
     */
    public function proposePublishAndSignTransaction(CopayWallet $wallet, $args) {
        $transaction_proposal = $this->proposeTransaction($wallet, $args);
        $transaction_proposal = $this->publishTransactionProposal($wallet, $transaction_proposal);
        return $this->signTransactionProposal($wallet, $transaction_proposal);
    }


    /**
     * Publishes and signs a transaction
     * @param  CopayWallet $wallet The seeded wallet
     * @return array               The address info
     * {
     *   "address": "39VbaiHFKTzwCvjFJczdjVQX11cVgu29g1",
     *   "walletId": "72abff08-6414-4d91-adfc-509cbc26e53f"
     * }
     */
    public function getAddressInfo(CopayWallet $wallet) {
        $copayer_id          = $wallet['copayerId'];
        $request_private_key = $wallet['requestPrivKey'];

        $result = $this->post('/v3/addresses/', [], ['copayer_id' => $copayer_id, 'private_key' => $request_private_key]);
        return $result;
    }


    /**
     * Get wallet info
     * @param  CopayWallet $wallet The seeded wallet
     * @return array               wallet information
     * {
     *    "wallet": {
     *        "id": "72abff08-6414-4d91-adfc-509cbc26e53f",
     *        "name": "{\"iv\":\"rd4soA+NYsc3ah+4mj/HKw==\",\"v\":1,\"iter\":1,\"ks\":128,\"ts\":64,\"mode\":\"ccm\",\"adata\":\"\",\"cipher\":\"aes\",\"ct\":\"SbhHwk3+IBDouT8lHkXnTf+kUyd5rw==\"}",
     *        "m": 2,
     *        "n": 2,
     *        "singleAddress": true,
     *        "status": "complete",
     *        "copayers": [
     *            ...
     *        ]
     *        ...
     *    }
     *  }
     */
    public function getWallet(CopayWallet $wallet) {
        $copayer_id          = $wallet['copayerId'];
        $request_private_key = $wallet['requestPrivKey'];
        $result = $this->get('/v2/wallets/', [], ['copayer_id' => $copayer_id, 'private_key' => $request_private_key]);
        return $result;
    }


    /**
     * Fetch UTXOs
     * @param  CopayWallet $wallet The seeded wallet
     * @return array               List of UTXOs
     * [
     *     {
     *         "txid": "ad501692ab08cf637696ce886946849455a7fb452fb0712f7488b031f5aca3a2",
     *         "vout": 0,
     *         "address": "3NHozEeYbtoy9HDTW7F2t8HvzoSyrqodWc",
     *         "scriptPubKey": "a914e1f71c422899ef7efe627dea65de08f0d00a106087",
     *         "satoshis": 500000,
     *         "confirmations": 4367,
     *         "locked": false,
     *         "path": "m/0/0",
     *         "publicKeys": [
     *             "030279ef7cbe72238065c2aa28b77ab8b90a7195b930c1b39f5c482958620d201f",
     *             "0236d40074887286c6bf1843702c77b47dc1fcc92e99532b2cccb58355c6df4de7"
     *         ]
     *     }
     * ]
     */
    public function getUtxos(CopayWallet $wallet) {
        $copayer_id          = $wallet['copayerId'];
        $request_private_key = $wallet['requestPrivKey'];
        $result = $this->get('/v1/utxos/', [], ['copayer_id' => $copayer_id, 'private_key' => $request_private_key]);
        return $result;
    }



    // returns the wallet id
    public function createWallet(CopayWallet $wallet, $wallet_name, $args) {
        $args = array_merge([
            'm'             => 2,
            'n'             => 2,
            'network'       => 'livenet',
            'singleAddress' => true,
            'pubKey'        => $wallet['walletPubKey']->getHex(),
        ], $args);

        $args['m'] = intval($args['m']);
        $args['n'] = intval($args['n']);

        $encrypted_wallet_name = $this->requireSharedEncryptionService()->encrypt($wallet_name);
        $args['name'] = $encrypted_wallet_name;

        $result = $this->post('/v2/wallets/', $args);

        return $result['walletId'];
    }

    public function joinWallet(CopayWallet $wallet, $wallet_id, $copayer_name) {
        $wallet_priv_key           = $wallet['walletPrivKey'];
        $wallet_address_public_key = $wallet['xPubKey'];
        $request_public_key        = $wallet['requestPubKey'];

        $custom_data = ['walletPrivKey' => $wallet_priv_key->getHex()];
        $encoded_custom_data = $this->requirePersonalEncryptionService()->encrypt(json_encode($custom_data));
        $encoded_copayer_name = $this->requireSharedEncryptionService()->encrypt($copayer_name);

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


    public function proposeTransaction(CopayWallet $wallet, $args) {
        $transaction_type = $this->counterpartyTypeFromArgs($args);

        if ($transaction_type == 'send') {
            if (!isset($args['address']) OR !$args['address']) { throw new Exception("address is required", 1); }
        } else if ($transaction_type == 'send') {
            if (!isset($args['description']) OR !$args['description']) { throw new Exception("description is required", 1); }
        }
        if (!isset($args['amountSat']) OR !$args['amountSat']) { throw new Exception("amountSat is required", 1); }
        if (!isset($args['feePerKBSat']) OR !$args['feePerKBSat']) {
            if (!isset($args['feeSat']) OR !$args['feeSat']) {
                throw new Exception("feePerKBSat or feeSat is required", 1);
            }
        }

        if (isset($args['token']) AND strlen($args['token'])) {
            $args['token'] = strtoupper($args['token']);
        } else {
            $args['token'] = 'BTC';
        }

        $args['divisible'] = (isset($args['divisible']) ? !!$args['divisible'] : true);
        $args['amountSat'] = intval($args['amountSat']);
        $args['amountFloat'] = $args['divisible'] ? ($args['amountSat'] / self::SATOSHI) : $args['amountSat'];

        if ($this->isBTCSend($args)) {
            // simple one-time send
            return $this->sendTransactionProposal($wallet, $args);
        }

        // send a dry run to get the input txid
        $transaction_proposal = $this->sendTransactionProposal($wallet, $args, true);

        // now compose the actual script with the first input txid
        return $this->sendTransactionProposal($wallet, $args, false, $transaction_proposal['inputs'][0]['txid']);
    }


    public function getTransactionProposal(CopayWallet $wallet, $tx_proposal_id) {
        $copayer_id          = $wallet['copayerId'];
        $request_private_key = $wallet['requestPrivKey'];

        $result = $this->get('/v1/txproposals/'.$tx_proposal_id, [], ['copayer_id' => $copayer_id, 'private_key' => $request_private_key]);
        return $result;
    }

    public function publishTransactionProposal(CopayWallet $wallet, $transaction_proposal) {
        $copayer_id          = $wallet['copayerId'];
        $request_private_key = $wallet['requestPrivKey'];

        // build the transaction from the proposal
        $tx_builder = new CopayTransactionBuilder();
        $transaction = $tx_builder->buildTransactionFromProposal($transaction_proposal);

        $copay_args = [];
        // sign the transaction hex
        $copay_args['proposalSignature'] = $this->signMessage($transaction->getHex(), $request_private_key);
        // return null;

        // publish the proposal
        $tx_proposal_id = $transaction_proposal['id'];
        $result = $this->post('/v1/txproposals/'.$tx_proposal_id.'/publish/', $copay_args, ['copayer_id' => $copayer_id, 'private_key' => $request_private_key]);
        return $result;
    }

    public function signTransactionProposal(CopayWallet $wallet, $transaction_proposal) {
        $copayer_id          = $wallet['copayerId'];
        $request_private_key = $wallet['requestPrivKey'];
        $address_hd_priv_key = $wallet['address_priv_hd_key'];

        // build the transaction from the proposal
        $tx_builder = new CopayTransactionBuilder();
        $signatures = $tx_builder->buildTransactionSignaturesFromProposal($address_hd_priv_key, $transaction_proposal);

        $copay_args = [
            'signatures' => $signatures,
        ];

        // sign the transaction
        $tx_proposal_id = $transaction_proposal['id'];
        $result = $this->post('/v1/txproposals/'.$tx_proposal_id.'/signatures/', $copay_args, ['copayer_id' => $copayer_id, 'private_key' => $request_private_key]);
        return $result;
    }

    // only for published transactions
    public function deleteTransactionProposal(CopayWallet $wallet, $tx_proposal_id) {
        $copayer_id          = $wallet['copayerId'];
        $request_private_key = $wallet['requestPrivKey'];

        $result = $this->delete('/v1/txproposals/'.$tx_proposal_id, [], ['copayer_id' => $copayer_id, 'private_key' => $request_private_key]);
        return $result;
    }

    // ------------------------------------------------------------------------

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

    protected function requireSharedEncryptionService() {
        if (!isset($this->shared_encryption_service)) { throw new Exception("Shared encryption service is required", 1); }
        return $this->shared_encryption_service;
    }

    protected function requirePersonalEncryptionService() {
        if (!isset($this->personal_encryption_service)) { throw new Exception("Personal encryption service is required", 1); }
        return $this->personal_encryption_service;
    }

    protected function computeTransactionProposalSignature($copay_args, $request_private_key) {
        // build a limited subset of outputs for signing
        $outputs_for_signing = [];
        foreach ($copay_args['outputs'] as $txp_output) {
            $output = [];
            $output['amount'] = $txp_output['amount'];
            $output['message'] = isset($txp_output['message']) ? $txp_output['message'] : null;
            $output['toAddress'] = isset($txp_output['toAddress']) ? $txp_output['toAddress'] : null;
            $output['script'] = isset($txp_output['script']) ? $txp_output['script'] : null;
            $outputs_for_signing[] = $output;
        }

        $proposal_header = [
            'message'   => $copay_args['message'],
            'outputs'   => $outputs_for_signing,
            'payProUrl' => null,
        ];

        $string_to_sign = json_encode($proposal_header, JSON_UNESCAPED_SLASHES);
        // echo "\$string_to_sign: ".($string_to_sign)."\n";
        return $this->signMessage($string_to_sign, $request_private_key);
    }

    protected function signMessage($message, $priv_key) {
        // hash
        $hash = Hash::sha256d(new Buffer($message));

        $ec = Bitcoin::getEcAdapter();
        $signature = $ec->sign(
                $hash,
                $priv_key,
                new Rfc6979(
                    $ec,
                    $priv_key,
                    $hash,
                    'sha256'
                )
        );

        return $signature->getBuffer()->getHex();
    }

    protected function signRequest($method, $url, $parameters, $priv_key) {
        // req.method.toLowerCase() + '|' + req.url + '|' + JSON.stringify(req.body)
        $api_path = parse_url($url)['path'];
        if (substr($api_path, 0, 8) == '/bws/api') { $api_path = substr($api_path, 8); }

        $parameters_to_sign_string = '{}';
        if ($parameters) {
            $parameters_to_sign_string = json_encode($parameters, JSON_UNESCAPED_SLASHES);
        }
        $message = strtolower($method).'|'.$api_path.'|'.$parameters_to_sign_string;
        // echo "Signing Request:\n{$message}\n";
        return $this->signMessage($message, $priv_key);
    }


    // ------------------------------------------------------------------------

    protected function sendTransactionProposal(CopayWallet $wallet, $args, $as_dry_run=false, $input_0_txid=null) {
        $copayer_id          = $wallet['copayerId'];
        $request_private_key = $wallet['requestPrivKey'];

        $copay_args = [
            'amount'    => $args['amountSat'],
        ];

        if (isset($args['feePerKBSat'])) {
            $copay_args['feePerKb'] = $args['feePerKBSat'];
        } else if (isset($args['feeSat'])) {
            $copay_args['fee'] = $args['feeSat'];
        }
        if (isset($args['address'])) {
            $copay_args['toAddress'] = $args['address'];
        }

        if ($as_dry_run) { $copay_args['dryRun'] = true; }

        // build the token or BTC outputs
        $encrypted_message = null;
        if (isset($args['message'])) {
            $encrypted_message = $this->requireSharedEncryptionService()->encrypt($args['message']);
        }
        $outputs = $this->buildTransactionOutputsFromArgs($args, $encrypted_message, $input_0_txid);

        // modify the args depending on the send type
        if ($this->isTokenTransaction($args)) {
            $copay_args = $this->modifyCopayArgsForTokenTransaction($copay_args, $args);
        } else {
            $copay_args = $this->modifyCopayArgsForBTCSend($copay_args, $args);
        }

        $copay_args['message'] = $encrypted_message;
        $copay_args['outputs'] = $outputs;

        $copay_args['proposalSignature'] = $this->computeTransactionProposalSignature($copay_args, $request_private_key);


        // send the transaction proposal
        $result = $this->post('/v2/txproposals/', $copay_args, ['copayer_id' => $copayer_id, 'private_key' => $request_private_key]);
        return $result;
    }
    
    // ------------------------------------------------------------------------
    
    protected function isBTCSend($args) {
        return ($args['token'] == 'BTC');
    }

    protected function isTokenTransaction($args) {
        return !$this->isBTCSend($args);
    }

    protected function modifyCopayArgsForBTCSend($copay_args, $args) {
        $copay_args['customData'] = ['isCounterparty' => false, 'counterparty' => null];
        return $copay_args;
    }

    protected function modifyCopayArgsForTokenTransaction($copay_args, $args) {
        // token sends aren't validated by the server
        $copay_args['validateOutputs']  = false;
        //outputs must remain in the given order for token sends
        $copay_args['noShuffleOutputs'] = true;

        // txp.customData = {isCounterparty: txp.isCounterparty, counterparty: txp.counterparty};  
        $quantity_float = $args['amountFloat'];
        $transaction_type = $this->counterpartyTypeFromArgs($args);
        $copay_args['customData'] = [
            'isCounterparty' => true,
            'counterpartyType' => $transaction_type,
            'counterparty'   => [
                'token'         => $args['token'],
                'quantity'      => $args['amountSat'],
                'quantityFloat' => $quantity_float,
                'amountStr'     => $quantity_float." ".$args['token'],
                'divisible'     => $args['divisible'],
                'type'          => $transaction_type,
            ],
        ];

        return $copay_args;
    }

    protected function buildTransactionOutputsFromArgs($args, $encrypted_message=null, $input_0_txid=null) {
        $outputs = [];
        if ($this->isBTCSend($args)) {
            $output = [
                'toAddress' => $args['address'],
                'amount'    => $args['amountSat'],
            ];
            if ($encrypted_message !== null) {
                $output['message'] = $encrypted_message;
            }
            $outputs[] = $output;
        } else {
            // build the token send or issuance scripts

            // send or issuance
            $transaction_type = $this->counterpartyTypeFromArgs($args);


            if ($transaction_type == 'send') {
                // build the dust send (for sends only)
                $output = [
                    'toAddress' => $args['address'],
                    'amount'    => (isset($args['dustSize']) ? isset($args['dustSize']) : self::CP_DUST_SIZE),
                ];
                if ($encrypted_message !== null) {
                    $output['message'] = $encrypted_message;
                }
                $outputs[] = $output;
            }

            $op_return_builder = new OpReturnBuilder();
            $quantity_obj = $args['divisible'] ? CryptoQuantity::fromSatoshis($args['amountSat']) : CryptoQuantity::fromIndivisibleAmount($args['amountSat']);

            // build the OP_RETURN script
            switch ($transaction_type) {
                case 'send':
                    $op_return = $op_return_builder->buildOpReturnForSend($quantity_obj, $args['token'], $input_0_txid);
                    break;
                case 'issuance':
                    $op_return = $op_return_builder->buildOpReturnForIssuance($quantity_obj, $args['token'], $args['divisible'], $args['description'], $input_0_txid);
                    break;
            }
            $script = ScriptFactory::create()->op('OP_RETURN')->push(Buffer::hex($op_return))->getScript();
            $op_return = $script->getBuffer()->getHex();

            $output = [
                'amount'    => 0,
                'script'    => $op_return,
            ];
            $outputs[] = $output;

        }
        return $outputs;
    }

    protected function counterpartyTypeFromArgs($args) {
        if (isset($args['counterpartyType']) AND $args['counterpartyType'] == 'issuance') {
            return 'issuance';
        }
        return 'send';
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

    protected function buildAuthentication($copayer_id, $priv_key, $method, $url, $parameters, $headers=[]) {
        $headers['x-identity'] = $copayer_id;
        $headers['x-signature'] = $this->signRequest($method, $url, $parameters, $priv_key);

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
        if ($is_bad_status_code AND $json) {
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

