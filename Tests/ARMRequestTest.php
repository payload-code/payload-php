<?php

// Mock curl functions in the Payload namespace
namespace Payload {
    // Global variable to capture curl options
    $GLOBALS['curl_options'] = [];
    $GLOBALS['curl_handle_counter'] = 0;


    function curl_init($url = null)
    {
        $GLOBALS['curl_handle_counter']++;
        $handle = 'mock_handle_' . $GLOBALS['curl_handle_counter'];
        $GLOBALS['curl_options'][$handle] = [
            'url' => $url,
            'options' => [],
        ];
        return $handle;
    }

    function curl_setopt($handle, $option, $value)
    {
        if (!isset($GLOBALS['curl_options'][$handle])) {
            $GLOBALS['curl_options'][$handle] = ['options' => []];
        }
        $GLOBALS['curl_options'][$handle]['options'][$option] = $value;
        return true;
    }

    function curl_exec($handle)
    {
        // Return a mock successful response
        return json_encode([
            'object' => 'customer',
            'id' => 'cust_test123',
            'email' => 'test@example.com',
            'name' => 'Test Customer',
        ]);
    }

    function curl_getinfo($handle, $option = null)
    {
        if ($option === CURLINFO_HTTP_CODE) {
            return 200;
        }
        return null;
    }

    function curl_close($handle)
    {
        return true;
    }
}

namespace {
    use PHPUnit\Framework\TestCase;
    use Payload\API as pl;

    final class ARMRequestTest extends TestCase
    {
        protected $original_api_version;
        protected $original_api_key;
        protected $original_api_url;

        protected function setUp(): void
        {
            // Store original API settings
            $this->original_api_version = Payload\API::$api_version ?? null;
            $this->original_api_key = Payload\API::$api_key ?? null;
            $this->original_api_url = Payload\API::$api_url ?? null;

            // Set test API settings
            Payload\API::$api_key = 'test_key';
            Payload\API::$api_url = 'https://api.test.com';

            // Reset curl options tracker
            $GLOBALS['curl_options'] = [];
            $GLOBALS['curl_handle_counter'] = 0;
        }

        protected function tearDown(): void
        {
            // Restore original API settings
            Payload\API::$api_version = $this->original_api_version;
            Payload\API::$api_key = $this->original_api_key;
            Payload\API::$api_url = $this->original_api_url;

            // Clear curl options
            $GLOBALS['curl_options'] = [];
        }

        /**
         * Test that X-API-Version header is NOT set when API::$api_version is null
         */
        public function test_no_api_version_header_when_not_set()
        {
            Payload\API::$api_version = null;

            // Make a request
            $customer = Payload\Customer::create([
                'email' => 'test@example.com',
                'name' => 'Test Customer',
            ]);

            // Get the curl options that were set
            $curl_options = $GLOBALS['curl_options'];
            $this->assertNotEmpty($curl_options);

            $handle = array_key_first($curl_options);
            $options = $curl_options[$handle]['options'];

            // Check if CURLOPT_HTTPHEADER was set
            if (isset($options[CURLOPT_HTTPHEADER])) {
                $headers = $options[CURLOPT_HTTPHEADER];
                // Verify X-API-Version is NOT in the headers
                foreach ($headers as $header) {
                    $this->assertStringNotContainsString('X-API-Version:', $header);
                }
            }
            // If CURLOPT_HTTPHEADER is not set at all, that's also fine for this test
        }

        /**
         * Test that X-API-Version header IS set when API::$api_version is set
         */
        public function test_api_version_header_when_set()
        {
            Payload\API::$api_version = 'v2.0';

            // Make a request
            $customer = Payload\Customer::create([
                'email' => 'test@example.com',
                'name' => 'Test Customer',
            ]);

            // Get the curl options that were set
            $curl_options = $GLOBALS['curl_options'];
            $this->assertNotEmpty($curl_options);

            $handle = array_key_first($curl_options);
            $options = $curl_options[$handle]['options'];

            // Check that CURLOPT_HTTPHEADER was set
            $this->assertArrayHasKey(CURLOPT_HTTPHEADER, $options);

            $headers = $options[CURLOPT_HTTPHEADER];
            $this->assertIsArray($headers);

            // Verify X-API-Version header is present with correct value
            $found_version_header = false;
            foreach ($headers as $header) {
                if (strpos($header, 'X-API-Version:') === 0) {
                    $found_version_header = true;
                    $this->assertEquals('X-API-Version: v2.0', $header);
                    break;
                }
            }

            $this->assertTrue($found_version_header, 'X-API-Version header was not found');
        }

