<?php
namespace Payload;

require_once('ARMObject.php');

class PaymentMethod extends ARMObject {
    public static $spec = array('object'=>'payment_method');
}
?>
