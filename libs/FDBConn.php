<?php
class FDBConn
{
	private static $instance;
	static function &getInstance() {
		if (!isset(self::$instance)) {
			$conf = FConf::getInstance();
			require_once('DB.php');
			self::$instance = & DB::connect($conf->a['db'], $conf->a['dboptions']);
			if (PEAR::isError(self::$instance)) die(self::$instance->getMessage());
			self::$instance->query("set character_set_client = utf8");
			self::$instance->query("set character_set_connection= utf8");
			self::$instance->query("set character_set_results = utf8");
			self::$instance->query("set character_name = utf8");
		}
		return self::$instance;
	}
}
