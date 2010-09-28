<?php
//TODO: deprecated - udalosto se daji vkladat do klubu, alerty do posty pokud jsou sledovany, nekde stranka se vsema eventama, i opakujicima se 
include_once('iPage.php');
class page_UserDiary implements iPage {

	static function process($data) {
		
		//---nova udalost
		if (isset($data['save'])){
				
			$user = FUser::getInstance();
				
			$arrd['recurrence'] = $data['drepeat'] * 1;
			$arrd['name'] = FSystem::textins($data['dzkratka'],array('plainText'=>1));
			$arrd['text'] = FSystem::textins($data['dtext']);

			list($nden,$nmesic,$nrok)=explode(".",$data['addfdate']);
			if(checkdate($nmesic,$nden,$nrok)) $arrd['dateEvent'] = sprintf("%04d-%02d-%02d",$nrok,$nmesic,$nden); else FError::addError(ERROR_DATA_FORMAT);
			$arrd['userId'] = $user->userVO->userId;
			$arrd['reminder'] = $data['dpripomen'] * 1;
			$arrd['dateCreated'] = 'NOW()';
			if(isset($data['did'])) $arrd['diaryId'] = $data['did'] * 1;
			if($arrd['name']=='') FError::addError(FLang::$ERROR_DIARY_NAME);
			if($arrd['text']=='') FError::addError(FLang::$ERROR_DIARY_TEXT);
			$arrd['everyday'] = $data['dopakovat'] * 1;
			$arrd['eventForAll'] = $data['dpublic'] * 1;
			if(!FError::isError()){
				$fdbtool = new FDBTool('sys_users_diary','diaryId');
				$fdbtool->save($arrd,array('dateCreated'));
				$cache = FCache::getInstance('f');
				$cache->invalidateGroup('calendarlefthand');
				FUserDraft::clear($user->pageVO->pageId);
			} else {
				$cache = FCache::getInstance('s');
				$cache->setData($arrd,'diary','form');
			}
			FHTTP::redirect(FSystem::getUri('ddate='.$arrd['dateEvent']));
		}

		//---mazani udalosti
		if(!empty($_REQUEST['del']) && FUser::logon()) {
			$dot = 'delete from sys_users_diary where diaryId="'.($_REQUEST['del']*1).'"';
			if(FDBTool::query($dot)) {
				FError::addError(FLang::$LABEL_DELETED_OK);
				$cache = FCache::getInstance('f');
				$user->invalidateGroup('calendarlefthand');
			}
			FHTTP::redirect(FSystem::getUri('ddate='.$_REQUEST['ddate']));
		}
	}

	static function build($data=array()) {
		$user = FUser::getInstance();

		$dden = 0;
		if(!empty($_REQUEST['ddate']))
		list($drok,$dmesic,$dden)=explode("-",$_REQUEST['ddate']);

		if(empty($_REQUEST['ddate']) || !checkdate($dmesic,$dden,$drok)) {
			$dmesic = date("m");
			$drok = date("Y");
			$dden = date("d");
		}

		if(!empty($_REQUEST['did'])) {
			$eventId = $_REQUEST['did'] * 1;
			$qDiar = new FDBTool('sys_users_diary','diaryId');
			$qDiar->setSelect("diaryId,name,text,date_format(dateEvent,'%d.%m.%Y') as devent,reminder,everyday,eventForAll,recurrence");
			$arrd = $qDiar->get($eventId);
		}
		
		$cache = FCache::getInstance('s');
				
		if(($arrCache = $cache->getData('diary','form'))!==false) {
			$arrd = $arrCache;
			$da = explode("-",$arrd['datum']);
			if(!empty($da[0])) $arrd['rok']=$da[0]; else $arrd['rok'] = $drok;
			if(!empty($da[1])) $arrd['mesic']=$da[1]; else $arrd['mesic'] = $dmesic;
			if(!empty($da[2])) $arrd['den']=$da[2]; else $arrd['den'] = $dden;
			$cache->invalidateData('diary','form');
		}
		
		//---show part
		$tpl = FSystem::tpl('users.diary.tpl.html');

		$tpl->setVariable('FORMACTION',FSystem::getUri());
		$tpl->setVariable('ADDFORMACTION',FSystem::getUri());

		if(isset($_POST['dsearch'])) $tpl->setVariable('SEARCHTEXT',$_POST['dsearch']);
		if(isset($_POST['dsden'])) $tpl->setVariable('SEARCHDAY',$_POST['dsden']);
		if(isset($_POST['dsmesic'])) $tpl->setVariable('SEARCHMONTH',$_POST['dsmesic']);
		if(isset($_POST['dsrok'])) $tpl->setVariable('SEARCHYEAR',$_POST['dsrok']);

		$tpl->setVariable('TODAYDATE',$dden.'.'.$dmesic.'.'.$drok);

		$tpl->setVariable('SHOWMINELINK',FSystem::getUri('ddate='.$drok.'-'.$dmesic.'-00&l=m'));

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
			$draftText = FUserDraft::get($user->pageVO->pageId);
			if($draftText) $tpl->setVariable('DTEXT',$draftText);
		}

