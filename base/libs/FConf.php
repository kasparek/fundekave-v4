<?php
if (!function_exists('array_replace_recursive'))
{
function array_replace_recursive($base, $replacements) { 
	foreach (array_slice(func_get_args(), 1) as $replacements) { 
		$bref_stack = array(&$base); 
		$head_stack = array($replacements); 
		do { 
			end($bref_stack); 
			$bref = &$bref_stack[key($bref_stack)]; 
			$head = array_pop($head_stack); 
			unset($bref_stack[key($bref_stack)]); 
			foreach (array_keys($head) as $key) { 
				if (isset($key, $bref) && isset($bref[$key]) && isset($head[$key]) && is_array($bref[$key]) && is_array($head[$key])) {
					$bref_stack[] = &$bref[$key]; 
					$head_stack[] = $head[$key]; 
				} else { 
					if(strpos($head[$key], '+=')===0) {
						$bref[$key] .= substr($head[$key], 2);
					} else {
						$bref[$key] = $head[$key];
					}
				} 
			} 
		} while(count($head_stack)); 
	} 
	return $base; 
}
}

class FConf
{

	/**
	 * SINGLETON for main config file
	 */
	private static $instance;
	static function &getInstance($filename = array(),$host=null) {
		if (!isset(self::$instance)) {
			self::$instance = new FConf();
			if(!empty($filename)) {
				self::$instance->loadConfigFile($filename);
			}
			if(!empty($host)) {
				self::$instance->host = $host;
			}
		}
		return self::$instance;
	}

	private $type;

	public $host;

	public $a;

	function loadConfigFile($filenameList) {
		$configParsed = array();
		$hasAtLeastOne = false;
		foreach($filenameList as $filename) {
			if(file_exists($filename)) {
				$arr = explode('/',$filename);
				$configFilename = array_pop($arr);
				$configClassnameArr = explode('.',$configFilename);
				$this->type = array_pop($configClassnameArr); //remove extension
				$configClassname = implode('_',$configClassnameArr);
				if($this->type=='php') {
					$configParsed[$configClassname] = get_object_vars(new $configClassname());
				} else {
					$configParsed = array_replace_recursive($configParsed, parse_ini_file($filename, true));
				}
				$hasAtLeastOne=true;
			}
		}

		if(!$hasAtLeastOne) {
			die('Error: unable to locate config file '.$filename);
		}

		if(!empty($configParsed)) {
			if(!empty($configParsed["phpdefined"])) {
				foreach($configParsed["phpdefined"] as $k=>$v) {
					define(strtoupper($k),$v);
				}
			}
			if(!empty($configParsed["include_path"])) {
				$includePath = implode(PATH_SEPARATOR,$configParsed["include_path"]);
				if(!empty($configParsed["settings"]["include_path_append"])) {
					$includePath = get_include_path() . PATH_SEPARATOR .  $includePath;
				}
				set_include_path( $includePath );
			}
			unset($configParsed["include_path"]);
			$this->a = $configParsed;
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

	static function host($host=null) {
		$conf = FConf::getInstance();
		if(!empty($host)) $cong->host = $host;
		return $conf->host;
	}
}
