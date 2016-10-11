#!/usr/bin/env php
<?php 

use BitWasp\Bitcoin\Crypto\Random\Random;
use Tokenly\CopayClient\CopayClient;
use Tokenly\CopayClient\CopayException;
use Tokenly\CopayClient\CopayWallet;
use Tokenly\CopayClient\EncryptionService\EncryptionServiceClient;

require __DIR__.'/../vendor/autoload.php';

$platform_seed = getenv('PLATFORM_WALLET_SEED'); if ($platform_seed === false) { $platform_seed = 'TESTPLATFORMSEED'; }

$wallet_name = $argv[1];
if (!$wallet_name) { throw new Exception("wallet_name is required", 1); }

$copayer_name = $argv[2];
if (!$copayer_name) { throw new Exception("copayer_name is required", 1); }


$random = new Random();
$wallet_seed = $random->bytes(16)->getHex();

$new_wallet = CopayWallet::newWalletFromPlatformSeedAndWalletSeed($platform_seed, $wallet_seed);

$number_of_signatures_required = 2;
$number_of_signatures_possible = 2;

$create_wallet_args = [
    'm'      => $number_of_signatures_required,
    'n'      => $number_of_signatures_possible,
];


$shared_encryption_service = (new EncryptionServiceClient('http://127.0.0.1:8088'))->withEncryptionKey($new_wallet['sharedEncryptingKey']);

$copay_client = new CopayClient('https://pockets-service.tokenly.com/bws/api');
$copay_client->withSharedEncryptionService($shared_encryption_service);

try {
    $result = $copay_client->createAndJoinWallet($wallet, $wallet_name, $copayer_name, $create_wallet_args);
} catch (CopayException $e) {
    echo "Error ".$e->getCode().": ".$e->getMessage()." (".$e->getCopayStatusCode().")\n";
    exit(1);
}


echo "Wallet seed used to make the root private key was: ".$wallet_seed."\n";
echo "New wallet created with id: ".$result."\n";
