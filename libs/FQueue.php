<?php
class FQueue {
	private static $instance;
	private static $allowInstantiation = false;

	private $cache;

	function __construct() {
		if(self::$allowInstantiation==true) {
			if($this->cache) return $this->cache;
			$cacheDir = FConf::get('settings','logs_path').'queue/';
			if(!is_dir($cacheDir)) mkdir($cacheDir,0777,true);
			$this->cache = new FCacheFile(array('cacheDir'=>$cacheDir));
		}
	}

	static function &getInstance() {
		if (!isset(self::$instance)) {
			self::$allowInstantiation = true;
			self::$instance = new FQueue();
			self::$allowInstantiation = false;
		}
		return self::$instance;
	}

	public function push($type,$data) {
		$q = $this->cache->get('queue');
		if(!empty($q)) $q = unserialize($q);
		else $q=array();
		$q[]=array('type'=>$type,'data'=>$data);
		$this->cache->save(serialize($q));
	}

	public function process() {
		$data = $this->cache->get('queue');
		if($data===false) return;
		$data = unserialize($data);
		if(!empty($data)) {
			foreach($data as $q) {
				switch($q['type']) {
					case 'query':
						$db = FDBConn::getInstance();
						$db->query($q['data']);
						break;
					case 'invalidate':
						FSystem::superInvalidateHandle($q['data']);
						break;
				}
				FError::write_log("FQueue::Process - ".$q['data']);
			}
		}
		$this->cache->remove('queue');
	}
}