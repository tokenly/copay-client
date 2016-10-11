#!/usr/bin/env php
<?php 

use BitWasp\Bitcoin\Script\ScriptFactory;


require __DIR__.'/../vendor/autoload.php';

$platform_seed = getenv('PLATFORM_WALLET_SEED'); if ($platform_seed === false) { $platform_seed = 'TESTPLATFORMSEED'; }

// get args
$script_hex = isset($argv[1]) ? $argv[1] : null;
if (!$script_hex) { throw new Exception("script_hex is required", 1); }

$script = ScriptFactory::fromHex($script_hex);
echo $script->getScriptParser()->getHumanReadable()."\n";
