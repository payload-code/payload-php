<?php

use PHPUnit\Framework\TestCase;
use Payload\API as pl;
use phpmock\phpunit\PHPMock;

final class QueryParamsTest extends TestCase
{
    use PHPMock;

    protected $original_api_version;
    protected $original_api_key;
    protected $original_api_url;
    protected $curl_requests = [];

    protected function setUp(): void
    {
        $this->original_api_version = Payload\API::$api_version ?? null;
        $this->original_api_key = Payload\API::$api_key ?? null;
        $this->original_api_url = Payload\API::$api_url ?? null;

        Payload\API::$api_key = 'test_key';
        Payload\API::$api_url = 'https://api.test.com';
        Payload\API::$api_version = null;

        $this->curl_requests = [];
        $this->mockCurlFunctions();
    }

    protected function tearDown(): void
    {
        Payload\API::$api_version = $this->original_api_version;
        Payload\API::$api_key = $this->original_api_key;
        Payload\API::$api_url = $this->original_api_url;
    }

    protected function mockCurlFunctions()
    {
        $test = $this;

        $curl_init = $this->getFunctionMock('Payload', 'curl_init');
        $curl_init->expects($this->any())->willReturnCallback(function ($url) use ($test) {
            $handle = 'mock_handle_' . count($test->curl_requests);
            $test->curl_requests[$handle] = [
                'url' => $url,
                'options' => [],
            ];
            return $handle;
        });

        $curl_setopt = $this->getFunctionMock('Payload', 'curl_setopt');
        $curl_setopt->expects($this->any())->willReturnCallback(function ($handle, $option, $value) use ($test) {
            if (!isset($test->curl_requests[$handle])) {
                $test->curl_requests[$handle] = ['options' => []];
            }
            $test->curl_requests[$handle]['options'][$option] = $value;
            return true;
        });

        $curl_exec = $this->getFunctionMock('Payload', 'curl_exec');
        $curl_exec->expects($this->any())->willReturn(json_encode([
            'object' => 'list',
            'values' => [
                ['object' => 'customer', 'id' => 'cust_1', 'email' => 'a@example.com', 'name' => 'A'],
                ['object' => 'customer', 'id' => 'cust_2', 'email' => 'b@example.com', 'name' => 'B'],
            ],
        ]));

        $curl_getinfo = $this->getFunctionMock('Payload', 'curl_getinfo');
        $curl_getinfo->expects($this->any())->willReturnCallback(function ($handle, $option = null) {
            if ($option === CURLINFO_HTTP_CODE) {
                return 200;
            }
            return null;
        });

        $curl_close = $this->getFunctionMock('Payload', 'curl_close');
        $curl_close->expects($this->any())->willReturn(true);
    }

    protected function getRequestUrl()
    {
        $this->assertNotEmpty($this->curl_requests);
        $handle = array_key_first($this->curl_requests);
        return $this->curl_requests[$handle]['url'];
    }

    protected function getQueryParams()
    {
        $url = $this->getRequestUrl();
        $query = parse_url($url, PHP_URL_QUERY);
        if ($query === null) {
            return [];
        }
        parse_str($query, $params);
        return $params;
    }

    // --- order_by tests ---

    public function test_order_by_on_arm_request()
    {
        $req = new Payload\ARMRequest(Payload\Customer::class);
        $req->order_by('created_at')->all();

        $params = $this->getQueryParams();
        $this->assertArrayHasKey('order_by', $params);
        $this->assertContains('created_at', (array)$params['order_by']);
    }

    public function test_order_by_on_arm_object()
    {
        Payload\Customer::order_by('created_at')->all();

        $params = $this->getQueryParams();
        $this->assertArrayHasKey('order_by', $params);
        $this->assertContains('created_at', (array)$params['order_by']);
    }

    public function test_order_by_multiple_fields()
    {
        Payload\Customer::order_by('created_at', 'name')->all();

        $params = $this->getQueryParams();
        $this->assertArrayHasKey('order_by', $params);
        $order_by = (array)$params['order_by'];
        $this->assertContains('created_at', $order_by);
        $this->assertContains('name', $order_by);
    }

    // --- limit tests ---

    public function test_limit_on_arm_request()
    {
        $req = new Payload\ARMRequest(Payload\Customer::class);
        $req->limit(10)->all();

        $params = $this->getQueryParams();
        $this->assertArrayHasKey('limit', $params);
        $this->assertEquals(10, $params['limit']);
    }

    public function test_limit_on_arm_object()
    {
        Payload\Customer::limit(5)->all();

        $params = $this->getQueryParams();
        $this->assertArrayHasKey('limit', $params);
        $this->assertEquals(5, $params['limit']);
    }

    // --- offset tests ---

    public function test_offset_on_arm_request()
    {
        $req = new Payload\ARMRequest(Payload\Customer::class);
        $req->offset(20)->all();

        $params = $this->getQueryParams();
        $this->assertArrayHasKey('offset', $params);
        $this->assertEquals(20, $params['offset']);
    }

    public function test_offset_on_arm_object()
    {
        Payload\Customer::offset(15)->all();

        $params = $this->getQueryParams();
        $this->assertArrayHasKey('offset', $params);
        $this->assertEquals(15, $params['offset']);
    }

    // --- chaining tests ---

    public function test_limit_and_offset_chained()
    {
        Payload\Customer::limit(10)->offset(20)->all();

        $params = $this->getQueryParams();
        $this->assertEquals(10, $params['limit']);
        $this->assertEquals(20, $params['offset']);
    }

    public function test_order_by_with_limit_and_offset()
    {
        Payload\Customer::order_by('created_at')->limit(5)->offset(10)->all();

        $params = $this->getQueryParams();
        $this->assertContains('created_at', (array)$params['order_by']);
        $this->assertEquals(5, $params['limit']);
        $this->assertEquals(10, $params['offset']);
    }

    public function test_filter_by_with_order_by_limit_offset()
    {
        Payload\Customer::filter_by(
            ['status' => 'active']
        )->order_by('created_at')->limit(25)->offset(50)->all();

        $params = $this->getQueryParams();
        $this->assertEquals('active', $params['status']);
        $this->assertContains('created_at', (array)$params['order_by']);
        $this->assertEquals(25, $params['limit']);
        $this->assertEquals(50, $params['offset']);
    }

    // --- default_params tests ---

    public function test_default_params_applied()
    {
        $original = Payload\Customer::getDefaultParams();
        Payload\Customer::setDefaultParams(['limit' => 100]);
        $this->assertEquals([], Payload\Account::getDefaultParams());

        Payload\Customer::filter_by(['status' => 'active'])->all();

        $params = $this->getQueryParams();
        $this->assertEquals(100, $params['limit']);

        Payload\Customer::setDefaultParams($original);
    }

    public function test_default_params_do_not_override_explicit()
    {
        $original = Payload\Customer::getDefaultParams();
        Payload\Customer::setDefaultParams(['limit' => 100]);

        Payload\Customer::limit(5)->all();

        $params = $this->getQueryParams();
        $this->assertEquals(5, $params['limit']);

        Payload\Customer::setDefaultParams($original);
    }

    // --- no params when not set ---

    public function test_no_query_params_when_none_set()
    {
        Payload\Customer::filter_by([])->all();

        $url = $this->getRequestUrl();
        $this->assertStringNotContainsString('order_by', $url);
        $this->assertStringNotContainsString('limit', $url);
        $this->assertStringNotContainsString('offset', $url);
    }
}
