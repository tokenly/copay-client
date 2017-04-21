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
// $shared_encryption_service = (new EncryptionServiceClient('http://127.0.0.1:8088'))->withEncryptionKey($wallet['sharedEncryptingKey']);
// $personal_encryption_service = (new EncryptionServiceClient('http://127.0.0.1:8088'))->withEncryptionKey($wallet['personalEncryptingKey']);

$copay_client = new CopayClient('https://pockets-service.tokenly.com/bws/api');
// $copay_client->withSharedEncryptionService($shared_encryption_service);
// $copay_client->withPersonalEncryptionService($personal_encryption_service);

try {
    $result = $copay_client->getUtxos($wallet);
} catch (CopayException $e) {
    echo "Error ".$e->getCode().": ".$e->getMessage()." (".$e->getCopayStatusCode().")\n";
    exit(1);
}

echo "\$result:\n".json_encode($result, 192)."\n";

/*
[
    {
        "txid": "ad501692ab08cf637696ce886946849455a7fb452fb0712f7488b031f5aca3a2",
        "vout": 0,
        "address": "3NHozEeYbtoy9HDTW7F2t8HvzoSyrqodWc",
        "scriptPubKey": "a914e1f71c422899ef7efe627dea65de08f0d00a106087",
        "satoshis": 500000,
        "confirmations": 4367,
        "locked": false,
        "path": "m/0/0",
        "publicKeys": [
            "030279ef7cbe72238065c2aa28b77ab8b90a7195b930c1b39f5c482958620d201f",
            "0236d40074887286c6bf1843702c77b47dc1fcc92e99532b2cccb58355c6df4de7"
        ]
    },
    {
        "txid": "053c965a0bbce226a3143d8dec0ba1e67178918f32edcb6509d376dc6ce92445",
        "vout": 0,
        "address": "3NHozEeYbtoy9HDTW7F2t8HvzoSyrqodWc",
        "scriptPubKey": "a914e1f71c422899ef7efe627dea65de08f0d00a106087",
        "satoshis": 5430,
        "confirmations": 9808,
        "locked": false,
        "path": "m/0/0",
        "publicKeys": [
            "030279ef7cbe72238065c2aa28b77ab8b90a7195b930c1b39f5c482958620d201f",
            "0236d40074887286c6bf1843702c77b47dc1fcc92e99532b2cccb58355c6df4de7"
        ]
    },
    {
        "txid": "48a395a81c35fdef174515daf8b44204270568d568478c9e280fd522832b092d",
        "vout": 0,
        "address": "3NHozEeYbtoy9HDTW7F2t8HvzoSyrqodWc",
        "scriptPubKey": "a914e1f71c422899ef7efe627dea65de08f0d00a106087",
        "satoshis": 15430,
        "confirmations": 25878,
        "locked": false,
        "path": "m/0/0",
        "publicKeys": [
            "030279ef7cbe72238065c2aa28b77ab8b90a7195b930c1b39f5c482958620d201f",
            "0236d40074887286c6bf1843702c77b47dc1fcc92e99532b2cccb58355c6df4de7"
        ]
    },
    {
        "txid": "c19b604cb4dc0166f6d3f20386fa786289212b8962d4e442a91c601bfc69cbb9",
        "vout": 0,
        "address": "3NHozEeYbtoy9HDTW7F2t8HvzoSyrqodWc",
        "scriptPubKey": "a914e1f71c422899ef7efe627dea65de08f0d00a106087",
        "satoshis": 5430,
        "confirmations": 26399,
        "locked": false,
        "path": "m/0/0",
        "publicKeys": [
            "030279ef7cbe72238065c2aa28b77ab8b90a7195b930c1b39f5c482958620d201f",
            "0236d40074887286c6bf1843702c77b47dc1fcc92e99532b2cccb58355c6df4de7"
        ]
    }
]

*/
