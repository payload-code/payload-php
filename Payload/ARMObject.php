<?php
namespace Payload;

require_once('ARMRequest.php');

class ARMObject {
    private static $_object_cache = array();
    public static $spec = array();
    public static $type = null;

    public static function new($data) {
        $class = get_called_class();
        if ( isset($data['id']) && isset(self::$_object_cache[$data['id']]) ) {
            $cached_object = self::$_object_cache[$data['id']];
            $cached_object->data($data);
            return $cached_object;
        }
        return new $class($data);
    }

    function __construct($data) {
        if ($data !== null)
            $this->data($data);
    }

    public function __get($name) {
        if (array_key_exists($name, $this->_data))
            return $this->_data[$name];
        else
            throw new \Exception("$name does not exist");
    }

    public function json() {
        return json_encode($this->data(), JSON_PRETTY_PRINT);
    }


    public function data($data=null) {
        if ( $data !== null ) {
            $this->_data = Utils::data2object($data);
            if ( isset($data['id']) )
                self::$_object_cache[$this->id] = $this;
        } else {
            return Utils::object2data($this->_data);
        }
    }

    public function update($update) {
        return (new ARMRequest(get_called_class()))->request('put',
            array('id'=>$this->id, 'json'=>$update));
    }

    public function delete($update=null) {
        if ($update !== null){
            return (new ARMRequest(get_called_class()))->request('delete',
            array('id'=>$update->id));
        } else {
            return (new ARMRequest(get_called_class()))->request('delete',
                array('id'=>$this->id));
        }
    }

    public static function get($id) {
        return (new ARMRequest(get_called_class()))->get($id);
    }

    public static function filter_by(...$filters) {
        if ( isset(get_called_class()::$spec['polymorphic_type']) )
            array_push($filters, ['type' => get_called_class()::$spec['polymorphic_type']]);

        $req = new ARMRequest(get_called_class());
        return call_user_func_array(array($req, 'filter_by'), $filters);
    }

    public static function create($obj) {
        if ( isset(get_called_class()::$spec['polymorphic_type']) )
            $obj['type'] = get_called_class()::$spec['polymorphic_type'];

        return (new ARMRequest(get_called_class()))->create($obj);
    }

    public static function select(...$attrs) {
        $req = new ARMRequest(get_called_class());
        return call_user_func_array(array($req, 'select'), $attrs);
    }

    public static function delete_all(...$objs) {

        $func = function($value) {
            return $value->id;
        };

        return (new ARMRequest(get_called_class()))->request('delete',
            array('id'=>join("|",array_map($func, $objs))));
    }
}
?>
