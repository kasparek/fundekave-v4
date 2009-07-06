<?php
class FVO {
	
	//---watcher
	var $saveOnlyChanged = false;
	var $changed = false;
	
	function set($key, $value, $params=array()) {
		$changed = false;
		if(property_exists($this,$key)) {
			//---verify
			if(isset($params['type'])) {
				switch($params['type']) {
					case 'date':
						$value = FSystem::switchDate($value);
						if(true !== FSystem::isDate($value)) return false;
						break;
				}
			}
			//---check if changed
			if($this->{$key} != $value) {
				$changed = true;
				$this->changed = $changed;
			}
			//---set
			$this->{$key} = $value;
				
		}
		return $changed;
	}
	
}