<?php
set_time_limit(3600);
if(isset($_GET['del'])) {
  $itemId = (int) $_GET['del'];
  $q = "delete from sys_pages_items_properties where name='picasaPhoto' and value='INPROGRESS' and itemId='".$itemId."'";
  FDBTool::query($q);
  echo 'Stuck item deleted: '.$itemId;
  exit; 
}
if(isset($_GET['update'])) {
  $itemId = (int) $_GET['update'];
  $q = "update sys_pages_items_properties set value='TODO' where name='picasaPhoto' and value='INPROGRESS' and itemId='".$itemId."'";
  FDBTool::query($q);
  echo 'Stuck item set TODO: '.$itemId;
  exit; 
}

$done = 0;
$numTodo=10;

if(!empty($_GET['done'])) $done = $done + $_GET['done'];
if(!empty($_GET['todo'])) $numTodo = (int) $_GET['todo'] * 1;

$q = "select itemId from sys_pages_items_properties where name='picasaPhoto' and value='TODO' limit 0,".$numTodo;
$itemIdList = FDBTool::getCol($q);

foreach($itemIdList as $itemId) {
  echo $itemId.'<br>';
  $confGalery = FConf::get('galery');
  $itemVO = FactoryVO::get('ItemVO',(int) $itemId,true);
  $pageVO = null;
  if($itemVO) $pageVO = FactoryVO::get('PageVO',$itemVO->pageId,true);
  if(empty($itemVO) || empty($pageVO)) {
    //delete from TODO
    $q = "delete from sys_pages_items_properties where itemId='".$itemId."' and value='TODO'";
    FDBTool::query($q);
    echo 'DELETED: '.$itemId.'<br>';    
  } else {
    $itemVO->setProperty('picasaPhoto','INPROGRESS');
    $picasaAlbumId = $itemVO->pageVO->getProperty('picasaAlbum',false,true);
    $fgapps = FGApps::getInstance();
    $picasaPhotoUrl = $fgapps->createPhoto($picasaAlbumId,$confGalery['sourceServerBase'] . $itemVO->pageVO->get('galeryDir') . '/' . $itemVO->enclosure,$itemVO->text,$itemVO->enclosure);
    $itemVO->setProperty('picasaPhoto',$picasaPhotoUrl);
    $done++;
  }
}

echo 'JOB DONE<br><br>';
if($numTodo==1) {
  $q = "SELECT count(1) FROM `sys_pages_properties` WHERE `name` LIKE 'picasaAlbum';";
  echo "Albums: ".FDBTool::getOne($q).'<br>';
  $q = "SELECT count(1) FROM `sys_pages_items_properties` WHERE `name` LIKE 'picasaPhoto' and (value!='TODO' and value!='INPROGRESS' );";
  echo "Photos picased: ".FDBTool::getOne($q).'<br>';
  $q = "SELECT count(1) FROM `sys_pages_items_properties` WHERE `name` LIKE 'picasaPhoto' and value='TODO';";
  echo "Photos todo: ".FDBTool::getOne($q).'<br>';
  $q = "SELECT count(1) FROM `sys_pages_items_properties` WHERE `name` LIKE 'picasaPhoto' and value='INPROGRESS';";
  echo "Photos in progress or stuck: ".FDBTool::getOne($q).'<br>';
  $q = "SELECT * FROM `sys_pages_items_properties` WHERE `name` LIKE 'picasaPhoto' and value='INPROGRESS';";
  $list = FDBTool::getAll($q);
  echo "Stuck list:<br>";
  foreach($list as $item) {
    $itemVO = new ItemVO($item[0],true);
    $album = $itemVO->pageVO->getProperty('picasaAlbum');
    $fgapps = FGApps::getInstance();
    $albumEntry = $fgapps->getAlbum($album);
    $link = $albumEntry->getAlternateLink();
    
    

    echo '<a href="'.$link->getHref().'">Album</a>, <a href="http://fundekave.net/?i='.$itemVO->itemId.'">Photo: '.$itemVO->enclosure.'</a> <a href="/cron-picasa?update='.$itemVO->itemId.'">TODO</a> <a href="/cron-picasa?del='.$itemVO->itemId.'">Del</a></br>';
  }
}
