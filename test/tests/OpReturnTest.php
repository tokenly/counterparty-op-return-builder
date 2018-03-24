<?php

use Tokenly\CounterpartyOpReturnBuilder\OpReturnBuilder;
use Tokenly\CryptoQuantity\CryptoQuantity;
use \PHPUnit_Framework_Assert as PHPUnit;

/*
 *
 */
class OpReturnTest extends \PHPUnit_Framework_TestCase
{

    const SATOSHI = 100000000;

    public function testComposeOpReturn()
    {
        $op_return_builder = new OpReturnBuilder();

        $destination = '1AAAA1111xxxxxxxxxxxxxxxxxxy43CZ9j';
        $fake_txid = 'deadbeef00000000000000000000000000000000000000000000000000001111';
        $hex = $this->arc4decrypt($fake_txid, $op_return_builder->buildOpReturnForSend(100, 'SOUP', $destination, $fake_txid));

        //               434e545250525459  | 02   | 000000000004fadf   | 00000002540be400   | 006474849fc9ac0f5bd6b49fe144d14db7d32e2445
        //               prefix              type   asset                amount               public key
        $expected_hex = '434e545250525459' . '02' . '000000000004fadf' . '00000002540be400' . '006474849fc9ac0f5bd6b49fe144d14db7d32e2445';
        PHPUnit::assertEquals($expected_hex, $hex);
    }

    public function testComposeTestnetOpReturn()
    {
        $op_return_builder = new OpReturnBuilder();

        $destination = 'mgFRGY1KbbRTj3dMdw7KQaapvZCy6ne2Ha';
        $fake_txid = 'deadbeef00000000000000000000000000000000000000000000000000001111';
        $hex = $this->arc4decrypt($fake_txid, $op_return_builder->buildOpReturnForSend(100, 'SOUP', $destination, $fake_txid));

        // testnet address version is 0x6f
        //               434e545250525459  | 02   | 000000000004fadf   | 00000002540be400   | 6f0807fcac4280213c06c4d71e943451e58f576750
        //               prefix              type   asset                amount               public key
        $expected_hex = '434e545250525459' . '02' . '000000000004fadf' . '00000002540be400' . '6f0807fcac4280213c06c4d71e943451e58f576750';
        PHPUnit::assertEquals($expected_hex, $hex);
    }

    public function testComposeIndivisibleAssetOpReturn()
    {
        $op_return_builder = new OpReturnBuilder();

        $destination = '1AAAA1111xxxxxxxxxxxxxxxxxxy43CZ9j';
        $fake_txid = 'deadbeef00000000000000000000000000000000000000000000000000001111';
        $hex = $this->arc4decrypt($fake_txid, $op_return_builder->buildOpReturnForSend(CryptoQuantity::fromSatoshis(600), 'SOUP', $destination, $fake_txid));

        //               434e545250525459  | 02   | 000000000004fadf   | 0000000000000258   | 006474849fc9ac0f5bd6b49fe144d14db7d32e2445
        //               prefix              type   asset                amount               public key
        $expected_hex = '434e545250525459' . '02' . '000000000004fadf' . '0000000000000258' . '006474849fc9ac0f5bd6b49fe144d14db7d32e2445';
        PHPUnit::assertEquals($expected_hex, $hex);
    }

    public function testComposeDivisibleAssetOpReturn()
    {
        $op_return_builder = new OpReturnBuilder();

        $destination = '1AAAA1111xxxxxxxxxxxxxxxxxxy43CZ9j';
        $fake_txid = 'deadbeef00000000000000000000000000000000000000000000000000001111';
        $hex = $this->arc4decrypt($fake_txid, $op_return_builder->buildOpReturnForSend(CryptoQuantity::fromFloat(600), 'SOUP', $destination, $fake_txid));

        //               434e545250525459  | 02   | 000000000004fadf   | 0000000df8475800   | 006474849fc9ac0f5bd6b49fe144d14db7d32e2445
        //               prefix              type   asset                amount               public key
        $expected_hex = '434e545250525459' . '02' . '000000000004fadf' . '0000000df8475800' . '006474849fc9ac0f5bd6b49fe144d14db7d32e2445';
        PHPUnit::assertEquals($expected_hex, $hex);
    }

    public function testComposeNumericAssetID()
    {
        $op_return_builder = new OpReturnBuilder();

        $destination = '1AAAA1111xxxxxxxxxxxxxxxxxxy43CZ9j';
        $fake_txid = 'deadbeef00000000000000000000000000000000000000000000000000001111';
        $hex = $this->arc4decrypt($fake_txid, $op_return_builder->buildOpReturnForSend(100, 'A768915753791388330', $destination, $fake_txid));

        //               434e545250525459  | 02   | 000000000004fadf   | 00000002540be400   | 006474849fc9ac0f5bd6b49fe144d14db7d32e2445
        //               prefix              type   asset                amount               public key
        $expected_hex = '434e545250525459' . '02' . '0aabbccddeeffaaa' . '00000002540be400' . '006474849fc9ac0f5bd6b49fe144d14db7d32e2445';
        PHPUnit::assertEquals($expected_hex, $hex);
    }

    /**
     * @expectedException        Exception
     * @expectedExceptionMessage Asset ID was too high
     */
    public function testComposeTooBigNumericAssetID()
    {
        $op_return_builder = new OpReturnBuilder();
        $destination = '1AAAA1111xxxxxxxxxxxxxxxxxxy43CZ9j';
        $fake_txid = 'deadbeef00000000000000000000000000000000000000000000000000001111';
        $op_return_builder->buildOpReturnForSend(100, 'A18446744073709551616', $destination, $fake_txid);
    }

    /**
     * @expectedException        Exception
     * @expectedExceptionMessage Asset ID was too low
     */
    public function testComposeTooSmallNumericAssetID()
    {
        $op_return_builder = new OpReturnBuilder();
        $destination = '1AAAA1111xxxxxxxxxxxxxxxxxxy43CZ9j';
        $fake_txid = 'deadbeef00000000000000000000000000000000000000000000000000001111';
        $op_return_builder->buildOpReturnForSend(100, 'A95428956661682176', $destination, $fake_txid);
    }

    // ------------------------------------------------------------------------

    protected function arc4decrypt($key, $encrypted_text)
    {
        return bin2hex($this->arc4(hex2bin($encrypted_text), hex2bin($key)));
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

}
