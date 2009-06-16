<?php
include_once('iPage.php');
class page_EventsEdit implements iPage {

	static function process($data) {
		$user = FUser::getInstance();
		
		if($user->itemVO->itemId > 0) {
			$itemVO = $user->itemVO; 
		} else {
			$itemVO = new ItemVO();
			$this->typeId = 'event';
			$itemVO->userId = $user->userVO->userId;
			$itemVO->name = $user->userVO->name;
			$itemVO->dateCreated = 'NOW()';
			$itemVO->pageId = $user->pageVO->pageId;
		}

		if(isset($_POST['del']) && $itemId > 0) {
			$itemVO->delete();
			$cache = FCache::getInstance('f');
			$cache->invalidateDate('eventtip');
			$cache->invalidateDate('calendarlefthand');
			FError::addError(FLang::$LABEL_DELETED_OK);
			FHTTP::redirect(FUser::getUri());
		}

		if(isset($data["nav"])) {
			//---check flyer to upload
			if(isset($data['delfly']) && !empty($itemId)){
				if($itemVO->enclosure!='') {
					if(file_exists(FConf::get('events','flyer_source').$itemVO->enclosure)) unlink(FConf::get('events','flyer_source').$itemVO->enclosure);
					if(file_exists(FConf::get('events','flyer_cache').$itemVO->enclosure)) unlink(FConf::get('events','flyer_cache').$itemVO->enclosure);
				}
				$itemVO->enclosure = '';
			}
			//---check time
			$timeStart = '';
			$timeEnd = '';
			$timeStartTmp = trim($data['timestart']);
			if(FSystem::isTime($timeStartTmp)) $timeStart = ' '.$timeStartTmp;
			$timeEndTmp = trim($data['timeend']);
			if(FSystem::isTime($timeEndTmp)) $timeEnd = ' '.$timeEndTmp;
			//---check time
			$dateStart = FSystem::textins($data['datestart'],array('plainText'=>1));
			$dateStart = FSystem::switchDate($dateStart);
			if(FSystem::isDate($dateStart)) $dateStart .= $timeStart;
			else FError::addError(FLang::$ERROR_DATE_FORMAT);

			//---save array
			$itemVO->location = FSystem::textins($data['place'],array('plainText'=>1));
			$itemVO->addon = FSystem::textins($data['name'],array('plainText'=>1));
			$itemVO->dateStart = $dateStart;
			$itemVO->text = FSystem::textins($data['description']);

			$dateEnd = FSystem::textins($data['dateend'],array('plainText'=>1));
			$dateEnd = FSystem::switchDate($dateEnd);
			if(FSystem::isDate($dateEnd)) $itemVO->dateEnd = $dateEnd.$timeEnd;

			if($data['category'] > 0) $itemVO->categoryId = (int) $data['category'];

			if(empty($itemVO->addon)) FError::addError(FLang::$ERROR_NAME_EMPTY);

			if(!FError::isError()) {

				$itemId = $itemVO->save();
				if(!empty($data['akceletakurl'])) {
					$filename = "flyer".$itemId.'.jpg';
					if($file = file_get_contents($data['akceletakurl'])) {
						file_put_contents(FConf::get('events','flyer_source').$filename,$file);
					}
					$cachedThumb = FEvents::thumbUrl($filename);
					if(file_exists($cachedThumb)) { @unlink($cachedThumb); }

					$fImg = new FImgProcess(FConf::get('events','flyer_source') . $filename
					,$cachedThumb, array('quality'=>FConf::get('events','thumb_quality')
					,'width'=>FConf::get('events','thumb_width'),'height'=>0));

					$itemVO->enclosure = $filename;
					$itemVO->save();
					$user->cacheRemove('eventtip','calendarlefthand');
				} elseif(isset($data['__files'])) {
					if($data['__files']['akceletak']['error'] == 0) {
						$flypath = FConf::get('events','flyer_source');
						$arr = explode('.',$data['__files']['akceletak']['name']);
						$data['__files']['akceletak']['name'] = "flyer".$itemId.'.'.strtolower($arr[count($arr)-1]);
						if(FSystem::upload($data['__files']['akceletak'],$flypath,800000)) {
							$cachedThumb = FEvents::thumbUrl($data['__files']['akceletak']['name']);
							if(file_exists($cachedThumb)) { @unlink($cachedThumb); }
							//---create thumb
							$fImg = new FImgProcess(FConf::get('events','flyer_source') . $data['__files']['akceletak']['name']
							,$cachedThumb, array('quality'=>FConf::get('events','thumb_quality')
							,'width'=>FConf::get('events','thumb_width'),'height'=>0));
							$itemVO->enclosure = $data['__files']['akceletak']['name'];
							$itemVO->save();
							$user->cacheRemove('eventtip','calendarlefthand');
							FError::addError(FLang::$MESSAGE_SUCCESS_SAVED);
						}
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