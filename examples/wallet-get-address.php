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

try {
    $result = $copay_client->getAddressInfo($wallet['copayerId'], $wallet['requestPrivKey']);
} catch (CopayException $e) {
    echo "Error ".$e->getCode().": ".$e->getMessage()." (".$e->getCopayStatusCode().")\n";
    exit(1);
}

echo "\$result: ".json_encode($result, 192)."\n";

/*
$result: {
    "version": "1.0.0",
    "createdOn": 1475616439,
    "address": "39VbaiHFKTzwCvjFJczdjVQX11cVgu29g1",
    "walletId": "72abff08-6414-4d91-adfc-509cbc26e53f",
    "network": "livenet",
    "isChange": false,
    "path": "m/0/0",
    "publicKeys": [
        "03de0c5325e83ca7e66419edaf4b6b8af8a707858128f8be01b9d7c3bab48d3768",
        "0240efc0a6f2583c47ba3407d2ad83fed26421dddad0b974a6cd7d8b97007cd701"
    ],
    "type": "P2SH",
    "hasActivity": null
}
*/