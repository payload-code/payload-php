<?php
namespace Payload;

require_once('ARMObject.php');

class Org extends ARMObject
{
    public static $spec = ['object'=>'org', 'endpoint'=>'/accounts/orgs'];
}
