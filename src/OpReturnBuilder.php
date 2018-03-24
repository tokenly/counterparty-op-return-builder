<?php

namespace Tokenly\CounterpartyOpReturnBuilder;

use Tokenly\CounterpartyOpReturnBuilder\Quantity;
use Tokenly\CryptoQuantity\CryptoQuantity;
use \Exception;

/*
 * OpReturnBuilder
 */
class OpReturnBuilder
{

    const SATOSHI = 100000000;
    const B58_DIGITS = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';

    public function buildOpReturnForSend($raw_amount, $asset, $destination_string, $txid, $memo_id = null)
    {
        $amount_hex = $this->paddedRawAmountHex($raw_amount);
        $asset_hex = $this->padHexString($this->assetNameToIDHex($asset), 8);
        $destination_hex = substr($this->base58_decode($destination_string), 0, -8);

        // memo hex
        $memo_hex = '';
        if ($memo_id !== null) {
            $unpadded_memo_hex = dechex($memo_id);
            $memo_hex = $unpadded_memo_hex;
            if (strlen($unpadded_memo_hex) % 2 == 1) {
                $memo_hex = '0'.$memo_hex;
            }
        }

        $data_hex = $asset_hex . $amount_hex . $destination_hex . $memo_hex;

        return $this->assembleCounterpartyOpReturn(0x02, $data_hex, $txid);
    }

    // ------------------------------------------------------------------------

    protected function assembleCounterpartyOpReturn($type_id, $data_hex, $txid)
    {
        // construct the op_return data
        $prefix_hex = '434e545250525459'; // CNTRPRTY
        $type_hex = $this->padHexString(dechex($type_id), 1);
        $unobfuscated_op_return = $prefix_hex . $type_hex . $data_hex;

        // obfuscate with ARC4
        if ($txid === null) {
            $op_return = $unobfuscated_op_return;
        } else {
            $arc4_key = $txid;
            $op_return = bin2hex($this->arc4(hex2bin($unobfuscated_op_return), hex2bin($arc4_key)));
        }

        return $op_return;
    }

    protected function paddedRawAmountHex($raw_amount)
    {
        // normalize $raw_amount
        if ($raw_amount instanceof CryptoQuantity) {
            $amount = $raw_amount->toHex();
        } else {
            $amount = dechex(round($raw_amount * self::SATOSHI));
        }

        return $this->padHexString($amount, 8);
    }

    protected function padHexString($hex_string, $bytes)
    {
        return str_pad($hex_string, $bytes * 2, '0', STR_PAD_LEFT);
    }

    protected function assetNameToIDHex($asset_name)
    {
        if ($asset_name == 'BTC') {return '0';}
        if ($asset_name == 'XCP') {return '1';}

        if (substr($asset_name, 0, 1) == 'A') {
            // numerical asset
            // An integer between 26^12 + 1 and 256^8 (inclusive)
            $asset_id = gmp_init(substr($asset_name, 1));
            if (!preg_match('!^\\d+$!', $asset_id)) {throw new Exception("Invalid asset ID", 1);}
            if ($asset_id < gmp_init(26) ** 12 + 1) {throw new Exception("Asset ID was too low", 1);}
            if ($asset_id > gmp_init(2) ** 64 - 1) {throw new Exception("Asset ID was too high", 1);}

            return gmp_strval($asset_id, 16);
        }

        $n = gmp_init(0);
        for ($i = 0; $i < strlen($asset_name); $i++) {
            $n = gmp_mul($n, 26);
            $char = ord(substr($asset_name, $i, 1));
            if ($char < 65 or $char > 90) {throw new Exception("Asset name invalid", 1);}
            $digit = $char - 65;
            $n = gmp_add($n, $digit);
        }

        return gmp_strval($n, 16);
    }

    protected function arc4($data_binary, $key_binary)
    {
        $key = [];
        $data = [];
        for ($i = 0; $i < strlen($key_binary); $i++) {
            $key[] = ord(substr($key_binary, $i, 1));
        }
        for ($i = 0; $i < strlen($data_binary); $i++) {
            $data[] = ord(substr($data_binary, $i, 1));
        }

        $state = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15,
            16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31,
            32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47,
            48, 49, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62, 63,
            64, 65, 66, 67, 68, 69, 70, 71, 72, 73, 74, 75, 76, 77, 78, 79,
            80, 81, 82, 83, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95,
            96, 97, 98, 99, 100, 101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111,
            112, 113, 114, 115, 116, 117, 118, 119, 120, 121, 122, 123, 124, 125, 126, 127,
            128, 129, 130, 131, 132, 133, 134, 135, 136, 137, 138, 139, 140, 141, 142, 143,
            144, 145, 146, 147, 148, 149, 150, 151, 152, 153, 154, 155, 156, 157, 158, 159,
            160, 161, 162, 163, 164, 165, 166, 167, 168, 169, 170, 171, 172, 173, 174, 175,
            176, 177, 178, 179, 180, 181, 182, 183, 184, 185, 186, 187, 188, 189, 190, 191,
            192, 193, 194, 195, 196, 197, 198, 199, 200, 201, 202, 203, 204, 205, 206, 207,
            208, 209, 210, 211, 212, 213, 214, 215, 216, 217, 218, 219, 220, 221, 222, 223,
            224, 225, 226, 227, 228, 229, 230, 231, 232, 233, 234, 235, 236, 237, 238, 239,
            240, 241, 242, 243, 244, 245, 246, 247, 248, 249, 250, 251, 252, 253, 254, 255);
        $key_length = count($key);
        $index1 = 0;
        $index2 = 0;
        for ($i = 0; $i < 256; $i++) {
            $index2 = ($key[$index1] + $state[$i] + $index2) % 256;
            $state_swap = $state[$i];
            $state[$i] = $state[$index2];
            $state[$index2] = $state_swap;
            $index1 = ($index1 + 1) % $key_length;
        }

        $data_length = count($data);
        $x = 0;
        $y = 0;
        for ($i = 0; $i < $data_length; $i++) {
            $x = ($x + 1) % 256;
            $y = ($state[$x] + $y) % 256;
            $state_swap = $state[$x];
            $state[$x] = $state[$y];
            $state[$y] = $state_swap;
            $data[$i] ^= $state[($state[$x] + $state[$y]) % 256];
        }

        $data_binary = '';
        for ($i = 0; $i < $data_length; $i++) {
            $data_binary .= chr($data[$i]);
        }

        return $data_binary;
    }

    protected function base58_decode($base58)
    {
        $origbase58 = $base58;
        $return = "0";
        for ($i = 0; $i < strlen($base58); $i++) {
            $return = gmp_add(gmp_mul($return, 58), strpos(self::B58_DIGITS, $base58[$i]));
        }
        $return = gmp_strval($return, 16);
        for ($i = 0; $i < strlen($origbase58) && $origbase58[$i] == "1"; $i++) {
            $return = "00" . $return;
        }
        if (strlen($return) % 2 != 0) {
            $return = "0" . $return;
        }
        return $return;
    }

    protected function hash256($string)
    {
        $bs = @pack("H*", $string);
        return hash("sha256", hash("sha256", $bs, true));
    }

}
