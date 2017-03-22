#!/usr/bin/env php
<?php 

// this only proposes a transaction
//   you probably want tx-propose-publish-and-sign.php

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
    
    $amount = isset($argv[2]) ? $argv[2] : null;
    if (!$amount) { throw new Exception("amount is required", 1); }
    
    $token = isset($argv[3]) ? $argv[3] : null;
    if (!$token) { throw new Exception("token is required", 1); }
    
    $divisible = isset($argv[4]) ? (substr(strtolower($argv[4]),0,1) == 't' OR $argv[4] == 1) : null;
    if ($divisible === null) { $divisible = true; }
    
    $description = isset($argv[5]) ? $argv[5] : null;
    if (!$description) { $description = null; }
    
    $fee_per_kb = isset($argv[6]) ? $argv[6] : null;
    if (!$fee_per_kb) { $fee_per_kb = 0.00051; }
} catch (Exception $e) {
    echo "Error: ".$e->getMessage()."\n\n";
    echo "Usage: $argv[0] <seed> <amount> <token> <divisible> <description> <feePerKB>]\n";
    exit(1);
}

$wallet = CopayWallet::newWalletFromPlatformSeedAndWalletSeed($platform_seed, $wallet_seed);
$shared_encryption_service = (new EncryptionServiceClient('http://127.0.0.1:8088'))->withEncryptionKey($wallet['sharedEncryptingKey']);
// $personal_encryption_service = (new EncryptionServiceClient('http://127.0.0.1:8088'))->withEncryptionKey($wallet['personalEncryptingKey']);

$copay_client = new CopayClient('https://pockets-service.tokenly.com/bws/api');
$copay_client->withSharedEncryptionService($shared_encryption_service);
// $copay_client->withPersonalEncryptionService($personal_encryption_service);

try {
    $SATOSHI = 100000000;
    $args = [
        'counterpartyType' => 'issuance',
        'amountSat'        => intval($amount * $SATOSHI),
        'token'            => $token,
        'divisible'        => $divisible,
        'description'      => $description,
        'feePerKBSat'      => intval($fee_per_kb * $SATOSHI),
    ];
    $result = $copay_client->proposeTransaction($wallet, $args);
} catch (CopayException $e) {
    echo "Error ".$e->getCode().": ".$e->getMessage()." (".$e->getCopayStatusCode().")\n";
    exit(1);
}

echo "\$result: ".json_encode($result, 192)."\n";


/*
{
    "version": 3,
    "createdOn": 1490193158,
    "id": "d88e3c6c-bff3-4c75-82f4-775622e87c5d",
    "walletId": "6ac234f6-92b6-4f47-98ed-fda010c496f0",
    "creatorId": "777a3aea57dd99d80f2ebbe1f2a05580dcf5e5f3fcf1179115734c0a38c26871",
    "message": null,
    "changeAddress": {
        "version": "1.0.0",
        "createdOn": 1475726857,
        "address": "37n52EDHAH8wFLiB5qjGrjooCRmc8TmWwn",
        "walletId": "6ac234f6-92b6-4f47-98ed-fda010c496f0",
        "network": "livenet",
        "isChange": false,
        "path": "m/0/0",
        "publicKeys": [
            "026442d3dd30a0b7ce47a38c09e8edd8859cc498c8d640abd519e9df0b27452738",
            "0262ad339be37fe729998db9fe75009b07e325b5ad854c483ea67c996db2748834"
        ],
        "type": "P2SH",
        "hasActivity": null
    },
    "outputs": [
        {
            "amount": 0,
            "script": "6a3248c994d5c6679a3dbec8ae157237f1e5b88e8390985507e993aa78e0637a26e84ee8af5214292ff43bc43a3e745ff34185de"
        }
    ],
    "outputOrder": [
        0,
        1
    ],
    "walletM": 2,
    "walletN": 2,
    "requiredSignatures": 2,
    "requiredRejections": 1,
    "status": "temporary",
    "actions": [],
    "feePerKb": 51000,
    "excludeUnconfirmedUtxos": false,
    "addressType": "P2SH",
    "customData": {
        "isCounterparty": true,
        "counterparty": {
            "token": "A10203205023283554629",
            "quantity": 10000000000,
            "quantityFloat": 100,
            "amountStr": "100 A10203205023283554629",
            "divisible": true
        }
    },
    "amount": 0,
    "inputs": [
        {
            "txid": "a550f451fcd77ad9e3bfc777ee5a0edaed475f96c47af99020fc5d7c6b12fdf1",
            "vout": 0,
            "address": "37n52EDHAH8wFLiB5qjGrjooCRmc8TmWwn",
            "scriptPubKey": "a91442c56bacb48b9bbcad806edcadd191612f4b513d87",
            "satoshis": 500000,
            "confirmations": 24368,
            "locked": false,
            "path": "m/0/0",
            "publicKeys": [
                "026442d3dd30a0b7ce47a38c09e8edd8859cc498c8d640abd519e9df0b27452738",
                "0262ad339be37fe729998db9fe75009b07e325b5ad854c483ea67c996db2748834"
            ]
        }
    ],
    "inputPaths": [
        "m/0/0"
    ],
    "fee": 18156
}

*/