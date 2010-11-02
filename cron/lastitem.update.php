<?php
$counter=0;
$pageList = FDBTool::getCol("select pageId from sys_pages where typeId in ('blog','galery','forum')");
$total = count($pageList);
foreach($pageList as $pageId) {
$item = new ItemVO();
$item->pageId = $pageId; 
if($item->updateItemIdLast()>0) $counter++;
}

echo 'total: '.$total.' updated:'.$counter;
