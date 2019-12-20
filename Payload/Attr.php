<?php
namespace Payload;

class Attr {
    private $is_method = false;

    function __construct($param=null, $parent=null) {
        $this->param = $param;
        $this->parent = $parent;

        if ( !$this->parent || !$this->parent->key )
            $this->key = $this->param;
        else
            $this->key = $this->parent->key . '[' . $this->param . ']';
    }

    public function __get($key) {
        return new Attr($key, $this);
    }

    public function __toString() {
        if ($this->is_method)
            return $this->param . '(' . $this->parent->key . ')';
        return $this->key;
    }

    public function eq($val) {
        $ret = array();
        $ret[strval($this)] = $val;
        return $ret;
    }

    public function ne($val) {
        return $this->eq('!'.$val);
    }

    public function lt($val) {
        return $this->eq('<'.$val);
    }

    public function le($val) {
        return $this->eq('<='.$val);
    }

    public function gt($val) {
        return $this->eq('>'.$val);
    }

    public function ge($val) {
        return $this->eq('>='.$val);
    }

    public function contains($val) {
        return $this->eq('?*'.$val.'*');
    }
}

?>
