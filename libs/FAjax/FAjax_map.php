<?php
class FAjax_map extends FAjaxPluginBase {
  static function selector($data) {
		
		//---create response
		$tpl=FSystem::tpl('positionSelector.tpl.html');
		$tpl->setVariable('PAGEICOLINK',URL_PAGE_AVATAR.$pageVO->pageIco.'?r='.rand());
		
		$mapId = '';
		//set coordinates

		FAjax::addResponse('body', '$html', $tpl->get());
		FAjax::addResponse('function','getScript','http://maps.google.com/maps/api/js?sensor=false;gmapinit'.$mapId);
	}
	
}