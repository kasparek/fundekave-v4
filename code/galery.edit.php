<?php
$deleteThumbs = false;
$galery = new fGalery();
if (isset($_POST['savegal'])) {
	$arr = $_POST['arr'];
	if($user->currentPageParam=='e') {
		$arr['pageId'] = $user->currentPageId;
	} else {
		$arr['userIdOwner'] = $user->gid;
		$arr['authorContent'] = $user->gidname;
	}
	$fPagesSave = new fPagesSaveTool('galery');
	
	if(($xperpage = $_POST['xperpage']*1) < 1) $xperpage = $galery->get('thumbNumWidth');

	if(($xwidthpx = $_POST['xwidthpx']*1) < 10) $xwidthpx = $galery->get('widthThumb');
	if(($xheightpx = $_POST['xheightpx']*1) < 10) $xheightpx = $galery->get('heightThumb');
	$xthumbstyle = $_POST['xthumbstyle']*1;
	$xml = new SimpleXMLElement($fPagesSave->getDef('pageParams'));
	$xml->enhancedsettings[0]->perpage = $xperpage;
	$xml->enhancedsettings[0]->widthpx = $xwidthpx;
	$xml->enhancedsettings[0]->heightpx = $xheightpx;
	$xml->enhancedsettings[0]->thumbnailstyle = $xthumbstyle;
	$xml->enhancedsettings[0]->orderitems = $_POST['galeryorder'] * 1;
	$xml->enhancedsettings[0]->fotoforum = $_POST['fotoforum'] * 1;
	$newParams = $xml->asXML();
	if($user->currentPage['pageParams'] != $newParams && $user->currentPageParam=='e') {
	    $deleteThumbs = true;
	}
	$arr['pageParams'] = $newParams;
	
	$arr['name'] = fSystem::textins($arr['name'],array('plainText'=>1));
    if(empty($arr['name'])) fError::addError(ERROR_GALERY_NAMEEMPTY);
	if(empty($arr['pageId'])) {
	   if(fPages::page_exist('name',$arr['name']))	fError::addError(ERROR_GALERY_NAMEEXISTS.': '.$arr['name']);
	} else {
	    if($arr['name']!=$user->currentPage['name'] && fPages::page_exist('name',$arr['name']))	fError::addError(ERROR_GALERY_NAMEEXISTS.': '.$arr['name']);
	}
	$arr['dateContent'] = fSystem::switchDate($arr['dateContent']);
	if(!fSystem::isDate($arr['dateContent'])) fError::addError(ERROR_DATA_FORMAT);
	$arr['categoryId'] *= 1;
	if(empty($arr['categoryId'])) unset($arr['categoryId']);
	$arr['galeryDir'] = Trim($arr['galeryDir']);
	if($arr['galeryDir']=='') fError::addError(ERROR_GALERY_DIREMPTY);
	elseif (!fSystem::checkDirname($arr['galeryDir'])) fError::addError(ERROR_GALERY_DIRWRONG);
	elseif($user->currentPageParam=='e' && $user->currentPage['galeryDir'] != $arr['galeryDir']) {
	    $deleteThumbs = true;
	}
	$arr['description'] = fSystem::textins($arr['description'],array('plainText'=>1));
	$arr['content'] = fSystem::textins($arr['content']);
	
	if(fError::isError()) {
	   $_SESSION['galerie_arr'] = $arr;
	} else { 
	    if($deleteThumbs) {
    	  $galery->getGaleryData($user->currentPageId);
    	  $cachePath = ROOT.ROOT_WEB.$galery->getThumbCachePath();
    		fSystem::rm_recursive($cachePath);
    		$systemCachePath = ROOT.ROOT_WEB.$galery->getThumbCachePath($galery->_cacheDirSystemResolution);
    		fSystem::rm_recursive($systemCachePath);
	    }
	
		$pageId = $fPagesSave->savePage($arr);
		$adr = $galery->get('rootImg').$arr['galeryDir'];
		if(!file_exists($adr)) {
			if(mkdir ($adr, 0777)) {
				mkdir ($adr."/nahled", 0777);
				chmod ( $adr, 0777 );
				chmod ( $adr.'/nahled', 0777 );
			}
		}
		//---rules update	
		$rules = new fRules($pageId);
		$rules->public = $_POST['public']*1;
		$rules->ruleText = $_POST['rule'];
		$rules->update();
		$fRelations = new fPagesRelations($pageId);
		$fRelations->update();
	}
	if(!empty($pageId)) {
       if(!empty($_FILES)) {
            if(!empty($user->currentPage['galeryDir'])) {
            	$adr = $galery->get('rootImg').$user->currentPage['galeryDir'];
            	
            	foreach ($_FILES as $foto) {
            		if ($foto["error"]==0) $up=fSystem::upload($foto,$adr,500000);
            	}
            } else fError::addError('Neni nastaven adresar galerie');
        }
		
    	if($user->currentPageParam=='e' && isset($_POST['fot'])) {
        	//---foto description, foto deleteing
        	if(isset($_POST['delfoto'])) foreach ($_POST['delfoto'] as $dfoto) $galery->removeFoto($dfoto);
        	if(isset($_POST['fot'])) {
        	    foreach ($_POST['fot'] as $k=>$v) {
        	        $changed = false;
        	        $newDesc = fSystem::textins($v['comm'],array('plainText'=>1));
        	        $galery->getFoto($k);
        	        $oldDesc = $galery->get('fComment');
        	        $oldDate = $galery->get('fDate');
        	        if($newDesc!=$oldDesc) {
        	            $galery->set('fComment',$newDesc);
        	            $changed = true;
        	        }
        	        $newDate = $v['date'];
        	        if(!empty($newDate)) {
        	           if(strpos($newDate,'.')===true) $newDate = fSystem::den($newDate);
        	           elseif(!fSystem::isDate($newDate)) $newDate = '';
        	           if(empty($newDate)) fError::addError(ERROR_DATE_FORMAT);
        	           else {
        	               $galery->set('fDate',$newDate);
        	               $changed=true;
        	           }
        	        }
        	        if($changed) $galery->updateFoto();
        	    }
        	}
    	 
    	} 
	}
	
	//fHTTP::redirect($user->getUri('',(!empty($pageId))?($pageId.'e'):('')));
	fHTTP::redirect($user->getUri());
	
}

