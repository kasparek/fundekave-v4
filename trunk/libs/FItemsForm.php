<?php
class FItemsForm {

	static function process($itemVO,$data) {
		$redirectParam = '';
		$newItem=false;
		if(empty($itemVO->itemId)) $newItem=true;
		$redirect = false;
		$user = FUser::getInstance();
		$captchaCheck = true;
		if($user->idkontrol !== true) {
				$captcha = new FCaptcha();
				if(!$captcha->validate_submit($data['captchaimage'],$data['pcaptcha'])) $cap = false;
		}
		if($user->itemVO) {
			$data['itemIdTop'] = $user->itemVO->itemId;
		}
		if(!isset($data['itemIdTop'])) $data['itemIdTop']=0;
		if($captchaCheck===false) {
			FError::addError(FLang::$ERROR_CAPTCHA);
		}
		
		//check permissions
		if(FRules::getCurrent(2) === true 
			|| ($user->pageVO->typeId=='forum' && FRules::getCurrent(1) !== true)
			|| ($user->pageVO->typeId!='forum' && $itemVO->typeId=='forum' && ($user->pageVO->prop('forumSet')==1 || ($user->idkontrol && $user->pageVO->prop('forumSet')==2))) {
			//access granted
		} else {
			FError::addError(FLang::$ERROR_RULES_CREATE)
		}
		
		if(!empty($data['item'])) $itemVO->itemId = (int) $data['item'];
		if($itemVO->load()) $newItem=false;
		
		switch($data['action']) {
			case 'deleteImage':
				$itemVO = new ItemVO((int) $data['item']);
				if($itemVO->load()) {
					$itemVO->deleteImage();
					$itemVO->save();
				}
				break;
			case 'delete':
			  $itemVO = new ItemVO((int) $data['item']);
				if($itemVO->load()) {
					$itemVO->delete();
				}
				FError::addError(FLang::$LABEL_DELETED_OK,1);
				FAjax::redirect(FSystem::getUri('',$user->pageVO->typeId,''));
				break;
			case 'save':
			default:
				
				/**
				 *process data
				 **/		 		
				if(!FError::isError()) {
						if(isset($data['addon'])) $data['addon'] = FSystem::textins($data['addon'],array('plainText'=>1)); //title for blog,event
						if(empty($data['addon']) && $itemVO->typeId!='forum') FError::addError(FLang::$ERROR_NAME_EMPTY);
				    $data['name'] = isset($data['name']) ? $user->userVO->name : FSystem::textins($data['name'],array('plainText'=>1));
				    if(empty($data['name'])) $data['name'] = $user->userVO->name; 
				    $data['text'] = FSystem::textins($data['text'],$user->idkontrol ? array() : array('plainText'=>1)));
				    $data['textLong'] = FSystem::textins($data['textLong']));
				    if(empty($data['text']) && $itemVO->typeId=='forum') FError::addError(FLang::$MESSAGE_EMPTY);
						if(empty($data['name'])) FError::addError(FLang::$MESSAGE_NAME_EMPTY);
				    elseif($user->idkontrol==false) {
				    	if (FUser::isUsernameRegistered($data['name'])) FError::addError(FLang::$MESSAGE_NAME_USED);
						}
						if(!empty($data['categoryNew'])) $data['categoryId'] = FCategory::tryGet( $data['categoryNew'], $user->pageVO->typeId);
						if(!empty($data['categoryId'])) $data['categoryId'] = (int) $data['categoryId'];
						if(isset($data['dateStartLocal'])) $data['dateStart'] = FSystem::checkDate($data['dateStartLocal'].(isset($data['dateStartTime']?' '.$data['dateStartTime']:''));
						if(isset($data['dateEndLocal'])) $data['dateEnd'] = FSystem::checkDate($data['dateEndLocal'].(isset($data['dateEndTime']?' '.$data['dateEndTime']:''));
						if(empty($data['dateStart']) && $itemVO->typeId!='forum') FError::addError(FLang::$ERROR_DATE_FORMAT);
						if(isset($data['location'])) $data['location'] = FSystem::textins($data['location'],array('plainText'=>1)); 
				}
				/**
				 *save item
				 */		 		
				if(!FError::isError()) {
					$itemVO->pageId = $user->pageVO->pageId;
					$itemVO->userId = (int) $user->userVO->userId;
					$itemVO->name = $data['name'];
					if(!empty($data['text']) $itemVO->set('text', $data['text']);
					if(!empty($data['textLong']) $itemVO->set('textLong', $data['textLong']);
					$itemVO->typeId = $user->pageVO->typeId;
					$itemVO->itemIdTop = $data['itemIdTop']>0 ? (int) $data['itemIdTop'] : null;
					if(!empty($data['categoryId']) $itemVO->set('categoryId', $data['categoryId']);  
					if(!empty($data['dateStart']) $itemVO->set('dateStart', $data['dateStart']);
					if(!empty($data['dateEnd']) $itemVO->set('dateEnd', $data['dateEnd']);
					if(isset($data['public']) $itemVO->set('public', (int) $data['public']);
					if($itemVO->save()>0){
					  if(!empty($data['imageUrl'])) {
					  	$itemVO->deleteImage();
					  	$filename = FSystem::safeFilename($data['imageUrl']);
					  	if($file = file_get_contents($data['imageUrl'])) {
					  		$ffile = new FFile(FConf::get("galery","ftpServer"),FConf::get("galery","ftpUser"),FConf::get("galery","ftpPass"));
								$ffile->file_put_contents(ROOT_FLYER.$flyerName,$file);
								$itemVO->enclosure = $filename;
								$itemVO->save();	
							}
					  } elseif(isset($data['__files'])) {
					  	if($data['__files']['imageFile']['error'] == 0) {
					  		$data['__files']['imageFile']['name'] = FSystem::safeFilename($data['__files']['imageFile']['name']);
					  		$itemVO->deleteImage();
					  		$ffile = new FFile(FConf::get("galery","ftpServer"),FConf::get("galery","ftpUser"),FConf::get("galery","ftpPass"));
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
							if(strpos($posData,';')!==false) $itemVO->setProperty('distance', FSystem::journeyLength($posData); 
						}
						if(isset($data['forumset'])) $itemVO->setProperty('forumSet',(int) $data['forumset']);
						if(isset($data['reminder'])) $itemVO->prop('reminder',$data['reminder']*1);
						if(isset($data['reminderEveryday'])) $itemVO->prop('reminderEveryday',$data['reminderEveryday']*1);
						if(isset($data['repeat'])) $itemVO->prop('repeat',$data['repeat']*1);
						
						if($itemVO->typeId!='forum') {
							FError::addError(FLang::$MESSAGE_SUCCESS_SAVED,1);
							if($newItem===true) FAjax::redirect(FSystem::getUri('i='.$itemVO->itemId,$pageId,'u'));
						}
						
						$filename = FFile::getTemplFilename();
						if($filename!==false) {
							//delete old image
							$itemVO->deleteImage();
							$filenameArr = explode('/',$filename);
							$pageVO = $itemVO->pageVO;
							$galdir = FConf::get('galery','sourceServerBase') . $pageVO->galeryDir.'/';
							$flyerTarget = $galdir.array_pop($filenameArr);
							$ffile = new FFile(FConf::get("galery","ftpServer"),FConf::get("galery","ftpUser"),FConf::get("galery","ftpPass"));
							$ffile->move_uploaded_file($filename,$flyerTarget);
							$itemVO->enclosure = $flyerName;
							$itemVO->save();
						}
						
						//clean up stored data
						$cache = FCache::getInstance('s',0);
						$cache->invalidateData($itemVO->pageId.$itemVO->typeId,'form');
						//---on success
						$redirectParam = '#dd';
						$redirect = true;
					}
				}
		}
		//if any error safe data to display in form
		if(FError::isError()) {
			$cache = FCache::getInstance('s',0);
			$cache->setData($data, $itemVO->pageId.$itemVO->typeId, 'form');
		}
		
		//redirect
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
			FHTTP::redirect(FSystem::getUri($redirectParam));
		}
	}

	//TODO: pass filter in data / content
	//TODO: pass perpage in data
	static function show($itemVO,$data) {
		$user = FUser::getInstance();
		if(!isset($data['simple'])) $data['simple']=false;
		$cache = FCache::getInstance('s',0);
		$tempData = $cache->getData( $itemVO->pageId.$itemVO->typeId, 'form');
		if($tempData !== false) {
			foreach($tempData as $k=>$v) {
				$data[$k] = $v;
			}
			$cache->invalidateData( $itemVO->pageId.$itemVO->typeId, 'form');
		}
		foreach($data as $k=>$v) {
			$itemVO->set($k,$v);
		}

		$tpl = FSystem::tpl('form.'.$itemVO->typeId.'.tpl.html');
		//GENERIC
		$tpl->setVariable('FORMACTION',FSystem::getUri('m=item-submit&t='.$itemVO->typeId));
		if(!empty($itemVO->itemId)) $tpl->setVariable('ITEMID',$itemVO->itemId);

		if(!empty($itemVO->addon)) {
			$tpl->setVariable('TITLE',$itemVO->addon);
		}
		$tpl->setVariable('CONTENTID',$itemVO->typeId.$itemVO->pageId.'text');
		$tpl->setVariable('CONTENTLONGID',$itemVO->typeId.$itemVO->pageId.'textLong');
		if(!empty($itemVO->text)) {
			$tpl->setVariable('CONTENT',$itemVO->text);
		}
		if(!empty($itemVO->textLong)) {
			$tpl->setVariable('CONTENTLONG',$itemVO->textLong);
		}
		if(!empty($itemVO->dateStartLocal)) {
			$tpl->setVariable('DATESTART',$itemVO->dateStartLocal);
			$tpl->setVariable('TIMESTART',$itemVO->dateStartTime);
		}
		if(!empty($itemVO->dateEndLocal)) {
			$tpl->setVariable('DATEEND',$itemVO->dateEndLocal);
			$tpl->setVariable('TIMEEND',$itemVO->dateEndTime);
		}

		if(!empty($itemVO->name)) {
			$tpl->setVariable('USERNAME',$itemVO->name);
		}

		//TYPE DEPEND
		switch($itemVO->typeId) {
			case 'forum':
				if ($user->idkontrol) {
					if($data['simple']===false) {
						$tpl->setVariable('PERPAGE',$data['perpage']);
					}
				} else {
					$tpl->setVariable('USERNAME','');
					$captcha = new FCaptcha();
					$tpl->setVariable('CAPTCHASRC',$captcha->get_b2evo_captcha());
				}
				break;
			case 'blog':
			case 'event':
				if($opt = FCategory::getOptions($itemVO->pageId,$itemVO->categoryId,true,'')) $tpl->setVariable('CATEGORYOPTIONS',$opt);

				break;
			case 'galery':
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
				if($itemVO->itemId>0)
				$tpl->touchBlock('delete');
				
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