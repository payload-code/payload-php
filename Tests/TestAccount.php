<?php

use PHPUnit\Framework\TestCase;
use Payload\API as pl;
use Test\Fixtures as fixtures;

include('Fixtures.php');
pl::$api_key = 'your_secret_key_13ksbI5IvnaeNtsx9nf7Fb';

final class AccountTest extends TestCase
{
    protected $customer_accnt;
    protected $processing_accnt;


    protected function setUp(): void
    {
        $this->customer_accnt = fixtures::customer_accnt_data();
        $this->processing_accnt = fixtures::processing_accnt_data();
    }

    public function test_delete()
    {
        $this->expectException(Payload\Exceptions\NotFound::class);
        
        $this->customer_accnt->delete();
        Payload\Account::get($this->customer_accnt->id);
    }

    public function test_create_mult_accounts()
    {
        $str1 = rand();
        $rand_email1 = sha1($str1) . '@example.com';

        $str2 = rand();
        $rand_email2 = sha1($str2) . '@example.com';

        Payload\Account::create(array(
            new Payload\Account(array(
                'email' => $rand_email1,
                'name' => 'Manny Perez',
                'type' => 'customer'
            )),
            new Payload\Account(array(
                'email' => $rand_email2,
                'name' => 'Andy Kearney',
                'type' => 'customer'
            ))
        ));

        $get_account_1 = Payload\Account::filter_by(
            pl::attr()->email->eq($rand_email1)
        )->all()[0];


        $get_account_2 = Payload\Account::filter_by(
            pl::attr()->email->eq($rand_email2)
        )->all()[0];

        $this->assertEquals($rand_email1, $get_account_1->email);
        $this->assertEquals($rand_email2, $get_account_2->email);
    }

    
    public function test_get_processing_account()
    {
        $this->assertSame('pending', $this->processing_accnt->processing['status']);
    }


    public function test_paging_and_ordering_results()
    {
        Payload\Account::create(array(
            new Payload\Account(array(
                'email' => 'account1@example.com',
                'name' => 'Randy Robson',
                'type' => 'customer'
            )),
            new Payload\Account(array(
                'email' => 'account2@example.com',
                'name' => 'Brandy Bobson',
                'type' => 'customer'
            )),
            new Payload\Account(array(
                'email' => 'account3@example.com',
                'name' => 'Mandy Johnson',
                'type' => 'customer'
            ))
        ));

        $customers = Payload\Account::filter_by(array(
            'order_by' => 'created_at',
            'limit' => 3,
            'offset' => 1
        ))->all();

        $this->assertEquals(3, count($customers));
        $this->assertTrue($customers[0]->created_at < $customers[1]->created_at );
        $this->assertTrue($customers[1]->created_at < $customers[2]->created_at );
        }


    public function test_update_cust()
    {
        $this->assertNotNull($this->customer_accnt->id);

        $this->customer_accnt->update(array('email' => 'test2@example.com'));

        $this->assertSame('test2@example.com', $this->customer_accnt->email);
    }


    public function test_get_cust()
    {
        $sel_cust = Payload\Account::filter_by(
            pl::attr()->email->eq($this->customer_accnt->email)
        )->all()[0];

        $get_cust = Payload\Account::get($sel_cust->id);

        $this->assertSame($get_cust->id, $sel_cust->id);
    }


    public function test_select_cust()
    {
        $results = Payload\Account::filter_by(
            pl::attr()->email->eq($this->customer_accnt->email)
        )->all();

        foreach ($results as $key => $val) {
            $this->assertSame($this->customer_accnt->email, $val->email);
        }
    }


    public function test_create_cust()
    {
        $account = Payload\Account::create(array(
            'email' => 'joe.schmoe@example.com',
            'name' => 'Joe Schmoe',
            'type' => 'customer'
        ));

        $this->assertSame('joe.schmoe@example.com', $account->email);

        $this->assertNotNull($account->id);
    }
}
