<?php
class FItemsForm {

	static function canComment($pageVO=null,$itemVO=null) {
		$user = FUser::getInstance();
		if(!$pageVO) $pageVO = $user->pageVO;
		if(!$itemVO && $itemVO!==false) $itemVO = $user->itemVO;
		if($pageVO->typeId=='top') return false;
		if($pageVO->typeId == 'forum' && $pageVO->locked > 0) return false;
		if(($pageVO->typeId == 'forum' || $pageVO->typeId == 'galery' || $itemVO) && !FConf::get('settings','perm_forum_unsigned')) {
			if(!$user->idkontrol) return false;
		}
		if($pageVO->typeId == 'blog' || $pageVO->typeId == 'galery' || $pageVO->typeId == 'event') {
			$writePerm = $pageVO->prop('forumSet');
			if($writePerm==0) return false;
			if(!$itemVO && $pageVO->typeId != 'galery') return false;
			if($itemVO) $writePerm = $itemVO->prop('forumSet');
			if($writePerm==0 || ($writePerm==2 && !$user->idkontrol)) return false;
		}
		return true;
	}

	static function moveImage($data,$itemVO=null) {
		if($itemVO==null) {
			if(empty($data['i'])) return;
			$itemVO = new ItemVO($data['i']);
			if(!$itemVO->load()) return;
		}
		$dir = $itemVO->pageVO->get('galeryDir');
		if(empty($dir)) {
			$itemVO->pageVO->set('galeryDir','page/'.$itemVO->pageId.'-'.FText::safeText($itemVO->pageVO->get('name')));
			$itemVO->pageVO->save();
		}
		$filename = FFile::getTemplFilename();
		if($filename!==false) {
			$ftpserver = FConf::get("galery","ftpServer");
			$ffile = new FFile($ftpserver);
			if(empty($ftpserver)) {
				//on direct check image size
				try{
					$size=getimagesize(FConf::get('galery','sourceServerBase').$filename);
				} catch(Exception $e) {
					FError::add(FLang::$ERROR_IMAGE_FORMAT);
				}
			} else {
				//on ftp site check extension and file exists
				$ext = FFile::fileExt($filename);
				if($ext == 'jpg' || $ext == 'jpeg' || $ext == 'png' || $ext == 'gif') {
					if($ffile->file_exists(FConf::get('galery','sourceServerBase').$filename)) {
						$size = true;
					}
				}
			}
			if(empty($size)) return $itemVO;
			//delete old image
			$itemVO->deleteImage();
			$pageVO = $itemVO->pageVO;
			$filenameArr = explode('/',$filename);
			$enclosure = FFile::safeFilename(array_pop($filenameArr));
			$enclosure = $itemVO->itemId.'-'.$enclosure;
			$target = FConf::get('galery','sourceServerBase') . $pageVO->get('galeryDir').'/'.$enclosure;
			$ffile->makeDir(FConf::get('galery','sourceServerBase') . $pageVO->get('galeryDir'));
			$ffile->rename(FConf::get('galery','sourceServerBase').$filename,$target);
			$itemVO->set('enclosure',$enclosure);
			$itemVO->setSaveOnlyChanged(true);
			$itemVO->save();
			$itemVO->prepare();
			FFile::flushTemplFile();
			return $itemVO;
		}
	}

