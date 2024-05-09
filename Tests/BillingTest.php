<?php

use PHPUnit\Framework\TestCase;
use Payload\API as pl;
use Test\Fixtures as fixtures;

include_once('Fixtures.php');

final class BillingTest extends TestCase
{
    protected $customer_accnt;
    protected $processing_accnt;
    protected $billing_schedule;

    protected function setUp(): void
    {
        fixtures::init_payload();
        $this->customer_accnt = fixtures::customer_accnt_data();
        $this->processing_accnt = fixtures::processing_accnt_data();
        $this->billing_schedule = Payload\BillingSchedule::create(array(
            'start_date' => '2019-01-01',
            'end_date' => '2019-12-31',
            'recurring_frequency' => 'monthly',
            'type' => 'subscription',
            'processing_id' => $this->processing_accnt->id,
            'charges' => array(
                new Payload\BillingCharge(array(
                    'type' => 'option_1',
                    'amount' => 39.99
                ))
            ),
            'customer_id' => $this->customer_accnt->id
        ));
    }


    function test_create_billing_schedule()
    {
        $this->assertEquals($this->processing_accnt->id, $this->billing_schedule->processing_id);
        $this->assertEquals(39.99, $this->billing_schedule->charges[0]->amount);
    }


    function test_update_billing_schedule_frequency()
    {
        $this->assertEquals($this->processing_accnt->id, $this->billing_schedule->processing_id);
        $this->assertEquals(39.99, $this->billing_schedule->charges[0]->amount);
        $this->assertEquals('monthly', $this->billing_schedule->recurring_frequency);

        $this->billing_schedule->update(array('recurring_frequency' => 'quarterly'));

        $this->assertEquals('quarterly', $this->billing_schedule->recurring_frequency);
    }
}
