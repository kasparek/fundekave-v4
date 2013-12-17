<?php
class FCalendarPlugins {

	static function selectBase($dateCol='i.dateStart',$titleCol="i.addon",$id="i.itemId") { 
		return $id." as id, ".$titleCol." as title, date_format(".$dateCol." ,'%Y-%m-%d') as date, date_format(".$dateCol." ,'%d.%m.%Y') as dateLocal";
	}

	static function whereBase($typeId,$userId,$pageId) {
		return ' join sys_pages as p on p.pageId=i.pageId '.($userId>0?"left join sys_users_perm as up on up.userId='".$userId."' and up.pageId=i.pageId ":' ')
		."where i.typeId='".$typeId."'" 
		.(SITE_STRICT ? "and p.pageIdTop='".SITE_STRICT."' " : " ")
		.(!empty($pageId)?"and p.pageId='".$pageId."' ":" ")
		.($userId>0?
			"and (i.public>0 or (i.public=0 and i.userId='".$userId."')) and (p.public>0 or up.rules>0) and p.locked<2 "
			:'and p.locked=0 and p.public=1 and i.public = 1 ');
	}
	
  static function diaryRecurrenceItems($year,$month,$userId,$pageId) {
    $db = FDBConn::getInstance();
	$q = "select ".self::selectBase().",'year' as rep from sys_pages_items as i ".self::whereBase('event',$userId,$pageId)
	."and ((month(i.dateStart)=".($month*1)." and i.textLong = 'year') or (i.textLong = 'month'))";
	return $db->getAll($q);
  }
  static function events($year,$month,$userId,$pageId = '') {
    $db = FDBConn::getInstance();
	$q = "select ".self::selectBase()." from sys_pages_items as i ".self::whereBase('event',$userId,$pageId)
	."and i.dateStart >= '".$year."-".$month."%'";
    return $db->getAll($q);
  }
  static function blogItems($year,$month,$userId,$pageId) {
  	$db = FDBConn::getInstance();
    $q = "select ".self::selectBase()." from sys_pages_items as i ".self::whereBase('blog',$userId,$pageId)
	."and i.dateStart <= '".$year."-".$month."%' "
	."order by i.dateStart desc limit 0,50";
    $result = $db->getAll($q);
	return $result;
  }
  static function galeryItems($year,$month,$userId,$pageId = '') {
	$db = FDBConn::getInstance();
	$q = "select ".self::selectBase('i.dateCreated','p.name','max(i.itemId)')." from sys_pages_items as i ".self::whereBase('galery',$userId,$pageId)
	."and i.dateCreated like '".$year."-".$month."%' "
	."group by concat(p.pageId,date_format(i.dateCreated ,'%Y%m%d')) "
	."";
	$result = $db->getAll($q);
	return $result;
  } 
}