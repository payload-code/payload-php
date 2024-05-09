<?php

use PHPUnit\Framework\TestCase;
use Payload\API as pl;
use Test\Fixtures as fixtures;

include_once('Fixtures.php');

final class PaymentTest extends TestCase
{

    protected $card_payment;
    protected $bank_payment;
    protected $processing_accnt;

    protected function setUp(): void
    {
        fixtures::init_payload();
        $this->card_payment = fixtures::card_payment_data();
        $this->bank_payment = fixtures::bank_payment_data();
        $this->processing_accnt = fixtures::processing_accnt_data();

    }


    public function test_create_payment_card()
    {
        $this->assertEquals('processed', $this->card_payment->status);
    }


    public function test_create_payment_bank()
    {
        $this->assertEquals('processed', $this->bank_payment->status);
    }


    public function test_payment_filters()
    {
        $str = rand();
        $rand_description = sha1($str);

        $payment = Payload\Transaction::create(array(
            'amount' => 100.0,
            'type' => 'payment',
            'description' => $rand_description,
            'payment_method' => new Payload\PaymentMethod(array(
                'type' => 'card',
                'card' => array('card_number' => '4242 4242 4242 4242', 'expiry' => '12/30')
            )),
        ));

        $payments = Payload\Transaction::filter_by(
            pl::attr()->amount->gt(99),
            pl::attr()->amount->lt(200),
            pl::attr()->description->contains($rand_description),
            pl::attr()->created_at->gt('2019-12-31')
        )->all();

        $this->assertEquals(1, count($payments));
        $this->assertEquals($payment->id, $payments[0]->id);
    }


    public function test_void_payment()
    {
        $this->card_payment->update(array('status' => 'voided'));

        $this->assertEquals('voided', $this->card_payment->status);
    }


    public function test_refund_card_payment()
    {
        $refund = Payload\Transaction::select('*', 'ledger')->create(array(
            'type' => 'refund',
            'amount' => $this->card_payment->amount,
            'ledger' => array(array(
                'assoc_transaction_id' => $this->card_payment->id
            ))
        ));

        $this->assertEquals('refund', $refund->type);
        $this->assertEquals(100, $refund->amount);
        $this->assertEquals('approved', $refund->status_code);
    }


    public function test_partial_refund_card_payment()
    {
        $refund = Payload\Transaction::select('*', 'ledger')->create(array(
            'type' => 'refund',
            'amount' => 10,
            'ledger' => array(array(
                'assoc_transaction_id' => $this->card_payment->id
            ))
        ));

        $this->assertEquals('refund', $refund->type);
        $this->assertEquals(10, $refund->amount);
        $this->assertEquals('approved', $refund->status_code);
    }


    public function test_blind_refund_card_payment()
    {
        $refund = Payload\Transaction::create(array(
            'type' => 'refund',
            'amount' => 10,
            'payment_method' => new Payload\PaymentMethod(array(
                'type' => 'card',
                'card' => array('card_number' => '4242 4242 4242 4242', 'expiry' => '12/30')
            )),
            'processing_id' => $this->processing_accnt->id
        ));

        $this->assertEquals('refund', $refund->type);
        $this->assertEquals(10, $refund->amount);
        $this->assertEquals('approved', $refund->status_code);
    }


    public function test_refund_bank_payment()
    {
        $refund = Payload\Transaction::select('*', 'ledger')->create(array(
            'type' => 'refund',
            'amount' => $this->bank_payment->amount,
            'ledger' => array(array(
                'assoc_transaction_id' => $this->bank_payment->id
            ))
        ));

        $this->assertEquals('refund', $refund->type);
        $this->assertEquals(100, $refund->amount);
        $this->assertEquals('approved', $refund->status_code);
    }


    public function test_partial_refund_bank_payment()
    {
        $refund = Payload\Transaction::select('*', 'ledger')->create(array(
            'type' => 'refund',
            'amount' => 10,
            'ledger' => array(array(
                'assoc_transaction_id' => $this->bank_payment->id
            ))
        ));

        $this->assertEquals('refund', $refund->type);
        $this->assertEquals(10, $refund->amount);
        $this->assertEquals('approved', $refund->status_code);
    }


    public function test_convenience_fee()
    {
        $payment = Payload\Transaction::select('*', 'fee', 'conv_fee')->create(array(
            'amount' => 100.0,
            'type' => 'payment',
            'payment_method' => new Payload\PaymentMethod(array(
                'type' => 'card',
                'card' => array('card_number' => '4242 4242 4242 4242', 'expiry' => '12/30')
            )),
        ));

        $this->assertNotNull($payment->fee);
        $this->assertNotNull($payment->conv_fee);
    }


    public function test_invalid_payment_method_type_invalid_attributes()
    {
        $this->expectException(Payload\Exceptions\InvalidAttributes::class);

        Payload\Transaction::create(array(
            'amount' => 100.0,
            'type' => 'payment',
            'payment_method' => new Payload\PaymentMethod(array(
                'type' => 'bank_account',
                'card' => array('card_number' => '4242 4242 4242 4242', 'expiry' => '12/30')
            )),
        ));
    }
}
