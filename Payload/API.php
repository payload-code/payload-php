<?php
namespace Payload;

require_once('Attr.php');

class API
{
    const version = '1.0.0';
    public static $api_key;
    public static $api_url = 'https://api.payload.com';
    public static $api_version;
    public static function __callStatic($name, $arguments)
    {
        assert($name == 'attr');
        return new Attr();
    }
}
