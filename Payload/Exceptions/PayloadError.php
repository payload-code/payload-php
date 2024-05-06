<?php
namespace Payload\Exceptions;

class PayloadError extends \Exception {
    public static $status_code = null;
    public $details = null;
    public static function getClassName() {
        return (new \ReflectionClass(static::class))->getShortName();
    }

    public function __construct($message=null, $error = null) {
        if ( $error && array_key_exists("details", $error) )
            $this->details = $error["details"];
        return parent::__construct($message);

    }
}
?>
