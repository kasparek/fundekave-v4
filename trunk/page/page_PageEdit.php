<?php
include_once('iPage.php');
class page_PageEdit implements iPage {

	static function process( $data ) {
		$redirParam = '';
		//---action
		$action = '';
		if(isset($data['action'])) $action = $data['action'];
		if(isset($data["save"])) $action = 'save';
		if(isset($data["del"])) $action = 'del';
		if(isset($data["delpageavatar"])) $action = 'delpageavatar';
	
		$user = FUser::getInstance();

		$redirectAdd=false; //keep current value
		if($user->pageParam == 'a') $redirectAdd = 'e';

		$textareaIdDescription = 'desc'.$user->pageVO->pageId;
		$textareaIdContent =  'cont'.$user->pageVO->pageId;
		$textareaIdForumHome = 'home'.$user->pageVO->pageId;

		if($action == 'delpageavatar') {
			$pageVO = new PageVO($data['pageId'],true);
			$pageVO->saveOnlyChanged=true;
			$pageVO->set('pageIco','');
			$pageVO->save();
			FAjax::addResponse('pageavatarBox','$html','');
			return;
		}

		/*if(strpos($action,'-')) list($action,$actionValue) = explode('-',$action);
		if($action == 'delfoto') {
			$deleteItemId = (int) $actionValue;
			$itemVO = new ItemVO($deleteItemId);
			if($itemVO->load()) {
				$itemVO->delete();
				if($data['__ajaxResponse']) {
					FAjax::addResponse('call', 'remove', 'foto-'.$deleteItemId);
				}
			}
			return;
		}*/

		if($action == "save") {
			FError::reset();

			$pageVO = new PageVO();
			if($user->pageParam == 'a') {
				//---new page
				if(isset(FLang::$TYPEID[$data['t']])) {
					$pageVO->typeId = FSystem::safeText($data['t']);
				} else {
					$pageVO->typeId = $user->pageVO->typeIdChild;
				}
				if(empty($pageVO->typeId)) {
					FError::add('missing page type');
				}
				$pageVO->pageIdTop = HOME_PAGE;
				$pageVO->setDefaults();
			} else {
				$pageVO->pageId = $data['pageId'];
				$pageVO->load();
			}


			//---categories
			if($pageVO->typeId=='blog' && $user->pageParam!='a') {
				$category = new FCategory('sys_pages_category','categoryId');
				$category->addWhere("typeId = '".$pageVO->pageId."'");
				$category->arrSaveAddon = array('typeId'=>$pageVO->pageId,'pageIdTop'=>HOME_PAGE);
				$category->process($data);
			}

			//---leftpanel
			/*
			if(isset($data['leftpanel'])) {
				$fLeft = new FLeftPanel($pageVO->pageId,0,$pageVO->typeId);
				$fLeft->process($data['leftpanel']);
			}
			*/

			$nameChanged = $pageVO->set('name', FSystem::textins($data['name'],array('plainText'=>1)));
			if(empty($pageVO->name)) {
				FError::add(FLang::$ERROR_PAGE_ADD_NONAME);
			}
			if($nameChanged) {
				if(FPages::page_exist('name',$pageVO->name)) {
					FError::add(FLang::$ERROR_PAGE_NAMEEXISTS);
				}
			}
			
			
			
			if($user->pageParam=='sa') {
				$pageVO->template = FSystem::textins($data['template'],array('plainText'=>1));
				if(isset($data['locked'])) {
					$pageVO->locked = (int) $data['locked'];
				}
				if(empty($data['description'])) $data['description']='';
				$pageVO->description = FSystem::textins($data['description'],array('plainText'=>1));
			} else {
				if($pageVO->description==FSystem::textins($pageVO->content,array('plainText'=>1))) $pageVO->description=null;
			}
			
			if(empty($data['content'])) $data['content']='';
			$pageVO->content = FSystem::textins($data['content']);
			
			if($user->pageParam!='sa' && empty($pageVO->description) && !empty($pageVO->content)) {
				$pageVO->description=FSystem::textins($pageVO->content,array('plainText'=>1));
			}
			 
			if(isset($data['datecontent'])) {
				$pageVO->set('dateContent',$data['datecontent'],array('type'=>'date'));
			}

			if(!empty($data['category'])) {
				$pageVO->categoryId = (int) $data['category'];
			}

			if(isset($data['forumhome'])) {
				$pageVO->prop('home', FSystem::textins($data['forumhome']));
			}

			if(!FError::is()) {

				if($user->pageParam == 'a') {
					$pageVO->userIdOwner = $user->userVO->userId;
				}

				//---first save - if new page to get pageId
				if(empty($pageVO->pageId)) {
					$pageVO->pageId = FPages::newPageId();
					$pageVO->forceInsert=true;
					$pageVO->save();
				}
				if(!empty($data['categoryNew'])) {
					$pageVO->categoryId = FCategory::tryGet( $data['categoryNew'],$pageVO->typeId,HOME_PAGE);
					$redirectAjax = true;
				}

				/* PAGE AVATAR */
				if(!empty($data['audicourl'])) {
					$pageVO->pageIco = FPages::avatarFromUrl( $pageVO->pageId, $data['audicourl'] );
				}
				if(isset($data['_files'])) {
					if ($data['_files']["audico"]['error']==0) {
						$pageVO->pageIco = FPages::avatarUpload( $pageVO->pageId, $data['_files']['audico'] );
					}
				}
				if(isset($data['delpic'])) {
					$pageVO->pageIco = FPages::avatarDelete( $pageVO->pageId );
				}

				/* GALERY SETTINGS */
				if($pageVO->typeId == 'galery') {
					//---create folder string if not set
					if(empty($pageVO->galeryDir)) {
						$pageVO->galeryDir = FSystem::safeText(FUser::getgidname($pageVO->userIdOwner)) . '/' . date("Ymd") .'_'.FSystem::safeText($pageVO->name).'_'. $pageVO->pageId;
						//---create folder if not exits
						$file = new FFile(FConf::get("galery","ftpServer"));
						$file->makeDir(FConf::get("galery","sourceServerBase") .$pageVO->galeryDir);
					}

					//---load settings from defaults if not in limits
					$thumbCut = FConf::get('galery','thumbCut');
					list($xwidthpx,$xheightpx) = explode('x',substr($thumbCut,0,strpos($thumbCut,'/'))); //thumbCut = 170x170/crop

					if(isset($data['xwidthpx'])) if($data['xwidthpx'] > 20) $xwidthpx = (int) $data['xwidthpx'];
					if(isset($data['xheightpx'])) if($data['xheightpx'] > 20) $xheightpx = (int) $data['xheightpx'];

					$thumbStyleSelectedIndex = 2;
					if(isset($data['xthumbstyle'])) $thumbStyleSelectedIndex = (int) $data['xthumbstyle'];
					$thumbStyle = $thumbStyleSelectedIndex=='2' ? 'crop' : 'prop';
					$pageVO->prop('thumbCut',$xwidthpx.'x'.$xheightpx.'/'.$thumbStyle);

					if(isset($data['galeryorder'])) $pageVO->prop('order',(int) $data['galeryorder']);

					//---if setting changed on edited galery delete thumbs
					if($pageVO->xmlChanged === true && $user->pageParam!='a') {
						$galery = new FGalery();
						$galery->pageVO = PageVO::factory($pageVO->pageId, true);
						$galery->flush();
					}
				}

				//---second save to save pageId related stuff
				$pageVO->save();

				FCommand::run(PAGE_UPDATED,$pageVO);

				//---page editing
				if($user->pageParam != 'a') {
					//---permissions update
					$rules = new FRules($pageVO->pageId,$pageVO->userIdOwner);
					$rules->update( $data );

					//---relations update
					/*
					 $fRelations = new FPagesRelations($pageVO->pageId);
					 $fRelations->update();
					 */
				}

				//---set special properties
				if ($pageVO->typeId == 'blog' || $pageVO->typeId == 'galery') {
					if(isset($data['forumReact'])) {
						$pageVO->prop('forumSet',(int) $data['forumReact']);
					}
				}
				if(isset($data['homesite'])) {
					$pageVO->prop('homesite', FSystem::textins($data['homesite'],array('plainText'=>1)));
				}
				if(isset($data['position'])) {
					$position = FSystem::textins($data['position'],array('plainText'=>1));
					if(!empty($position)) {
						$pageVO->prop('position', $position);
					}
				}

				//---set current page for redirect
				if(!empty($pageVO->pageId)) {
					$user->pageVO->pageId = $pageVO->pageId;
				}
				//---if page has been created reset pageParam before redirect
				if($user->pageParam=='a') {
					$user->pageParam = '';
					$pageCreated = true;
				} else {
					$pageCreated = false;
				}
				//---CLEAR CACHE
				$cache = FCache::getInstance('f');
				$cache->invalidateGroup('forumdesc');

				/* galery foto upload */
				if($pageVO->typeId == 'galery') {
					if(!empty($data['__files'])) {
						//---upload new foto
						$adr = FConf::get("galery","sourceServerBase") . $pageVO->galeryDir;
						$ffile = new FFile(FConf::get("galery","ftpServer"));
						foreach ($_FILES as $foto) {
							if ($foto["error"]==0) $up=$ffile->upload($foto,$adr,500000);
						}
					}

					//---foto delete
					if(isset($data['delfoto'])) {
						foreach ($data['delfoto'] as $deleteItemId) {
							$deleteItemId = (int) $deleteItemId;
							$itemVO = new ItemVO($deleteItemId);
							if($itemVO->load()) {
								$itemVO->delete();
							}
						}
					}

					//--prepare foto array
					foreach($data as $k=>$v) {
						if(strpos($k, 'foto-') !== false) {
							$keyArr = explode('-',$k);
							$fotoArr[$keyArr[1]][$keyArr[2]] = $v;
						}
					}
					if(isset($fotoArr)) {
						foreach ($fotoArr as $k=>$v) {
							$itemVO = new ItemVO($k,true);
							$itemVO->saveOnlyChanged = true;
							$itemVO->set('text',FSystem::textins($v['desc'],array('plainText'=>1)));
							$v['position'] = FSystem::textins($v['position'],array('plainText'=>1));
							if(!empty($v['position'])) $itemVO->prop('position',$v['position']);
							if(!empty($v['date'])) {
								if(false === $itemVO->set('dateStart',$v['date'],array('type'=>'date'))) {
									FError::add(FLang::$ERROR_DATE_FORMAT);
								}
							}
							$itemVO->save();
						}
					}

					if(!isset($data['draftable'])) {
						$redirectParam = '#dd';
					}
				}

				/* redirect */
				if($pageCreated === true) {
					FError::add(FLang::$MESSAGE_SUCCESS_CREATE.': <a href="'.FSystem::getUri('',$pageVO->pageId).'">'.$pageVO->name.'</a>',1);
				}
				if($data['__ajaxResponse']) {
					if($pageCreated === true) {
						//if new page redirect
						FAjax::errorsLater();
						FAjax::addResponse('call','redirect',FSystem::getUri('',$pageVO->pageId,$redirectAdd));
					} else {
						//if updating just message
						FError::add(FLang::$MESSAGE_SUCCESS_SAVED,1);
						if(!empty($redirectAjax)) {
							FAjax::redirect(FSystem::getUri(!isset($data['draftables'])?'dd=1':'','','e'));
						}
					}
				} else {
					FHTTP::redirect(FSystem::getUri($redirParam,'',$redirectAdd));
				}
			} else {
				//---error during value check .. let the values stay in form - data remain in _POST
				FUserDraft::save($textareaIdDescription, $data['description']);
				FUserDraft::save($textareaIdContent, $data['content']);
				if($user->pageVO->typeId=='forum' || $user->pageVO->typeId=='blog') FUserDraft::save($textareaIdForumHome, $data['forumhome']);
				//---cache data
				$cache = FCache::getInstance('l');
				$cache->setData($pageVO, 'page', 'form');
			}
		}

		/*  DELETE PAGE */
		if ($action == "del" && $user->pageParam!='a') {
			$pageId = $data['pageId'];
			//---check if page has any related items
			$arrd = FDBTool::getCol("SELECT itemId FROM sys_pages_items WHERE pageId='".$pageId."'");

			$delete = false;
			if(empty($arrd)) $delete = true;
			if($user->pageParam == 'sa') $delete = true;

			$pageVO = new PageVO($pageId,true);
			if($delete === false) {
				//---lock & hide
				$pageVO->locked = 3;
				$pageVO->save();
			} else {
				//---complete delete
				FPages::deletePage($pageId);
			}
			FCommand::run(PAGE_UPDATED,$pageVO);
			FError::add(FLang::$LABEL_DELETED_OK,1);
			FAjax::redirect(FSystem::getUri('',HOME_PAGE,''));
		}

	}

