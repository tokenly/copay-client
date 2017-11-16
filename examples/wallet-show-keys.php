#!/usr/bin/env php
<?php 

use Tokenly\CopayClient\CopayClient;
use Tokenly\CopayClient\CopayException;
use Tokenly\CopayClient\CopayWallet;
use Tokenly\CopayClient\EncryptionService\EncryptionServiceClient;

require __DIR__.'/../vendor/autoload.php';

$platform_seed = getenv('PLATFORM_WALLET_SEED'); if ($platform_seed === false) { $platform_seed = 'TESTPLATFORMSEED'; }

// join a wallet
$wallet_seed = $argv[1];
if (!$wallet_seed) { throw new Exception("wallet_seed is required", 1); }


$wallet = CopayWallet::newWalletFromPlatformSeedAndWalletSeed($platform_seed, $wallet_seed);
$copay_client = new CopayClient('https://pockets-service.tokenly.com/bws/api');


// calculate the secret code
$wallet_data = $wallet->export();
echo "\$wallet_data: ".json_encode($wallet_data, 192)."\n";


try {
    $result = $copay_client->getWallet($wallet);
} catch (CopayException $e) {
    echo "Error ".$e->getCode().": ".$e->getMessage()." (".$e->getCopayStatusCode().")\n";
    exit(1);
}

echo "Wallet ID: ".$result['wallet']['id']."\n";

echo "export WALLET_DATA_JSON='".str_replace("'","\\'",json_encode($wallet_data))."'; ";
echo "export WALLET_ID='".$result['wallet']['id']."';";
echo "\n\n";


/*
$wallet_data: {
    "mnemonic": "blue pearl ....",
    "entropySource": "xxxxx",
    "xPrivKey": "xprv9xxxx",
    "xPubKey": "xpub6xxxx",
    "requestPrivKey": "deadbeefxxxx",
    "requestPubKey": "deadbeefxxxx",
    "personalEncryptingKey": "yzxxxxxx==",
    "copayerId": "deadbeefxxxxx",
    "sharedEncryptingKey": "yzxxxxx==",
    "walletPrivKey": "deadbeefxxxxx"
}

Wallet ID: 4f2c03a5-51fd-43c4-97a7-a6f8ccd72dab
*/

