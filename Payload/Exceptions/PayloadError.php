<?php
namespace Payload\Exceptions;

class PayloadError extends \Exception {
	public static $status_code = null;
    public static function getClassName() {
        return static::class;
    }
}
?>
