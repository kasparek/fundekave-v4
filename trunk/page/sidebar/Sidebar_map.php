<?php
class Sidebar_map {
	static function show() {
		$user = FUser::getInstance();
		
		$dbtool = new FDBTool('sys_pages_items_properties');
		$dbtool->setSelect('value');
		$dbtool->setWhere("name='position'");
		if($user->pageVO->typeId!='top') {
			$dbtool->addJoinAuto('sys_pages_items','itemId',array(),'join');
			$dbtool->addWhere('sys_pages_items.public=1 and sys_pages_items.pageId="'.$user->pageVO->pageId.'"');
		}
		
		$posList = $dbtool->getContent(0,20);
		if(empty($posList)) return;
		
		$tpl = FSystem::tpl('sidebar.map.tpl.html');
		$tpl->setVariable("URL",FSystem::getUri('',$user->pageVO->typeId!='top','m'));
		while($row=array_pop($posList)) {
			$position = $row[0];
			$journey = explode(';',$position);
			$tpl->setVariable('STATICMARKERPOS',$journey[count($journey)-1]);
			$tpl->parse('marker');
		}
		$ret = $tpl->get();
		return $ret;
	}
}