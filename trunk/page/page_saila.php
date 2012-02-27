<?php
class page_saila  {

	static function show($tpl) {
	
    $fitems = new FItems('blog');
		$fitems->joinOnPropertie('position',0,'join');
    $fitems->addWhere('sys_pages_items.pageId in ("DTMTH","vd98H")');
    $fitems->setOrder('itemId desc');
		$list = $fitems->getList(0,5);
    
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
    
    //$tpl->setVariable('STATICMARKERPOS',implode('|',$markers));
    
    //if(!empty($paths)) $tpl->setVariable('SWPLIST',implode('&path=',$paths));
    
    $url = 'http://maps.google.com/maps/api/staticmap?size=170x170&markers='.$redStr.'&markers='.$blueStr.'&sensor=false&path='.implode('&path=',$paths);

    $tpl->setVariable('GMAPURL',$url);
	}
  
}