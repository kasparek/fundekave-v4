<?php
class FItemsForm {

	static function moveImage($data,$itemVO=null) {
		if($itemVO==null) {
			if(empty($data['item'])) return;
			$itemVO = new $itemVO($data['item']);
			if(!$itemVO->load()) return;
		}
		$filename = FFile::getTemplFilename();
		if($filename!==false) {
			//delete old image
			$itemVO->deleteImage();
			$pageVO = $itemVO->pageVO;
			$filenameArr = explode('/',$filename);
			$enclosure = array_pop($filenameArr);
			$target = FConf::get('galery','sourceServerBase') . $pageVO->galeryDir.'/'.$enclosure;
			$ffile = new FFile(FConf::get("galery","ftpServer"));
			$ffile->rename(FConf::get('galery','sourceServerBase').$filename,$target);
			$itemVO->enclosure = $enclosure;
			$itemVO->save();
			FFile::flushTemplFile();
			return $itemVO;
		}
	}

	static function process($data) {
	
    $itemVO = new ItemVO();
		if(false===$itemVO->set('typeId',$data['t'])) {
			FError::add(FLang::$ERROR_FORM_TYPE);
			FError::write_log('FItemsForm::process - unset type - item:'.$data['item']);
			return;
		}
		
		$redirectParam = '';
		$newItem=false;
		if(empty($itemVO->itemId)) $newItem=true;
		$redirect = false;
		$user = FUser::getInstance();
		$captchaCheck = true;
		if($user->idkontrol !== true) {
			$captcha = new FCaptcha();
			if(!$captcha->validate_submit($data['captchaimage'],$data['pcaptcha'])) $captchaCheck = false;
		}
		$itemVO->itemIdTop=null;
		if($user->itemVO) {
			$itemVO->itemIdTop = $user->itemVO->itemId;
		}
		//no reactions to forum items
		if($itemVO->itemIdTop > 0) {
			$itemVOTop = new ItemVO($data['itemIdTop'],true);
			if($itemVOTop->typeId=='forum') $itemVO->itemIdTop=null;
			else $itemVO->pageIdTop = $itemVOTop->pageVO->pageIdTop; 
		}

		if($captchaCheck===false) {
			FError::add(FLang::$ERROR_CAPTCHA);
		}

		//check permissions
		if(FRules::getCurrent(2) === true
		|| ($user->pageVO->typeId=='forum' && FRules::getCurrent(1) === true)
		|| ($user->pageVO->typeId!='forum' && $itemVO->typeId=='forum'
		&& ($user->pageVO->prop('forumSet')==1 || ($user->idkontrol && $user->pageVO->prop('forumSet')==2)))) {
			//access granted
		} else {
			FError::add(FLang::$ERROR_RULES_CREATE);
		}

		if(!empty($data['item'])) $itemVO->itemId = (int) $data['item'];
		if($itemVO->itemId>0) if($itemVO->load()) $newItem=false;

		$itemVO->pageId = $user->pageVO->pageId;

		if(empty($data['action'])) {
			if(isset($data['filtr'])) $data['action']='search';
		}

		if (isset($data["perpage"])) $user->pageVO->perPage( $data["perpage"] );

		switch($data['action']) {
			case 'search':
				$cache = FCache::getInstance('s',0);
				$cache->setData(FSystem::textins($data["text"],array('plainText'=>1)), $user->pageVO->pageId, 'filter');
				break;
			case 'deleteImage':
				//TODO: currently it is done via item_delete
				$itemVO = new ItemVO((int) $data['item']);
				if($itemVO->load()) {
					$itemVO->deleteImage();
					$itemVO->save();
				}
				//TODO: refactor - this is remove item for forum only
				//FAjax::addResponse('function','call','remove;i'.$data['item']);
				break;
			case 'delete':
				$itemVO = new ItemVO((int) $data['item']);
				if($itemVO->load()) {
					$itemVO->delete();
				}
				$itemVO=null;
				$user->itemVO=null;
				FError::add(FLang::$LABEL_DELETED_OK,1);
				$redirect = true;
				break;
			case 'save':
			default:
				/**
				 *process data
				 **/
				if(!FError::is()) {
					if(isset($data['addon'])) $data['addon'] = FSystem::textins($data['addon'],array('plainText'=>1)); //title for blog,event
					if(empty($data['addon']) && $itemVO->typeId!='forum') FError::add(FLang::$ERROR_NAME_EMPTY);
					if(isset($data['name'])) $data['name'] =  FSystem::textins($data['name'],array('plainText'=>1));
					if(empty($data['name'])) $data['name'] = $user->userVO->name;
					$data['text'] = FSystem::textins($data['text'],$user->idkontrol ? array() : array('plainText'=>1));
					$data['textLong'] = FSystem::textins($data['textLong']);
					if(empty($data['text']) && $itemVO->typeId=='forum') FError::add(FLang::$MESSAGE_EMPTY);
					if(empty($data['name'])) FError::add(FLang::$MESSAGE_NAME_EMPTY);
					elseif($user->idkontrol==false) {
						if (FUser::isUsernameRegistered($data['name'])) FError::add(FLang::$MESSAGE_NAME_USED);
					}
					if(!empty($data['categoryNew'])) {
						$data['category'] = FCategory::tryGet( $data['categoryNew'], $itemVO->pageId, $itemVO->pageVO->pageIdTop);
					}
					if(isset($data['dateStartLocal'])) $data['dateStart'] = FSystem::checkDate($data['dateStartLocal'].(isset($data['dateStartTime'])?' '.$data['dateStartTime']:''));
					if(isset($data['dateEndLocal'])) $data['dateEnd'] = FSystem::checkDate($data['dateEndLocal'].(isset($data['dateEndTime'])?' '.$data['dateEndTime']:''));
					if(empty($data['dateStart']) && $itemVO->typeId!='forum') FError::add(FLang::$ERROR_DATE_FORMAT);
					if(isset($data['location'])) $data['location'] = FSystem::textins($data['location'],array('plainText'=>1));
				}
				/**
				 *save item
				 */
				if(!FError::is()) {
					$itemVO->userId = (int) $user->userVO->userId;
					$itemVO->name = $data['name'];
					if(!empty($data['addon'])) $itemVO->set('addon', $data['addon']);
					if(!empty($data['text'])) $itemVO->set('text', $data['text']);
					if(!empty($data['textLong'])) $itemVO->set('textLong', $data['textLong']);
					if(!empty($data['location'])) $itemVO->set('location', $data['location']);
					if(empty($itemVO->typeId)) $itemVO->typeId = $user->pageVO->typeId;
					if(!empty($data['category'])) $itemVO->set('categoryId', (int) $data['category']);
					if(!empty($data['dateStart'])) $itemVO->set('dateStart', $data['dateStart']);
					if(!empty($data['dateEnd'])) $itemVO->set('dateEnd', $data['dateEnd']);
					if(isset($data['public'])) $itemVO->set('public', (int) $data['public']);
					//save items
					if($itemVO->save()>0){
						if(!empty($data['imageUrl'])) {
							$itemVO->deleteImage();
							$filename = FSystem::safeFilename($data['imageUrl']);
							if($file = file_get_contents($data['imageUrl'])) {
								$itemVO->deleteImage();
								$filename = FSystem::safeFilename( $data['imageUrl'] );
								$ffile = new FFile(FConf::get("galery","ftpServer"));
								$ffile->file_put_contents(FConf::get("galery","sourceServerBase").$itemVO->pageVO->galeryDir.'/'.$filename,$file);
								$itemVO->enclosure = $filename;
								$itemVO->save();
							}
						} elseif(isset($data['__files'])) {
							if($data['__files']['imageFile']['error'] == 0) {
								$data['__files']['imageFile']['name'] = FSystem::safeFilename($data['__files']['imageFile']['name']);
								$itemVO->deleteImage();
								$ffile = new FFile(FConf::get("galery","ftpServer"));
								if($ffile->upload($data['__files']['imageFile'],FConf::get("galery","sourceServerBase").$itemVO->pageVO->galeryDir,800000)) {
									$itemVO->enclosure = $data['__files']['imageFile']['name'];
									$itemVO->save();
								}
							}
						}
						//properties
						if(isset($data['position'])) {
							$posData = FSystem::positionProcess($data['position']);
							$itemVO->setProperty('position', $posData);
							if(strpos($posData,';')!==false) {
								$distance = FSystem::journeyLength($posData);
								$itemVO->setProperty('distance', $distance);
							}
						}
						if(isset($data['forumset'])) $itemVO->setProperty('forumSet',(int) $data['forumset']);
						if(isset($data['reminder'])) $itemVO->prop('reminder',$data['reminder']*1);
						if(isset($data['reminderEveryday'])) $itemVO->prop('reminderEveryday',$data['reminderEveryday']*1);
						if(isset($data['repeat'])) $itemVO->prop('repeat',$data['repeat']*1);

						FItemsForm::moveImage($data,$itemVO);

						//clean up stored data
						$cache = FCache::getInstance('s',0);
						$cache->invalidateData($itemVO->pageId.$itemVO->typeId,'form');
						//---on success
						if($data['__ajaxResponse']!=true) {
							$redirectParam = '#dd';
							$redirect=true;
						}
					}
				}
		}
		//if any error safe data to display in form
		if(FError::is()) {
			$cache = FCache::getInstance('s',0);
			$cache->setData($data, $itemVO->pageId.$itemVO->typeId, 'form');
		}

		//redirect
		if($itemVO->typeId!='forum') {
			$redirectParam = 'i='.$itemVO->itemId.$redirectParam;
			FError::add(FLang::$MESSAGE_SUCCESS_SAVED,1);
		}
		if($redirect==true) {
			//TODO: test commands
			//	$cache = FCache::getInstance('f');
			//$cache->invalidateGroup('eventtip');
			//$cache->invalidateGroup('calendarlefthand');
			//	$cache->invalidateGroup('lastBlogPost');
			//$commandList[] = itemAdded;
			//$cache->invalidateData('lastForumPost');
			//if($command) {
			//galery - lastForumPost
			//blog - lastForumPost,lastBlogPost
			//$commandList[] = $command;
			//}
			//FCommand::run($commandList);
			
			if($data['__ajaxResponse']==true) {
				FAjax::redirect(FSystem::getUri($redirectParam,$pageId,'u')); //new item
				if($itemVO==null) FAjax::redirect(FSystem::getUri('',$user->pageVO->pageId,'')); //deleted item
			} else {
				FHTTP::redirect(FSystem::getUri($redirectParam)); //non ajax processing
			}
		}
	}


