<?php

use PHPUnit\Framework\TestCase;
use Payload\API as pl;
use Test\Fixtures as fixtures;

include('Fixtures.php');
pl::$api_key = 'your_secret_key_13ksbI5IvnaeNtsx9nf7Fb';


final class PaymentLinkTest extends TestCase
{

    protected $customer_accnt;
    protected $processing_accnt;

    protected function setUp(): void
    {
        $this->customer_accnt = fixtures::customer_accnt_data();
        $this->processing_accnt = fixtures::processing_accnt_data();
    }

    public function test_create_payment_link()
    {
        $payment_link = Payload\PaymentLink::create(array(
            'type' => 'one_time',
            'description' => 'Payment Request',
            'amount' => 10.00,
            'processing_id' => $this->processing_accnt->id
        ));

        $this->assertEquals($this->processing_accnt->id, $payment_link->processing_id);
    }

}
