<?php
namespace Payload;
require_once('Attr.php');

class API {
    const version = '0.1.0';
    public static $api_key;
    public static $api_url = 'https://api.payload.com';
    public static function __callStatic($name, $arguments) {
        assert( $name == 'attr' );
        return new Attr();
    }
}
?>
