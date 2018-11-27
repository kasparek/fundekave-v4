<?php
class FQueue
{
    private static $instance;
    private static $allowInstantiation = false;

    private $cache;

    public $to_log = '';

    public function __construct()
    {
        if (self::$allowInstantiation == true) {
            if ($this->cache) {
                return $this->cache;
            }

            $cacheDir = FConf::get('settings', 'tmp') . 'queue/';
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0777, true);
            }

            $this->cache = new FCacheFile(array('cacheDir' => $cacheDir));
        }
    }

    public static function &getInstance()
    {
        if (!isset(self::$instance)) {
            self::$allowInstantiation = true;
            self::$instance           = new FQueue();
            self::$allowInstantiation = false;
        }
        return self::$instance;
    }

    public function push($type, $data)
    {
        $q = $this->cache->get('queue');
        if (!empty($q)) {
            $q = unserialize($q);
        } else {
            $q = array();
        }

        $q[] = array('type' => $type, 'data' => $data);
        $this->cache->save(serialize($q));
    }

    public function process()
    {
        $data = $this->cache->get('queue');
        if ($data === false) {
            return;
        }

        $data = unserialize($data);
        if (empty($data)) {
            return;
        }

        $grouped = array();
        foreach ($data as $qrow) {
        	$md5 = md5($qrow['type'].$qrow['data']);
        	$grouped[$md5] = $qrow;
        }
		$data = array_values($grouped);

        $limit  = 100;
        $i      = 0;
        $length = count($data);
        while (count($data) > 0) {
            $q = array_shift($data);
            switch ($q['type']) {
                case 'query':
                    $db = FDBConn::getInstance();
                    $db->query($q['data']);
                    break;
                case 'invalidate':
                    FSystem::superInvalidateHandle($q['data']);
                    break;
            }
            $i++;
            if ($i > $limit) {
                break;
            }

        }
        $this->to_log = "FQueue::Process - " . $i . "/" . $length;
        FError::write_log($this->to_log);

        if (!empty($data)) {
            $this->cache->save(serialize($data));
        } else {
            $this->cache->remove('queue');
        }
    }
}
