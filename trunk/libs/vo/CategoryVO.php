<?php
class CategoryVO extends Fvob {

	var $table = 'sys_pages_category';
	var $primaryCol = 'categoryId';

	var $columns = array('categoryId' => 'categoryId',
	'typeId' => 'typeId',
	'pageIdTop' => 'pageIdTop',
	'name' => 'name',
	'description' => 'description',
	'ord' => 'ord',
	'public' => 'public',
	'num' => 'num'
	);

  var $categoryId;
  var $typeId;
  var $pageIdTop;
  var $name;
  var $description = '';
  var $ord = 0;
  var $public = 1;
  var $num = 0;
  
  public function updateNum() {
		$currentNum = $this->get('num');
		$pageVO = new PageVO($this->pageIdTop);
		
		$pageType=$pageVO->get('typeId');
		if($pageType=='top') {
			$q="select count(1) from sys_pages where categoryId='".$this->categoryId."'";
		} else if($pageType=='event') {
		  $q="select count(1) from sys_pages_items where (dateStart >= date_format(NOW(),'%Y-%m-%d') or (dateEnd is not null and dateEnd >= date_format(NOW(),'%Y-%m-%d'))) and categoryId='".$this->categoryId."' and typeId='event'";
		} else {
		  $q="select count(1) from sys_pages_items where categoryId='".$this->categoryId."' and public=1";
		}
		$newNum = FDBTool::getOne($q);
		if($currentNum!=$newNum) {
			$this->set('num',$newNum);
			$this->save();
			FCommand::run(CATEGORIES_UPDATED);
		} 
	}
}