	static function process($data) {

		$itemVO = new ItemVO();
		
		$redirectParam = '';
		$newItem = true;
		$redirect = false;
		
		$user = FUser::getInstance();
		
		$captchaCheck = true;
		if(!$user->idkontrol) {
			$captchaCheck = FSystem::recaptchaCheck($data);
			if($captchaCheck!==true) {
				if($captchaCheck!==false) $data['recaptchaError'] = $captchaCheck;
				$captchaCheck = false;
			}
		}
    		
		if(false===$itemVO->set('typeId',$data['t'])) {
			FError::add(FLang::$ERROR_FORM_TYPE);
			FError::write_log('FItemsForm::process - unset type - type:'.$data['t'].(isset($data['i'])?'item:'.$data['i']:''));
			return;
		}
		
		$itemVO->itemIdTop=null;
		if($user->itemVO) {
			$itemVO->itemIdTop = $user->itemVO->itemId;
		}
		//no reactions to forum items
		if($user->itemVO && $user->itemVO->typeId == 'forum') $itemVO->itemIdTop=null;
		if(empty($itemVO->pageIdTop)) $itemVO->pageIdTop = $user->pageVO->pageIdTop;

		if($captchaCheck===false) {
			FError::add(FLang::$ERROR_CAPTCHA);
		}

		//check permissions
		if(FRules::getCurrent(2) === true
		|| ($user->pageVO->pageId=='event' && $user->userVO->userId>0)
		|| ($user->pageVO->typeId=='forum' && FRules::getCurrent(1) === true)
		|| ($user->pageVO->typeId!='forum' && $itemVO->typeId=='forum'
		&& ($user->pageVO->prop('forumSet')==1 || ($user->idkontrol && $user->pageVO->prop('forumSet')==2)))) {
			//access granted
		} else {
			FError::add(FLang::$ERROR_RULES_CREATE);
		}

		if(!empty($data['i'])) $itemVO->itemId = (int) $data['i'];
		if($itemVO->itemId>0) if($itemVO->load()) $newItem=false;

		$itemVO->pageId = $user->pageVO->pageId;
		$itemVO->pageIdTop = $user->pageVO->pageIdTop;
		
		//check if site has strict limitations
		if(SITE_STRICT && !$user->idkontrol) {
			if($itemVO->pageIdTop != SITE_STRICT) {
				FError::add(FLang::$ERROR_RULES_CREATE);
			}
		}

		if(empty($data['action'])) {
			$data['action']='';
			if(isset($data['filtr'])) $data['action']='search';
		}

		//if (isset($data["perpage"])) $user->pageVO->perPage( $data["perpage"] );
    
		switch($data['action']) {
			case 'search':
				$cache = FCache::getInstance('s',0);
				$cache->setData(FText::preProcess($data["text"],array('plainText'=>1)), $user->pageVO->pageId, 'filter');
				break;
			case 'deleteImage':
				if(!FError::is()) {
					$itemVO = new ItemVO((int) $data['i']);
					if($itemVO->load()) {
						$itemVO->deleteImage();
						$itemVO->setSaveOnlyChanged(true);
						$itemVO->save();
					}
					FFile::flushTemplFile();
				}
				break;
			case 'del':
			case 'delete':
				if(!FError::is()) {
					$itemVO = new ItemVO((int) $data['i']);
					if($itemVO->load()) {
						$itemVO->delete();
					}
					FCommand::run(ITEM_UPDATED,$itemVO);
					$itemVO=null;
					$user->itemVO=null;
					FError::add(FLang::$LABEL_DELETED_OK,1);
					$redirect = true;
				}
				break;
			case 'save':
			default:
				/**
				 *process data
				 **/
				if(!FError::is()) {
					if(isset($data['addon'])) $data['addon'] = FText::preProcess($data['addon'],array('plainText'=>1)); //title for blog,event
					if($itemVO->typeId!='galery' && empty($data['addon']) && $itemVO->typeId!='forum') FError::add(FLang::$ERROR_NAME_EMPTY);
					if(isset($data['name'])) $data['name'] =  FText::preProcess($data['name'],array('plainText'=>1));
					if(empty($data['name'])) $data['name'] = $user->userVO->name;
					$data['text'] = FText::preProcess($data['text'],$user->idkontrol ? array() : array('plainText'=>1));
					if(isset($data['textLong'])) $data['textLong'] = FText::preProcess($data['textLong']);
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
					if(isset($data['location'])) $data['location'] = FText::preProcess($data['location'],array('plainText'=>1));
				}

				/**
				 *save item
				 */
				if(!FError::is()) {
					if(empty($itemVO->itemId)){
						$itemVO->userId = (int) $user->userVO->userId;
						$itemVO->name = $data['name'];
					}
					if(!empty($data['addon'])) $itemVO->set('addon', $data['addon']);
					if(!empty($data['text'])) $itemVO->set('text', $data['text']);
					if(!empty($data['textLong'])) $itemVO->set('textLong', $data['textLong']);
					if(!empty($data['location'])) $itemVO->set('location', $data['location']);
					if(empty($itemVO->typeId)) $itemVO->typeId = $user->pageVO->typeId;
					if(isset($data['category'])) {
						if($itemVO->categoryId>0) if($data['category']!=$itemVO->categoryId) $oldCategoryId=$itemVO->categoryId;
						$itemVO->set('categoryId', (int) $data['category']);
						if($itemVO->categoryId>0) {
							$categoryVO = new CategoryVO($itemVO->categoryId);
							if(!$categoryVO->load()) {
								$categoryVO=null;
								$itemVO->categoryId=0;
							}
						}
					}
					if(!empty($data['dateStart'])) $itemVO->set('dateStart', $data['dateStart']);
					if(!empty($data['dateEnd'])) $itemVO->set('dateEnd', $data['dateEnd']);
					if(isset($data['public'])) $itemVO->set('public', (int) $data['public']);
					if(isset($data['repeat'])) $itemVO->set('textLong',FText::safeText($data['repeat']));
					//save items
					if($itemVO->save() > 0){
						if(!empty($categoryVO)) $categoryVO->updateNum();
						if(!empty($oldCategoryId)) {
							$categoryVO = new CategoryVO($oldCategoryId);
							if($categoryVO->load()) {
								$categoryVO->updateNum();
							}
						}

						FItemsForm::moveImage($data,$itemVO);

						if(!empty($data['imageUrl'])) {
							$itemVO->deleteImage();
							$filename = FFile::safeFilename($data['imageUrl']);
							if($file = file_get_contents($data['imageUrl'])) {
								$temp_file = tempnam(sys_get_temp_dir(), 'imageUrl');
								file_put_contents($temp_file,$file);
								$processFile=false;
								try{
									$size=getimagesize($temp_file);
								} catch(Exception $e) {
									FError::add(FLang::$ERROR_IMAGE_FORMAT);
								}
								if(!empty($size)) if($size[0]>0) $processFile=true;
								if($processFile) {
									$itemVO->deleteImage();
									$filename = FFile::safeFilename( str_replace(array('http://','/'),'',$data['imageUrl']) );
									$ffile = new FFile(FConf::get("galery","ftpServer"));
									$ffile->makeDir(FConf::get("galery","sourceServerBase").$itemVO->pageVO->galeryDir);
									$ffile->file_put_contents(FConf::get("galery","sourceServerBase").$itemVO->pageVO->galeryDir.'/'.$filename,$file);
									$itemVO->enclosure = $filename;
									$itemVO->save();
									$itemVO->prepare();
								} else FError::add(FLang::$ERROR_IMAGE_FORMAT);
							}
						} elseif(isset($data['__files'])) {
							if($data['__files']['imageFile']['error'] == 0) {
								$data['__files']['imageFile']['name'] = FFile::safeFilename($data['__files']['imageFile']['name']);
								$processFile=false;
								try{
									$size=getimagesize($data['__files']['imageFile']);
								} catch(Exception $e) {
									FError::add(FLang::$ERROR_IMAGE_FORMAT);
								}
								if(!empty($size)) if($size[0]>0) $processFile=true;

								if($processFile) {
									$itemVO->deleteImage();
									$ffile = new FFile(FConf::get("galery","ftpServer"));
									if($ffile->upload($data['__files']['imageFile'],FConf::get("galery","sourceServerBase").$itemVO->pageVO->galeryDir,800000)) {
										$itemVO->enclosure = $data['__files']['imageFile']['name'];
										$itemVO->save();
										$itemVO->prepare();
									}
								} else FError::add(FLang::$ERROR_IMAGE_FORMAT);
							}
						}
						//properties
						if(isset($data['position'])) {
							$posData = FSystem::positionProcess($data['position']);
							if($itemVO->setProperty('position', $posData)){
								FCommand::run(POSITION_UPDATED);
							}
							if(strpos($posData,';')!==false) {
								$distance = FSystem::journeyLength($posData);
								$itemVO->setProperty('distance', $distance);
							}
						}
						if(isset($data['forumset'])) $itemVO->setProperty('forumSet',(int) $data['forumset']);

						//clean up stored data
						$cache = FCache::getInstance('s',0);
						$cache->invalidateData($itemVO->pageId.$itemVO->typeId,'form');
						//---on success
						$redirect=true;

						if($itemVO->itemIdTop > 0) {
							$itemVOTop = new ItemVO($itemVO->itemIdTop);
							$itemVOTop->updateReaded($itemVO->userId);
						} else {
							$itemVO->pageVO->updateReaded($user->userVO->userId);
						}

						//redirect
						if($itemVO->typeId!='forum') {
							$redirectParam = 'i='.$itemVO->itemId.$redirectParam;
							FError::add(FLang::$MESSAGE_SUCCESS_SAVED,1);
						}
					}
				}
		}
		
		//if any error safe data to display in form
		if(FError::is()) {
			$cache = FCache::getInstance('s',0);
			$cache->setData($data, $itemVO->pageId.$itemVO->typeId, 'form');
		}
		 
		if($redirect==true) {
			if($itemVO) FCommand::run(ITEM_UPDATED,$itemVO);
			if(!empty($data['__ajaxResponse'])) {
				if($itemVO) {
					//new item
					if($itemVO->typeId == 'forum') $newItem = false;
					if($newItem) FAjax::redirect(FSystem::getUri($redirectParam,$user->pageVO->pageId,'u',array('short'=>1)));
				} else {
					//deleted item
					FAjax::redirect(FSystem::getUri('',$user->pageVO->pageId,'',array('short'=>1)));
				}
			} else {
				FAjax::redirect(FSystem::getUri($redirectParam)); //non ajax processing
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

		if(is_array($data)) {
			foreach($data as $k=>$v) {
				$itemVO->set($k,$v);
			}
		}

		$tpl = FSystem::tpl('form.'.$itemVO->typeId.'.tpl.html');
		//GENERIC
		$tpl->setVariable('FORMACTION',FSystem::getUri('','',false,array('short'=>1)));
		$tpl->setVariable('M','item-submit');
		$tpl->setVariable('T',$itemVO->typeId);
		if(isset($data['fajaxform'])) {
			$tpl->touchBlock('fajaxform');
		}
		$tpl->touchBlock('geo');
		
		if(!empty($itemVO->itemId)) {
			$tpl->setVariable('ITEMID',$itemVO->itemId);
			$position = $itemVO->prop('position');
			if(!empty($data['position'])) $position = $data['position'];
			if(!empty($position)) {
				$tpl->setVariable('POSITION',str_replace(';',"\n",$position));
			}
		}
		
		if($itemVO->typeId=='forum' && $user->itemVO) {
			$tpl->setVariable('ITEMID',$user->itemVO->itemId);
		}

		$tpl->setVariable('TITLE',$itemVO->addon);

		$tpl->setVariable('TEXTID',$itemVO->typeId.$itemVO->pageId.'text');
		$tpl->setVariable('TEXTLONGID',$itemVO->typeId.$itemVO->pageId.'textLong');

		$tpl->setVariable('TEXT',FText::textToTextarea($itemVO->text));
		$tpl->setVariable('TEXTLONG',FText::textToTextarea($itemVO->textLong));

		$tpl->setVariable('DATESTART',$itemVO->dateStartLocal);
		$tpl->setVariable('TIMESTART',$itemVO->dateStartTime);

		$tpl->setVariable('DATEEND',$itemVO->dateEndLocal);
		$tpl->setVariable('TIMEEND',$itemVO->dateEndTime);

		//TYPE DEPEND
		if($itemVO->typeId=='forum') {
			if(!$user->idkontrol) {
				$tpl->setVariable('USERNAME','');
				if(!empty($itemVO->name)) {
					if($itemVO->typeId!='forum' || !$user->idkontrol) {
						$tpl->setVariable('USERNAME',$itemVO->name);
					}
				}
				if(empty($data['__ajaxResponse'])) $tpl->setVariable('RECAPTCHA',FSystem::recaptchaGet($tempData['recaptchaError']));
			} else {
				$tpl->touchBlock('usersigned');
			}
		} else {
			if($opt = FCategory::getOptions($itemVO->pageId,$itemVO->categoryId,true,'')) $tpl->setVariable('CATEGORYOPTIONS',$opt);
			$tpl->setVariable('LOCATION',$itemVO->location);

			//comments settings
			$tpl->touchBlock('comments'.$itemVO->getProperty('forumSet',$user->pageVO->prop('forumSet'),true));
			//public settings
			if($itemVO->typeId=='blog' || $itemVO->typeId=='event') {
				if($itemVO->public == 0) {
					$tpl->touchBlock('classnotpublic');
					$tpl->touchBlock('headernotpublic');
				} else {
					$tpl->touchBlock('public'.$itemVO->public);
				}
			}
			//delete block
			if($itemVO->itemId>0) {
				$tpl->touchBlock('delete');
			}

			//event specials
			if($itemVO->typeId=='event') {
				$tpl->touchBlock('repeat'.$itemVO->textLong);
			}

			if(!empty($itemVO->enclosure)) {
				$tpl->setVariable('IMAGEURL', $itemVO->detailUrl );
				$tpl->setVariable('IMAGETHUMBURL', $itemVO->thumbUrl );
			}
		}
		return $tpl->get();
	}
}