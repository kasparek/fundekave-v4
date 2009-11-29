<?php
include_once('iPage.php');
class page_SysEditCategories implements iPage {

	static function process($data) {
		if(isset($_REQUEST['f'])) {
			$cache = FCache::getInstance('s');
			$cache->setData($_REQUEST['f'],'ecat','filtr');
		}
		
		$category = new FCategory('sys_pages_category','categoryId');
		$category->process($data,true);
	}

	static function build($data=array()) {
		$user = FUser::getInstance();
		/**
		 * typeId - page types - top,blog,culture,galery,forum
		 */
		$arrType = FDBTool::getAll('select c.typeId,p.name,p.typeId from sys_pages_category as c 
		left join sys_pages as p on p.pageId=c.typeId group by c.typeId order by p.name,p.typeId,c.typeId');

		$cache = FCache::getInstance('s');
		if(false !== ($filtr = $cache->getData('ecat','filtr'))) $type = $filtr;
		if($filtr===false) $type='forum';

		$tmptext= '<p><label for="typefilter">Skupina</label>
		<select id="typefilter" onchange="location = \''.FSystem::getUri().'&f=\' + this.options[this.selectedIndex].value;">';
		foreach ($arrType as $row) {
			$tmptext.='<option value="'.$row[0].'"'.(($row[0]==$type)?(' selected="selected" '):('')).'>'.((!empty($row[2]))?($row[2]):($row[0])).' '.$row[1].'</option>';
			$arrTypeDefs[$row[0]] = ((!empty($row[2]))?($row[2]):($row[0])).' '.$row[1];	
		}
		$tmptext.='</select></p><form action="" method="post" name="kateg">';

		$category = new FCategory('sys_pages_category','categoryId');
		if(!empty($type)) $category->addWhere("typeId='".$type."'");
		$category->arrSaveAddon = array('typeId'=>$type);
		$category->arrInputType[] = 'select';
		$category->arrClass[] = 'short';
		$category->arrDbUsedCols[] = 'typeId';
		$category->arrHead[] = 'type';
		$category->arrDefaults[] = 'forum';
		$category->arrOptions['typeId'] = $arrTypeDefs;

		$tmptext .= $category->getEdit();

		FBuildPage::addTab(array("MAINDATA"=>$tmptext.'</form>'));
	}
}