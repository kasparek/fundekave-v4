<?php
class FCalendarPlugins {
  static function diaryRecurrenceItems($year,$month,$userId,$pageId = '') {
    $db = FDBConn::getInstance();
	$q = "select itemId as id,
    addon as title,
    date_format(dateStart ,'%Y-%m-%d') as date, 
    date_format(dateStart ,'%d.%m.%Y') as dateLocal,
	'year' as rep
    from sys_pages_items where typeId='event'" 
	.(SITE_STRICT ? "and pageIdTop='".SITE_STRICT."' " : "")
	."and (userId='".$userId."' or public=1) and ((month(dateStart)=".($month*1)." and textLong = 'year') or (textLong = 'month'))";
	return $db->getAll($q);
  }
  static function events($year,$month,$userId,$pageId = '') {
    $db = FDBConn::getInstance();
	$q = "select itemId as id,
    addon as title,
    date_format(dateStart ,'%Y-%m-%d') as date, 
    date_format(dateStart ,'%d.%m.%Y') as dateLocal 
    from sys_pages_items where typeId='event'" 
	.(SITE_STRICT ? "and pageIdTop='".SITE_STRICT."' " : "")
	."and (userId='".$userId."' or public=1) "
	."and dateStart like '".$year."-".$month."%'";
    return $db->getAll($q);
  }
  static function blogItems($year,$month,$userId,$pageId = '') {
  	$db = FDBConn::getInstance();
    $q = "select itemId as id,
    addon as title,
    date_format(dateStart ,'%Y-%m-%d') as date, 
    date_format(dateStart ,'%d.%m.%Y') as dateLocal 
    from sys_pages_items where typeId='blog' "
	.(SITE_STRICT ? "and pageIdTop='".SITE_STRICT."' " : "")
	." and ".($pageId!=''?"pageId='".$pageId."' and ":"")."dateStart <= '".$year."-".$month."%' and (itemIdTop is null or itemIdTop=0)
	and public=1 order by dateStart desc limit 0,50";
    $result = $db->getAll($q);
	return $result;
  }
  static function galeryItems($year,$month,$userId,$pageId = '') {
    $fPages = new FPages('galery',$userId);
    $fPages->setSelect("i.itemId as id,
    p.name as title,
    date_format(i.dateCreated ,'{#date_iso#}') as date, 
    date_format(i.dateCreated ,'{#date_local#}') as dateLocal 
    ");
    $fPages->addJoin("join sys_pages_items as i on p.pageId=i.pageId");
    $fPages->addWhere("i.dateCreated like '".$year."-".$month."%'");
	if(SITE_STRICT) $fPages->addWhere("p.pageIdTop='".SITE_STRICT."'");
    $fPages->setGroup("p.pageId");
    $fPages->setOrder("rand()");
    return $fPages->getContent();
  } 
}