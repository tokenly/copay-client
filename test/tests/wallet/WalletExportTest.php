<?php

use Tokenly\CopayClient\CopayWallet;
use \PHPUnit_Framework_Assert as PHPUnit;

/*
* 
*/
class WalletExportTest extends \PHPUnit_Framework_TestCase
{


    public function testExportWallet() {
        $wallet = CopayWallet::newWalletFromPlatformSeedAndWalletSeed('PLATFORMSEED1', 'WALLETSEED1');
        $exported_wallet = $wallet->export();
        PHPUnit::assertNotEmpty($exported_wallet['xPrivKey']);
        PHPUnit::assertNotEmpty($exported_wallet['walletPrivKey']);

        // $second_wallet = CopayWallet::newFromSerializedCredentials($exported_wallet);
        // $second_exported = $wallet->export();

        // PHPUnit::assertEquals($exported_wallet, $second_exported);
    }




}
