<?php
class fCalendarPlugins {
  static function diaryItems($year,$month,$userId,$pageId = '') {
    global $db;
    return $db->getAll("select date_format(dateEvent,'%d') as den,
    concat('?k=fdiar&amp;ddate=',date_format(dateEvent,'%Y-%m-%d')) as link,
    diaryId,
    name,
    date_format(dateEvent ,'%Y-%m-%d'), 
    date_format(dateEvent ,'%d.%m.%Y') 
    from sys_users_diary where (userId='".$userId."' or eventForAll=1) and dateEvent like '".$year."-".$month."%'");
  }
  static function diaryRecurrenceItems($year,$month,$userId,$pageId = '') {
    global $db;
    return $db->getAll("select date_format(dateEvent,'%d') as den,
    concat('?k=fdiar&amp;ddate=',date_format(dateEvent,'%Y-%m-%d')) as link,
    diaryId,
    name,
    date_format(dateEvent ,'%Y-%m-%d'), 
    date_format(dateEvent ,'%d.%m.%Y') 
    from sys_users_diary where (userId='".$userId."' or eventForAll=1) and ((month(dateEvent)=".($month*1)." and recurrence = 1) or (recurrence = 2))");
  }
  static function events($year,$month,$userId,$pageId = '') {
    global $db;
    return $db->getAll("select date_format(dateStart,'%d') as den,
    concat('?k=event&amp;i=',itemId) as link,
    itemId,
    addon,
    date_format(dateStart ,'%Y-%m-%d'), 
    date_format(dateStart ,'%d.%m.%Y') 
    from sys_pages_items where dateStart like '".$year."-".$month."%'");
  }
  static function blogItems($year,$month,$userId,$pageId = '') {
    global $db;
    $fPages = new fPages('blog',$userId,$db);
    if($pageId!='') $fPages->addWhere('p.pageId="'.$pageId.'"');
    $fPages->setSelect("date_format(i.dateCreated,'%d') as den,
    concat('?k=',p.pageId,'&amp;i=',i.itemId) as link,
    i.itemId,
    i.addon,
    date_format(i.dateCreated ,'{#date_iso#}'), 
    date_format(i.dateCreated ,'{#date_local#}') 
    ");
    $fPages->addJoin("join sys_pages_items as i on p.pageId=i.pageId");
    $fPages->addWhere("i.dateCreated like '".$year."-".$month."%' and itemIdTop is null");
    return $fPages->getContent();
  }
  static function galeryItems($year,$month,$userId,$pageId = '') {
    global $db;
    $fPages = new fPages('galery',$userId,$db);
    $fPages->setSelect("date_format(i.dateCreated,'%d') as den,
    concat('?k=',p.pageId,'&amp;i=',i.itemId) as link,
    i.itemId,
    p.name,
    date_format(i.dateCreated ,'{#date_iso#}'), 
    date_format(i.dateCreated ,'{#date_local#}') 
    ");
    $fPages->addJoin("join sys_pages_items as i on p.pageId=i.pageId");
    $fPages->addWhere("i.dateCreated like '".$year."-".$month."%'");
    $fPages->setGroup("p.pageId");
    $fPages->setOrder("rand()");
    return $fPages->getContent();
  } 
  static function forums($year,$month,$userId,$pageId = '') {
    global $db;
    $fPages = new fPages('forum',$userId,$db);
    $fPages->setSelect("date_format(p.dateCreated,'%d') as den,
    concat('?k=',p.pageId) as link
    ,p.pageId,
    p.name,
    date_format(p.dateCreated ,'{#date_iso#}'), 
    date_format(p.dateCreated ,'{#date_local#}') 
    ");
    $fPages->addWhere("p.dateCreated like '".$year."-".$month."%'");
    return $fPages->getContent();
  }
}