<?php
class Sidebar_map {
	static function show() {
		$dbtool = new FDBTool('sys_pages_items_properties');
		$dbtool->setSelect('value');
		$dbtool->setWhere("name='position'");
		$posList = $dbtool->getContent();
		$tpl = FSystem::tpl('sidebar.map.tpl.html');
		$tpl->setVariable("URL",FSystem::getUri('','','m'));
		while($row=array_pop($posList)) {
			$position = $row[0];
			$journey = explode(';',$position);
			$tpl->setVariable('STATICMARKERPOS',$journey[count($journey)-1]);
			$tpl->parse('marker');
			/*if(count($journey)>1) {
				$tpl->setVariable('STATICWPLIST',implode('|',$journey));
				$tpl->parse('stwp');
			}*/
		}
		$ret = $tpl->get();
		return $ret;
	}
}