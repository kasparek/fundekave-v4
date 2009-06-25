<?php
/**
 * TAGGING section
 *
 * @param number $itemId
 * @param number $userId
 * @param tinyint $weight
 * @param varchar(255) $tag
 * @return boolean
 */
class FItemTags {
	static function tag($itemId,$userId,$weight=1,$tag='') {
		$cache = FCache::getInstance('s');
		$cache->invalidateGroup('iTags');
		if(0==FDBTool::getOne("select count(1) from sys_pages_items_tag where itemId='".$itemId."' and userId='".$userId."'")) {
			FDBTool::query('update sys_pages_items set tag_weight=tag_weight+1 where itemId="'.$itemId.'"');
			return FDBTool::query("insert into sys_pages_items_tag values ('".$itemId."','".$userId."',".(($tag!='')?("'".FSystem::textins($tag,array('plainText'=>1))."'"):('null')).",'".($weight*1)."',now())");
		}
	}
	static function removeTag($itemId,$userId) {
		if(FDBTool::getOne("select count(1) from sys_pages_items_tag where itemId='".$itemId."' and userId='".$userId."'")) {
			$cache = FCache::getInstance('s');
			$cache->invalidateGroup('iTags');
			FDBTool::query("delete from sys_pages_items_tag where itemId='".$itemId."' and userId='".$userId."'");
			FDBTool::query("update sys_pages_items set tag_weight=(select IF( sum( weight ) IS NULL , 0, sum( weight ) ) from sys_pages_items_tag where itemId='".$itemId."' ) where itemId='".$itemId."'");
			return true;
		}
	}
	static function isTagged($itemId,$userId) {
		if($itemId > 0 && $userId > 0) {
			$q = "select count(1) from sys_pages_items_tag where userId='".$userId."' and itemId='".$itemId."'";
			$tagged = FDBTool::getOne($q,$userId.'-'.$itemId,'mytags','s',60);
			return (($tagged>0)?(true):(false));
		}
	}
	static function totalTags($itemId) {
		if($itemId > 0) {
			$q = "select sum(weight) from sys_pages_items_tag where itemId='".$itemId."'";
			$ret = FDBTool::getOne($q,$itemId, 'iTags','s',60);
			return $ret;
		}
	}

	static function getTag($itemId,$userId,$typeId='') {
		if($typeId=='') {
			$typeId = FDBTool::getOne("select typeId from sys_pages_items where itemId='".$itemId."'");
		}
		//---templates
		$templateNameActive = 'item.tag.{TYPE}.active.tpl.html';
		$templateNameUsed = 'item.tag.{TYPE}.used.tpl.html';

		$isTagged = FItemTags::isTagged($itemId,$userId);
		if($isTagged === true) {
			$template = $templateNameUsed;
		} else {
			$template = $templateNameActive;
		}
		if(!FTemplateIT::templateExist(str_replace('{TYPE}',$typeId,$template))) {
			$typeId = 'default';
		}
		$template = str_replace('{TYPE}',$typeId,$template);
		
		$tpl = new FTemplateIT($template);
		
		if($isTagged !== true) {
			$tpl->setVariable('URLACCEPT',FUser::getUri('m=user-tag&d=item:'.$itemId.';a:a'));
		}
		$tpl->setVariable('ITEMID',$itemId);
		$tpl->setVariable('CSSSKINURL',FUser::getSkinCSSFilename());
		$tpl->setVariable('SUM',FItemTags::totalTags($itemId));
		$tpl->setVariable('URLREMOVE',FUser::getUri('m=user-tag&d=item:'.$itemId.';a:r'));
		return $tpl->get();
	}

	static function getItemTagList($itemId) {
		$arr = FDBTool::getAll("select userId,tag,weight from sys_pages_items_tag where itemId='".$itemId."'");
		return $arr;
	}
}