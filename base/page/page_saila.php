<?php
class page_saila  {

	static function show($tpl) {
	  
		$fitems = new FItems('blog');
		$fitems->joinOnPropertie('position',0,'join');
		$fitems->addWhere('sys_pages_items.pageId="vd98H"');
		$fitems->setOrder('dateStart desc');
		$list = $fitems->getList(0,5);
    
		$fitems = new FItems('blog');
		$fitems->joinOnPropertie('position',0,'join');
		$fitems->addWhere('sys_pages_items.pageId="DTMTH"');
		$fitems->setOrder('sys_pages_items.pageId,itemId desc');
		$list = array_merge($list,$fitems->getList(0,5));
    
		if(!empty($list))
		while($itemVO = array_pop($list)) {
			$journey = explode(';',$itemVO->prop('position'));
			list($lat,$lng)=explode(',',$journey[count($journey)-1]);
			if($itemVO->pageId=='vd98H') {
				//red
				$markersRed[]=round($lat,4).','.round($lng,4);
			} else {
				//blue
				$markersBlue[]=round($lat,4).','.round($lng,4);
			}
			if(count($journey)>1) {
				$geoEncode = new GooEncodePoly();
				$paths[]='color:'.($itemVO->pageId=='vd98H'?'red':'blue').'|enc:'.$geoEncode->encode($journey);
			}
		}
    
		$redStr="color:red|size:small|".implode('|',$markersRed);
		$blueStr="color:blue|size:small|".implode('|',$markersBlue);
    
		$url = 'http://maps.google.com/maps/api/staticmap?size=170x170&markers='.$redStr.'&markers='.$blueStr.'&sensor=false&path='.implode('&path=',$paths);
		
		FSystem::addSuperVar('GMAPURL',$url);
	}
  
}