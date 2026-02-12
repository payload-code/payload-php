<?php
namespace Payload;

require_once('ARMRequest.php');

class ARMObject
{
    private static $_object_cache = [];
    private static $_registry = [];
    public static $spec = [];
    public static $default_params = null;
    public static $type = null;

    public static function getRegistry()
    {
        if (empty(self::$_registry)) {
            foreach (glob(__DIR__ . '/*.php') as $file) {
                require_once($file);
            }
            foreach (get_declared_classes() as $class) {
                if (is_subclass_of($class, self::class) && isset($class::$spec['object'])) {
                    self::$_registry[$class::$spec['object']] = $class;
                }
            }
        }
        return self::$_registry;
    }

    public static function new($data)
    {
        $class = get_called_class();
        if (isset($data['id']) && isset(self::$_object_cache[$data['id']])) {
            $cached_object = self::$_object_cache[$data['id']];
            $cached_object->data($data);
            return $cached_object;
        }
        return new $class($data);
    }

    function __construct($data)
    {
        if ($data !== null) {
            $this->data($data);
        }
    }

    private static function _build_request($apply_polymorphic = false)
    {
        $filters = [];
        if ($apply_polymorphic && isset(get_called_class()::$spec['polymorphic_type'])) {
            array_push($filters, ['type' => get_called_class()::$spec['polymorphic_type']]);
        }

        $req = new ARMRequest(get_called_class());
        return call_user_func_array([$req, 'filter_by'], $filters);
    }

    public function __get($name)
    {
        if (array_key_exists($name, $this->_data)) {
            return $this->_data[$name];
        } else {
            throw new \Exception("$name does not exist");
        }
    }

    public function json()
    {
        return json_encode($this->data(), JSON_PRETTY_PRINT);
    }


    public function data($data = null)
    {
        if ($data !== null) {
            $this->_data = Utils::data2object($data);
            if (isset($data['id'])) {
                self::$_object_cache[$this->id] = $this;
            }
        } else {
            return Utils::object2data($this->_data);
        }
    }

    public function update($update)
    {
        return $this->_build_request()->request(
            'put',
            ['id'=>$this->id, 'json'=>$update]
        );
    }

    public function delete($update = null)
    {
        if ($update !== null) {
            return $this->_build_request()->request(
                'delete',
                ['id'=>$update->id]
            );
        } else {
            return $this->_build_request()->request(
                'delete',
                ['id'=>$this->id]
            );
        }
    }

    public static function get($id)
    {
        return self::_build_request()->get($id);
    }

    public static function all()
    {
        return self::_build_request()->all();
    }

    public static function filter_by(...$filters)
    {
        $req = self::_build_request(true);
        return call_user_func_array([$req, 'filter_by'], $filters);
    }

    public static function create($obj)
    {
        if (isset(get_called_class()::$spec['polymorphic_type'])) {
            $obj['type'] = get_called_class()::$spec['polymorphic_type'];
        }

        return self::_build_request()->create($obj);
    }

    public static function select(...$attrs)
    {
        $req = self::_build_request(true);
        return call_user_func_array([$req, 'select'], $attrs);
    }

    public static function order_by(...$attrs)
    {
        $req = self::_build_request(true);
        return call_user_func_array([$req, 'order_by'], $attrs);
    }

    public static function limit($limit)
    {
        return self::_build_request(true)->limit($limit);
    }

    public static function offset($offset)
    {
        return self::_build_request(true)->offset($offset);
    }

    public static function delete_all(...$objs)
    {

        $func = function ($value) {
            return $value->id;
        };

        return self::_build_request()->request(
            'delete',
            ['id'=>join("|", array_map($func, $objs))]
        );
    }
}
