<?php namespace Tancredi\Entity;

class Scope {
    public $id;
    public $data = array();
    public $metadata = array();
    private $storage;

    function __construct($id, $scopeType = null) {
        $this->id = $id;

        $this->storage = new FileStorage($id);
        $ini_array = $this->storage->read();

        if (array_key_exists('data',$ini_array)) {
            $this->data = $ini_array['data'];
        }

        if (array_key_exists('metadata',$ini_array)) {
            $this->metadata = $ini_array['metadata'];
        }

        if (!empty($scopeType) and $this->metadata['scopeType'] != $scopeType) {
            $this->metadata['scopeType'] = $scopeType;
            $this->setVariables();
        }
    }

    public function setParent($parent) {
        $this->metadata['inheritFrom'] = $parent;
        return $this->setVariables();
    }

    /* Get variables from parent and all its parents*/
    public function getParentsVariables() {
        $var_arrays = array();
        if (array_key_exists('inheritFrom',$this->metadata)) {
            $parent = $this->metadata['inheritFrom'];
        }

        while (!empty($parent) && $parent !== null) {
            $parentFileStorage = new FileStorage($parent);
            $variables = $parentFileStorage->read();
            if (array_key_exists('metadata',$variables) and array_key_exists('inheritFrom',$variables['metadata'])) {
                $parent = $variables['metadata']['inheritFrom'];
            } else {
                $parent = null;
            }
            if (array_key_exists('data',$variables)) {
                $var_arrays[] = $variables['data'];
            }
        }
        $var_arrays = array_reverse($var_arrays);
        if (!empty($var_arrays)) {
            return call_user_func_array('array_merge', $var_arrays);
        } else {
            return array();
        }
    }

    /* Get variables from current scope and from all parents*/
    public function getVariables(){
       // Read variables from current scope
       $parents_vars = $this->getParentsVariables();
       $var_arrays[] = $parents_vars;
       $var_arrays[] = $this->data;
       $this->setLastReadTime();
       return call_user_func_array('array_merge', $var_arrays);
    }

    public function setVariables($data = array()) {
        $this->data = array_merge($this->data,$data);
        $this->metadata['last_edit_time'] = time();
        return $this->writeToStorage();
    }

    private function writeToStorage() {
        return $this->storage->write(array('metadata' => $this->metadata, 'data' => $this->data));
    }

    public function setLastReadTime() {
        $this->metadata['last_read_time'] = time();
        $this->writeToStorage();
    }

    public function getLastEditTime() {
        if (array_key_exists('inheritFrom',$this->metadata)) {
            $inheritFrom = $this->metadata['inheritFrom'];
        }
        if (array_key_exists('last_edit_time',$this->metadata)) {
            $last_edit_time = $this->metadata['last_edit_time'];
        } else { 
            $last_edit_time = 0;
        }
        while (!empty($inheritFrom) && $inheritFrom !== null) {
            $parent = new Scope($inheritFrom);
            if (array_key_exists('inheritFrom',$parent->metadata) and !empty($parent->metadata['inheritFrom'])) { 
                $inheritFrom = $parent->metadata['inheritFrom'];
            } else {
                $inheritFrom = null;
            }
            if (!empty($parent->metadata['last_edit_time']) and $parent->metadata['last_edit_time'] > $last_edit_time) {
                $last_edit_time = $parent->metadata['last_edit_time'];
            }
        }
        return $last_edit_time;
    }

    public function getLastReadTime() {
        if (array_key_exists('last_read_time',$this->metadata)) {
            return $this->metadata['last_read_time'];
        }
        return null;
    }
}

