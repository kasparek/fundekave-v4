<?php
/**
 * memory driver for FCache
 * 
 * PHP versions 4 and 5
 * 
 * @author frantisek.kaspar
 *
 */
class LoadDriver
{

	var $data;

	var $lifeTimeDefault = 0;
	var $lifeTime = 0;

	function LoadDriver() {
		
	}
	
	function setConf( $lifeTime ) {
		$this->lifeTime = $lifeTime;
	}

	function getGroup($group = 'default') {
		if(isset($this->data[$group])) {
			$arr = $this->data[$group];
			while($row = array_shift($arr)) {
				$arrUnserialized[] = unserialize($row);
			}
			return $arrUnserialized;
		} else return false;
	}

	function setData($id, $data, $group = 'default') {
		$this->data[$group][$id] = serialize($data);
		return true;
	}

	function getData($id, $group = 'default') {
		if(isset($this->data[$group][$id])) {
			return unserialize($this->data[$group][$id]);
		} else return false;
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