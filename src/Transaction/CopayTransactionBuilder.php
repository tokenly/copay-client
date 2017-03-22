<?php

namespace Tokenly\CopayClient\Transaction;

use BitWasp\Bitcoin\Address\AddressFactory;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\Random\Rfc6979;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Transaction\Factory\Signer;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Buffertools\Buffer;
use Exception;
use Tokenly\CounterpartyTransactionComposer\OpReturnBuilder;
use Tokenly\CryptoQuantity\CryptoQuantity;

/*
* CopayTransactionBuilder
*/
class CopayTransactionBuilder
{

    public function buildTransactionFromProposal($transaction_proposal, $with_op_0=true) {
        $is_token_transaction = !!$transaction_proposal['customData']['isCounterparty'];
        $transaction_type = $is_token_transaction ? ($transaction_proposal['customData']['counterpartyType']) : null;

        $tx_builder = TransactionFactory::build();

        // "inputs": [
        //     {
        //         "txid": "049119c298b8035517bf781d730e2a4fbfd1f7f8e56e683387abc9c51ad0d7c6",
        //         "vout": 0,
        //         "address": "37n52EDHAH8wFLiB5qjGrjooCRmc8TmWwn",
        //         "scriptPubKey": "a91442c56bacb48b9bbcad806edcadd191612f4b513d87",
        //         "satoshis": 750000,
        //         "publicKeys": [
        //             "026442d3dd30a0b7ce47a38c09e8edd8859cc498c8d640abd519e9df0b27452738",
        //             "0262ad339be37fe729998db9fe75009b07e325b5ad854c483ea67c996db2748834"
        //         ]
        //     }
        // ],
        $input_amount = 0;
        foreach ($transaction_proposal['inputs'] as $input) {
            // build the redeem script
            $redeem_script = $this->createRedeemScript($input, $transaction_proposal);

            // create a script that pushes the redeem script
            //  this appears to be a quirk of bitcore
            $script_creator = ScriptFactory::create();
            if ($with_op_0) {
                $script_creator->op('OP_0');
                $script_creator->push($redeem_script->getBuffer());
                $pubkey_script = $script_creator->getScript();
            } else {
                $pubkey_script = $redeem_script;
            }
            // echo "\$pubkey_script: ".$pubkey_script->getHex()."\n";

            $tx_builder->input($input['txid'], $input['vout'], $pubkey_script);
            $input_amount += $input['satoshis'];
        }

        // outputs
        // "outputs": [
        //     {
        //         "amount": 50000,
        //         "toAddress": "1H42mKvwutzE4DAip57tkAc9KEKMGBD2bB"
        //     }
        // ],
        $output_offsets = $transaction_proposal['outputOrder'];


        // calculate change
        //   "fee": 22454,
        $spent_amount = 0;
        foreach($transaction_proposal['outputs'] as $output) {
            $spent_amount += $output['amount'];
        }
        $change_amount = $input_amount - $spent_amount - $transaction_proposal['fee'];


        // need to treat change as the last output for dealing with output order

        $change_offset = count($output_offsets) - 1;
        $spent_amount = 0;
        foreach($output_offsets as $output_offset) {
            if ($output_offset === $change_offset) {
                // change
                if ($change_amount > 0) {
                    // "changeAddress": {
                    //     "address": "37n52EDHAH8wFLiB5qjGrjooCRmc8TmWwn",
                    //     "walletId": "6ac234f6-92b6-4f47-98ed-fda010c496f0",
                    //     "publicKeys": [
                    //         "026442d3dd30a0b7ce47a38c09e8edd8859cc498c8d640abd519e9df0b27452738",
                    //         "0262ad339be37fe729998db9fe75009b07e325b5ad854c483ea67c996db2748834"
                    //     ],
                    //     "type": "P2SH",
                    // },
                    $tx_builder->payToAddress($change_amount, AddressFactory::fromString($transaction_proposal['changeAddress']['address']));
                }
            } else {
                // non-change output
                $output = $transaction_proposal['outputs'][$output_offset];
                
                if ($is_token_transaction AND $output_offset === ($change_offset - 1)) {
                    // build the OP_RETURN output
                    $tx_builder->output(0, ScriptFactory::fromHex($output['script']));
                } else {
                    // regular BTC send - handles 1xxx and 3xxx addresses
                    $tx_builder->payToAddress($output['amount'], AddressFactory::fromString($output['toAddress']));
                }
            }
        }


        $transaction = $tx_builder->get();
        return $transaction;
    }

    public function buildTransactionSignaturesFromProposal($address_hd_priv_key, $transaction_proposal) {
        $ec = Bitcoin::getEcAdapter();

        // -----------------------------------
        $derived_key_cache = [];
        $fn_getDerivedKey = function($path) use ($address_hd_priv_key, &$derived_key_cache) {
            if (!isset($derived_key_cache[$path])) {
                $derived_key_cache[$path] = $address_hd_priv_key->derivePath($path)->getPrivateKey();
            }
            return $derived_key_cache[$path];
        };
        // -----------------------------------

        // build the spend transaction
        //   and a signer for it
        $spend_transaction = $this->buildTransactionFromProposal($transaction_proposal, false);
        $signer = new Signer($spend_transaction);

        foreach ($transaction_proposal['inputs'] as $input_offset => $input) {
            // build the redeem script
            $redeem_script = $this->createRedeemScript($input, $transaction_proposal);

            // sign this input
            $priv_key = $fn_getDerivedKey($input['path']);
            $output = new TransactionOutput($input['satoshis'], ScriptFactory::scriptPubKey()->payToAddress(AddressFactory::fromString($input['address'])));
            $signer->sign($input_offset, $priv_key, $output, $redeem_script);
        }

        $signed_transaction = $signer->get();

        // get all the signatures
        $signatures = [];
        foreach ($signed_transaction->getInputs() as $input) {
            $script = $input->getScript();
            foreach ($script->getScriptParser() as $offset => $op) {
                if ($offset == 1) {
                    $signature = $op->getData()->getHex();

                    // trim off the SIGHASH flags at the end of the signature
                    $length = 4 + 2 * hexdec(substr($signature, 2, 2));
                    $signature = substr($signature, 0, $length);

                    $signatures[] = $signature;
                    break;
                }
            }
        }

        return $signatures;
    }

    // ------------------------------------------------------------------------

    protected function createRedeemScript($input, $transaction_proposal) {
        $public_keys = [];
        foreach ($input['publicKeys'] as $public_key_hex) {
            $public_keys[] = PublicKeyFactory::fromHex($public_key_hex);
        }
        return ScriptFactory::p2sh()->multisig($transaction_proposal['walletM'], $public_keys);
    }
}