		$tpl->addTextareaToolbox('DTEXTTOOLBOX','addftext');

		$reminderOptions='';

		foreach (FLang::$DIARYREMINDER as $k=>$v) {
			$reminderOptions.='<option value="'.$k.'"'.(($k==$arrd[4])?(' selected="selected"'):('')).'>'.$v.'</option>';
		}
		$tpl->setVariable('REMINDEROPTIONS',$reminderOptions);
		$repeatOptions='';
		foreach (FLang::$DIARYREPEATER as $k=>$v) {
			$repeatOptions.='<option value="'.$k.'"'.(($k==$arrd[7])?(' selected="selected"'):('')).'>'.$v.'</option>';
		}
		$tpl->setVariable('REPEATOPTIONS',$repeatOptions);

		//--------------vypis udalosti
		$qDiar = new FDBTool('sys_users_diary','diaryId');
		$qDiar->setSelect("date_format(dateEvent,'{#date_local#}') as datumcz,name,text,diaryId as id,userId,date_format(dateEvent,'{#date_iso#}') as dateEvent,dateCreated,0 as typ");
		if(!isset($_POST['search'])) {

			$qDiar->addWhere("YEAR(dateEvent)='".$drok."' AND MONTH(dateEvent)='".$dmesic."'");

		} else {

			if(!empty($_POST['dsrok'])) $qDiar->addWhere("YEAR(dateEvent)='".($_POST['dsrok']*1)."'");
			if(!empty($_POST['dsmesic'])) $qDiar->addWhere("MONTH(dateEvent)='".($_POST['dsmesic']*1)."'");
			if(!empty($_POST['dsden'])) $qDiar->addWhere("DAYOFMONTH(dateEvent)='".($_POST['dsden']*1)."'");
			if(trim($_POST['dsearch'])!="") $qDiar->addWhere("(LOWER(name) LIKE '%".strtolower(trim($_POST['dsearch']))."%' OR LOWER(text) LIKE '%".strtolower(trim($_POST['dsearch']))."%')");

		}
		
		if(isset($_REQUEST['l'])) {
			$qDiar->addWhere("userId='".$user->userVO->userId."'");
		} else {
			$qDiar->addWhere("(userId='".$user->userVO->userId."' or eventForAll='1')");
		}
		$qDiar->addWhere("recurrence=0");
		$dot1=$qDiar->buildQuery();

		//---pripraveni druheho dotazu na union s akcema - tipu
		$qAkce = new FDBTool('sys_pages_items','itemId');
		$qAkce->setSelect("date_format(dateStart,'{#date_local#}') as datumcz,addon,location,itemId as id,userId,date_format(dateStart,'{#date_iso#}') as dateEvent,dateCreated,3 as typ");
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
		if(isset($_REQUEST['l'])) $qAkce->addWhere("userId='".$user->userVO->userId."'");
		$dot2 = $qAkce->buildQuery();

