<?php
namespace Payload;

require_once('ARMObject.php');

class PaymentLink extends ARMObject
{
    public static $spec = ['object'=>'payment_link'];
}
