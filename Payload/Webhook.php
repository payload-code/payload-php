<?php
namespace Payload;

require_once('ARMObject.php');

class Webhook extends ARMObject
{
    public static $spec = ['object'=>'webhook'];
}
