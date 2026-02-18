<?php
namespace Payload;

require_once('ARMObject.php');

class InvoiceItem extends ARMObject
{
    public static $spec = ['object'=>'invoice_item'];
}
