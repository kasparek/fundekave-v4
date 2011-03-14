<?php
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

//insert into sys_cache values ('antibot','abot4d7096bfbb51e','a:8:{s:1:"m";s:11:"item-submit";s:1:"t";s:5:"forum";s:4:"name";s:15:"paja-ostrovanka";s:4:"text";s:286:"vivat afrika! mne se libi vsechny, fakt bych je nosila, i kdyz normnalne saty a sukne moc nenosim. gabino, nechces tu pani svadlenu zardit mezi sve charitativni projekty? kdyz jeji saty prodas na netu za nekolikanasobek, bude spokojena svadlena, zakaznik i charita... premyslej o tom...";s:8:"position";s:0:"";s:4:"send";s:7:"Odeslat";s:14:"__ajaxResponse";b:0;s:5:"__get";a:1:{s:1:"i";s:6:"184408";}}',now(),now(),'0');