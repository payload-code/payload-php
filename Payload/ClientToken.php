<?php
namespace Payload;

require_once('ARMObject.php');

class ClientToken extends ARMObject
{
    public static $spec = ['object'=>'access_token', 'polymorphic_type'=>'client'];
}
