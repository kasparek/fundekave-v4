<?php
$fItems = new fItems();
if(isset($_POST['del']) && $user->currentItemId>0) {
	$fItems->deleteItem($user->currentItemId);
	$user->cacheRemove('eventtip','calendarlefthand');
	fError::addError(LABEL_DELETED_OK);
	fHTTP::redirect($user->getUri());
}
if(isset($_POST["nav"])){
    $arrSave = array();
    //---check flyer to upload
	if(isset($_POST['delfly']) && !empty($user->currentItemId)){
	    $fItems->setSelect('enclosure');
	    $arr = $fItems->getItem($user->currentItemId);
		$file = $arr[0];
		if($file!='') {
		    if(file_exists($conf['events']['flyer_source'].$file)) unlink($conf['events']['flyer_source'].$file);
		    if(file_exists($conf['events']['flyer_cache'].$file)) unlink($conf['events']['flyer_cache'].$file);
		}
		$arrSave['enclosure'] = '';
	}
	//---check time
	$timeStart = '';
	$timeEnd = '';
	$timeStartTmp = trim($_POST['timestart']);
	if(fSystem::isTime($timeStartTmp)) $timeStart = ' '.$timeStartTmp;
	$timeEndTmp = trim($_POST['timeend']);
	if(fSystem::isTime($timeEndTmp)) $timeEnd = ' '.$timeEndTmp;
	//---check time
	$dateStart = fSystem::textins($_POST['datestart'],array('plainText'=>1));
	$dateStart = fSystem::switchDate($dateStart);
	if(fSystem::isDate($dateStart)) $dateStart .= $timeStart;
	else fError::addError(ERROR_DATE_FORMAT);
	
	//---save array
	$arrSave = array('location'=>fSystem::textins($_POST['place'],array('plainText'=>1))
	,'addon'=>fSystem::textins($_POST['name'],array('plainText'=>1))
	,'dateStart'=>$dateStart
	,'text'=>fSystem::textins($_POST['description'])
	);
	
	$dateEnd = fSystem::textins($_POST['dateend'],array('plainText'=>1));
	$dateEnd = fSystem::switchDate($dateEnd);
	if(fSystem::isDate($dateEnd)) $arrSave['dateEnd'] = $dateEnd.$timeEnd;
	
	//print_r($arrSave);
	//die();
	if($_POST['category']*1>0) $arrSave['categoryId'] = $_POST['category']*1;
	
	if($arrSave['addon']=="") fError::addError(ERROR_NAME_EMPTY);
	
	if(!empty($user->currentItemId)) {
		$arrSave['itemId'] = $user->currentItemId;
	} else {
		$arrSave['userId'] = $user->gid;
		$arrSave['name'] = $user->gidname;
		$arrSave['typeId'] = $user->currentPage['typeIdChild'];
		$arrSave['dateCreated'] = 'NOW()';
		$arrSave['pageId'] = $user->currentPage['typeIdChild'];
	}
	
	if(!fError::isError()) {
		
		$user->currentItemId = $fItems->saveItem($arrSave);
		if(!empty($_POST['akceletakurl'])) {
		    $filename = "flyer".$user->currentItemId.'.jpg';
		    if($file = file_get_contents($_POST['akceletakurl'])) {
	            file_put_contents($conf['events']['flyer_source'].$filename,$file);
	        }
	        $cachedThumb = fEvents::thumbUrl($filename);
    			if(file_exists($cachedThumb)) { @unlink($cachedThumb); }
    			
    			$fImg = new fImgProcess($conf['events']['flyer_source'] . $filename
              ,$cachedThumb, array('quality'=>$conf['events']['thumb_quality']
                ,'width'=>$conf['events']['thumb_width'],'height'=>0));
                
	        $fItems->saveItem(array("itemId"=>$user->currentItemId,'enclosure'=>$filename));
    			$user->cacheRemove('eventtip','calendarlefthand');
		}
		elseif($_FILES['akceletak']['error'] == 0) {
		    
			$flypath = $conf['events']['flyer_source'];
			
			$arr = explode('.',$_FILES['akceletak']['name']);
			$_FILES['akceletak']['name'] = "flyer".$user->currentItemId.'.'.strtolower($arr[count($arr)-1]);
			
			if(fSystem::upload($_FILES['akceletak'],$flypath,800000)) {
			   $cachedThumb = fEvents::thumbUrl($_FILES['akceletak']['name']);
    			if(file_exists($cachedThumb)) { @unlink($cachedThumb); }
    			//---create thumb
          
          $fImg = new fImgProcess($conf['events']['flyer_source'] . $_FILES['akceletak']['name']
              ,$cachedThumb, array('quality'=>$conf['events']['thumb_quality']
                ,'width'=>$conf['events']['thumb_width'],'height'=>0));
    			
          $fItems->saveItem(array("itemId"=>$user->currentItemId,'enclosure'=>$_FILES['akceletak']['name']));
    			$user->cacheRemove('eventtip','calendarlefthand');
    			fError::addError(MESSAGE_SUCCESS_SAVED);
			}
		}
	} else {
		$_SESSION['akce_arr'] = $arr;
	}

	fHTTP::redirect($user->getUri());
}
//---SHOWTIME
if($user->currentItemId > 0) {
    $fItems->setSelect("itemId,categoryId,location,addon
    ,date_format(dateStart,'{#date_local#}'),date_format(dateStart,'{#time_short#}')
    ,date_format(dateEnd,'{#date_local#}'),date_format(dateEnd,'{#time_short#}')
    ,text,enclosure");
    $arrTmp = $fItems->getItem($user->currentItemId);
    
	$arr = array('itemId'=>$arrTmp[0]
	,'categoryId'=>$arrTmp[1]
	,'location'=>$arrTmp[2]
	,'name'=>$arrTmp[3]
	,'dateStart'=>$arrTmp[4]
	,'timeStart'=>$arrTmp[5]
	,'dateEnd'=>$arrTmp[6]
	,'timeEnd'=>$arrTmp[7]
	,'description'=>$arrTmp[8]
	,'flyer'=>$arrTmp[9]);
	if($arr['timeStart']=='00:00')  $arr['timeStart']='';
	if($arr['timeEnd']=='00:00')  $arr['timeEnd']='';
	if(empty($arr['dateEnd']) || $arr['dateEnd']=='0000-00-00')  $arr['dateEnd']='';
} elseif(isset($_SESSION['akce_arr'])) {
	$arr=$_SESSION['akce_arr'];
	unset($_SESSION['akce_arr']);
} else {
    $arr = array('itemId'=>0
	,'categoryId'=>0
	,'location'=>''
	,'name'=>''
	,'dateStart'=>Date("d.m.Y")
	,'timeStart'=>''
	,'dateEnd'=>''
	,'timeEnd'=>''
	,'description'=>''
	,'flyer'=>'');
}

$tpl = new fTemplateIT('events.edit.tpl.html');
$tpl->setVariable('FORMACTION',$user->getUri());
$tpl->setVariable('HEADING',(($arr['itemId']>0)?($arr['name']):(LABEL_EVENT_NEW)));
$tpl->setVariable('ITEMID',$arr['itemId']);

$arrOpt = $db->getAll('select categoryId,name from sys_pages_category where typeId="event" order by ord,name');
$options = '';
if(!empty($arrOpt)) foreach ($arrOpt as $row) {
	$options .= '<option value="'.$row[0].'"'.(($row[0]==$arr['categoryId'])?(' selected="selected"'):('')).'>'.$row[1].'</option>';
}
$tpl->setVariable('CATOPTIONS',$options);

$tpl->setVariable('PLACE',$arr['location']);
$tpl->setVariable('NAME',$arr['name']);
$tpl->setVariable('DATESTART',$arr['dateStart']);
$tpl->setVariable('TIMESTART',$arr['timeStart']);
$tpl->setVariable('DATEEND',$arr['dateEnd']);
$tpl->setVariable('TIMEEND',$arr['timeEnd']);
$tpl->setVariable('DESCRIPTION',fSystem::textToTextarea($arr['description']));
$tpl->addTextareaToolbox('DESCRIPTIONTOOLBOX','event');
if($user->currentItemId > 0)
    $tpl->touchBlock('delakce');

if(!empty($arr['flyer'])) {
    $tpl->setVariable('FLYERURL',fEvents::flyerUrl($arr['flyer']));
    $tpl->setVariable('FLYERTHUMBURL',fEvents::thumbUrl($arr['flyer']));
}
$TOPTPL->addTab(array("MAINDATA"=>$tpl->get()));