<?php namespace Tancredi\Entity;

define ("DATA_DIR", "/usr/share/nethvoice/tancredi/data/");

class Scope {
    public $name;
    public $vars = array();
    public $inheritFrom = null;
    private $scopeType;
    public $displayName;

    function __construct($name, $scopeType) {
        $this->name = $name;
        $this->scopeType = $scopeType;
        
        $ini_array = $this->readIniFile($this->name . '.ini');
        if (array_key_exists('data',$ini_array)) {
            $this->vars = $ini_array['data'];
        }
        if (array_key_exists('metadata',$ini_array) and array_key_exists('inheritFrom',$ini_array['metadata'])) {
            $this->inheritFrom = $ini_array['metadata']['inheritFrom'];
        }
    }

    public function setParent($parent) {
        $this->inheritFrom = $parent;
        return setVars(array());
    }

    /* Get variables from current scope and from all parents*/
    public function getVariables(){
       // Read variables from current scope
       $var_arrays = array();
       $var_arrays[] = $this->vars;
       $parent = $this->inheritFrom;

       while ($parent !== null) {
           $variables = $this->readIniFile($parent . '.ini');
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
       return call_user_func_array('array_merge', $var_arrays);
    }

    /* Get variables from selected scope*/
    private function readIniFile($inifile) {
        // Read vars from file
        if (file_exists(DATA_DIR . '/scopes/' . $inifile)){
            $inifile = DATA_DIR . '/scopes/' . $inifile;
        } else {
            return array();
        }
        $ini_array = parse_ini_file($inifile, $process_sections = TRUE);
        return $ini_array;
    }

    public function setVars($vars) {
        $this->vars = array_merge($this->vars,$vars);
        $ini_array = array(
            'metadata' => array(
                'inheritFrom' => $this->inheritFrom,
                'scopeType' => $this->scopeType,
                'displayName' => $this->displayName		
            ),
            'data' => $this->vars
        );
        return $this->writeIniFile(DATA_DIR . '/scopes/' . $this->name . '.ini',$ini_array);
    }

    /**
     * Write an ini configuration file
     * 
     * @param string $file
     * @param array  $array
     * @return bool
     */
    private function writeIniFile($file, $array = []) {
        // check first argument is string
        if (!is_string($file)) {
            throw new \InvalidArgumentException('Function argument 1 must be a string.');
        }

        // check second argument is array
        if (!is_array($array)) {
            throw new \InvalidArgumentException('Function argument 2 must be an array.');
        }

        // process array
        $data = array();
        foreach ($array as $key => $val) {
            if (is_array($val)) {
                $data[] = "[$key]";
                foreach ($val as $skey => $sval) {
                    if (is_array($sval)) {
                        foreach ($sval as $_skey => $_sval) {
                            if (is_numeric($_skey)) {
                                $data[] = $skey.'[] = '.(is_numeric($_sval) ? $_sval : (ctype_upper($_sval) ? $_sval : '"'.$_sval.'"'));
                            } else {
                                $data[] = $skey.'['.$_skey.'] = '.(is_numeric($_sval) ? $_sval : (ctype_upper($_sval) ? $_sval : '"'.$_sval.'"'));
                            }
                        }
                    } else {
                        $data[] = $skey.' = '.(is_numeric($sval) ? $sval : (ctype_upper($sval) ? $sval : '"'.$sval.'"'));
                    }
                }
            } else {
                $data[] = $key.' = '.(is_numeric($val) ? $val : (ctype_upper($val) ? $val : '"'.$val.'"'));
            }
            // empty line
            $data[] = null;
        }

        // open file pointer, init flock options
        $fp = fopen($file, 'w');
        $retries = 0;
        $max_retries = 100;

        if (!$fp) {
            return false;
        }

        // loop until get lock, or reach max retries
        do {
            if ($retries > 0) {
                usleep(rand(1, 5000));
            }
            $retries += 1;
        } while (!flock($fp, LOCK_EX) && $retries <= $max_retries);

        // couldn't get the lock
        if ($retries == $max_retries) {
            return false;
        }

        // got lock, write data
        fwrite($fp, implode(PHP_EOL, $data).PHP_EOL);

        // release lock
        flock($fp, LOCK_UN);
        fclose($fp);

        return true;
    }
}
