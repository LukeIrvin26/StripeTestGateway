<?php

require_once 'code-challenge.php';

$stripe = new StripeGateway('sk_test_lBzwJ4lQzQvEPZwgl3s59Mal');
$stripe->setName('Bob Smith')
    ->setAddress1('123 Test Street')
    ->setAddress2('Suite #4')
    ->setCity('Morristown')
    ->setProvince('TN')
    ->setPostal('37814')
    ->setCountry('US')
    ->setCardNumber(4007000000027)
    ->setExpirationDate('10', '2021')
    ->setCvv('123')
;

if ($stripe->charge(4999, 'USD')) {
    echo 'Charge successful! Transaction ID: '.$stripe->getTransactionId();
} else {
    echo 'Charge failed. Errors: '.print_r($stripe->getErrors(), true);
}
