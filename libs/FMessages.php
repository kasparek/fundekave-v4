<?php
class FMessages {

	private $userId;
	
	public $searchText;
	public $searchUser;

	function __construct($userId,$searchText=null,$searchUser=null) {
	    $this->userId = $userId;
	    $this->searchText = $searchText;
	    $this->searchUser = $searchUser;
	}
	
	function total() {
		return $this->load(0,0,true);
	}
	
	//---get post
	function load($from, $perpage, $count=false) {
		$base = ' FROM sys_users_post WHERE userId='.$this->userId;
		if(!empty($this->searchText)) $base.=" AND lower(text) LIKE '%".strtolower($this->searchText)."%'";
		if(!empty($this->searchUser)) {
			$searchUserId = FUser::getUserIdByName($this->searchUser);
			if($searchUserId > 0) $base.=" AND (userIdTo='".$searchUserId."' OR userIdFrom='".$searchUserId."')";
		}
	 if($count==true) return FDBTool::getOne('select count(1) '.$base);
	 else {
	 	$q = "SELECT postId,userId,userIdTo,userIdFrom,date_format(dateCreated,'%H:%i:%S %d.%m.%Y'),text,readed,date_format(dateCreated,'%Y-%m-%dT%T')".$base." ORDER BY dateCreated DESC";
	 	$arr = FDBTool::getAll($q.' limit '.$from.','.$perpage);
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
		$db = FDBConn::getInstance();
		$komu = $komu*1; $odkoho=$odkoho*1; $zprava = $db->escape($zprava); 
		FDBTool::query("insert into sys_users_post (userId,userIdTo,userIdFrom,dateCreated,text,readed,postIdFrom) values (".$komu.",".$komu.",".$odkoho.",NOW(),'".$zprava."',0,null)");
		$maxid = FDBTool::getOne("SELECT LAST_INSERT_ID()");
		FDBTool::query("insert into sys_users_post (userId,userIdTo,userIdFrom,dateCreated,postIdFrom,text,readed) values (".$odkoho.",".$komu.",".$odkoho.",NOW(),".$maxid.",'".$zprava."',0)");
	}

	/**
	 * delete messegas
	 * @param $messageId - array or number
	 * @return void
	 */
	static function delete($messageId) { //--might be array or not
		if(!is_array($messageId)) $messageId[] = $messageId;
		FDBTool::query("delete from sys_users_post where postId in (" . implode(',',$messageId).")");
	}
	
	/**
	 * check if any of input messId are already readed if so it return them in return
	 * @param $sentUnreaded - comma separeated postId 
	 * @return String - comma separeted postId	 	 
	 */	 	
	static function sentReaded($sentUnreaded) {
		$sentUnreadedList = explode(',',$sentUnreaded);
		if(empty($sentUnreadedList)) return;
		while($postId = array_pop($sentUnreadedList)) {
			$postId = trim($postId) * 1;
			if($postId>0) $validatedList[] = $postId;  
		}
		if(empty($validatedList)) return;
		$res = FDBTool::getCol("select postId from sys_users_post where readed=1 and postId in (".implode(',',$validatedList).")");
		if(!empty($res)) return implode(',',$res);			
	}
}