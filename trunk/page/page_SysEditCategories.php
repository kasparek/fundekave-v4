<?php
include_once('iPage.php');
class page_SysEditCategories implements iPage {

	static function process() {
		$category = new fCategory('sys_pages_category','categoryId');
		$category->process();
		
		if(isset($_REQUEST['f'])) {
			$cache = FCache::getInstance('s');
			$cache->setData($_REQUEST['f'],'ecat','filtr');
		}
		
	}

	static function build() {
		/**
		 * TODO: add textarea for editing category description
		 * typeId - page types - top,blog,culture,galery,forum
		 */
		$arrType = FDBTool::getCol('select typeId from sys_pages_category group by typeId order by typeId');

		$cache = FCache::getInstance('s');
		if(false !== ($filtr = $cache->getData('ecat','filtr'))) $type = $filtr;
		else $type = 'forum';

		$tmptext= '<p><label for="typefilter">Skupina</label><select id="typefilter" onchange="location = \'?k='.$user->currentPageId.'&f=\' + this.options[this.selectedIndex].value;">';
		foreach ($arrType as $k=>$v) $tmptext.='<option value="'.$v.'"'.(($v==$type)?(' selected="selected" '):('')).'>'.$v.'</option>';
		$tmptext.='</select></p><form action="" method="post" name="kateg">';

		$category = new fCategory('sys_pages_category','categoryId');

		$category->addWhere("typeId='".$type."'");
		$category->arrSaveAddon = array('typeId'=>$type);

		$tmptext .= $category->getEdit();

		FBuildPage::addTab(array("MAINDATA"=>$tmptext.'</form>'));
	}
}