		//---dotaz pro opakujici se akce
		$qDiarEvery = new FDBTool('sys_users_diary');
		$qDiarEvery->setSelect("concat(date_format(dateEvent,'%d.%m.'),date_format(NOW(),'%Y')) as datumcz,name,text,diaryId as id,userId,concat(date_format(NOW(),'%Y'),date_format(dateEvent,'-%m-%d')) as dateEvent,dateCreated,recurrence as typ");
		if(!isset($_POST['search'])) {
			$qDiarEvery->addWhere("month(dateEvent)='".($dmesic*1)."'");
			if(!$allmonth) $qDiarEvery->addWhere("dayofmonth(dateEvent)='".($dden*1)."'");
		} else {
			if(!empty($_POST['dsmesic'])) $qDiarEvery->addWhere("month(dateEvent)='".($_POST['dsmesic']*1)."'");
			if(!empty($_POST['dsden'])) $qDiarEvery->addWhere("dayofmonth(dateEvent)='".($_POST['dsden']*1)."'");
			if(trim($_POST['dsearch'])!="") $qDiarEvery->addWhere("(LOWER(name) LIKE '%".strtolower(trim($_POST['dsearch']))."%' OR LOWER(text) LIKE '%".strtolower(trim($_POST['dsearch']))."%')");
		}
		if(isset($_REQUEST['l'])) $qDiarEvery->addWhere("userId='".$user->userVO->userId."'");
		else $qDiarEvery->addWhere("(userId='".$user->userVO->userId."' or eventForAll='1')");
		$qDiarEvery->addWhere("recurrence in (1,2)");
		$dot3 = $qDiarEvery->buildQuery();
		//---konec a kompletace dotazu
		$dot="(".$dot1.") union (".$dot2.") union (".$dot3.") order by dateEvent";

		$arr = FDBTool::getAll($dot);

		if(count($arr)>0) {
			$oldDate = $arr[0][0];
			$parseBlock = false;
			while($arr) {
				$row = array_shift($arr);

				$tpl->setCurrentBlock('diaryevent');
				$tpl->setVariable('STARTDATETIMELOCAL',$row[0]);
				$tpl->setVariable('STARTDATETIMEISO',$row[5]);

				$tpl->setVariable('EVENTNAME',$row[1]);
					
				if($row[7] == 3) {
					$tpl->setVariable('EVENTLINK',FSystem::getUri('i='.$row[4],'event'));
					$tpl->touchBlock('eventlinkclose');
				}
				if($row[7]=='1') $tpl->touchBlock('repeatyear');
				elseif($row[7]=='2') $tpl->touchBlock('repeatmonth');
				$tpl->setVariable('EVENTTEXT',nl2br($row[2]));
				if($row[7]==3) $tpl->setVariable('EVENTEVENTLINK',FSystem::getUri('i='.$row[3],'event'));
				if($row[5]!=$user->userVO->userId && $row[7]!=0) {
					$tpl->setVariable('AUTHORLINK',FSystem::getUri('who='.$row[4],'finfo'));
					$tpl->setVariable('AUTHOR',FUser::getgidname($row[4]));
				} else {
					$tpl->setVariable('EVENTEDITLINK',FSystem::getUri('ddate='.$drok.'-'.$dmesic.'-'.$dden.'&did='.$row[3]));
					$tpl->setVariable('EVENTDELETELINK',FSystem::getUri('ddate='.$drok.'-'.$dmesic.'-'.$dden.'&del='.$row[3]));
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
		  	$tpl->touchBlock('diarday');
		  	$tpl->parseCurrentBlock();
		  	$parseBlock = false;
		  }
			}
		} else {
			$tpl->touchBlock('emptydiary');
		}
		//----------------konec vypis udalosti
		FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));
	}
}