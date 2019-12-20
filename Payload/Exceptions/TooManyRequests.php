<?php
namespace Payload\Exceptions;

class TooManyRequests extends PayloadError {
	public static $status_code = 429;
}
?>
