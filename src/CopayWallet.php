<?php

namespace Tokenly\CopayClient;

use ArrayAccess;
use BitWasp\Bitcoin\Base58;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\KeyInterface;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKey;
use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKeyFactory;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39SeedGenerator;
use BitWasp\Bitcoin\Mnemonic\MnemonicFactory;
use BitWasp\Buffertools\Buffer;
use Exception;

/*
* CopayWallet
*/
class CopayWallet implements ArrayAccess
{

    const PUBLIC_KEY  = 1;
    const PRIVATE_KEY = 2;

    protected $credentials = [];

    protected $root_entropy_key        = null; // the master HD key that builds all other keys
    protected $master_priv_hd_key_seed = null; // the seed for the master hd key

    public static function newWalletFromPlatformSeedAndWalletSeed($platform_seed, $wallet_seed) {
        $wallet = new CopayWallet();
        $wallet->initEntropyFromSeeds($platform_seed, $wallet_seed);
        return $wallet;
    }

    public static function newFromMnemonic($mnemonic, $platform_seed, $wallet_seed) {
        $wallet = new CopayWallet();
        $wallet->initEntropyFromMnemonic($mnemonic, $platform_seed, $wallet_seed);
        return $wallet;
    }

    // ------------------------------------------------------------------------

    public function export() {
        return [
            'mnemonic'              => $this['mnemonic'],
            'entropySource'         => $this->serializeBuffer($this['entropySource']),
            'xPrivKey'              => $this->serializeHDKey($this['xPrivKey']),
            'xPubKey'               => $this->serializeHDKey($this['xPubKey']),  // this is the address generation public key
            'requestPrivKey'        => $this->serializeKey($this['requestPrivKey']),
            'requestPubKey'         => $this->serializeKey($this['requestPubKey']),
            'personalEncryptingKey' => $this['personalEncryptingKey'],
            'copayerId'             => $this['copayerId'],
            'sharedEncryptingKey'   => $this['sharedEncryptingKey'],
            'walletPrivKey'         => $this->serializeKey($this['walletPrivKey']),
        ];
    }

    public function getCopayerSecretInvitationCode($wallet_id) {
        $wallet_id_hex = str_replace('-', '', $wallet_id);
        $base58_string = str_pad(Base58::encode(Buffer::hex($wallet_id_hex)), 22, '0', STR_PAD_RIGHT);
        return $base58_string.$this['walletPrivKey']->toWif().'L';
    }


    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            throw new Exception("Undefined offset ".json_encode($offset, 192), 1);
        } else {
            $this->credentials[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($this->credentials[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->credentials[$offset]);
    }

    public function offsetGet($offset) {
        if (isset($this->credentials[$offset])) {
            return $this->credentials[$offset];
        }

        $method = "buildCredential_{$offset}";
        if (method_exists($this, $method)) {
            $this->credentials[$offset] = call_user_func([$this, $method]);
            return $this->credentials[$offset];
        }

        return null;
    }

    // ------------------------------------------------------------------------

    protected function __construct() {
    }

    protected function initRootEntropyKeyFromSeeds($platform_seed, $wallet_seed) {
        $this->root_entropy_key = HierarchicalKeyFactory::fromEntropy(new Buffer($platform_seed.$wallet_seed));
    }
    
    protected function initEntropyFromSeeds($platform_seed, $wallet_seed) {
        $this->initRootEntropyKeyFromSeeds($platform_seed, $wallet_seed);

        // build a mnemonic seed from the root entropy key
        $bip39 = MnemonicFactory::bip39();
        $mnemonic = $bip39->entropyToMnemonic($this->root_entropy_key->derivePath("m/1'/0")->getPrivateKey()->getBuffer());

        $this->initMasterPrivateHDKeySeedFromMnemonic($mnemonic);
    }

    protected function initEntropyFromMnemonic($mnemonic, $platform_seed, $wallet_seed, $password='') {
        $this->initRootEntropyKeyFromSeeds($platform_seed, $wallet_seed);
        $this->initMasterPrivateHDKeySeedFromMnemonic($mnemonic, $password);
    }

    protected function initMasterPrivateHDKeySeedFromMnemonic($mnemonic, $password='') {
        // build the master key from the mnemonic
        $bip39 = MnemonicFactory::bip39();
        $seedGenerator = new Bip39SeedGenerator($bip39);
        $this->master_priv_hd_key_seed = $seedGenerator->getSeed($mnemonic, $password);
        $this['mnemonic'] = $mnemonic;
    }


    protected function buildCredential_xPrivKey() {
        return HierarchicalKeyFactory::fromEntropy($this->master_priv_hd_key_seed);
    }

    protected function buildCredential_address_priv_hd_key() {
        $address_derivation = "m/44'/0'/0'";
        return $this['xPrivKey']->derivePath($address_derivation);
    }

    protected function buildCredential_xPubKey() {
        return $this['address_priv_hd_key']->toPublic();
    }

    protected function buildCredential_requestPrivKey() {
        $request_derivation = "m/1'/0";
        return $this['xPrivKey']->derivePath($request_derivation)->getPrivateKey();
    }

    protected function buildCredential_requestPubKey() {
        return $this['requestPrivKey']->getPublicKey();
    }

    protected function buildCredential_personalEncryptingKey() {
        return base64_encode($this->root_entropy_key->derivePath("m/3'/0")->getPrivateKey()->getBuffer()->slice(0, 16)->getBinary());
    }

    protected function buildCredential_walletPrivKey() {
        return $this->root_entropy_key->derivePath("m/2'/0")->getPrivateKey();
    }

    protected function buildCredential_walletPubKey() {
        return $this['walletPrivKey']->getPublicKey();
    }

    protected function buildCredential_sharedEncryptingKey() {
        return base64_encode(Hash::sha256($this['walletPrivKey']->getBuffer())->slice(0, 16)->getBinary());
    }

    protected function buildCredential_entropySource() {
        return Hash::sha256($this['requestPrivKey']->getBuffer());
    }

    protected function buildCredential_copayerId() {
        return Hash::sha256(new Buffer($this['xPubKey']->toExtendedKey()))->getHex();
    }



    // ------------------------------------------------------------------------

    protected function serializeKey(KeyInterface $key=null) {
        if ($key === null) { return null; }
        return $key->getHex();
    }

    protected function serializeHDKey(HierarchicalKey $key=null) {
        if ($key === null) { return null; }
        return $key->toExtendedKey();
    }

    protected function serializeBuffer(Buffer $buffer=null) {
        if ($buffer === null) { return null; }
        return $buffer->getHex();
    }
}
