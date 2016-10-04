<?php

use Tokenly\CopayClient\CopayWallet;
use \PHPUnit_Framework_Assert as PHPUnit;

/*
* 
*/
class WalletMnemonicTest extends \PHPUnit_Framework_TestCase
{

    public function testNewWalletWithMnemonic() {
        $mnemonic = 'coast raw damp visit burden rent pet permit harbor opera panda peanut';
        $wallet = CopayWallet::newFromMnemonic($mnemonic, 'PLATFORMSEED1', 'WALLETSEED1');

        PHPUnit::assertEquals(
            'xprv9s21ZrQH143K3JhzZzvYxCR6HypiKf21vz847XLvX2awA2p3w7pBSAgGExVBJtfyhuLVoTxjDzS5Ja12dn2b2gSqQZB2qnnKzug7GNCLZkX',
            $wallet['xPrivKey']->toExtendedKey()
        );

        PHPUnit::assertEquals(
            'xpub6DWUhnq4Y2ZRdq8LQA93fJsoVNreoPt4UYnpNDN8kvHY2MLKnZpQVfg9ww5sbFQL1CVvSmDRG61o6uXGk3yw2PioTxMZpMXQUU7sNkz64BE',
            $wallet['xPubKey']->toExtendedKey()
        );

        PHPUnit::assertEquals(
            '80ef3d9091f8bed73082e05aa197e677700858434b0935783cbf298f4e7b3aac',
            $wallet['copayerId']
        );

        PHPUnit::assertEquals(
            'eaa6280be8929fb5fb5c5ff9d7df9dd90b13f05f8a21925db8de6ad78a83bdc7',
            $wallet['requestPrivKey']->getHex()
        );

        PHPUnit::assertEquals(
            '02bf3fd9d036171dbf30c1420f4ba1000db618318bab3997f3b5ef8367c7f23a15',
            $wallet['requestPubKey']->getHex()
        );

        PHPUnit::assertEquals(
            'b3c9be9319c7e6d991d20c4f0fee9c720002d9f3b3b84e5a9701275ab7f077a7',
            $wallet['entropySource']->getHex()
        );

        PHPUnit::assertEquals(
            'XqGll21lC00kIUKokXie3Q==',
            $wallet['personalEncryptingKey']
        );

        PHPUnit::assertEquals(
            'f2f655400695fa5036bb5049e61a41402314274e6f5647cbcb7b5218b6a5943d',
            $wallet['walletPrivKey']->getHex()
        );
        PHPUnit::assertEquals(
            '02a4086a012b25e4e8c4d09b22cf6e11a09f43d2976c7d0df968881bc32c48fb40',
            $wallet['walletPubKey']->getHex()
        );

        PHPUnit::assertEquals(
            '0G5pVC9g97fCy9usplE3Jw==',
            $wallet['sharedEncryptingKey']
        );


        PHPUnit::assertEquals(
            $mnemonic,
            $wallet['mnemonic']
        );

        PHPUnit::assertEquals(
            '3WfZP3YrVVSxS1N5rZqHBbL5MzpbWaSYLn9QDsxx3sGpFLrJHVfqAtiexfdxUr5nvcVas4BgxQL',
            $wallet->getCopayerSecretInvitationCode('14569be9-8da3-489b-bc6f-d12d08fd4546')
        );

    }


    public function testGenerateWalletMnemonic() {
        $wallet = CopayWallet::newWalletFromPlatformSeedAndWalletSeed('PLATFORMSEED1', 'WALLETSEED1');
        PHPUnit::assertEquals(
            'swallow proof volcano visual excite drink copper sorry lock inject forward notable hope umbrella convince badge piece wedding primary future morning list brick top',
            $wallet['mnemonic']
        );
    }


}

/*
{
    "account": 0,
    "addressType": "P2SH",
    "copayerId": "80ef3d9091f8bed73082e05aa197e677700858434b0935783cbf298f4e7b3aac",
    "copayerName": "Devon",
    "derivationStrategy": "BIP44",
    "entropySource": "b3c9be9319c7e6d991d20c4f0fee9c720002d9f3b3b84e5a9701275ab7f077a7",
    "m": 2,
    "mnemonic": "coast raw damp visit burden rent pet permit harbor opera panda peanut",
    "mnemonicHasPassphrase": false,
    "n": 2,
    "network": "livenet",
    "personalEncryptingKey": "VU8AnyKj08gpo3hDAUPF4A==",
    "publicKeyRing": [
        {
            "requestPubKey": "02bf3fd9d036171dbf30c1420f4ba1000db618318bab3997f3b5ef8367c7f23a15",
            "xPubKey": "xpub6DWUhnq4Y2ZRdq8LQA93fJsoVNreoPt4UYnpNDN8kvHY2MLKnZpQVfg9ww5sbFQL1CVvSmDRG61o6uXGk3yw2PioTxMZpMXQUU7sNkz64BE"
        }
    ],
    "requestPrivKey": "eaa6280be8929fb5fb5c5ff9d7df9dd90b13f05f8a21925db8de6ad78a83bdc7",
    "requestPubKey": "02bf3fd9d036171dbf30c1420f4ba1000db618318bab3997f3b5ef8367c7f23a15",
    "sharedEncryptingKey": "Kj0nrJ5yYh7tFoJ0bR8gQw==",
    "walletId": "14569be9-8da3-489b-bc6f-d12d08fd4546",
    "walletName": "Two-3",
    "walletPrivKey": "d3e839597fede57bb67311a058f1669511c0f8a788f58e96fa49ae588bbd4af1",
    "xPrivKey": "xprv9s21ZrQH143K3JhzZzvYxCR6HypiKf21vz847XLvX2awA2p3w7pBSAgGExVBJtfyhuLVoTxjDzS5Ja12dn2b2gSqQZB2qnnKzug7GNCLZkX",
    "xPubKey": "xpub6DWUhnq4Y2ZRdq8LQA93fJsoVNreoPt4UYnpNDN8kvHY2MLKnZpQVfg9ww5sbFQL1CVvSmDRG61o6uXGk3yw2PioTxMZpMXQUU7sNkz64BE"
}
*/