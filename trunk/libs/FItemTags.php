<?php
/**
 * TAGGING ITEMS
 */
class FItemTags {
	/**
	 * tag item 
	 * @param number $itemId
 	 * @param number $userId
 	 * @param tinyint $weight
 	 * @param varchar(255) $tag
 	 * @return boolean
	 */
	static function tag($itemId,$userId,$weight=1,$tag='') {
		if(0 == FDBTool::getOne("select count(1) from sys_pages_items_tag where itemId='".$itemId."' and userId='".$userId."'")) {
			FItemTags::invalidateCache();
			FDBTool::query('update sys_pages_items set tag_weight=tag_weight+1 where itemId="'.$itemId.'"');
			FDBTool::query("insert into sys_pages_items_tag values ('".$itemId."','".$userId."',".(($tag!='')?("'".FSystem::textins($tag,array('plainText'=>1))."'"):('null')).",'".($weight*1)."',now())");
			FCommand::run(ITEM_UPDATED,new ItemVO($itemId));
		}
	}
	static function removeTag($itemId,$userId) {
		if(FDBTool::getOne("select count(1) from sys_pages_items_tag where itemId='".$itemId."' and userId='".$userId."'")) {
			FItemTags::invalidateCache();
			FDBTool::query("delete from sys_pages_items_tag where itemId='".$itemId."' and userId='".$userId."'");
			FDBTool::query("update sys_pages_items set tag_weight=(select IF( sum( weight ) IS NULL , 0, sum( weight ) ) from sys_pages_items_tag where itemId='".$itemId."' ) where itemId='".$itemId."'");
			FCommand::run(ITEM_UPDATED,new ItemVO($itemId));
		}
	}
	static function isTagged($itemId,$userId) {
		if(empty($itemId) || empty($userId)) return false;
		$q = "select count(1) from sys_pages_items_tag where userId='".$userId."' and itemId='".$itemId."'";
		$tagged = FDBTool::getOne($q,$userId.'-'.$itemId,'myTags','s',0);
		return (($tagged>0)?(true):(false));
	}
	static function totalTags($itemId) {
		if($itemId > 0) {
			$q = "select sum(weight) from sys_pages_items_tag where itemId='".$itemId."'";
			$ret = FDBTool::getOne($q);
			return (int) $ret;
		}
	}

	static function getTag($itemId,$userId,$typeId='',$sum=false) {
		if($typeId=='') {
			$typeId = FDBTool::getOne("select typeId from sys_pages_items where itemId='".$itemId."'");
		}
		//---templates
		$templateNameActive = 'item.tag.{TYPE}.active.tpl.html';
		$templateNameUsed = 'item.tag.{TYPE}.used.tpl.html';
		$isTagged=false;
		if($userId>0) $isTagged = FItemTags::isTagged($itemId,$userId);
		if($isTagged === true) {
			$template = $templateNameUsed;
		} else {
			$template = $templateNameActive;
		}
		if(!FSystem::tplExist(str_replace('{TYPE}',$typeId,$template))) {
			$typeId = 'default';
		}
		$template = str_replace('{TYPE}',$typeId,$template);
		
		$tpl = file_get_contents(ROOT.ROOT_TEMPLATES.$template);
		
		if($isTagged !== true) {
			$tpl = str_replace('{URLACCEPT}',FSystem::getUri('m=user-tag&d=item:'.$itemId.';a:a'),$tpl);
		} else {
			$tpl = str_replace('{URLREMOVE}',FSystem::getUri('m=user-tag&d=item:'.$itemId.';a:r'),$tpl);
		}
		
		$tpl = str_replace('{ITEMID}',$itemId,$tpl);
		$tpl = str_replace('{SUM}',(($sum!==false)?($sum):(FItemTags::totalTags($itemId))),$tpl);
		return $tpl;
	}

	static function getItemTagList($itemId) {
		$arr = FDBTool::getAll("select userId,tag,weight from sys_pages_items_tag where itemId='".$itemId."'");
		return $arr;
	}
	
	/**
	 * invalidate tag cache
	 * @return void
	 */
	static function invalidateCache() {
		$cache = FCache::getInstance('s');
		$cache->invalidateGroup('myTags');
	}
	
}