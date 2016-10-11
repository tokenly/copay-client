#!/usr/bin/env php
<?php 

use Tokenly\CopayClient\CopayClient;
use Tokenly\CopayClient\CopayException;
use Tokenly\CopayClient\CopayWallet;
use Tokenly\CopayClient\EncryptionService\EncryptionServiceClient;

require __DIR__.'/../vendor/autoload.php';

$platform_seed = getenv('PLATFORM_WALLET_SEED'); if ($platform_seed === false) { $platform_seed = 'TESTPLATFORMSEED'; }

// get args
try {
    $wallet_seed = isset($argv[1]) ? $argv[1] : null;
    if (!$wallet_seed) { throw new Exception("wallet_seed is required", 1); }
    $proposal_id = isset($argv[2]) ? $argv[2] : null;
    if (!$proposal_id) { throw new Exception("proposal_id is required", 1); }
} catch (Exception $e) {
    echo "Error: ".$e->getMessage()."\n\n";
    echo "Usage: $argv[0] <seed> <proposal_id>\n";
    exit(1);
}

$wallet = CopayWallet::newWalletFromPlatformSeedAndWalletSeed($platform_seed, $wallet_seed);
$shared_encryption_service = (new EncryptionServiceClient('http://127.0.0.1:8088'))->withEncryptionKey($wallet['sharedEncryptingKey']);
// $personal_encryption_service = (new EncryptionServiceClient('http://127.0.0.1:8088'))->withEncryptionKey($wallet['personalEncryptingKey']);

$copay_client = new CopayClient('https://pockets-service.tokenly.com/bws/api');
$copay_client->withSharedEncryptionService($shared_encryption_service);
// $copay_client->withPersonalEncryptionService($personal_encryption_service);

try {
    $result = $copay_client->deleteTransactionProposal($wallet, $proposal_id);
} catch (CopayException $e) {
    echo "Error ".$e->getCode().": ".$e->getMessage()." (".$e->getCopayStatusCode().")\n";
    exit(1);
}

echo "\$result: ".json_encode($result, 192)."\n";


/*

*/
