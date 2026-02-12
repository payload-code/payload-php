<?php
namespace Payload;

require_once('ARMObject.php');

class Entity extends ARMObject {
    public static $spec = array('object'=>'entity', 'endpoint'=>'/entities');
}
?>
