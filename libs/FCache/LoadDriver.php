<?php
class LoadDriver
{

	public $data;

	public $lifeTimeDefault = 0;
	private $lifeTime = 0;

	public function setConf( $lifeTime ) {
		$this->lifeTime = $lifeTime;
	}

	public function getGroup($group = 'default') {
		if(isset($this->data[$group])) {
			$arr = $this->data[$group];
			while($row = array_shift($arr)) {
				$arrUnserialized[] = unserialize($row);
			}
			return $arrUnserialized;
		} else return false;
	}

	public function setData($id, $data, $group = 'default') {
		$this->data[$group][$id] = serialize($data);
	}

	public function getData($id, $group = 'default') {
		if(isset($this->data[$group][$id])) {
			return unserialize($this->data[$group][$id]);
		} else return false;
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