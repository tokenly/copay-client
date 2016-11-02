#!/usr/bin/env php
<?php 

// this only publishes an unsigned transaction
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
    $result = $copay_client->getTransactionProposal($wallet, $proposal_id);

} catch (CopayException $e) {
    echo "Error ".$e->getCode().": ".$e->getMessage()." (".$e->getCopayStatusCode().")\n";
    exit(1);
}

echo "\$result: ".json_encode($result, 192)."\n";


/*
{
    "version": 3,
    "createdOn": 1478015629,
    "id": "7ad91da0-f4d3-4768-8002-4e395169d252",
    "walletId": "3606b655-1e35-46ea-b533-98769db101b1",
    "creatorId": "e323366a2cba4f4961d881e418a95b942fffc2ecc2e86a5f37735766f4cc76ba",
    "network": "livenet",
    "outputs": [
        {
            "amount": 5430,
            "toAddress": "1Aq4MVsUzPNQsKmiLL9Fy2pKvfJ9WWkStw",
            "message": "{\"iv\":\"Kx/ujS6HeR5tvHbromx4jQ==\",\"v\":1,\"iter\":1,\"ks\":128,\"ts\":64,\"mode\":\"ccm\",\"adata\":\"\",\"cipher\":\"aes\",\"ct\":\"ITmzPxuG9Msz3bZg0zm3s7wlKAtsUcUAjIrvnAY=\"}"
        },
        {
            "amount": 0,
            "script": "6a1c235122d9a1180d5f8cad585b8db7d436fb9691ad6f69cb46c8f1175f"
        }
    ],
    "amount": 5430,
    "message": "{\"iv\":\"Kx/ujS6HeR5tvHbromx4jQ==\",\"v\":1,\"iter\":1,\"ks\":128,\"ts\":64,\"mode\":\"ccm\",\"adata\":\"\",\"cipher\":\"aes\",\"ct\":\"ITmzPxuG9Msz3bZg0zm3s7wlKAtsUcUAjIrvnAY=\"}",
    "payProUrl": null,
    "changeAddress": {
        "version": "1.0.0",
        "createdOn": 1477755414,
        "address": "3NHozEeYbtoy9HDTW7F2t8HvzoSyrqodWc",
        "walletId": "3606b655-1e35-46ea-b533-98769db101b1",
        "network": "livenet",
        "isChange": false,
        "path": "m/0/0",
        "publicKeys": [
            "030279ef7cbe72238065c2aa28b77ab8b90a7195b930c1b39f5c482958620d201f",
            "0236d40074887286c6bf1843702c77b47dc1fcc92e99532b2cccb58355c6df4de7"
        ],
        "type": "P2SH",
        "hasActivity": null
    },
    "inputs": [
        {
            "txid": "e5690e306cc1c3b32c72a8567161fd07d2eed6d1a6f570dea737daee1de981f8",
            "vout": 2,
            "address": "3NHozEeYbtoy9HDTW7F2t8HvzoSyrqodWc",
            "scriptPubKey": "a914e1f71c422899ef7efe627dea65de08f0d00a106087",
            "satoshis": 15430,
            "confirmations": 462,
            "locked": false,
            "path": "m/0/0",
            "publicKeys": [
                "030279ef7cbe72238065c2aa28b77ab8b90a7195b930c1b39f5c482958620d201f",
                "0236d40074887286c6bf1843702c77b47dc1fcc92e99532b2cccb58355c6df4de7"
            ]
        }
    ],
    "walletM": 2,
    "walletN": 2,
    "requiredSignatures": 2,
    "requiredRejections": 1,
    "status": "broadcasted",
    "txid": "5db796697240c6014a9de792d6cc546b323fafa4a243e127cb5a8b7774c03df4",
    "broadcastedOn": 1478015643,
    "inputPaths": [
        "m/0/0"
    ],
    "actions": [
        {
            "version": "1.0.0",
            "createdOn": 1478015630,
            "copayerId": "e323366a2cba4f4961d881e418a95b942fffc2ecc2e86a5f37735766f4cc76ba",
            "type": "accept",
            "signatures": [
                "3044022068a12359e99cc25f6a9e2665e18b34c942fb2ba369551f021ee3735afd4ea16e02201586f28c361ddb2c571710908ce6d15d5b7f748902fb1d0a597f7a14b6c60442"
            ],
            "xpub": "xpub6BjWZ7kPJih3Wwz2S5ngwDHJPRg32pfFwvTUff7d47gTyEjyXpYPEbkc2cCGrpvk8Lo4qGMSjECExXbf2yWHkrZw27XtUd1d32b6yffwwQA",
            "comment": null,
            "copayerName": "{\"iv\":\"kKPU6C0dZ64f0sXU8leCLQ==\",\"v\":1,\"iter\":1,\"ks\":128,\"ts\":64,\"mode\":\"ccm\",\"adata\":\"\",\"cipher\":\"aes\",\"ct\":\"gd6JV4UkoXZ1Gv9tFGekm4UnYg==\"}"
        },
        {
            "version": "1.0.0",
            "createdOn": 1478015642,
            "copayerId": "3c200abd987c67643e2b3c9ea451ba31bcbd29ef647fb29569f1321788728260",
            "type": "accept",
            "signatures": [
                "3044022052a9c628af9e7dbe4da39084dee7cd095bc96e56e71d514d32512d12f85c31cc02204e88296301539e47dbbdc2f5bcd227c033b7ad7e82caec350adb17f0f04d0b85"
            ],
            "xpub": "xpub6CGiahpHfECP1NeS4uymhfjR8j2kFJYUtzR9BSraKMrfNTvSVJZvek2pc2oj8MTQonQisSCZwQ3bqU16VB3DQeSrGrCnwRvWzWUMR9yVvDw",
            "comment": null,
            "copayerName": "{\"iv\":\"4DgakQLpjsIbDoMVIspmXA==\",\"v\":1,\"iter\":1,\"ks\":128,\"ts\":64,\"mode\":\"ccm\",\"adata\":\"\",\"cipher\":\"aes\",\"ct\":\"y2xTNrcRy/Dwhxnysg==\"}"
        }
    ],
    "outputOrder": [
        0,
        1,
        2
    ],
    "fee": 10000,
    "feeLevel": null,
    "feePerKb": 25500,
    "excludeUnconfirmedUtxos": false,
    "addressType": "P2SH",
    "customData": {
        "isCounterparty": true,
        "counterparty": {
            "token": "SOUP",
            "quantity": 200000000,
            "quantityFloat": 2,
            "amountStr": "2 SOUP",
            "divisible": true
        }
    },
    "proposalSignature": "304402207a84120c67254e3c066c0a5ae860b82ff103f9ed57a49a931cd20318da3f632a022026cd8cd72d0c135e9e1f33c5767effad97e5540e3a6b20cd4c9fa8f691f4cba7",
    "proposalSignaturePubKey": null,
    "proposalSignaturePubKeySig": null,
    "derivationStrategy": "BIP44",
    "creatorName": "{\"iv\":\"kKPU6C0dZ64f0sXU8leCLQ==\",\"v\":1,\"iter\":1,\"ks\":128,\"ts\":64,\"mode\":\"ccm\",\"adata\":\"\",\"cipher\":\"aes\",\"ct\":\"gd6JV4UkoXZ1Gv9tFGekm4UnYg==\"}"
}

*/
