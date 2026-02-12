<?php
namespace Payload;
use Payload\Utils;
include('Exceptions.php');

class ARMRequest {

    function __construct($cls) {
        $this->cls = $cls;
        $this->filters = array();
        $this->attrs = array();
        $this->group_by = array();
        $this->order_by = array();
    }

    function request($method, $args=array()) {
        if ( isset($this->cls::$spec['endpoint']) )
            $endpoint = $this->cls::$spec['endpoint'];
        else {
            $object = $this->cls::$spec['object'];
            $endpoint = '/'.$object . (substr($object, -1) === 's' ? '' : 's');
        }

        if (isset($args['id']))
            $endpoint .= '/'.$args['id'];

        $params = array_merge(array(), $this->filters);

        if (count($this->attrs))
            $params['fields'] = array_map(function ($item) {
                return strval($item);
            }, $this->attrs);

        if (count($this->group_by))
            $params['group_by'] = array_map(function ($item) {
                return strval($item);
            }, $this->group_by);

        if (count($this->order_by))
            $params['order_by'] = array_map(function ($item) {
                return strval($item);
            }, $this->order_by);

        if (isset($this->limit))
            $params['limit'] = $this->limit;

        if (isset($this->offset))
            $params['offset'] = $this->offset;

        if (isset($args['mode']))
            $params['mode'] = $args['mode'];

        if ($this->cls::$default_params) {
            foreach ($this->cls::$default_params as $k => $v) {
                if (!isset($params[$k]))
                    $params[$k] = $v;
            }
        }

        if (count($params))
            $endpoint .= '?'.http_build_query($params, '', '&');

        $req = curl_init(API::$api_url . $endpoint);
        curl_setopt($req, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($req, CURLOPT_USERPWD, API::$api_key . ':');
        curl_setopt($req, CURLOPT_RETURNTRANSFER, true);

        $headers = array();

        if (isset($args['json'])) {
           $json = json_encode($args['json']);
           curl_setopt($req, CURLOPT_POSTFIELDS, $json);
           $headers[] = 'Content-Type: application/json';
           $headers[] = 'Content-Length: '.strlen($json);
        }

        if (isset(API::$api_version)) {
           $headers[] = 'X-API-Version: '.API::$api_version;
        }

        if (count($headers) > 0) {
           curl_setopt($req, CURLOPT_HTTPHEADER, $headers);
        }

        $resp    = curl_exec($req);
        $status_code = curl_getinfo($req, CURLINFO_HTTP_CODE);
        curl_close($req);

        $result = json_decode($resp, true);
        if ( $result === null ) {
            if ( $status_code == 500 ) throw new Exceptions\InternalServerError();
            throw new Exceptions\UnknownResponse();
        }

        if ( !isset($result['object']) )
            return $result;

        if ( $status_code == 200 ) {
            if ( $result['object'] == 'list' )
                return array_map(function($item) { return $this->cls::new($item); },
                    $result['values']);
            return $this->cls::new($result);
        }

        if ( $result['object'] == 'error' ) {
            foreach( get_declared_classes() as $cls ) {
                if ( !is_subclass_of($cls, Exceptions\PayloadError::class) ) continue;
                if ( $cls::getClassName() != $result['error_type']) continue;
                throw new $cls($result['error_description'], $result);
            }
            throw new Exceptions\BadRequest($result['error_description'], $result);
        }

        throw new Exceptions\UnknownResponse();

    }

    public function get($id) {
        return self::request('GET', array('id'=>$id));
    }

    public function select(...$attrs) {
        $this->attrs = array_merge($this->attrs, $attrs);
        return $this;
    }

    public function group_by(...$attrs) {
        $this->group_by = array_merge($this->group_by, $attrs);
        return $this;
    }

    public function order_by(...$attrs) {
        $this->order_by = array_merge($this->order_by, $attrs);
        return $this;
    }

    public function limit($limit) {
        $this->limit = $limit;
        return $this;
    }

    public function offset($offset) {
        $this->offset = $offset;
        return $this;
    }

    public function create($obj) {
        if ( !Utils::is_assoc_array( $obj ) )
            $obj = array('object'=>'list', 'values'=>$obj);
        $obj = Utils::object2data($obj);
        return self::request('POST', array('json'=>$obj));
    }

    public function delete($obj) {
        if ( !Utils::is_assoc_array( $obj ) )
            $obj = array('object'=>'list', 'values'=>$obj);
        $obj = Utils::object2data($obj);
        return self::request('DELETE', array('json'=>$obj, 'mode'=>'query'));
    }

    public function update($obj) {
        if ( !Utils::is_assoc_array( $obj ) )
            $obj = array('object'=>'list', 'values'=>$obj);
        if ( $obj instanceof ARMObject )
            $obj = $obj->data();
        else
            $obj = Utils::object2data($obj);
        return self::request('PUT', array('json'=>$obj, 'mode'=>'query'));
    }

    public function filter_by(...$filters) {
        $filters = call_user_func_array('array_merge', $filters);
        $this->filters = array_merge($filters, $this->filters);
        return $this;
    }

    public function all() {
        return $this->request('get');
    }
}

?>
