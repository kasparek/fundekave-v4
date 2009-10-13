<?php
class FConf
{
	private static $instance;
	static function &getInstance() {
		if (!isset(self::$instance)) {
			self::$instance = new FConf();
			self::$instance->loadConfigFile();
		}
		return self::$instance;
	}
  
  public $a;
  
  function loadConfigFile() {
    if(file_exists(CONFIG_FILENAME)){
    	$this->a = parse_ini_file(CONFIG_FILENAME, true);
    	if(!empty($this->a["phpdefined"]))
    	foreach($this->a["phpdefined"] as $k=>$v) define(strtoupper($k),$v);
    	$this->a["phpdefined"] = array();
    	if(!empty($this->a["include_path"])) {
    		$includePath = implode(PATH_SEPARATOR,$this->a["include_path"]);
    		if(!empty($this->a["settings"]["include_path_append"])) {
    			$includePath = get_include_path() . PATH_SEPARATOR .  $includePath;
				}
				set_include_path( $includePath );
			}
    	//---load language
    	if(!empty($this->a['language']['path'])) {
				require(ROOT.$this->a['language']['path'].$this->a['language']['filename']);
			}
    } else {
    	die('Error: unable to locate config file');
    }
  }
    
  static function get($group, $prop='') {
  	$conf = FConf::getInstance();
  	if($prop == '') {
  		if(isset($conf->a[$group])) {
  			return $conf->a[$group];
  		}
  	} else {
  		if(isset($conf->a[$group][$prop])) {
  			return $conf->a[$group][$prop];
  		}	
  	}
  	return false;
  }
}
