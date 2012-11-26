<?php
class CategoryVO extends Fvob {

	public function getTable(){ return 'sys_pages_category'; }
	public function getPrimaryCol() { return 'categoryId'; }
	public function getColumns() { return array('categoryId' => 'categoryId',
	'typeId' => 'typeId',
	'pageIdTop' => 'pageIdTop',
	'name' => 'name',
	'description' => 'description',
	'ord' => 'ord',
	'public' => 'public',
	'num' => 'num'
	); }

  public $categoryId;
  public $typeId;
  public $pageIdTop;
  public $name;
  public $description = '';
  public $ord = 0;
  public $public = 1;
  public $num = 0;
  
  public function updateNum() {
		$currentNum = $this->num;
		$typeId=$this->typeId;
		if(isset(FLang::$TYPEID[$typeId]) && $typeId!='event') {
			$q="select count(1) from sys_pages where categoryId='".$this->categoryId."'";
		} else if($typeId=='event') {
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