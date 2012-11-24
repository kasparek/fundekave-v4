<?php
return false;

$uid = $_GET['abot'];
if(empty($uid)) {
	echo 'missing parameter';
	exit;
}
$data=array();
$data['__get']['abot'] = $uid;

$cache = FCache::getInstance('d');
$storedData = $cache->getData($uid,'antibot');
if(!$storedData) {
	echo 'invalid data';
	exit;
}

$user->itemVO = new ItemVO($storedData['__get']['i'],true);
if(!$user->itemVO->loaded) {
	echo 'top does not exist';
	exit;
} 

$user->pageId = $topItemVO->pageId;
$user->pageVO = new PageVO($user->pageId,true); 

FItemsForm::process($data);

echo 'processed';