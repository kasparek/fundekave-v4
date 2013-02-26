<?php
include_once('iPage.php');
class page_SysEditLeftpanel implements iPage {

	static function process($data) {
		//TODO: Icant see category process - page not tested
	}

	static function build($data=array()) {
		if(empty($_REQUEST['rh'])) $rh=0; else $rh = $_REQUEST['rh']*1;
		
		$sidebarGroup = 0;
		$cache = FCache::getInstance('s');
		if(false !== ($sGroup = $cache->getData('grp','lp'))) {
			$sidebarGroup = $sGroup;
		}
		if(isset($_GET['rh'])) {
			$sidebarGroup = (int) $_GET['rh'];
			$cache->setData($sidebarGroup, 'grp', 'lp');
		}

		$category = new FCategory('sys_leftpanel','leftpanelId');
		$category->ident = 'rhleftbar';
		$category->setWhere("leftpanelGroup in (0".(($sidebarGroup>0)?(','.$sidebarGroup):('')).")");

		$category->arrHead=array(FLang::$LABEL_CATEGORY_GROUP, FLang::$LABEL_CATEGORY_FUNCTION, FLang::$LABEL_CATEGORY_ORDER);
		$category->arrInputType=array("select","select",'text');
		$category->arrClass=array('','','small');
		$category->arrDbUsedCols=array('leftpanelGroup','functionId','ord');
		$category->requiredCol = 'ord';

		$arrtmp = FDBTool::getAll('select functionId,name,function from sys_leftpanel_functions order by name');
		foreach ($arrtmp as $row) $arr[$row[0]] = $row[1].' - '.$row[2];
		$category->arrOption['functionId']=$arr;

		$arr=array();
		$arrtmp = FDBTool::getAll('select leftpanelGroup from sys_leftpanel group by leftpanelGroup order by leftpanelGroup');
		foreach ($arrtmp as $row) $arr[$row[0]] = $row[0];
		$category->arrOption['leftpanelGroup'] = $arr;

		$tmptext= '<p><label for="typefilter">Skupina</label><select id="typefilter" onchange="location = \'?k='.$user->currentPageId.'&rh=\' + this.options[this.selectedIndex].value;">';
		foreach ($arr as $k=>$v) $tmptext.=FText::options($v,$v,$sidebarGroup);
		$tmptext.='</select></p><form method="post" action="">';

		$tmptext .= $category->getEdit();

		$tmptext.='</form>';

		$TOPTPL->addTab(array("MAINDATA"=>$tmptext));

		$tmptext='';
		if($sidebarGroup>0){
			$arr = FDBTool::getAll("select pageId,name from sys_pages where leftpanelGroup='".$sidebarGroup."'");
			if(count($arr)>0){
				foreach ($arr as $page) $arrLink[]='<a href="?k='.$page[0].'">'.$page[1].'</a>';
				$tmptext = implode('<br/>',$arrLink);
			}
		} else {
			$tmptext = FLang::$LABEL_PAGES_DEFAULTFORALL;
		}
		FBuildPage::addTab(array("MAINHEAD"=>FLang::$LABEL_PAGES_USEDON,"MAINDATA"=>$tmptext));
	}
}