<?php
if(isset($_REQUEST['usrfilter'])) $_SESSION['bannanusrfilter']=$usrfilter=$_REQUEST['usrfilter']; 
elseif(!empty($_SESSION['bannanusrfilter'])) $usrfilter=$_SESSION['bannanusrfilter'];
else $usrfilter=0;

if(isset($_GET['du']) && FRules::getCurrent()) FUser::invalidateUsers($_GET['du']);
if(isset($_POST["usersstat"]) && FRules::getCurrent()) {
	foreach ($_POST["usersstat"] as $k=>$v){
		FDBTool::query("update sys_users set deleted=".$v." where userId=".$k);
	}
	FHTTP::redirect(FUser::getUri());
}

//-----------------SELECT
$base = "FROM sys_users as s ".(($usrfilter!=3)?(' left join '):(' join '))." sys_users_logged as l on l.userId=s.userId ";
if($usrfilter==1) $base.=" where s.dateUpdated is null";
elseif($usrfilter==2) $base.=" where s.dateUpdated is not null";
elseif($usrfilter==4) $base.=" where s.deleted = 0";
elseif($usrfilter==5) $base.=" where s.deleted = 1";
$base.=' order by s.userId desc ';
$perpage = 40;
$total = $db->getOne("SELECT count(1) ".$base);

$tpl = new fTemplateIT('sys.admin.bann.tpl.html');
$tpl->setVariable('TOTALITEMS',$total);
$dot = "SELECT s.userId,s.name,s.deleted,s.dateUpdated,s.dateCreated,s.hit,l.ip,s.ipcheck ".$base;
if($total>$perpage) {
  $pager = fSystem::initPager($total,$perpage);
  $od=($pager->getCurrentPageID()-1) * $perpage;
  $dot .=" limit ".$od.",".$perpage;
  $tpl->setVariable('PAGER',$pager->links);
}
$tpl->touchBlock('filter'.$usrfilter);

$users=$db->getAll($dot);
//-------------------SHOW users
foreach ($users as $usr) {
  $tpl->setCurrentBlock('user');
  $tpl->setVariable('ID',$usr[0]);
  $tpl->setVariable('NAME',$usr[1]);
  $tpl->setVariable('URL','?k=finfo&who='.$usr[0]);
  $tpl->setVariable('CREATED',$usr[3]);
  $tpl->setVariable('UPDATED',$usr[4]);
  $tpl->setVariable('HIT',$usr[5]);
  $tpl->touchBlock('userlocked'.($usr[2]*1));
  if(!empty($usr[6])) {
    $tpl->setVariable('DISCONNECTURL',$user->getUri('du='.$usr[0]));
    $tpl->setVariable('IP',$usr[6]);
  }
  $tpl->parseCurrentBlock();
}

$TOPTPL->addTab(array("MAINDATA"=>$tpl->get()));
?>