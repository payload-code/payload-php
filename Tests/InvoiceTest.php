<?php

use PHPUnit\Framework\TestCase;
use Payload\API as pl;
use Test\Fixtures as fixtures;

include_once('Fixtures.php');

final class InvoiceTest extends TestCase
{
    protected $invoice;
    protected $customer_accnt;
    protected $processing_accnt;

    protected function setUp(): void
    {

        fixtures::init_payload();
        $this->customer_accnt = fixtures::customer_accnt_data();
        $this->processing_accnt = fixtures::processing_accnt_data();

        $this->invoice = Payload\Invoice::create(array(
            'type' => 'bill',
            'processing_id' => $this->processing_accnt->id,
            'due_date' => '2019-01-01',
            'items' => array(
                new Payload\LineItem(array(
                    'entry_type' => 'charge',
                    'amount' => 29.99
                ))
            ),
            'customer_id' => $this->customer_accnt->id
        ));
    }
    public function test_create_invoice()
    {
        $this->assertEquals('2019-01-01', $this->invoice->due_date);
        $this->assertEquals('unpaid', $this->invoice->status);
    }


    public function test_pay_invoice()
    {

        $this->assertEquals('2019-01-01', $this->invoice->due_date);
        $this->assertEquals('unpaid', $this->invoice->status);

        $card_payment = Payload\Transaction::create(array(
            'amount' => 100.0,
            'type' => 'payment',
            'customer_id' => $this->customer_accnt->id,
            'payment_method' => new Payload\PaymentMethod(array(
                'type' => 'card',
                'card' => array('card_number' => '4242 4242 4242 4242', 'expiry' => '12/30')
            )),
        ));


        $get_card = Payload\Transaction::get($card_payment->id);

        if ($this->invoice->status != 'paid') {
            Payload\Transaction::create(array(
                'amount' => $this->invoice->amount_due,
                'customer_id' => $this->invoice->customer_id,
                'type' => 'payment',
                'payment_method_id' => $get_card->payment_method_id,
                'allocations' => array(
                    Payload\LineItem::new(array(
                        'invoice_id' => $this->invoice->id,
                        'entry_type' => 'payment'
                    ))
                )
            ));
        }

        $get_invoice = Payload\Invoice::get($this->invoice->id);
        $this->assertEquals('paid', $get_invoice->status);
    }
}
