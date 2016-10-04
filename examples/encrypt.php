#!/usr/bin/env php
<?php 

use Tokenly\CopayClient\EncryptionService\EncryptionServiceClient;


require __DIR__.'/../vendor/autoload.php';

$plaintext = $argv[1];
if (!$plaintext) { throw new Exception("plaintext is required", 1); }

$key = $argv[2];
if (!$key) { throw new Exception("key is required", 1); }


$client = new EncryptionServiceClient();
$result = $client->encrypt($plaintext, $key);


echo "\$result:\n".$result."\n";
