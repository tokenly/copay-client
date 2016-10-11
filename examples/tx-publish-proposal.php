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
    $tx_proposal = $copay_client->getTransactionProposal($wallet, $proposal_id);
    // echo "\$tx_proposal: ".json_encode($tx_proposal, 192)."\n";

    $result = $copay_client->publishTransactionProposal($wallet, $tx_proposal);

} catch (CopayException $e) {
    echo "Error ".$e->getCode().": ".$e->getMessage()." (".$e->getCopayStatusCode().")\n";
    exit(1);
}

echo "\$result: ".json_encode($result, 192)."\n";


/*

// getTransactionProposal returns
{
    "version": 3,
    "createdOn": 1475843666,
    "id": "51df39bd-8bfd-4144-b73d-9eafcdc5dc6f",
    "walletId": "6ac234f6-92b6-4f47-98ed-fda010c496f0",
    "creatorId": "777a3aea57dd99d80f2ebbe1f2a05580dcf5e5f3fcf1179115734c0a38c26871",
    "network": "livenet",
    "outputs": [
        {
            "amount": 50000,
            "toAddress": "1H42mKvwutzE4DAip57tkAc9KEKMGBD2bB"
        }
    ],
    "amount": 50000,
    "message": null,
    "payProUrl": null,
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
    "inputs": [
        {
            "txid": "049119c298b8035517bf781d730e2a4fbfd1f7f8e56e683387abc9c51ad0d7c6",
            "vout": 0,
            "address": "37n52EDHAH8wFLiB5qjGrjooCRmc8TmWwn",
            "scriptPubKey": "a91442c56bacb48b9bbcad806edcadd191612f4b513d87",
            "satoshis": 750000,
            "confirmations": 5,
            "locked": false,
            "path": "m/0/0",
            "publicKeys": [
                "026442d3dd30a0b7ce47a38c09e8edd8859cc498c8d640abd519e9df0b27452738",
                "0262ad339be37fe729998db9fe75009b07e325b5ad854c483ea67c996db2748834"
            ]
        }
    ],
    "walletM": 2,
    "walletN": 2,
    "requiredSignatures": 2,
    "requiredRejections": 1,
    "status": "temporary",
    "inputPaths": [
        "m/0/0"
    ],
    "actions": [],
    "outputOrder": [
        0,
        1
    ],
    "fee": 22454,
    "feeLevel": "normal",
    "feePerKb": 63072,
    "excludeUnconfirmedUtxos": false,
    "addressType": "P2SH",
    "customData": null,
    "derivationStrategy": "BIP44",
    "creatorName": "{\"iv\":\"gtd7kv+3tWJbu+JZhNaebg==\",\"v\":1,\"iter\":1,\"ks\":128,\"ts\":64,\"mode\":\"ccm\",\"adata\":\"\",\"cipher\":\"aes\",\"ct\":\"Pk+jHYW7MKL2F83rRrEg\"}"
}


// publishTransactionProposal returns:
{
    "version": 3,
    "createdOn": 1475843666,
    "id": "51df39bd-8bfd-4144-b73d-9eafcdc5dc6f",
    "walletId": "6ac234f6-92b6-4f47-98ed-fda010c496f0",
    "creatorId": "777a3aea57dd99d80f2ebbe1f2a05580dcf5e5f3fcf1179115734c0a38c26871",
    "network": "livenet",
    "outputs": [
        {
            "amount": 50000,
            "toAddress": "1H42mKvwutzE4DAip57tkAc9KEKMGBD2bB"
        }
    ],
    "amount": 50000,
    "message": null,
    "payProUrl": null,
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
    "inputs": [
        {
            "txid": "049119c298b8035517bf781d730e2a4fbfd1f7f8e56e683387abc9c51ad0d7c6",
            "vout": 0,
            "address": "37n52EDHAH8wFLiB5qjGrjooCRmc8TmWwn",
            "scriptPubKey": "a91442c56bacb48b9bbcad806edcadd191612f4b513d87",
            "satoshis": 750000,
            "confirmations": 5,
            "locked": false,
            "path": "m/0/0",
            "publicKeys": [
                "026442d3dd30a0b7ce47a38c09e8edd8859cc498c8d640abd519e9df0b27452738",
                "0262ad339be37fe729998db9fe75009b07e325b5ad854c483ea67c996db2748834"
            ]
        }
    ],
    "walletM": 2,
    "walletN": 2,
    "requiredSignatures": 2,
    "requiredRejections": 1,
    "status": "pending",
    "inputPaths": [
        "m/0/0"
    ],
    "actions": [],
    "outputOrder": [
        0,
        1
    ],
    "fee": 22454,
    "feeLevel": "normal",
    "feePerKb": 63072,
    "excludeUnconfirmedUtxos": false,
    "addressType": "P2SH",
    "customData": null,
    "proposalSignature": "3045022100ceffbcede91ba573b5294de077d9e52239915158e0029542e3779e064e6933ac02206793449a2a5517feab96423cc76d3c048c65d113e1b80639be24f8bc97027329",
    "derivationStrategy": "BIP44",
    "creatorName": "{\"iv\":\"gtd7kv+3tWJbu+JZhNaebg==\",\"v\":1,\"iter\":1,\"ks\":128,\"ts\":64,\"mode\":\"ccm\",\"adata\":\"\",\"cipher\":\"aes\",\"ct\":\"Pk+jHYW7MKL2F83rRrEg\"}"
}

*/
