<?php
include_once('iPage.php');
class page_EventsEdit implements iPage {

	static function process() {
		$user = FUser::getInstance();
		$fItems = new FItems();

		$itemId = &$user->itemVO->itemId;

		if(isset($_POST['del']) && $itemId > 0) {
			$fItems->deleteItem( $itemId );
			$cache = FCache::getInstance('f');
			$cache->invalidateDate('eventtip');
			$cache->invalidateDate('calendarlefthand');
			FError::addError(FLang::$LABEL_DELETED_OK);
			FHTTP::redirect(FUser::getUri());
		}

		if(isset($_POST["nav"])) {
			$arrSave = array();
			//---check flyer to upload
			if(isset($_POST['delfly']) && !empty($itemId)){
				$fItems->setSelect('enclosure');
				$arr = $fItems->getItem($itemId);
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
			if(FSystem::isTime($timeStartTmp)) $timeStart = ' '.$timeStartTmp;
			$timeEndTmp = trim($_POST['timeend']);
			if(FSystem::isTime($timeEndTmp)) $timeEnd = ' '.$timeEndTmp;
			//---check time
			$dateStart = FSystem::textins($_POST['datestart'],array('plainText'=>1));
			$dateStart = FSystem::switchDate($dateStart);
			if(FSystem::isDate($dateStart)) $dateStart .= $timeStart;
			else FError::addError(FLang::$ERROR_DATE_FORMAT);

			//---save array
			$arrSave = array('location'=>FSystem::textins($_POST['place'],array('plainText'=>1))
			,'addon'=>FSystem::textins($_POST['name'],array('plainText'=>1))
			,'dateStart'=>$dateStart
			,'text'=>FSystem::textins($_POST['description'])
			);

			$dateEnd = FSystem::textins($_POST['dateend'],array('plainText'=>1));
			$dateEnd = FSystem::switchDate($dateEnd);
			if(FSystem::isDate($dateEnd)) $arrSave['dateEnd'] = $dateEnd.$timeEnd;

			//print_r($arrSave);
			//die();
			if($_POST['category']*1>0) $arrSave['categoryId'] = $_POST['category']*1;

			if($arrSave['addon']=="") FError::addError(FLang::$ERROR_NAME_EMPTY);

			if(!empty($itemId)) {
				$arrSave['itemId'] = $itemId;
			} else {
				$arrSave['userId'] = $user->userVO->userId;
				$arrSave['name'] = $user->userVO->name;
				$arrSave['typeId'] = $user->pageVO->typeIdChild;
				$arrSave['dateCreated'] = 'NOW()';
				$arrSave['pageId'] = $user->pageVO->pageId;
			}

			if(!FError::isError()) {

				$itemId = $fItems->saveItem($arrSave);
				if(!empty($_POST['akceletakurl'])) {
					$filename = "flyer".$itemId.'.jpg';
					if($file = file_get_contents($_POST['akceletakurl'])) {
						file_put_contents($conf['events']['flyer_source'].$filename,$file);
					}
					$cachedThumb = FEvents::thumbUrl($filename);
					if(file_exists($cachedThumb)) { @unlink($cachedThumb); }

					$fImg = new FImgProcess($conf['events']['flyer_source'] . $filename
					,$cachedThumb, array('quality'=>$conf['events']['thumb_quality']
					,'width'=>$conf['events']['thumb_width'],'height'=>0));

					$fItems->saveItem(array("itemId"=>$itemId,'enclosure'=>$filename));
					$user->cacheRemove('eventtip','calendarlefthand');
				}
				elseif($_FILES['akceletak']['error'] == 0) {
					$flypath = $conf['events']['flyer_source'];
					$arr = explode('.',$_FILES['akceletak']['name']);
					$_FILES['akceletak']['name'] = "flyer".$itemId.'.'.strtolower($arr[count($arr)-1]);
					if(FSystem::upload($_FILES['akceletak'],$flypath,800000)) {
						$cachedThumb = FEvents::thumbUrl($_FILES['akceletak']['name']);
						if(file_exists($cachedThumb)) { @unlink($cachedThumb); }
						//---create thumb
						$fImg = new FImgProcess($conf['events']['flyer_source'] . $_FILES['akceletak']['name']
						,$cachedThumb, array('quality'=>$conf['events']['thumb_quality']
						,'width'=>$conf['events']['thumb_width'],'height'=>0));

						$fItems->saveItem(array("itemId"=>$itemId,'enclosure'=>$_FILES['akceletak']['name']));
						$user->cacheRemove('eventtip','calendarlefthand');
						FError::addError(FLang::$MESSAGE_SUCCESS_SAVED);
					}
				}
			} else {
				$cache = FCache::getInstance('s');
				$cache->setData($arrSave,$user->pageVO->pageId,'form');
			}

			FHTTP::redirect(FUser::getUri());
		}


	}

	static function build() {

		$cache = FCache::getInstance('s');
		
		$user = FUser::getInstance();
		$itemId = &$user->itemVO->itemId;

		if($itemId > 0) {
			$fItems->setSelect("itemId,categoryId,location,addon
    ,date_format(dateStart,'{#date_local#}'),date_format(dateStart,'{#time_short#}')
    ,date_format(dateEnd,'{#date_local#}'),date_format(dateEnd,'{#time_short#}')
    ,text,enclosure");
			$arrTmp = $fItems->getItem($itemId);

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
		} elseif(false !== ($arrSave = $cache->getData($user->pageVO->pageId,'form'))) {
			$arr = $arrSave;
			$cache->invalidateData('eventForm');
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

		$tpl = new FTemplateIT('events.edit.tpl.html');
		$tpl->setVariable('FORMACTION',$user->getUri());
		$tpl->setVariable('HEADING',(($arr['itemId']>0)?($arr['name']):(LABEL_EVENT_NEW)));
		$tpl->setVariable('ITEMID',$arr['itemId']);

		$q = 'select categoryId,name from sys_pages_category where typeId="event" order by ord,name';
		$arrOpt = FDBTool::getAll($q,'event','categ','s');
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
		$tpl->setVariable('DESCRIPTION',FSystem::textToTextarea($arr['description']));
		$tpl->addTextareaToolbox('DESCRIPTIONTOOLBOX','event');
		if($itemId > 0)
		$tpl->touchBlock('delakce');

		if(!empty($arr['flyer'])) {
			$tpl->setVariable('FLYERURL',FEvents::flyerUrl($arr['flyer']));
			$tpl->setVariable('FLYERTHUMBURL',FEvents::thumbUrl($arr['flyer']));
		}
		FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));
	}
}