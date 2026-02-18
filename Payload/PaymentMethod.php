<?php
namespace Payload;

require_once('ARMObject.php');

class PaymentMethod extends ARMObject
{
    public static $spec = ['object'=>'payment_method'];
}
