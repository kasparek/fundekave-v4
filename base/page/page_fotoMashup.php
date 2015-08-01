<?php
include_once('iPage.php');

class page_fotoMashup implements iPage {

	/**
	 *  PROCESS FUNCTION
	 */
	static function process($data) {}

	/**
	 * VIEW FUNCTION
	 */
	static function build($data=array()) {
		$cache = FCache::getInstance('f',180);
		$data = $cache->getData('fotomashup');
		if(!$data) {
			$allList = FDBTool::getCol("select itemId from sys_pages_items where typeId='galery' and public='1'".(SITE_STRICT ? " and pageIdTop='".SITE_STRICT."'" : '')." order by itemId");
			$num = 100;
			if(count($allList) <= $num) $itemIdList = $allList;
			else {
				$keyList = array_rand($allList,$num);
				foreach($keyList as $k) $itemIdList[] = $allList[$k];
			}
			$data='<div class="fotomashup">';
			while($itemIdList) {
				$itemId = array_pop($itemIdList);
				$itemVO = new ItemVO($itemId,true);
				$itemVO->editable = false;
				$data .= $itemVO->render();
			}
			$data.='</div>';
			$cache->setData($data,'fotomashup');
		}
		FBuildPage::addTab(array("MAINDATA"=>$data,"MAINID"=>'fotoMashup'));
	}
}