<?php
class FConf
{

	/**
	 * SINGLETON for main config file
	 */
	private static $instance;
	static function &getInstance($filename = '') {
		if (!isset(self::$instance)) {
			self::$instance = new FConf();
			if(!empty($filename)) {
				self::$instance->loadConfigFile($filename);
			}
		}
		return self::$instance;
	}

	public $a;

	function loadConfigFile($filename) {
		if(file_exists($filename)) {

			$arr = explode('/',$filename);
			$configFilename = array_pop($arr);
			$configClassnameArr = explode('.',$configFilename);
			$type = array_pop($configClassnameArr); //remove extension
			$configClassname = implode('_',$configClassnameArr);
			 
			if($this->type=='php') {
				$configParsed[$configClassname] = get_object_vars(new $configClassname());
			} else {
				$configParsed = parse_ini_file($filename, true);
			}
			 
			if(!empty($configParsed["phpdefined"])) {
				foreach($configParsed["phpdefined"] as $k=>$v) {
					define(strtoupper($k),$v);
				}
			}
			$configParsed["phpdefined"] = array();
			 
			if(!empty($configParsed["include_path"])) {
				$includePath = implode(PATH_SEPARATOR,$configParsed["include_path"]);
				if(!empty($configParsed["settings"]["include_path_append"])) {
					$includePath = get_include_path() . PATH_SEPARATOR .  $includePath;
				}
				set_include_path( $includePath );
			}
			$configParsed["include_path"] = array();
				
			//---load language
			if(!empty($configParsed['language']['path'])) {
				require(ROOT.$configParsed['language']['path'].$configParsed['language']['filename']);
			}
				
			foreach($configParsed as $k=>$v) {
				$this->a[$k] = $v;
			}
				
		} else {
			die('Error: unable to locate config file');
		}
	}

	function save($branch) {

		foreach($this->a[$branch] as $k=>$v) {
			$values[] = "var $".$k." = ".((is_bool($v) || is_numeric($v))?($v):("'".$v."'")).";\n";
		}

		$data = "<?php\n"
		."class ".$branch." {\n"
		.implode("\n",$values)
		."}";

		$fh = fopen(str_replace('_','.',$branch).'.php','w');
		fwrite($fh,$data);
		fclose($fh);

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
