<?php

use PHPUnit\Framework\TestCase;
use Payload\API as pl;
use Test\Fixtures as fixtures;

include_once('Fixtures.php');

final class TransactionTest extends TestCase
{

    protected $customer_accnt;
    protected $processing_accnt;
    protected $card_payment;


    protected function setUp(): void
    {
        fixtures::init_payload();
        $this->customer_accnt = fixtures::customer_accnt_data();
        $this->processing_accnt = fixtures::processing_accnt_data();
        $this->card_payment = fixtures::card_payment_data();

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


    public function test_transaction_ledger_empty()
    {
        $payment = Payload\Transaction::select('*', 'ledger')->get($this->card_payment->id);
        $this->assertEmpty($payment->ledger);
    }


    public function test_unified_payout_batching()
    {
        Payload\Transaction::create(array(
            'type' => 'refund',
            'amount' => 10,
            'payment_method' => new Payload\PaymentMethod(array(
                'type' => 'card',
                'card' => array('card_number' => '4242 4242 4242 4242', 'expiry' => '12/30')
            )),
            'processing_id' => $this->processing_accnt->id
        ));


        $transactions = Payload\Transaction::select(
            '*',
            'ledger'
        )->filter_by(array(
            'type' => 'refund',
            'processing_id' => $this->processing_accnt->id
        ))->all();

        $this->assertEquals(1, count($transactions));
        $this->assertEquals($this->processing_accnt->id, $transactions[0]->processing_id);
    }

    public function test_get_transactions()
    {
        Payload\Transaction::create(array(
            'type' => 'payment',
            'amount' => 10,
            'payment_method' => new Payload\PaymentMethod(array(
                'type' => 'card',
                'card' => array('card_number' => '4242 4242 4242 4242', 'expiry' => '12/30')
            ))
        ));
        $payments = Payload\Transaction::filter_by(array('status' => 'processed', 'type' => 'payment'))->all();
        $this->assertGreaterThan(0, count($payments));
    }

    public function test_risk_flag()
    {
        $payments = Payload\Transaction::filter_by(array(
            'risk_flag' => 'allowed'
        ))->all();
        
        $this->assertEquals('allowed', $payments[0]->risk_flag);
    }

    public function test_update_processed()
    {
        $transaction = Payload\Transaction::filter_by(
            pl::attr()->id->eq($this->card_payment->id)
        )->update(array('status' => 'processed'));
    }

    public function test_transactions_not_found()
    {
        $this->expectException(Payload\Exceptions\NotFound::class);
        $transaction = Payload\Transaction::get('invalid');

    }
}
