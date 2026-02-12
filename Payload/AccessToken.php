<?php
namespace Payload;

require_once('ARMObject.php');

class AccessToken extends ARMObject
{
    public static $spec = ['object'=>'access_token'];
}
