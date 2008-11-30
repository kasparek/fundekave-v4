<?php
$draftTextId = 'addftext';

$dden = 0;
if(!empty($_REQUEST['ddate']))
	list($drok,$dmesic,$dden)=explode("-",$_REQUEST['ddate']);
	
if($dden<1 || $dden>31) {
	$allmonth=true; 
	$dden='01';
} else $allmonth=false;
if(empty($_REQUEST['ddate']) || !checkdate($dmesic,$dden,$drok)) {
	$dmesic = date("m");
	$drok = date("Y");
	$dden = date("d");
}

if (isset($_POST['save'])){
	$arrd['recurrence'] = $_POST['drepeat'] * 1;
	$arrd['name'] = fSystem::textins($_POST['dzkratka'],array('plainText'=>1));
	$arrd['text'] = fSystem::textins($_POST['dtext']);
	
	list($nden,$nmesic,$nrok)=explode(".",$_POST['addfdate']);
	if(checkdate($nmesic,$nden,$nrok)) $arrd['dateEvent'] = sprintf("%04d-%02d-%02d",$nrok,$nmesic,$nden); else fError::addError(ERROR_DATA_FORMAT);
	$arrd['userId'] = $user->gid;
	$arrd['reminder'] = $_POST['dpripomen'] * 1;
	$arrd['dateCreated'] = 'NOW()';
	if(isset($_POST['did'])) $arrd['diaryId'] = $_POST['did'] * 1;
	if($arrd['name']=='') fError::addError(ERROR_DIARY_NAME);
	if($arrd['text']=='') fError::addError(ERROR_DIARY_TEXT);
	$arrd['everyday']= $_POST['dopakovat'] * 1;
	$arrd['eventForAll']= $_POST['dpublic'] * 1;
	if(!fError::isError()){
		$sAnketa = new fSqlSaveTool('sys_users_diary','diaryId');
		$sAnketa->Save($arrd,array('dateCreated'));
		$user->cacheRemove('calendarlefthand');
		fUserDraft::clear($draftTextId);
	} else {
		$_SESSION['diar_arr'] = $arrd;
	}
	fHTTP::redirect($user->getUri('ddate='.$arrd['dateEvent']));
}

if(!empty($_REQUEST['del']) && $user->idkontrol) {
        $dot = 'delete from sys_users_diary where diaryId="'.($_REQUEST['del']*1).'"';
		if($db->query($dot)) { 
		  fError::addError(LABEL_DELETED_OK);
		  $user->cacheRemove('calendarlefthand');
		}
		fHTTP::redirect($user->getUri('ddate='.$_REQUEST['ddate']));
}

if(!empty($_REQUEST['did'])) {
  $eventId = $_REQUEST['did'] * 1;
	$qDiar = new fQueryTool('sys_users_diary as d');
	$qDiar->setSelect("diaryId,name,text,date_format(dateEvent,'%d.%m.%Y') as devent,reminder,everyday,eventForAll,recurrence");
	$qDiar->setWhere("userId='".$user->gid."'");
	$qDiar->addWhere('diaryId="'.$eventId.'"');
	$arrd = $db->getRow($qDiar->buildQuery());
}
if(isset($_SESSION['diar_arr'])) {
	$arrd=$_SESSION['diar_arr'];
	$da=explode("-",$arrd['datum']);
	if(!empty($da[0])) $arrd['rok']=$da[0]; else $arrd['rok']=$drok;
	if(!empty($da[1])) $arrd['mesic']=$da[1]; else $arrd['mesic']=$dmesic;
	if(!empty($da[2])) $arrd['den']=$da[2]; else $arrd['den']=$dden;
	unset($_SESSION['diar_arr']);
}
//---show part
$tpl = new fTemplateIT('users.diary.tpl.html');

$fCalendar = new fCalendar();
$tpl->setVariable('JSCALENDARBUTTON',$fCalendar->make_button('addfdate'));

$tpl->setVariable('FORMACTION',$user->getUri());

$tpl->setVariable('ADDFORMACTION',$user->getUri());

if(isset($_POST['dsearch'])) $tpl->setVariable('SEARCHTEXT',$_POST['dsearch']);
if(isset($_POST['dsden'])) $tpl->setVariable('SEARCHDAY',$_POST['dsden']);
if(isset($_POST['dsmesic'])) $tpl->setVariable('SEARCHMONTH',$_POST['dsmesic']);
if(isset($_POST['dsrok'])) $tpl->setVariable('SEARCHYEAR',$_POST['dsrok']);

