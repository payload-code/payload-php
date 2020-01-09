<?php
namespace Payload;

require_once('ARMObject.php');

class Org extends ARMObject {
    public static $spec = array('object'=>'org', 'endpoint'=>'/accounts/orgs');
}
?>
