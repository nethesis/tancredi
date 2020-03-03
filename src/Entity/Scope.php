<?php namespace Tancredi\Entity;

include_once(__DIR__.'/../init.php');

class Scope {
    public $id;
    public $data = array();
    public $metadata = array();
    private $storage;
    private $logger;

    function __construct($id, $storage, $logger, $scopeType = null, $original = false) {
        $this->id = $id;

        $this->storage = $storage;
        $this->logger = $logger;

        $ini_array = $this->storage->storageRead($id,$original);

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
            $variables = $this->storage->storageRead($parent);
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
       return call_user_func_array('array_merge', $var_arrays);
    }

    public function setVariables($data = array()) {
        $this->data = array_merge($this->data,$data);
        foreach ($data as $key => $value) {
            if (is_null($value)) {
                unset($this->data[$key]);
            }
        }
        $this->metadata['last_edit_time'] = time();
        return $this->writeToStorage();
    }

    private function writeToStorage() {
        return $this->storage->storageWrite($this->id,array('metadata' => $this->metadata, 'data' => $this->data));
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
            $parent = new Scope($inheritFrom,$this->storage, $this->logger);
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

    public static function getPhoneScope($mac, $storage, $logger, $inherit = false) {
        global $config;
        $scope = new \Tancredi\Entity\Scope($mac, $storage, $logger, null);
        $vars = $scope->getVariables();

        $hostname = empty($vars['hostname']) ? gethostname() : $vars['hostname'];
        $provisioning_url_path = trim($config['provisioning_url_path'], '/') . '/';
        $provisioning_url_scheme = empty($vars['provisioning_url_scheme']) ? 'http' : $vars['provisioning_url_scheme'];

        if ($inherit) {
            $scope_data = $vars;
            $scope_data['hostname'] = $hostname;
            $scope_data['provisioning_url_path'] = $provisioning_url_path;
            $scope_data['provisioning_url_scheme'] = $provisioning_url_scheme;
        } else {
            $scope_data = $scope->data;
        }
        $tok1 = \Tancredi\Entity\TokenManager::getToken1($mac);
        $tok2 = \Tancredi\Entity\TokenManager::getToken2($mac);
        $results = array(
            'mac' => $mac,
            'model' => $scope->metadata['inheritFrom'],
            'display_name' => $scope->metadata['displayName'],
            'tok1' => $tok1,
            'tok2' => $tok2,
            'variables' => empty($scope_data) ? new \stdClass() : $scope_data,
            'model_url' => $config['api_url_path'] . "models/" . $scope->metadata['inheritFrom']
        );

        if($tok1 && $provisioning_url_scheme && $hostname) {
            $results['provisioning_url1'] = "{$provisioning_url_scheme}://{$hostname}/{$provisioning_url_path}{$tok1}/{$vars['provisioning_url_filename']}";
        } else {
            $results['provisioning_url1'] = NULL;
        }
        if($tok2 && $provisioning_url_scheme && $hostname) {
            $results['provisioning_url2'] = "{$provisioning_url_scheme}://{$hostname}/{$provisioning_url_path}{$tok2}/{$vars['provisioning_url_filename']}";
        } else {
            // Never return back an invalid provisioning URL!
            throw new \LogicException(sprintf("%s - malformed provisioning_url2", 1582905675));
        }
        return $results;
    }
}