        /**
         * Test that both Content-Type and X-API-Version headers are set for JSON requests
         * This test will reveal if there's a bug where headers overwrite each other
         */
        public function test_api_version_header_with_json_request()
        {
            Payload\API::$api_version = 'v2.0';

            // Make a request with JSON body
            $customer = Payload\Customer::create(
                [
                    'email' => 'test@example.com',
                    'name'  => 'Test Customer',
                ]
            );

            // Get the curl options that were set
            $curl_options = $GLOBALS['curl_options'];
            $this->assertNotEmpty($curl_options);

            $handle = array_key_first($curl_options);
            $options = $curl_options[$handle]['options'];

            // Check that CURLOPT_HTTPHEADER was set
            $this->assertArrayHasKey(CURLOPT_HTTPHEADER, $options);

            $headers = $options[CURLOPT_HTTPHEADER];
            $this->assertIsArray($headers);

            // Check for expected headers
            $found_version_header = false;
            $found_content_type = false;
            $found_content_length = false;

            foreach ($headers as $header) {
                if (strpos($header, 'X-API-Version:') === 0) {
                    $found_version_header = true;
                    $this->assertEquals('X-API-Version: v2.0', $header);
                }
                if (strpos($header, 'Content-Type:') === 0) {
                    $found_content_type = true;
                }
                if (strpos($header, 'Content-Length:') === 0) {
                    $found_content_length = true;
                }
            }

            // This test may fail if the bug exists (second curl_setopt overwrites first)
            $this->assertTrue($found_version_header, 'X-API-Version header was not found');

            // These assertions will fail if there's a bug where setting CURLOPT_HTTPHEADER twice
            // causes the second call to overwrite the first one
            $this->assertTrue($found_content_type, 'Content-Type header was not found - headers may be overwriting each other');
            $this->assertTrue($found_content_length, 'Content-Length header was not found - headers may be overwriting each other');
        }

        /**
         * Test different API version values
         */
        public function test_different_api_version_values()
        {
            $test_versions = ['v2.0', 'v1.0', 'v3.1', 'v2.5'];

            foreach ($test_versions as $version) {
                // Reset curl options
                $GLOBALS['curl_options'] = [];

                Payload\API::$api_version = $version;

                // Make a request
                $customer = Payload\Customer::create(
                    [
                        'email' => 'test@example.com',
                        'name'  => 'Test Customer',
                    ]
                );

                // Get the curl options
                $curl_options = $GLOBALS['curl_options'];
                $handle = array_key_first($curl_options);
                $options = $curl_options[$handle]['options'];

                // Verify the header has the correct value
                $headers = $options[CURLOPT_HTTPHEADER];
                $found = false;
                foreach ($headers as $header) {
                    if (strpos($header, 'X-API-Version:') === 0) {
                        $this->assertEquals('X-API-Version: ' . $version, $header);
                        $found = true;
                        break;
                    }
                }
                $this->assertTrue($found, "X-API-Version header not found for version: $version");
            }
        }

        /**
         * Test that GET requests also include the API version header
         */
        public function test_api_version_header_on_get_request()
        {
            Payload\API::$api_version = 'v2.0';

            // Create an ARMRequest and call get
            $request = new Payload\ARMRequest(Payload\Customer::class);

            try {
                $request->get('cust_test123');
            } catch (\Exception $e) {
                // Ignore exceptions from the mock
            }

            // Get the curl options
            $curl_options = $GLOBALS['curl_options'];
            $this->assertNotEmpty($curl_options);

            $handle = array_key_first($curl_options);
            $options = $curl_options[$handle]['options'];

            // Verify X-API-Version header is present
            if (isset($options[CURLOPT_HTTPHEADER])) {
                $headers = $options[CURLOPT_HTTPHEADER];
                $found = false;
                foreach ($headers as $header) {
                    if (strpos($header, 'X-API-Version:') === 0) {
                        $found = true;
                        $this->assertEquals('X-API-Version: v2.0', $header);
                        break;
                    }
                }
                $this->assertTrue($found, 'X-API-Version header not found on GET request');
            }
        }
    }
}
