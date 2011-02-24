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
		$data = array();
		$dataRaw = $this->cache->get('queue');
		if($dataRaw!==false) $data = unserialize($dataRaw);
		$data[]=array('type'=>$type,'data'=>$data);
		$cache->save(serialize($data));
	}

	public function process() {
		$dataRaw = $cache->get('queue');
		if($dataRaw===false) return;
		$data = unserialize($dataRaw);
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
		$cache->remove('queue');
	}
}