<?php
class ItemVO {
  var $itemId = 0;
  var $typeId;
  var $pageId;
  
  var $text;
  var $enclosure;
  var $dateStart;
  var $dateEnd;
  var $dateCreated;
  var $hit;
  
  function checkItem() {
	    if($this->itemId > 0) {
	        $item = FDBTool::getRow("select typeId,pageId from sys_pages_items where itemId='".$this->itemId."'");
	        if(FRules::get(FUser::logon(),$item[1])) {
	         $this->typeId = $item[0];
	           $this->pageId = $item[1];
	        } else {
	           $this->itemId = 0;
	        }
	    }
	}
}