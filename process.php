<?php
/**
 *TODO: 
 *1. both logoff - delete chat
 *
 **/  


  $function = (int) $_REQUEST['f'];
  if(isset($_REQUEST['s'])) $state = (int) $_REQUEST['s'];
	if(isset($_POST['m'])) {
	    $message = $_POST['m'];
    	$message = trim($message);
			//$message = stripslashes($message);
			//$message = strip_tags($message);
	}
	
	
	
	$userRecipient = (int) $_REQUEST['i'];
	$userId = $userRecipient==1 ? 53 : 1;
	//session_start();
	//$userId = $_SESSION['userId'];
	
	$fileArr = array(sprintf("%05d",$userId),sprintf("%05d",$userRecipient));
	sort($fileArr);
	$file = 'c_'.implode($fileArr,'_').'.txt';
	$fileState = 'chat_state.txt';
    
  $log = array();
    
	switch ($function) {
		case 4:
			//initialization
			$log['readed'] = 0;
			if (file_exists($fileState)) {
				$fileContent = file($fileState);
				$obj = json_decode($fileContent[0]);
			}
			$log['readed'] = $obj->$userId;
    case 1:
    	$currentState = 0;
			$finish = time() + 50;
			if (file_exists($file)) $currentState = count(file($file));
			    
			while ($currentState <= $state) {
				$now = time();
				usleep(10000);
				if ($now <= $finish) {
					if (file_exists($file)) {
			  		$currentState = count(file($file));
			  	}
				} else {
					break;	
				}  
			}
			
			$log['s'] = $currentState;
			if ($state != $currentState) {
				$fileContent = file($file);
				foreach ($fileContent as $line_num => $line) {
					if ($line_num >= $state) {
						$line = json_decode(trim($line));
						$line[0] = $line[0]==$userId ? 1 : 0;
						$log['text'][] = $line; 
					}
				}
			}
			break;	
 	 
    case 2:
			if (!empty($message)) {
				fwrite(fopen($file, 'a'), json_encode(array($userId,Date("H:i:s"),$message)). "\n" );
				if (file_exists($file)) {
					$fileContent = file($file);
			  }
				$state = count($fileContent);
				updateState($fileState,$userId,$userRecipient,$state);
			}
			break;
		case 3:
			updateState($fileState,$userId,$userRecipient,$state);
			break;
		case 5:
		  $finish = time() + 50;
			while (count($log)==0) {
				$now = time();
				usleep(10000);
				if ($now <= $finish) {
					$log = checkState($fileState,$userId);
				} else {
					break;	
				}  
			}
			break;
	}
	
echo json_encode($log);

function updateState($fileState,$userId,$userRecipient,$state) {
	if (file_exists($fileState)) {
		$fileContent = file($fileState);
		$obj = json_decode($fileContent[0]);
	}
	
	$identArr = array(sprintf("%05d",$userId),sprintf("%05d",$userRecipient));
	sort($identArr);
	$ident = implode($identArr,'_');
	$obj->$ident->$userId = $state;
	
	fwrite(fopen($fileState, 'w'), json_encode($obj) );
}

function checkState($fileState,$userId) {
       $arr = array();
   if (file_exists($fileState)) {
		$fileContent = file($fileState);
		$obj = json_decode($fileContent[0]);
	}
	foreach($obj as $k=>$v) {
	                    
		if(strpos($k,sprintf("%05d",$userId))!==false) {
			$filename = 'c_'.$k.'.txt';
		   $currentState = file_exists($filename) ? count(file($filename)) : 0;
			 //---check if differs
			 $ids = explode('_',$k); //get recipient id
			 $recipientId = ($ids[0]==$userId ? $ids[1] : $ids[0])*1;
   		 if($v->$userId < $currentState) {
			 	  //have some unread with this recipient so open chat
			 	  $arr[] = $recipientId; 
			 } 
		}
	}
	return $arr;
}