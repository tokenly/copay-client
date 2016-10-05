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
    $result = $copay_client->getAddress($wallet['copayerId'], $wallet['requestPrivKey']);
} catch (CopayException $e) {
    echo "Error ".$e->getCode().": ".$e->getMessage()." (".$e->getCopayStatusCode().")\n";
    exit(1);
}

echo "\$result: ".json_encode($result, 192)."\n";

