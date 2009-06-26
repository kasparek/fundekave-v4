<?php
class FMessages {

	//---get post
	static function load($userId, $from, $perpage, $count=false) {
		$base = ' FROM sys_users_post WHERE userId='.$userId;
		$cache = FCache::getInstance('s');

		if($filterText = $cache->getData('text','filtrPost')) $base.=" AND lower(text) LIKE '%".strtolower($filterText)."%'";
		if($filterUsername = $cache->getData('name','filtrPost')) {
			$filterUserId = FUser::getUserIdByName($filterUsername);
			if($filterUserId > 0) $base.=" AND (userIdTo='".$filterUserId."' OR userIdFrom='".$filterUserId."')";
		}

		$d_post = "SELECT postId,userId,userIdTo,userIdFrom,
    date_format(dateCreated,'%H:%i:%S %d.%m.%Y'),text,readed,date_format(dateCreated,'%Y-%m-%dT%T')".$base." ORDER BY dateCreated DESC";
	 if($count==true) return FDBTool::getOne('select count(1) '.$base);
	 else {
	 	$arr = FDBTool::getAll($d_post.' limit '.$from.','.$perpage);
	 	if(!empty($arr)) {
	 		foreach ($arr as $row) {
	 			$arrRet[] = array('postId'=>$row[0],'userId'=>$row[1],'userIdTo'=>$row[2],
     		'userIdFrom'=>$row[3],'datumcz'=>$row[4],'text'=>$row[5],'readed'=>$row[6],'datum'=>$row[7]); 
	 		}
	 		return $arrRet;
	 	}
	 }
	}

	/**
	 * replace key words in string to specified values
	 * @param $arrVars
	 * @param $template
	 * @return String
	 */
	static function parseMessage($arrVars,$template) {
		$message = $template;
		if(!empty($arrVars)) foreach ($arrVars as $k=>$v) $message = str_replace('{'.$k.'}',$v,$message);
		$message = str_replace('\"','"',$message);
		$message = str_replace('"','\"',$message);
		return $message;
	}
	/**
	 * sends message to superAdmins
	 * @param $arrVars
	 * @param $template
	 * @return void
	 */
	function sendSAMessage($arrVars,$template) {
		$arr = FDBTool::getCol('select userId from sys_users_perm where rules=2 and pageId="sadmi"');
		if(!empty($arr)) {
			$message = FMessages::parseMessage($arrVars,$template);
			foreach ($arr as $userId) FMessages::send($userId,$message);
		}
	}

	/**
	 * sends a message
	 * @param $komu
	 * @param $zprava
	 * @param $odkoho
	 * @return void
	 */
	static function send($komu,$zprava,$odkoho=LAMA_USER) {
		//odkoho=75 id lama
		$dot = "insert into sys_users_post (userId,userIdTo,userIdFrom,dateCreated,text,readed,postIdFrom)
		values (".$komu.",".$komu.",".$odkoho.",NOW(),'".$zprava."',0,null)";
		FDBTool::query($dot);
		$maxid = FDBTool::getOne("SELECT LAST_INSERT_ID()");
		$dot = "insert into sys_users_post (userId,userIdTo,userIdFrom,dateCreated,postIdFrom,text,readed)
		values (".$odkoho.",".$komu.",".$odkoho.",NOW(),".$maxid.",'".$zprava."',0)";
		FDBTool::query($dot);
		//---invalidate cache
		$cache = FCache::getInstance('s');
		$cache->invalidateData('postwho');
	}

	/**
	 * delete messegas
	 * @param $messageId - array or number
	 * @return void
	 */
	static function delete($messageId) { //--might be array or not
		if(!is_array($messageId)) $messageId[] = $messageId;
		FDBTool::query("delete from sys_users_post where postId in (" . implode(',',$messageId).")");
		//---invalidate cache
		$cache = FCache::getInstance('s');
		$cache->invalidateData('postwho');
	}

	/**
	 * sends diary notifications to all users needed
	 * @return number - sum how many notifications sent
	 */
	static function diaryNotifications(){
		$sentCount = 0;
		$fQuery = new FDBTool('sys_users_diary');
		$fQuery->setSelect("diaryId, name, date_format(dateEvent,'{#date_local#}'), everyday, reminder, userId, date_format(dateEvent,'{#date_iso#}')");
		$fQuery->setWhere("DATE_SUB(dateEvent,INTERVAL (reminder-1) DAY)<=NOW() AND reminder != 0");
		$arr = $fQuery->getContent();
		if(!empty($arr)){
			foreach($arr as $row){
				if($row[3]==1) $newprip=$row[4]-1; else $newprip=0;
				$dot = "UPDATE sys_users_diary SET reminder=$newprip WHERE diaryId=".$row[0];
				FDBTool::query($dot);
				$arrVars = array('LINK'=>BASESCRIPTNAME.'?k=fdiar&ddate='.$row[6],'NAME'=>$row[1],'DATE'=>$row[2]);
				$message = FMessages::parseMessage($arrVars,MESSAGE_DIARY_REMINDER);
				FMessages::send($row[5],$message);
				$sentCount++;
			}
		}
		return $sentCount;
	}
}