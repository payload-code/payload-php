<?php
namespace Payload;

class Utils {
    public static function subclasses($parent) {
        $result = array();
        foreach (get_declared_classes() as $class) {
            if (is_subclass_of($class, $parent))
                $result[] = $class;
        }
        return $result;
    }

    public static function is_assoc_array($arr) {
        return is_array($arr) && array_keys($arr) !== range(0, count($arr) - 1);
    }

    public static function data2object($data) {
        $object = array();
        foreach ( $data as $key => $val ) {
            $object[$key] = $val;
            if ( Utils::is_assoc_array($val) && isset($val['object']) ) {
                foreach (Utils::subclasses(ARMObject::class) as $cls) {
                    if ($cls::$spec['object'] == $val['object']) {
                        $object[$key] = $cls::new($val);
                        break;
                    }
                }
            } else if (is_array($val)) {
                $object[$key] = self::data2object($val);
            }
        }
        return $object;
    }

    public static function object2data($object) {
        $data = array();
        foreach ( $object as $key => $val ) {
            $data[$key] = $val;
            if ( $val instanceof ARMObject ) {
                $data[$key] = $val->data();
            } else if (is_array($val)) {
                $data[$key] = self::object2data($val);
            }
        }
        return $data;
    }
}
?>
