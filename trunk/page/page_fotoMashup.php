<?php
include_once('iPage.php');

class page_fotoMashup implements iPage {

	/**
	 *  PROCESS FUNCTION
	 */
	static function process($data) {

	}

	/**
	 * VIEW FUNCTION
	 */
	static function build($data=array()) {
		$cache = FCache::getInstance('f');
		$cache->setConf(180);
		
		$data = $cache->getData('fotomashup','sidebar');
		
		if(!$data) {
			$allList = FDBTool::getCol("select itemId from sys_pages_items where typeId='galery' and public='1'".(SITE_STRICT ? " and pageIdTop='".SITE_STRICT."'" : '')." order by itemId desc");
				
			$num = 100;
			$len = count($allList);
			if($num>$len) $num = $len;
			$steps = ceil($len/$num);
			for($i=0;$i<$num;$i++){
				$from = $i*$steps;
				$rand = rand($from,$from+$steps>$len?$len:$from+$steps);
				$itemIdList[]=$allList[$rand];
			}
			$itemIdList = array_reverse($itemIdList);
			
			$data='';
	
			while($itemIdList) {
				$itemId = array_pop($itemIdList);
				if($itemId) {
					$itemVO = new ItemVO($itemId,true);
					$data .= '<a href="'.$itemVO->detailUrl.'" rel="lightbox-mashup" title="'.htmlspecialchars('<a href="'.FSystem::getUri('i='.$itemVO->itemId,'','').'">'.$itemVO->pageVO->get('name').'</a>').'"><img src="'.$itemVO->thumbUrl.'" class="leftbox" /></a>';
				}
			}
			
			$cache->setData($data,'fotomashup','sidebar');
		}

		FBuildPage::addTab(array("MAINDATA"=>$data,"MAINID"=>'fotoMashup'));


	}
}