$tpl->setVariable('TODAYDATE',$dden.'.'.$dmesic.'.'.$drok);

$tpl->setVariable('SHOWMONTHLINK',$user->getUri('ddate='.$drok.'-'.$dmesic.'-00'));
$tpl->setVariable('SHOWMINELINK',$user->getUri('ddate='.$drok.'-'.$dmesic.'-00&l=m'));

if(isset($arrd)) {
    $tpl->setVariable('DID',$arrd[0]);
    $tpl->setVariable('DNAME',$arrd[1]);
    $tpl->setVariable('DDATE',$arrd[3]);
    if($arrd[5]==1) $tpl->touchBlock('everydayselected');
    if($arrd[6]==1) $tpl->touchBlock('publicselected');
    $tpl->setVariable('DTEXT',$arrd[2]);
} else {
    $tpl->touchBlock('formhidden');
    $arrd[4]=0;
    $arrd[7]=0;
    $draftText = fUserDraft::get($draftTextId);
    if($draftText) $tpl->setVariable('DTEXT',$draftText);
}

$tpl->addTextareaToolbox('DTEXTTOOLBOX','addftext');

$reminderOptions='';
foreach ($DIARYREMINDER as $k=>$v) {
	$reminderOptions.='<option value="'.$k.'"'.(($k==$arrd[4])?(' selected="selected"'):('')).'>'.$v.'</option>';
}
$tpl->setVariable('REMINDEROPTIONS',$reminderOptions);
$repeatOptions='';
foreach ($DIARYREPEATER as $k=>$v) {
	$repeatOptions.='<option value="'.$k.'"'.(($k==$arrd[7])?(' selected="selected"'):('')).'>'.$v.'</option>';
}
$tpl->setVariable('REPEATOPTIONS',$repeatOptions);	

//--------------vypis udalosti
$qDiar = new fQueryTool('sys_users_diary as d');
$qDiar->setSelect("date_format(d.dateEvent,'%d.%m.%Y') as datumcz,name,text,diaryId as id,userId,dateEvent,dateCreated,0 as typ");
if(!isset($_POST['search'])) {
	$qDiar->addWhere("YEAR(dateEvent)='".$drok."' AND MONTH(dateEvent)='".$dmesic."'");
	if($allmonth!=true) $qDiar->addWhere("DAYOFMONTH(dateEvent)='".$dden."'");
} else {
	if(!empty($_POST['dsrok'])) $qDiar->addWhere("YEAR(dateEvent)='".($_POST['dsrok']*1)."'");
	if(!empty($_POST['dsmesic'])) $qDiar->addWhere("MONTH(dateEvent)='".($_POST['dsmesic']*1)."'");
	if(!empty($_POST['dsden'])) $qDiar->addWhere("DAYOFMONTH(dateEvent)='".($_POST['dsden']*1)."'");
	if(trim($_POST['dsearch'])!="") $qDiar->addWhere("(LOWER(name) LIKE '%".strtolower(trim($_POST['dsearch']))."%' OR LOWER(text) LIKE '%".strtolower(trim($_POST['dsearch']))."%')");
}
if(isset($_REQUEST['l'])) $qDiar->addWhere("userId='".$user->gid."'");
else $qDiar->addWhere("(userId='".$user->gid."' or eventForAll='1')");
$qDiar->addWhere("recurrence=0");
$dot1=$qDiar->buildQuery();

//---pripraveni druheho dotazu na union s akcema
$qAkce = new fQueryTool('sys_pages_items as e');
$qAkce->setSelect("date_format(dateStart,'%d.%m.%Y') as datumcz,addon,location,itemId as id,userId,dateStart,dateCreated,3 as typ");
if(!isset($_POST['search'])) {
	$qAkce->addWhere("YEAR(dateStart)='".$drok."' AND MONTH(dateStart)='".$dmesic."'");
	if(!$allmonth) $qAkce->addWhere("DAYOFMONTH(dateStart)='".$dden."'");
} else {
	if(!empty($_POST['dsrok'])) $qAkce->addWhere("YEAR(dateStart)='".($_POST['dsrok']*1)."'");
	if(!empty($_POST['dsmesic'])) $qAkce->addWhere("MONTH(dateStart)='".($_POST['dsmesic']*1)."'");
	if(!empty($_POST['dsden'])) $qAkce->addWhere("DAYOFMONTH(dateStart)='".($_POST['dsden']*1)."'");
	if(trim($_POST['dsearch'])!="") $qAkce->addWhere("(LOWER(addon) LIKE '%".strtolower(trim($_POST['dsearch']))."%' 
	OR LOWER(text) LIKE '%".strtolower(trim($_POST['dsearch']))."%' 
	OR LOWER(location) LIKE '%".strtolower(trim($_POST['dsearch']))."%')");
}
if(isset($_REQUEST['l'])) $qAkce->addWhere("userId='".$user->gid."'");
$dot2=$qAkce->buildQuery();

