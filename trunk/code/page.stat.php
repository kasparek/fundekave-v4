<?php
$tpl = new fTemplateIT('forums.stat.tpl.html');
$tpl->setVariable('OWNERLINK','?k=33&who='.$user->currentPage['userIdOwner']);
$tpl->setVariable('OWNERNAME',$user->getgidname($user->currentPage['userIdOwner']));
$tpl->setVariable('MESSAGESCOUNT',$user->currentPage['cnt']);
$tpl->setVariable('DATECREATED',$user->currentPage['dateCreated']);

$dot = "select c.userId,
sum(c.hit) as hitsum,
sum(c.ins),
f.cnt,
f.book 
from sys_pages_counter as c 
left join sys_pages_favorites as f on c.pageId=f.pageId and c.userId=f.userId 
where c.pageId='".$user->currentPageId."' group by c.userId order by hitsum desc";
$arr=$db->getAll($dot);

$poz = false;
$x=1;
foreach ($arr as $row) {
    $tpl->setCurrentBlock('userstat');
    if($poz) $tpl->setVariable('DUMMYODD',' ');
    $tpl->setVariable('USERNUMBER',$x++);
    if($row[0]>0) {
        $tpl->setVariable('USERLINK','?k=finfo&who='.$row[0]);
        $tpl->setVariable('USERNAME',$user->getgidname($row[0]));    
    } else {
        $tpl->setVariable('DUMMYNOTREG','');
    }
    $tpl->setVariable('OWN',$row[2]);
    $tpl->setVariable('UNREADED',$user->currentPage['cnt']-$row[3]);
    $tpl->setVariable('VISITS',$row[1]);
    if ($user->currentPage['userIdOwner']==$row[0]) $watchin = LABEL_OWNER;
	elseif ($row[0]==0) $watchin = LABEL_NOTREGISTEREDUSERS;
	elseif($row[4]==1) $watchin=LABEL_YES;
	else $watchin = LABEL_NO;
    $tpl->setVariable('BOOKED',$watchin);
    $tpl->parseCurrentBlock();
    
	if($poz) $poz=false; else $poz=true;
}

$TOPTPL->addTab(array("MAINHEAD"=>'',"MAINDATA"=>$tpl->get()));
?>