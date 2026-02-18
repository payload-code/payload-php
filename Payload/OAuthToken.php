<?php
namespace Payload;

require_once('ARMObject.php');

class OAuthToken extends ARMObject
{
    public static $spec = ['object'=>'oauth_token', 'endpoint'=>'/oauth/token'];
}
