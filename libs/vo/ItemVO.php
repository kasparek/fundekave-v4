<?php
class ItemVO {
  var $itemId = 0;
  var $typeId;
  var $pageId;
  
  var $text;
  var $addon;
  var $enclosure;
  var $dateStart;
  var $dateEnd;
  var $dateCreated;
  var $hit;
  
  var $thumbInSysRes = false;
  var $thumbUrl;
  var $detailUrl;
  var $detailWidth;
  var $detailHeight;
  var $detailUrlToGalery;
  var $detailUrlToPopup;
  
  function ItemVO($itemId=0, $autoLoad = false) {
  	$this->itemId = $itemId;
  	if($autoLoad == true) {
  		$this->load();
  	}
  }
  
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