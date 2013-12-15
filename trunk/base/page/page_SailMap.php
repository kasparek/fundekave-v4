<?php
include_once('iPage.php');
class page_SailMap implements iPage {

	static function process($data){}

	static function build($data=array()) {

		$user = FUser::getInstance();
		$user->pageVO->showHeading = false;

		$fitems = new FItems('blog');
		$fitems->joinOnPropertie('position',0,'join');
		$fitems->addWhere('sys_pages_items.pageId in ("DTMTH","vd98H")');
		$list = $fitems->getList();
    
		$tpl = FSystem::tpl('map.tpl.html');

		if(!empty($list))
		while($itemVO = array_pop($list)) {
			$info='';
		
			$title = $itemVO->addon;
			$info .= FLang::$TYPEID[$itemVO->typeId].':<br />';
			$info .= '<strong><a href="'.FSystem::getUri('i='.$itemVO->itemId,$itemVO->pageId,'').'">'.$itemVO->addon.'</a></strong><br />';
			$info .= $itemVO->dateStartLocal.'<br/>';
		
			$distance = (int) $itemVO->prop('distance');
			$info.= $distance>0 ? '<div>Distance: '.$distance.'NM</div>' : '';
      
      
      if($itemVO->pageId=='DTMTH') {
        $tpl->setVariable('PATHCOLOR','#0000ee');
        $tpl->setVariable('STROKEWEIGHT','4');
        
        $tpl->setVariable('MAPICO','sail');
      } else {
        $tpl->setVariable('PATHCOLOR','#ee0000');
        $tpl->setVariable('STROKEWEIGHT','2');
      }
				
			$tpl->setVariable(array('MAPTITLE'=>$title,'MAPINFO'=>$info,'MAPPOSITION'=>str_replace(";","\n",$itemVO->prop('position'))));
			$tpl->parse('mapdata');
		}

    $tpl->setVariable('CONTENT',FText::postProcess($user->pageVO->content));
		
		FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));
	}
}