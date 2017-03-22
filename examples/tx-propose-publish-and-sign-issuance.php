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
    echo "Usage: $argv[0] <seed> <amount> <token> <divisible> <description> [<feePerKB>]\n";
    exit(1);
}

$wallet = CopayWallet::newWalletFromPlatformSeedAndWalletSeed($platform_seed, $wallet_seed);
$shared_encryption_service = (new EncryptionServiceClient('http://127.0.0.1:8088'))->withEncryptionKey($wallet['sharedEncryptingKey']);
$personal_encryption_service = (new EncryptionServiceClient('http://127.0.0.1:8088'))->withEncryptionKey($wallet['personalEncryptingKey']);

$copay_client = new CopayClient('https://pockets-service.tokenly.com/bws/api');
$copay_client->withSharedEncryptionService($shared_encryption_service);
$copay_client->withPersonalEncryptionService($personal_encryption_service);

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
    $result = $copay_client->proposePublishAndSignTransaction($wallet, $args);

} catch (CopayException $e) {
    echo "Error ".$e->getCode().": ".$e->getMessage()." (".$e->getCopayStatusCode().")\n";
    exit(1);
}

echo "\$result: ".json_encode($result, 192)."\n";


/*

// getTransactionProposal returns
$result: {
    "version": 3,
    "createdOn": 1475859759,
    "id": "2f7732ee-990f-478c-8c38-f02762f86c85",
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
            "confirmations": 36,
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
    "txid": null,
    "broadcastedOn": null,
    "inputPaths": [
        "m/0/0"
    ],
    "actions": [
        {
            "version": "1.0.0",
            "createdOn": 1475890032,
            "copayerId": "777a3aea57dd99d80f2ebbe1f2a05580dcf5e5f3fcf1179115734c0a38c26871",
            "type": "accept",
            "signatures": [
                "30440220035f1979d67b5d16cecd89c7b1d2d99b225f57fdcdf5b33600db6a6e5c64ae21022019a37fa078f57675095cf114755d97a8eff9a5dd041593fdad5fff60c487bffc"
            ],
            "xpub": "xpub6CpbTYCmLGBW8T4XY7Z63aFZ3dLggRU4m461RSZstTM8YamZyjD8zwquHB3ytseeUphbbvuYQer2Lzs4YsmD3NZ9vWnRQadbY5AZ5rKWHzb",
            "comment": null
        }
    ],
    "outputOrder": [
        1,
        0
    ],
    "fee": 22457,
    "feeLevel": "normal",
    "feePerKb": 63081,
    "excludeUnconfirmedUtxos": false,
    "addressType": "P2SH",
    "customData": null,
    "proposalSignature": "30450221009262c13611b076aba598a2e4d410761e6e367543a299cdfda759dcac2f259579022022896f1fc90c45149529e246da840d7a15a2bccc11c2117375761891e03ffcfe",
    "proposalSignaturePubKey": null,
    "proposalSignaturePubKeySig": null,
    "derivationStrategy": "BIP44",
    "creatorName": "{\"iv\":\"gtd7kv+3tWJbu+JZhNaebg==\",\"v\":1,\"iter\":1,\"ks\":128,\"ts\":64,\"mode\":\"ccm\",\"adata\":\"\",\"cipher\":\"aes\",\"ct\":\"Pk+jHYW7MKL2F83rRrEg\"}"
}

*/