//---dotaz pro opakuj�c� se akce
$qDiarEvery = new fQueryTool('sys_users_diary');
$qDiarEvery->setSelect("concat(date_format(dateEvent,'%d.%m.'),date_format(NOW(),'%Y')) as datumcz,
name,
text,
diaryId as id,
userId,
concat(date_format(NOW(),'%Y'),date_format(dateEvent,'-%m-%d')) as dateEvent,
dateCreated,
recurrence as typ");
if(!isset($_POST['search'])) {
	$qDiarEvery->addWhere("month(dateEvent)='".($dmesic*1)."'");
	if(!$allmonth) $qDiarEvery->addWhere("dayofmonth(dateEvent)='".($dden*1)."'");
} else {
	if(!empty($_POST['dsmesic'])) $qDiarEvery->addWhere("month(dateEvent)='".($_POST['dsmesic']*1)."'");
	if(!empty($_POST['dsden'])) $qDiarEvery->addWhere("dayofmonth(dateEvent)='".($_POST['dsden']*1)."'");
	if(trim($_POST['dsearch'])!="") $qDiarEvery->addWhere("(LOWER(name) LIKE '%".strtolower(trim($_POST['dsearch']))."%' OR LOWER(text) LIKE '%".strtolower(trim($_POST['dsearch']))."%')");
}
if(isset($_REQUEST['l'])) $qDiarEvery->addWhere("userId='".$user->gid."'");
else $qDiarEvery->addWhere("(userId='".$user->gid."' or eventForAll='1')");
$qDiarEvery->addWhere("recurrence in (1,2)");
$dot3=$qDiarEvery->buildQuery();
//---konec a kompletace dotazu
$dot="(".$dot1.") union (".$dot2.") union (".$dot3.") order by dateEvent";
//echo $dot;
$arr=$db->getAll($dot);

if(count($arr)>0) {
	$oldDate = $arr[0][0];
	$parseBlock = false;
	while($arr) {
	 $row = array_shift($arr);
	 
      $tpl->setCurrentBlock('diaryevent');
	    if($row[7]==3) {
	       $tpl->setVariable('EVENTLINK',$user->getUri('i='.$row[4],'event'));
	       $tpl->setVariable('EVENTNAME',$row[1]);
	    } else {
	        $tpl->setVariable('EVENTNAMENOLINK',$row[1]);
	    }
	    if($row[7]=='1') $tpl->setVariable('DUMMYRYEAR',' ');
      elseif($row[7]=='2') $tpl->setVariable('DUMMYRMONTH',' ');
      $tpl->setVariable('EVENTTEXT',nl2br($row[2]));
	    if($row[7]==3) $tpl->setVariable('EVENTEVENTLINK',$user->getUri('i='.$row[3],'event'));
  		if($row[5]!=$user->gid && $row[7]!=0) {
  		    $tpl->setVariable('OWNERLINK','?k=33&who='.$row[4]);
  		    $tpl->setVariable('OWNERNAME',$user->getgidname($row[4])); 
              $tpl->setVariable('OWNERAVATAR',$user->showAvatar($row[4]));
  		} else {
  		    $tpl->setVariable('EVENTEDITLINK',$user->getUri('ddate='.$drok.'-'.$dmesic.'-'.$dden.'&did='.$row[3]));
  		    $tpl->setVariable('EVENTDELETELINK',$user->getUri('ddate='.$drok.'-'.$dmesic.'-'.$dden.'&del='.$row[3]));
  		}
		  $tpl->parseCurrentBlock();
     
      
	    if(!isset($arr[0])) $parseBlock = true;
		  elseif ($arr[0][0] != $oldDate) {
        $parseBlock=true;
        $oldDate = $arr[0][0];
      }
      
  		if($parseBlock==true) {
  		  //---parse day
  		  $tpl->setCurrentBlock('diarday');
      	$tpl->setVariable('DAYDATE',$row[0]);
  		  $tpl->parseCurrentBlock(); 
  		  $parseBlock = false;
      } 

	}
	
} else {
	$tpl->setVariable('DUMMYEMPTYDIARY',' ');
}
//----------------konec vypis udalosti
$TOPTPL->addTab(array("MAINDATA"=>$tpl->get()));