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
    }

    function request($method, $args=array()) {
        if ( isset($this->cls::$spec['endpoint']) )
            $endpoint = $this->cls::$spec['endpoint'];
        else
            $endpoint = '/'.$this->cls::$spec['object'] . 's';

        if (isset($args['id']))
            $endpoint .= '/'.$args['id'];

        $params = array_merge(array(), $this->filters);

        if (count($this->attrs))
            $params['fields'] = array_map(function ($item) {
                return strval($item);
            }, $this->attrs);

        if (count($this->group_by))
            $params['fields'] = array_map(function ($item) {
                return strval($item);
            }, $this->group_by);

        if (isset($args['mode']))
            $params['mode'] = $args['mode'];

        if (count($params))
            $endpoint .= '?'.http_build_query($params, '', '&');

        $req = curl_init(API::$api_url . $endpoint);
        curl_setopt($req, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($req, CURLOPT_USERPWD, API::$api_key . ':');
        curl_setopt($req, CURLOPT_RETURNTRANSFER, true);

        if (isset($args['json'])) {
           $json = json_encode($args['json']);
           curl_setopt($req, CURLOPT_POSTFIELDS, $json);
           curl_setopt($req, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: '.strlen($json)
           ));
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
            foreach( Utils::subclasses(Exceptions\PayloadError::class) as $exc ) {
                if ( $exc::getClassName() != $result['error_type']) continue;
                throw new $exc($result['error_description'], $result);
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
