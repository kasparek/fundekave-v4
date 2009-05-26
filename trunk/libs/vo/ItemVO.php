<?php
class ItemVO {
  var $itemId = 0;
  var $typeId;
  var $pageId;
  
  function checkItem() {
	    if($this->itemId > 0) {
	        $db = FDBConn::getInstance();
	        $item = $db->getRow("select typeId,pageId from sys_pages_items where itemId='".$this->itemId."'");
	        if(fRules::get($this->gid,$item[1])) {
	         $this->typeId = $item[0];
	           $this->pageId = $item[1];
	        } else {
	           $this->itemId = 0;
	        }
	    }
	}
}