if (isset($_POST['deletegal']) && $user->currentPageParam=='e'){
	$dir = $user->currentPage['galeryDir'];
	$arrd = $db->getCol("SELECT itemId FROM sys_pages_items WHERE pageId='".$user->currentPageId."'");
	foreach ($arrd as $df) $galery->removeFoto($df);
	if(!empty($dir)) {
	  fSystem::rm_recursive($galery->_rootImg.$dir);
	  $galery->getGaleryData($user->currentPageId);
		$cachePath = $galery->getThumbCachePath();
		fSystem::rm_recursive($cachePath);
		$systemCachePath = $galery->getThumbCachePath($galery->_cacheDirSystemResolution);
		fSystem::rm_recursive($systemCachePath);
	}
	fPages::deletePage($user->currentPageId);
	fHTTP::redirect($user->getUri('','galer'));
}

//---SHOWTIME
if($user->currentPageParam=='e') $arr = $user->currentPage;
if(!empty($_SESSION['galerie_arr'])) {
    $arr = $_SESSION['galerie_arr'];
    unset($_SESSION['galerie_arr']);
}

if(!empty($arr)) {
	$xml = new SimpleXMLElement($arr['pageParams']);
    $enhancedSettings = $xml->enhancedsettings[0];
    $arr['xperpage'] = $enhancedSettings->perpage;
    $arr['xwidthpx'] = $enhancedSettings->widthpx;
    $arr['xheightpx'] = $enhancedSettings->heightpx;
    $arr['xthumbstyle'] = $enhancedSettings->thumbnailstyle;
    $arr['fotoforum'] = $enhancedSettings->fotoforum;

    if($user->currentPageParam=='e' && $arr['galeryDir'] != '') {
	    $galery->refreshImgToDb($user->currentPageId);
	}
} else {
    //---set defaults
	$arr=array("categoryId"=>'1',
	"dateContent"=>Date("Y-m-d"),"name"=>'',"description"=>'',"content"=>'',
	'userIdOwner'=>$user->gid,'galeryDir'=>'',
	'xperpage'=>$galery->get('perpage'),
	'xwidthpx'=>$galery->get('widthThumb'),
	'xheightpx'=>$galery->get('heightThumb'),
	'xthumbstyle'=>'0','fotoforum'=>1);
}

$tpl = new fTemplateIT('galery.edit.tpl.html');

$tpl->setVariable('FORMACTION',$user->getUri());

$rules = new fRules(($user->currentPageParam=='e')?($user->currentPageId):(''));

$tpl->setVariable('GPAGEID',($user->currentPageParam=='e')?($user->currentPageId):('0'));
$tpl->setVariable('PERMISSSIONSFORM',$rules->printEditForm());
$fRelations = new fPagesRelations($user->currentPageId);
$tpl->setVariable('RELATIONSFORM',$fRelations->getForm());