	static function show($itemVO,$data=null) {
		$user = FUser::getInstance();
		if(!isset($data['simple'])) $data['simple']=false;
		$cache = FCache::getInstance('s',0);
		$tempData = $cache->getData( $itemVO->pageId.$itemVO->typeId, 'form');

		//set defaults
		if(empty($itemVO->itemId)) {
			$itemVO->categoryId = 0;
			$itemVO->public = 1;
			if($itemVO->typeId!='forum') {
				$itemVO->dateStart = Date("Y-m-d");
			}
		}

		if($tempData !== false) {
			foreach($tempData as $k=>$v) {
				$data[$k] = $v;
			}
			$cache->invalidateData( $itemVO->pageId.$itemVO->typeId, 'form');
		}

		if(!empty($data))
		foreach($data as $k=>$v) {
			$itemVO->set($k,$v);
		}

		$tpl = FSystem::tpl('form.'.$itemVO->typeId.'.tpl.html');
		//GENERIC
		$tpl->setVariable('FORMACTION',FSystem::getUri());
		$tpl->setVariable('M','item-submit');
		$tpl->setVariable('T',$itemVO->typeId);
		if(!empty($itemVO->itemId)) $tpl->setVariable('ITEMID',$itemVO->itemId);

		$tpl->setVariable('TITLE',$itemVO->addon);

		$tpl->setVariable('TEXTID',$itemVO->typeId.$itemVO->pageId.'text');
		$tpl->setVariable('TEXTLONGID',$itemVO->typeId.$itemVO->pageId.'textLong');

		$tpl->setVariable('TEXT',$itemVO->text);
		$tpl->setVariable('TEXTLONG',$itemVO->textLong);

		$tpl->setVariable('DATESTART',$itemVO->dateStartLocal);
		$tpl->setVariable('TIMESTART',$itemVO->dateStartTime);

		$tpl->setVariable('DATEEND',$itemVO->dateEndLocal);
		$tpl->setVariable('TIMEEND',$itemVO->dateEndTime);

		//TYPE DEPEND
		if($itemVO->typeId==='forum') {
				
			if ($user->idkontrol) {
				if($data['simple']===false) {
					$tpl->setVariable('PERPAGE',$data['perpage']);
				}
			} else {
				$tpl->setVariable('USERNAME','');
				if(!empty($itemVO->name)) {
					if($itemVO->typeId!='forum' || !$user->idkontrol) {
						$tpl->setVariable('USERNAME',$itemVO->name);
					}
				}
				$captcha = new FCaptcha();
				$tpl->setVariable('CAPTCHASRC',$captcha->get_b2evo_captcha());
			}
		}else{
			if($opt = FCategory::getOptions($itemVO->pageId,$itemVO->categoryId,true,'')) $tpl->setVariable('CATEGORYOPTIONS',$opt);
			$tpl->setVariable('LOCATION',$itemVO->location);

				


			$position = $itemVO->prop('position');
			if(!empty($data['position'])) $position = $data['position'];
			if(!empty($position)) {
				$tpl->setVariable('POSITION',str_replace(';',"\n",$position));
			}
			//TODO: comments not loaded from cache
			//comments settings
			$tpl->touchBlock('comments'.$itemVO->getProperty('forumSet',$user->pageVO->prop('forumSet'),true));
			//public settings
			if($itemVO->public == 0) {
				$tpl->touchBlock('classnotpublic');
				$tpl->touchBlock('headernotpublic');
			} else {
				$tpl->touchBlock('public'.$itemVO->public);
			}
			//delete block
			if($itemVO->itemId>0) {
				$tpl->touchBlock('delete');
			}

			//event specials
			$tpl->touchBlock('remindrepeat'.$itemVO->prop('reminderEveryday'));
			$tpl->touchBlock('remindbefore'.$itemVO->prop('reminder'));
			$tpl->touchBlock('repeat'.$itemVO->prop('repeat'));

			if(!empty($itemVO->enclosure)) {
				//TODO: change to item image rather than fevent::flyer
				$tpl->setVariable('IMAGEURL',FEvents::flyerUrl( $itemVO->enclosure ));
				$tpl->setVariable('IMAGETHUMBURL',FEvents::thumbUrl( $itemVO->enclosure ));
			}
		}
		return $tpl->get();
	}
}