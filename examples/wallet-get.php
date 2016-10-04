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
$shared_encryption_service = (new EncryptionServiceClient('http://127.0.0.1:8088'))->withEncryptionKey($wallet['sharedEncryptingKey']);
$personal_encryption_service = (new EncryptionServiceClient('http://127.0.0.1:8088'))->withEncryptionKey($wallet['personalEncryptingKey']);

$copay_client = new CopayClient('https://pockets-service.tokenly.com/bws/api');
$copay_client->withSharedEncryptionService($shared_encryption_service);
$copay_client->withPersonalEncryptionService($personal_encryption_service);

try {
    $result = $copay_client->getWallet($wallet['copayerId'], $wallet['requestPrivKey']);
} catch (CopayException $e) {
    echo "Error ".$e->getCode().": ".$e->getMessage()." (".$e->getCopayStatusCode().")\n";
    exit(1);
}

echo "\$result: ".json_encode($result, 192)."\n";


// calculate the secret code
$invitation_code = $wallet->getCopayerSecretInvitationCode($result['wallet']['id']);
echo "Copayer secret invitation code:\n$invitation_code\n";
