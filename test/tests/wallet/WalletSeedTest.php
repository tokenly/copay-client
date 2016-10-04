<?php

use Tokenly\CopayClient\CopayWallet;
use \PHPUnit_Framework_Assert as PHPUnit;

/*
* 
*/
class WalletSeedTest extends \PHPUnit_Framework_TestCase
{


    public function testSeedWallet() {
        $wallet = CopayWallet::newWalletFromPlatformSeedAndWalletSeed('PLATFORMSEED1', 'WALLETSEED1');
        PHPUnit::assertEquals(
            'xprv9s21ZrQH143K4VRxxJneFV3t5kGVXG9qZt6sCKWcaSjxK5EmgHUZC2Y9CQGZ63wUSWNm4bapC5WmTKSXcpmhVuDqShcWDDzYeTE45ia9esg',
            $wallet['xPrivKey']->toExtendedPrivateKey()
        );
        PHPUnit::assertEquals(
            'f2f655400695fa5036bb5049e61a41402314274e6f5647cbcb7b5218b6a5943d',
            $wallet['walletPrivKey']->getHex()
        );
        PHPUnit::assertEquals(
            'swallow proof volcano visual excite drink copper sorry lock inject forward notable hope umbrella convince badge piece wedding primary future morning list brick top',
            $wallet['mnemonic']
        );



        // changing seed changes keys
        $wallet = CopayWallet::newWalletFromPlatformSeedAndWalletSeed('PLATFORMSEED1', 'WALLETSEED2');
        PHPUnit::assertNotEquals(
            'xprv9s21ZrQH143K4VRxxJneFV3t5kGVXG9qZt6sCKWcaSjxK5EmgHUZC2Y9CQGZ63wUSWNm4bapC5WmTKSXcpmhVuDqShcWDDzYeTE45ia9esg',
            $wallet['xPrivKey']->toExtendedPrivateKey()
        );
        $wallet = CopayWallet::newWalletFromPlatformSeedAndWalletSeed('PLATFORMSEED2', 'WALLETSEED1');
        PHPUnit::assertNotEquals(
            'xprv9s21ZrQH143K4VRxxJneFV3t5kGVXG9qZt6sCKWcaSjxK5EmgHUZC2Y9CQGZ63wUSWNm4bapC5WmTKSXcpmhVuDqShcWDDzYeTE45ia9esg',
            $wallet['xPrivKey']->toExtendedPrivateKey()
        );
        $wallet = CopayWallet::newWalletFromPlatformSeedAndWalletSeed('PLATFORMSEED1', 'WALLETSEED2');
        PHPUnit::assertNotEquals(
            'f2f655400695fa5036bb5049e61a41402314274e6f5647cbcb7b5218b6a5943d',
            $wallet['walletPrivKey']->getHex()
        );
        $wallet = CopayWallet::newWalletFromPlatformSeedAndWalletSeed('PLATFORMSEED2', 'WALLETSEED1');
        PHPUnit::assertNotEquals(
            'f2f655400695fa5036bb5049e61a41402314274e6f5647cbcb7b5218b6a5943d',
            $wallet['walletPrivKey']->getHex()
        );
    }




}