$tpl->setVariable('OWNERLINK','?k=33&who='.$arr['userIdOwner']);
$tpl->setVariable('OWNERNAME',$user->getgidname($arr['userIdOwner']));
$options='';
$arrkat = $db->getAll('SELECT categoryId,name FROM sys_pages_category where typeId="galery" order by ord');
foreach ($arrkat as $kat)
	$options.='<option value="'.$kat[0].'"'.(($kat[0]==$arr['categoryId'])?(' selected="selected"'):('')).'>'.$kat[1].'</option>';
$tpl->setVariable('CATEGORYOPTIONS',$options);
$tpl->setVariable('GNAME',$arr['name']);

$tpl->setVariable('GDESCID',$user->currentPageId.'desc');
$tpl->setVariable('GCONTID',$user->currentPageId.'cont');
$tpl->setVariable('GDESC',fSystem::textToTextarea($arr['description']));
$tpl->setVariable('GCONT',fSystem::textToTextarea($arr['content']));
$tpl->addTextareaToolbox('GCONTTOOLBOX',$user->currentPageId.'cont');

$datePart = explode(' ',$arr['dateContent']);
$tpl->setVariable('GDATE',$datePart[0]);
$tpl->setVariable('GDIR',$arr['galeryDir']);
///---xmlparams
$tpl->setVariable('PERPAGE',$arr['xperpage']);
$tpl->setVariable('GTHUMBWIDTH',$arr['xwidthpx']);
$tpl->setVariable('GTHUMBHEIGHT',$arr['xheightpx']);
$options='';
$arrThumbstyleOptions = array('1'=>'Cele','2'=>'Orez na dany format');
foreach ($arrThumbstyleOptions as $k=>$v)
	$options.='<option value="'.$k.'"'.(($k==$arr['xthumbstyle'])?(' selected="selected"'):('')).'>'.$v.'</option>';
$tpl->setVariable('THUMSTYLEOPTIONS',$options);

if($user->currentPageParam=='e') {
    //$tpl->setVariable('GINTERNALLINK',htmlspecialchars('<a href="'.BASESCRIPTNAME.'?k='.$user->currentPageId.'">'.$arr['name'].'</a>'));
    //$tpl->setVariable('GSTANDALONELINK',htmlspecialchars('<a href="'.BASESCRIPTNAME.'?k='.$user->currentPageId.'">'.$arr['name'].'</a>'));
    //$tpl->setVariable('GEXTURL',htmlspecialchars('<a href="http://fundekave.net/gdk/?id='.$user->currentPageId.'">'.$arr['name'].'</a>'));
    $tpl->touchBlock('gdelete');
  
  $galery->getGaleryData($user->currentPageId);
  $galery->getFoto($user->currentPageId,true,(($galery->gOrderItems==1)?('i.dateCreated desc'):('i.enclosure')));
  
	$tpl->setVariable('FOTOTOTAL',count($galery->arrData));
	
	if($galery->gOrderItems) $tpl->touchBlock('gorddate');
	$tpl->touchBlock('fforum'.$arr['fotoforum']);
	
	if(!empty($galery->arrData)) {
    	foreach ($galery->arrData as $foto){
    	    list($date,$time) = explode('T',$foto['dateIso']);
    	    if($date=='0000-00-00') $date='';
    	    $exif = exif_read_data(ROOT.ROOT_WEB.$foto['detailUrl']);
    	    if($exif!==false) {
    	       
        	    if(empty($date)) {
                    $date = date("Y-m-d",$exif['FileDateTime']);
                    if(isset($exif['DateTimeOriginal'])) {
                        $da = new DateTime($exif['DateTimeOriginal']);
                        $date = $da->format("Y-m-d");
                    }
                    
                }
               
    	    }
            
            $tpl->setCurrentBlock('gfoto');
    	    $tpl->setVariable('FID',$foto['itemId']);
    	    $tpl->setVariable('FNAME',$foto['enclosure']);
    	    $tpl->setVariable('FTHUMBURL',$foto['thumbUrl']);
    	    $tpl->setVariable('FCOMMENT',$foto['text']);
    	    
    	    if($date!='0000-00-00') {
    	       $tpl->setVariable('FDATE',$date);
    	    }
    	    
    	    
    	    $tpl->parseCurrentBlock();
    	}
    	
	}
/**/
    $numInputs=7;
    for ($x=1;$x<$numInputs;$x++) {
    	$tpl->setCurrentBlock('uploadinput');
    	$tpl->setVariable('UPLOADINPUTLABEL','Foto '.$x.'.');
    	$tpl->setVariable('UPLOADINPUTID',$x);
    	$tpl->parseCurrentBlock();
    }
}
/**/

$TOPTPL->addRaw($tpl->get());