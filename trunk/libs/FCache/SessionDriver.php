<?php
/**
 * session driver for FCache
 * 
 * PHP versions 4 and 5
 * 
 * @author frantisek.kaspar
 *
 */
class SessionDriver
{

	var $data;
	var $lifeTimeDefault = 0;
	var $lifeTime = 0;

	function SessionDriver() {
		$this->data = &$_SESSION['FCache_data'];
	}

	function setConf( $lifeTime ) {
		$this->lifeTime = $lifeTime;
	}

	function getGroup($group = 'default') {
		if(isset($this->data[$group])) {
			$arr = $this->data[$group];
			while($row = array_shift($arr)) {
				$arrUnserialized[] = unserialize($row[2]);
			}
			return $arrUnserialized;
		} else return false;
	}

	function setData($id, $data, $group = 'default') {

		$this->data[$group][$id] = array($this->lifeTime, date("U") , serialize($data));
			
	}

	function getData($id, $group = 'default') {
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

	function invalidateData($id='',$group='default') {
		if(!empty($id)) {
			unset($this->data[$group][$id]);
		}
	}

	function invalidateGroup( $group='default' ) {
		$this->data[$group] = array();
	}

	function invalidate( ) {
		$this->data = array();
	}
}