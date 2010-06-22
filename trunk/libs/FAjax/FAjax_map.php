<?php
class FAjax_map extends FAjaxPluginBase {
  static function selector($data) {
		
		//---create response
		$tpl=FSystem::tpl('positionSelector.tpl.html');
		
		$tpl->setVariable('INITPOS', !empty($data['pos'])?$data['pos']:'0,0');
		$tpl->setVariable('POSX', $data['left']);
		$tpl->setVariable('POSY', $data['top']);
		$tpl->setVariable('ID', $data['el']);
		
		$tpl->setVariable('POSITIONELEMENT', $data['el']);
		$tpl->touchBlock('readposition');
		//set coordinates

		FAjax::addResponse('body', 'body', $tpl->get());
		FAjax::addResponse('function','getScript','http://maps.google.com/maps/api/js?sensor=false;gmapinit'.$data['el']);
	}
	
}