#!/usr/bin/env php
<?php 

use Tokenly\CopayClient\EncryptionService\EncryptionServiceClient;


require __DIR__.'/../vendor/autoload.php';

$ciphertext = $argv[1];
if (!$ciphertext) { throw new Exception("ciphertext is required", 1); }

$key = $argv[2];
if (!$key) { throw new Exception("key is required", 1); }


$client = new EncryptionServiceClient();
$result = $client->decrypt($ciphertext, $key);

echo "\$result:\n".$result."\n";
