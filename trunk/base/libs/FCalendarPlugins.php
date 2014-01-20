<?php
class FCalendarPlugins {
	static $label = '';

	static function selectBase($dateCol='i.dateStart',$titleCol="i.addon",$id="i.itemId") {
		$user = FUser::getInstance();
		
		$dateFormat = empty($user->year) ? '%Y':'%Y-%m';
		$dateFormatLocal = empty($user->year) ? '%Y':'%m.%Y';
		
		if(empty($user->month)) {
			return "date_format(".$dateCol." ,'".$dateFormat."') as id,concat('".FCalendarPlugins::$label."',count(1)) as title,date_format(".$dateCol." ,'".$dateFormat."') as date,date_format(".$dateCol." ,'".$dateFormatLocal."') as dateLocal";
		} else {
			return $id." as id, ".$titleCol." as title, date_format(".$dateCol." ,'%Y-%m-%d') as date, date_format(".$dateCol." ,'%d.%m.%Y') as dateLocal";
		}
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
	$user = FUser::getInstance();
    $db = FDBConn::getInstance();
	FCalendarPlugins::$label = 'Vıroèí ';
	$q = "select ".self::selectBase().",'year' as rep from sys_pages_items as i ".self::whereBase('event',$userId,$pageId)
	."and ((month(i.dateStart)=".($month*1)." and i.textLong = 'year') or (i.textLong = 'month'))"
	.(empty($user->month)?"group by id":"")
	."";
	
	return $db->getAll($q);
  }
  static function events($year,$month,$userId,$pageId = '') {
    $db = FDBConn::getInstance();
	FCalendarPlugins::$label = 'Události ';
	$q = "select ".self::selectBase()." from sys_pages_items as i ".self::whereBase('event',$userId,$pageId)
	."and i.dateStart >= '".$year."-".$month."%'"
	.(empty($user->month)?"group by id":"")
	."";
    return $db->getAll($q);
  }
  static function blogItems($year,$month,$userId,$pageId) {
	FCalendarPlugins::$label = 'Èlánky ';
  	$db = FDBConn::getInstance();
    $q = "select ".self::selectBase()." from sys_pages_items as i ".self::whereBase('blog',$userId,$pageId)
	."and year(i.dateStart) = '".$year."' "
	.(empty($user->month)?"group by id":"")
	."";
	
    $result = $db->getAll($q);
	return $result;
  }
  
  static function galeryPageCount($year,$month,$userId,$pageId = '') {
	$db = FDBConn::getInstance();
	
	$user = FUser::getInstance();
	$dateFormat = empty($user->year) ? '%Y':'%Y-%m';
	$dateFormatLocal = empty($user->year) ? '%Y':'%m.%Y';
	$dateFormat .= !empty($user->month) ? '-%d':'';
	$dateFormatLocal = (!empty($user->month) ? '%d.':'').$dateFormatLocal;
	
	$q = "select date_format(p.dateContent ,'".$dateFormat."') as id,concat('Galerie: ',count(1)) as title,date_format(p.dateContent ,'".$dateFormat."') as date,date_format(p.dateContent ,'".$dateFormatLocal."') as dateLocal from sys_pages as p "
	.($userId>0?"left join sys_users_perm as up on up.userId='".$userId."' and up.pageId=p.pageId ":' ')
	."where typeId='galery' "
	.($user->categoryVO ? "and p.categoryId='".$user->categoryVO->categoryId."' " : '')
	.(SITE_STRICT ? "and p.pageIdTop='".SITE_STRICT."' " : " ")
	.(!empty($pageId)?"and p.pageId='".$pageId."' ":" ")
	.($userId>0?"and (p.public>0 or up.rules>0) and p.locked<2 ":'and p.locked=0 and p.public=1 ')
	.(!empty($user->year)?" and year(p.dateContent)='".$user->year."'":'')
	.(!empty($user->month)?" and month(p.dateContent)='".$user->month."'":'')
	."group by id"
	."";
	
	$result = $db->getAll($q);
	return $result;
  }
}