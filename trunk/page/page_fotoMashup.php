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
		$itemIdList = $cache->getData('fotomashup','sidebar');
		$num = count($itemIdList);
		
		if(empty($itemIdList)) {
			$allList = FDBTool::getCol("select itemId from sys_pages_items where typeId='galery' and public='1'".(SITE_STRICT == 1 ? " and pageIdTop='".HOME_PAGE."'" : '')." order by itemId desc");
			
			$num = 100;
			$len = count($allList);
			$steps = ceil($len/$num);
			for($i=0;$i<$num;$i++){
				$from = $i*$steps;
				$rand = rand($from,$from+$steps>$len?$len:$from+$steps);
				$itemIdList[]=$allList[$rand];
			}
			$itemIdList = array_reverse($itemIdList);
			
			//$keyList = array_rand($allList,500);
			//$itemIdList=array();
			//while($k = array_pop($keyList)) $itemIdList[]=$allList[$k];
			
			$cache->setData($itemIdList,'fotomashup','sidebar');
		} 
		
		$data='';
		
		for($i=0;$i<$num;$i++){
			$itemVO = new ItemVO(array_pop($itemIdList),true);
			//$data .= '<a href="'.FSystem::getUri('i='.$itemVO->itemId,'','').'" rel="lightbox-mashup"><img src="'.$itemVO->thumbUrl.'" class="leftbox" /></a>';
			$data .= '<a href="'.$itemVO->detailUrl.'" rel="lightbox-mashup" title="'.htmlspecialchars('<a href="'.FSystem::getUri('i='.$itemVO->itemId,'','').'">'.$itemVO->pageVO->get('name').'</a>').'"><img src="'.$itemVO->thumbUrl.'" class="leftbox" /></a>';
		}
				
		FBuildPage::addTab(array("MAINDATA"=>$data,"MAINID"=>'fotoMashup'));
		
		
	}
}