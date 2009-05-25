<?php
class SessionDriver
{

	private $data;
	public $lifeTimeDefault = 0;
	private $lifeTime = 0;

	function __construct() {
		$this->data = &$_SESSION['FCache_data'];
	}

	public function setConf( $lifeTime ) {
		$this->lifeTime = $lifeTime;
	}

	public function getGroup($group = 'default') {
		if(isset($this->data[$group])) {
			$arr = $this->data[$group];
			while($row = array_shift($arr)) {
				$arrUnserialized[] = unserialize($row[2]);
			}
			return $arrUnserialized;
		} else return false;
	}

	public function setData($id, $data, $group = 'default') {

		$this->data[$group][$id] = array($this->lifeTime, date("U") , serialize($data));
		 
	}

	public function getData($id, $group = 'default') {
		if(isset($this->data[$group][$id])) {
			if($this->data[$group][$id][0] + $this->data[$group][$id][1] > date("U") || $this->data[$group][$id][0]==0) {
				return unserialize($this->data[$group][$id][2]);
			} else {
				$this->invalidateData($id, $group);
			}
		} else {
			return false;
		}
	}

	public function invalidateData($id='',$group='default') {
		if(!empty($id)) {
			unset($this->data[$group][$id]);
		}
	}

	public function invalidateGroup( $group='default' ) {
		$this->data[$group] = array();
	}

	public function invalidate( ) {
		$this->data = array();
	}

}