	static function build($data=array()) {

		$user = FUser::getInstance();

		$textareaIdDescription = 'desc'.$user->pageVO->pageId;
		$textareaIdContent =  'cont'.$user->pageVO->pageId;
		$textareaIdForumHome = 'home'.$user->pageVO->pageId;

		$cache = FCache::getInstance('l');

		if(false !== ($pageVOCached = $cache->getData('page','form'))) {
			//---load from cache data - unsaved
			$pageVO = $pageVOCached;
		} elseif($user->pageParam == 'a') {
			//---new page
			$pageVO = new PageVO();
			if(empty($user->pageVO->typeIdChild)) {
				//try data 't'
				if(isset(FLang::$TYPEID[$data['__get']['t']])) $pageVO->typeId=$data['__get']['t'];
				else {
					FError::add('missing page type');
					return;
				}
			} else {
				$pageVO->typeId = $user->pageVO->typeIdChild;
			}
			$pageVO->setDefaults();
		} else {
			$pageVO = new PageVO();
			$pageVO->pageId = $user->pageVO->pageId;
			$pageVO->load();

			if($pageVO->typeId=='galery') {
				$pageVO->refreshImages();
			}
		}

		//---SHOW TIME
		/***
		 *TODO:
		 *-kdyz je admin - tlacitko smazat
		 *- kdyz se maze top stranka tak se jen skryje
		 *-
		 *
		 *
		 **/
			
		$tpl=FSystem::tpl('page.edit.tpl.html');
		$tpl->setVariable('FORMACTION',FSystem::getUri('m=page-edit&u='.$user->userVO->userId));
		if($pageVO->typeId!="top" && $user->pageParam!='a') $tpl->touchBlock('delpage');
		if($user->pageParam!='a') {
			$tpl->setVariable('PAGEID',$pageVO->pageId);
			$tpl->touchBlock('extendedtab');
			if(empty($pageVO->pageIdTop) || $pageVO->pageIdTop==$pageVO->pageId) {
				$tpl->touchBlock('site');
				$tpl->setVariable('HOMESITE',$pageVO->prop('homesite'));
			}
			$tpl->setVariable('POSITION',$pageVO->prop('position'));
		} else {
			$tpl->setVariable('T',$pageVO->typeId);
		}
		if(!empty($pageData['userIdOwner'])) {
			$tpl->setVariable('OWNERLINK',FSystem::getUri('who='.$pageVO->userIdOwner,'finfo'));
			$tpl->setVariable('OWNERNAME',FUser::getgidname($pageVO->userIdOwner));
		}

		$pageDesc = '';
		$pageCont = '';

		if(isset($pageVO->name)) $tpl->setVariable('PAGENAME',$pageVO->name);
			
		if(isset($pageVO->description)) $pageDesc = $pageVO->description;
		if(isset($pageVO->content)) $pageCont = $pageVO->content;

		$tpl->setVariable('PAGECONTENTID',$textareaIdContent);
		$tpl->setVariable('PAGECONTENT',FSystem::textToTextarea($pageCont));

		if($user->pageParam!='a') {
			$tpl->touchBlock('permissionstab');
			$rules = new FRules($pageVO->pageId, $pageVO->userIdOwner);
			$tpl->setVariable('PAGEPERMISIONSFORM',$rules->printEditForm());

			/*
			 $tpl->touchBlock('relatedtab');
			 $fRelations = new FPagesRelations($pageVO->pageId);
			 $tpl->setVariable('RELATIONSFORM',$fRelations->getForm($pageVO->pageId));
			 */

			if(FConf::get('settings','pageAvatars')==1) {
				if(!empty($pageVO->pageIco)) $tpl->setVariable('PAGEICOLINK',URL_PAGE_AVATAR.$pageVO->pageIco);
				$tpl->touchBlock('pageavatarupload');
			}
		}

		if($pageVO->typeId == 'forum') {
			//enable avatar
			$tpl->touchBlock('forumspecifictab');
			//FORUM HOME
			$home = FSystem::textToTextarea($pageVO->prop('home'));
			$tpl->setVariable('CONTENT',$home);
			$tpl->setVariable('HOMEID',$textareaIdForumHome);
		}

		if($user->pageParam != 'a') {
			if($pageVO->typeId == 'galery') {
				$thumbPropList = explode('/',$pageVO->getProperty('thumbCut',FConf::get('galery','thumbCut'),true));
				$thumbSizeList = explode('x',$thumbPropList[0]);
				$tpl->setVariable('GTHUMBWIDTH',$thumbSizeList[0]);
				$tpl->setVariable('GTHUMBHEIGHT',$thumbSizeList[1]);
				if($thumbPropList[1]=='crop') $tpl->touchBlock('galerythumbstyle2');
				$tpl->touchBlock('fforum'.($user->pageVO->prop('forumSet')*1));
			} elseif ($pageVO->typeId=='blog') {
				$tpl->touchBlock('fforum'.($user->pageVO->prop('forumSet')*1));
			}
		}

		if($pageVO->typeId == 'galery' && $user->pageParam != 'a') {
			$tpl->touchBlock('galeryspecifictabs');
			if($pageVO->itemsOrder()=='dateCreated desc') {
				$tpl->touchBlock('gorddate');
			}
			$fItems = new FItems('galery',false);
			$fItems->setWhere("pageId='".$pageVO->pageId."' and (itemIdTop is null or itemIdTop=0)");
			$tpl->setVariable('FOTOTOTAL',$fItems->getCount());

			/* UPLOAD INPUTS */
			$numInputs=7;
			for ($x=1;$x<$numInputs;$x++) {
				$tpl->setCurrentBlock('uploadinput');
				$tpl->setVariable('UPLOADINPUTLABEL','Foto '.$x.'.');
				$tpl->setVariable('UPLOADINPUTID',$x);
				$tpl->parseCurrentBlock();
			}
		}

		$categoryId = (isset($pageVO->categoryId))?($pageVO->categoryId):(0);
		$arrTmp = FDBTool::getAll('select categoryId,name from sys_pages_category where typeId="'.$pageVO->typeId.'"');
		if(!empty($arrTmp)) $tpl->setVariable('CATOPTIONS',FCategory::getOptions($arrTmp,$categoryId));

		//---if pageParam = sa - more options to edit on page
		//--- template,categoryId,dateContent,locked
		if($user->pageParam=='sa') {
			$tpl->setVariable('LOCKEDOPTIONS',FCategory::getOptions(FLang::$ARRLOCKED,$pageVO->locked));
			$tpl->setVariable('PAGETEMPLATE',$pageVO->template);
			//seo plain text description
			$tpl->setVariable('PAGEDESCRIPTIONID',$textareaIdDescription);
			$tpl->setVariable('PAGEDESCRIPTION',FSystem::textToTextarea($pageDesc));
		}

		if($user->pageParam=='sa' || $pageVO->typeId=='galery') {
			$date = new DateTime((!empty($pageVO->dateContent))?($pageVO->dateContent):(''));
			$tpl->setVariable('DATECONTENT',$date->format("d.m.Y"));
		}

		if($pageVO->typeId=='blog' && $user->pageParam!='a') {
			$tpl->touchBlock('categorytab');
			$category = new FCategory('sys_pages_category','categoryId');
			$category->addWhere("typeId='".$pageVO->pageId."'");
			$category->arrSaveAddon = array('typeId'=>$pageVO->pageId);
			$tpl->setVariable('PAGECATEGORYEDIT',$category->getEdit());
		}

		//---left panels configure
		/*
		 if($user->pageParam != 'a') {
			$tpl->touchBlock('leftpaneltab');
			$fLeft = new FLeftPanelEdit($pageVO->pageId,0,$pageVO->typeId);
			$tpl->setVariable('LEFTPANELEDIT',$fLeft->showEdit());
			}
			/**/
			
		if(!empty($data['__ajaxResponse'])) {
			return $tpl->get();
		} else {
			FMenu::secondaryMenuAddItem(FSystem::getUri('','',''),FLang::$BUTTON_PAGE_BACK);
			FBuildPage::addTab(array("MAINID"=>'pageedit',"MAINHEAD"=>'',"MAINDATA"=>$tpl->get()));
		}
	}
}