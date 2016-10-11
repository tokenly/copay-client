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
    $result = $copay_client->getWallet($wallet);
} catch (CopayException $e) {
    echo "Error ".$e->getCode().": ".$e->getMessage()." (".$e->getCopayStatusCode().")\n";
    exit(1);
}

echo "\$result: ".json_encode($result, 192)."\n";


// calculate the secret code
$invitation_code = $wallet->getCopayerSecretInvitationCode($result['wallet']['id']);
echo "Copayer secret invitation code:\n$invitation_code\n";

/*
{
    "wallet": {
        "version": "1.0.0",
        "createdOn": 1475598272,
        "id": "72abff08-6414-4d91-adfc-509cbc26e53f",
        "name": "{\"iv\":\"rd4soA+NYsc3ah+4mj/HKw==\",\"v\":1,\"iter\":1,\"ks\":128,\"ts\":64,\"mode\":\"ccm\",\"adata\":\"\",\"cipher\":\"aes\",\"ct\":\"SbhHwk3+IBDouT8lHkXnTf+kUyd5rw==\"}",
        "m": 2,
        "n": 2,
        "singleAddress": true,
        "status": "complete",
        "copayers": [
            {
                "version": 2,
                "createdOn": 1475605970,
                "id": "5b8d82daf6b45d10cc83619accb8cf9ce04101513ba2292b14886c7055f1ce26",
                "name": "{\"iv\":\"Q4xx34p5cEXoVRc5tqYPBQ==\",\"v\":1,\"iter\":1,\"ks\":128,\"ts\":64,\"mode\":\"ccm\",\"adata\":\"\",\"cipher\":\"aes\",\"ct\":\"uOJkVKreP2fnIk+W4M7/IeUS2DmCaNg=\"}",
                "requestPubKeys": [
                    {
                        "key": "02b01a396508903e4bd269ebc49cc770c4a4b775fd6d36c5f2c600799def55f46d",
                        "signature": "3045022100912bea2d9ba884c79d052fbdfe4f303fa9cd6683c792e6720a914509db3d3e6a02204be6b0ad0f67360fbe24dd742fe7830f60168504c676e9c1a99d967319f36c6f"
                    }
                ]
            },
            {
                "version": 2,
                "createdOn": 1475616438,
                "id": "987773920a42ba771a9cd3d3bb4f109dbe515e380dcb368971cfb1935cb1c16e",
                "name": "{\"iv\":\"HHd8YNHL6E2stMG926dUeQ==\",\"v\":1,\"iter\":1,\"ks\":128,\"ts\":64,\"mode\":\"ccm\",\"adata\":\"\",\"cipher\":\"aes\",\"ct\":\"1rZRlhOk1CFSvbrPCw==\"}",
                "requestPubKeys": [
                    {
                        "key": "020286c2d43a9c8d9956d6a677b13b3767b311ee4c5ebe21834ae2f4f932086c78",
                        "signature": "3044022011708a30341d0efb41de0559b86c3639eaaa7dfa453464bc182212ef4cece04c02207dc5bd744b362b097a6a65c210395662c41ab0b381659a8a9378e6742555d8d0"
                    }
                ]
            }
        ],
        "network": "livenet",
        "derivationStrategy": "BIP44",
        "addressType": "P2SH",
        "scanStatus": "success"
    },
    "pendingTxps": [],
    "preferences": [],
    "balance": {
        "totalAmount": 0,
        "lockedAmount": 0,
        "totalConfirmedAmount": 0,
        "lockedConfirmedAmount": 0,
        "availableAmount": 0,
        "availableConfirmedAmount": 0,
        "byAddress": []
    }
}
*/