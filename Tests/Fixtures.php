<?php

namespace Test;

use Payload;
use Payload\API as pl;


class Fixtures
{
    public static function init_payload() {
      pl::$api_key = getenv('API_KEY');
      if (!empty(getenv('API_URL')))
        pl::$api_url = getenv('API_URL');
    }

    public static function customer_accnt_data()
    {
        $customer_accnt_data = Payload\Customer::create(array(
            'email' => 'joe.customer@example.com',
            'name' => 'Joe Customer',
        ));

        return $customer_accnt_data;
    }

    public static function processing_accnt_data()
    {
        $processing_accnt_data = Payload\ProcessingAccount::create(
            array(
                'name' => 'Processing Account',
                'payment_methods' => array(
                    new Payload\PaymentMethod(array(
                        'type' => 'bank_account',
                        'bank_account' => array(
                            'account_number' => '123456789',
                            'routing_number' => '036001808',
                            'account_type' => 'checking'
                        ),
                    ))
                ),
                'legal_entity' => array(
                    'legal_name' => 'Test',
                    'type' => 'INDIVIDUAL_SOLE_PROPRIETORSHIP',
                    'ein' => '23 423 4234', 'street_address' => '123 Example St',
                    'unit_number' => 'Suite 1',
                    'city' => 'New York', 'state_province' => 'NY',
                    'state_incorporated' => 'NY',
                    'postal_code' => '10002',
                    'phone_number' => '(111) 222-3333',
                    'website' => 'https://payload.com',
                    'start_date' => '05/01/2015',
                    'contact_name' => 'Test Person', 'contact_email' => 'test.person@example.com',
                    'contact_title' => 'VP',
                    'owners' => [array(
                        'full_name' => 'Test Person',
                        'email' => 'test.person@example.com', 'ssn' => '234 23 4234',
                        'birth_date' => '06/20/1985', 'title' => 'CEO', 'ownership' => '100',
                        'street_address' => '123 Main St',
                        'unit_number' => '#1A', 'city' => 'New York', 'state_province' => 'NY',
                        'postal_code' => '10001', 'phone_number' => '(111) 222-3333', 'type' => 'owner'
                    )]
                )
            )
        );

        return $processing_accnt_data;
    }


    public static function card_payment_data()
    {
        $card_payment_data = Payload\Transaction::create(array(
            'amount' => 100.0,
            'type' => 'payment',
            'payment_method' => new Payload\PaymentMethod(array(
                'type' => 'card',
                'card' => array('card_number' => '4242 4242 4242 4242', 'expiry' => '12/30')
            )),
        ));

        return $card_payment_data;
    }


    public static function bank_payment_data()
    {
        $bank_payment_data = Payload\Transaction::create(array(
            'amount' => 100.0,
            'type' => 'payment',
            'payment_method' => new Payload\PaymentMethod(array(
                'type' => 'bank_account',
                'bank_account' => array(
                    'account_number' => '1234567890',
                    'routing_number' => '036001808',
                    'account_type' => 'checking'
                )
            )),
        ));

        return $bank_payment_data;
    }
}
