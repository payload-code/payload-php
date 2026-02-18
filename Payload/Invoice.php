<?php
namespace Payload;

require_once('ARMObject.php');

class Invoice extends ARMObject
{
    public static $spec = ['object'=>'invoice'];
}
