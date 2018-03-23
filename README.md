# Counterparty OP_RETURN Builder

[![Build Status](https://travis-ci.org/tokenly/counterparty-op-return-builder.svg?branch=master)](https://travis-ci.org/tokenly/counterparty-op-return-builder)

Composes OP_RETURN data for Counterparty sends


## Installation

`composer require tokenly/counterparty-op-return-builder`

## Usage

```php
$op_return_builder = new OpReturnBuilder();

$destination = '1AAAA1111xxxxxxxxxxxxxxxxxxy43CZ9j';
$txid = 'deadbeef00000000000000000000000000000000000000000000000000001111';

// Send 100 of asset SOUP
$op_return_hex = $op_return_builder->buildOpReturnForSend(100, 'SOUP', $destination, $txid);

print $op_return_hex.PHP_EOL;
// "95f8483a315279d12a7314a8e82019d7fa6ba1354f09c61480dedf76e038875f405090c08be78ef8c7a4b60bb4"

```
