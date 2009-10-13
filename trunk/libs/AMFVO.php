<?php
class AMFVO 
{

  public $_explicitType;
	  
  private function getVodb() {
  	$className = get_called_class().'DB';
		$vodb = new $className();
		foreach($this as $k=>$v) {
			if(property_exists(get_called_class().'DB', $k)) {
				$vodb->{$k} = $v;
			}
		}
		return $vodb;
	}
	
	private function resetFromVodb( $vodb ) {
	   foreach($this as $k=>$v) {
			if(property_exists(get_called_class().'DB', $k)) {
				$this->{$k} = $vodb->{$k};
			}
		}
	}
  
  public function save() {
		$vodb = $this->getVodb();
		return $vodb->save();
	}
	
	public function load() {
		$vodb = $this->getVodb();
		$vodb->load();
		$this->resetFromVodb( $vodb );
	}
	
	public function delete() {
		$vodb = $this->getVodb();
		return $vodb->delete();
	}
  
}