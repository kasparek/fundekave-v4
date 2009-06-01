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
		$cache->invalidateGroup('mytags');
		FDBTool::query('update sys_pages_items set tag_weight=tag_weight+1 where itemId="'.$itemId.'"');
		return FDBTool::query("insert into sys_pages_items_tag values ('".$itemId."','".$userId."',".(($tag!='')?("'".FSystem::textins($tag,array('plainText'=>1))."'"):('null')).",'".($weight*1)."',now())");
	}
	static function removeTag($itemId,$userId) {
		if(FDBTool::getOne("select count(1) from sys_pages_items_tag where itemId='".$itemId."' and userId='".$userId."'")) {
			FDBTool::query('update sys_pages_items set tag_weight=tag_weight-1 where itemId="'.$itemId.'"');
			$cache = FCache::getInstance('s');
			$cache->invalidateGroup('mytags');
			$cache->invalidateGroup('itemTags');
			return FDBTool::query("delete from sys_pages_items_tag where itemId='".$itemId."' and userId='".$userId."'");
		}
	}
	static function isTagged($itemId,$userId) {
		if($itemId>0 && $userId>0) {
			$q = "select count(1) from sys_pages_items_tag where userId='".$userId."' and itemId='".$itemId."'";
			$tagged = FDBTool::getOne($q,$userId.'-'.$itemId,'mytags','s',60);
			return $tagged;
		}
	}
	static function totalTags($itemId) {
		if($itemId > 0) {
			$q = "select sum(weight) from sys_pages_items_tag where itemId='".$itemId."'";
			$ret = FDBTool::getOne($q,$itemId, 'itemTags','s',60);
			return $ret;
		}
	}

	static function getTag($itemId,$userId,$typeId='') {
		if($typeId=='') $typeId = $this->getOne("select typeId from sys_pages_items where itemId='".$itemId."'");
		$templateNameActive = 'item.tag.{TYPE}.active.tpl.html';
		$templateNameUsed = 'item.tag.{TYPE}.used.tpl.html';

		$tpl = new FHTMLTemplateIT();
		if(FItems::isTagged($itemId,$userId)) {

			if(FTemplateIT::templateExist(str_replace('{TYPE}',$typeId,$templateNameUsed))) {
				$templateNameUsed = str_replace('{TYPE}',$typeId,$templateNameUsed);
			} else {
				$templateNameUsed = str_replace('default',$typeId,$templateNameUsed);
			}

			$tpl->loadTemplatefile($templateNameUsed);
		} else {

			if(FTemplateIT::templateExist(str_replace('{TYPE}',$typeId,$templateNameActive))) {
				$templateNameActive = str_replace('{TYPE}',$typeId,$templateNameActive);
			} else {
				$templateNameActive = str_replace('default',$typeId,$templateNameActive);
			}

			$tpl->loadTemplatefile($templateNameActive);
			$tpl->setVariable('URLACCEPT',FUser::getUri('m=user-tag&d=item:'.$itemId));
		}

		$tpl->setVariable('ITEMID',$itemId);
		$tpl->setVariable('CSSSKINURL',FUser::getSkinCSSFilename());
		$tpl->setVariable('SUM',FItems::totalTags($itemId));
		$tpl->setVariable('URLREMOVE',FUser::getUri('rt='.$itemId));
		return $tpl->get();
	}

	static function getItemTagList($itemId) {
		$arr = $this->getAll("select userId,tag,weight from sys_pages_items_tag where itemId='".$itemId."'");
		return $arr;
	}
}