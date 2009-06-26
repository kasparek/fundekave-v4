<?php
//TODO: refactoring due to galery
include_once('iPage.php');
class page_PageEdit implements iPage {

	static function process( $data ) {

		$user = FUser::getInstance();
		
		$redirectAdd = '';
		if(($user->pageVO->pageId=='galed' || $user->pageVO->pageId=='paged') && $user->pageParam!='sa') {
			$user->pageParam = 'a' ;
			$redirectAdd = 'e';
		}

		$textareaIdDescription = 'desc'.$user->pageVO->pageId;
		$textareaIdContent =  'cont'.$user->pageVO->pageId;
		$textareaIdForumHome = 'home'.$user->pageVO->pageId;

		if(isset($data["save"])) {
			if($user->pageParam == 'a') {
				//---new page
				$pageVO = new PageVO();
				$pageVO->typeId = $user->pageVO->typeIdChild;
				$pageVO->setDefaults();
				$pageVO->nameshort = (isset(FLang::${$pageVO->typeId}))?(FLang::${$pageVO->typeId}):('');
			} else {
				$pageVO = $user->pageVO;
			}
			FError::resetError();
				
			//---categories
			if($pageVO->typeId=='blog' && $user->pageParam!='a') {
				$category = new FCategory('sys_pages_category','categoryId');
				$category->addWhere("typeId = '".$pageVO->pageId."'");
				$category->arrSaveAddon = array('typeId'=>$pageVO->pageId);
				$category->process($data);
			}
				
			//---leftpanel
			if(isset($data['leftpanel'])) {
				$fLeft = new FLeftPanel($pageVO->pageId,0,$pageVO->typeId);
				$fLeft->process($data['leftpanel']);
			}
				
			$nameChanged = $pageVO->set('name', FSystem::textins($data['name'],array('plainText'=>1)));
			if(empty($pageVO->name)) {
				FError::addError(FLang::$ERROR_PAGE_ADD_NONAME);	
			}
			if($nameChanged) {
				if(FPages::page_exist('name',$pageVO->name)) {
					FError::addError(FLang::$ERROR_PAGE_NAMEEXISTS);	
				}
			}
			$pageVO->description = FSystem::textins($data['description'],array('plainText'=>1));
			$pageVO->content = FSystem::textins($data['content']);
				
			if(isset($data['datecontent'])) {
				$pageVO->set('dateContent',$data['datecontent'],array('type'=>'date'));
			}

			if($user->pageParam=='sa') {
				$pageVO->nameShort = FSystem::textins($data['nameshort'],array('plainText'=>1));
				$pageVO->authorContent = FSystem::textins($data['authorcontent'],array('plainText'=>1));
				$pageVO->template = FSystem::textins($data['template'],array('plainText'=>1));
				if(isset($data['locked'])) {
					$pageVO->locked = (int) $data['locked'];
				}
				if(!empty($data['menusec'])) {
					$pageVO->menuSecondaryGroup = (int) $data['menusec'];
				}
			}

			if(!empty($data['category'])) {
				$pageVO->categoryId = (int) $data['category'];
			}

			if(isset($data['forumhome'])) {
				$pageVO->setXML('home', FSystem::textins($data['forumhome']));
			}

			if(!FError::isError()) {

				if($user->pageParam == 'a') {
					$pageVO->userIdOwner = $user->userVO->userId;
					$cache = FCache::getInstance('f');
					$cache->invalidateGroup('calendarlefthand');
				}

				//---first save - if new page to get pageId
				if(empty($pageVO->pageId)) {
					$pageVO->save();
				}

				/* PAGE AVATAR */
				if(!empty($data['audicourl'])) {
					$pageVO->pageIco = FPages::avatarFromUrl( $pageVO->pageId, $data['audicourl'] );
				}
				if ($_FILES["audico"]['error']==0) {
					$pageVO->pageIco = FPages::avatarUpload( $pageVO->pageId, $_FILES['audico'] );
				}
				if(isset($data['delpic'])) {
					$pageVO->pageIco = FPages::avatarDelete( $pageVO->pageId );
				}

				/* GALERY SETTINGS */
				if($pageVO->typeId == 'galery') {
					//---create folder string if not set
					if(empty($pageVO->galeryDir)) {
						$pageVO->galeryDir = FUser::getgidname($pageVO->userIdOwner) . '/' . date("Ymd") .'_'.FSystem::safeText($pageVO->name).'_'. $pageVO->pageId;
						//---create folder if not exits
						$dir = WEB_REL_GALERY .$pageVO->galeryDir;
						FSystem::makeDir($dir);						
					}

					//---load settings from defaults if not in limits
					if(($xperpage = $data['xperpage']*1) < 1) $xperpage = FConf::get('galery','perpage');
					if(($xwidthpx = $data['xwidthpx']*1) < 10) $xwidthpx = FConf::get('galery','widthThumb');
					if(($xheightpx = $data['xheightpx']*1) < 10) $xheightpx = FConf::get('galery','heightThumb');
					$pageVO->setXML('enhancedsettings','perpage',$xperpage);
					$pageVO->setXML('enhancedsettings','widthpx',$xwidthpx);
					$pageVO->setXML('enhancedsettings','heightpx',$xheightpx);
					$pageVO->setXML('enhancedsettings','thumbnailstyle',(int) $data['xthumbstyle']);
					if(isset($data['galeryorder'])) $pageVO->setXML('enhancedsettings','orderitems',(int) $data['galeryorder']);
					if(isset($data['forumReact'])) $pageVO->setXML('enhancedsettings','fotoforum',(int) $data['forumReact']);
					//---if setting changed on edited galery delete thumbs
					if($pageVO->xmlChanged === true && $user->pageParam!='a') {
						FGalery::deleteThumbs( $pageVO->pageId );
					}
				}

				//---second save to save pageId related stuff
				$pageVO->save();

				//---page editing
				if($user->pageParam != 'a') {
					//---permissions update
					$rules = new FRules($pageVO->pageId,$pageVO->userIdOwner);
					$rules->public = $data['public'];
					$rules->ruleText = $data['rule'];
					$rules->update();
					//---relations update
					$fRelations = new FPagesRelations($pageVO->pageId);
					$fRelations->update();
				}

				//---set special properties
				if ($pageVO->typeId == 'blog') {
					if(isset($data['forumReact'])) {
						FPages::setProperty($pageVO->pageId, 'forumSet',(int) $data['forumReact']);
					}
				}

				//CLEAR DRAFT
				FUserDraft::clear($textareaIdDescription);
				FUserDraft::clear($textareaIdContent);
				if($pageVO->typeId=='forum' || $pageVO->typeId=='blog') {
					FUserDraft::clear($textareaIdForumHome);
				}
				//---set current page for redirect
				if(!empty($pageVO->pageId)) {
					$user->pageVO->pageId = $pageVO->pageId;
				}
				//---if page has been created reset pageParam before redirect
				if($user->pageParam=='a') {
					$user->pageParam = '';
				}
				//---CLEAR CACHE
				$cache = FCache::getInstance('f'); 
				$cache->invalidateGroup('forumdesc');

				/* galery foto upload */
				if($pageVO->typeId == 'galery') {
					if(!empty($data['__files'])) {
						//---upload new foto
						$adr = ROOT . ROOT_WEB . WEB_REL_GALERY . $pageVO->galeryDir;
						foreach ($_FILES as $foto) {
							if ($foto["error"]==0) $up=FSystem::upload($foto,$adr,500000);
						}
					}

					//---foto delete
					if(isset($data['delfoto'])) {
						foreach ($data['delfoto'] as $dfoto) {
							FGalery::removeFoto($dfoto);
						}
					}

					if(isset($data['fot'])) {
						foreach ($data['fot'] as $k=>$v) {
							$itemVO = new ItemVO($k,true);
							$itemVO->saveOnlyChanged = true;
							$itemVO->set('text',FSystem::textins($v['comm'],array('plainText'=>1)));
							if(!empty($v['date'])) {
								if(false === $itemVO->set('dateCreated',$v['date'],array('type'=>'date'))) {
									FError::addError(ERROR_DATE_FORMAT);
								}
							}
							$itemVO->save();
						}
					}

				}

				/* redirect */
				FHTTP::redirect(FUser::getUri('','',$redirectAdd));
			} else {
				//---error during value check .. let the values stay in form - data remain in _POST
				FUserDraft::save($textareaIdDescription, $_POST['description']);
				FUserDraft::save($textareaIdContent, $_POST['content']);
				if($user->pageVO->typeId=='forum' || $user->pageVO->typeId=='blog') FUserDraft::save($textareaIdForumHome, $_POST['forumhome']);
				//---cache data
				$cache = FCache::getInstance('l');
				$cache->setData($pageVO, 'page', 'form');
			}
		}

		/*  DELETE PAGE */
		if (isset($_POST['del']) && $user->pageParam!='a') {
			//---check if page has any related items
			$arrd = $db->getCol("SELECT itemId FROM sys_pages_items WHERE pageId='".$user->pageVO->pageId."'");
				
			$delete = false;
			if(empty($arrd)) $delete = true;
			if($user->pageParam == 'sa') $delete = true;
				
			if($delete === false) {
				//---lock & hide
				$user->pageVO->locked=3;
				$user->pageVO->save();
			} else {
				//---complete delete
				FPages::deletePage($user->pageVO->pageId);
			}
				
			FHTTP::redirect($user->getUri('',''));
		}

	}

	static function build() {
		
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
			$pageVO->typeId = $user->pageVO->typeIdChild;
			$pageVO->setDefaults();
			$pageVO->nameshort = (isset(FLang::${$pageVO->typeId}))?(FLang::${$pageVO->typeId}):('');
		} else {
			$pageVO = $user->pageVO;
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
			
		$tpl=new FTemplateIT('page.edit.tpl.html');
		$tpl->setVariable('FORMACTION',FUser::getUri('m=page-edit&u='.$user->userVO->userId));
		if(!empty($pageData['userIdOwner'])) {
			$tpl->setVariable('OWNERLINK',FUser::getUri('who='.$pageVO->userIdOwner,'finfo'));
			$tpl->setVariable('OWNERNAME',FUser::getgidname($pageVO->userIdOwner));
		}

		$pageDesc = '';
		$pageCont = '';

		if(isset($pageVO->name)) $tpl->setVariable('PAGENAME',$pageVO->name);
		if(isset($pageVO->description)) if(!$pageDesc = FUserDraft::get($textareaIdDescription)) $pageDesc = $pageVO->description;
		if(isset($pageVO->content)) if(!$pageCont = FUserDraft::get($textareaIdContent)) $pageCont = $pageVO->content;

		$tpl->setVariable('PAGEDESCRIPTIONID',$textareaIdDescription);
		$tpl->setVariable('PAGEDESCRIPTION',FSystem::textToTextarea($pageDesc));

		$tpl->setVariable('PAGECONTENTID',$textareaIdContent);
		$tpl->setVariable('PAGECONTENT',FSystem::textToTextarea($pageCont));
		$tpl->addTextareaToolbox('PAGECONTENTTOOLBOX',$textareaIdContent);

		if(!empty($pageVO->pageIco)) $tpl->setVariable('PAGEICOLINK',WEB_REL_PAGE_AVATAR.$pageVO->pageIco);

		if($user->pageParam!='a') {
			$tpl->touchBlock('permissionstab');
			$tpl->touchBlock('relatedtab');
			$rules = new FRules($pageVO->pageId, $pageVO->userIdOwner);
			$tpl->setVariable('PAGEPERMISIONSFORM',$rules->printEditForm());
			$fRelations = new FPagesRelations($pageVO->pageId);
			$tpl->setVariable('RELATIONSFORM',$fRelations->getForm($pageVO->pageId));
		}

		$tpl->touchBlock('pageavatarupload');

		if($pageVO->typeId == 'forum') {
			//enable avatar
			$tpl->touchBlock('forumspecifictab');
			//FORUM HOME
			if(!$home = FUserDraft::get($textareaIdForumHome)) {
				$home = FSystem::textToTextarea($pageVO->getPageParam('home'));
			}
			$tpl->setVariable('CONTENT',$home);
			$tpl->setVariable('HOMEID',$textareaIdForumHome);
			$tpl->addTextareaToolbox('CONTENTTOOLBOX',$textareaIdForumHome);
		}

		if($pageVO->typeId == 'galery') {
			$tpl->setVariable('PERPAGE',$pageVO->getPageParam('enhancedsettings/perpage'));
			$tpl->setVariable('GTHUMBWIDTH',$pageVO->getPageParam('enhancedsettings/widthpx'));
			$tpl->setVariable('GTHUMBHEIGHT',$pageVO->getPageParam('enhancedsettings/heightpx'));
			if($pageVO->getPageParam('enhancedsettings/thumbnailstyle') == 2) $tpl->touchBlock('galerythumbstyle2');
			//$tpl->touchBlock('fforum'.($pageVO->getPageParam('enhancedsettings/fotoforum')*1));
		} elseif ($pageVO->typeId=='blog') {
			$tpl->touchBlock('fforum'.(FPages::getProperty($user->pageVO->pageId,'forumSet',1)*1));
		}

		if($pageVO->typeId == 'galery' && $user->pageParam != 'a') {
			$tpl->touchBlock('galeryspecifictabs');

			if($pageVO->itemsOrder()=='dateCreated desc') {
				$tpl->touchBlock('gorddate');
			}
			$fItems = new FItems('galery',false);
			$fItems->setWhere("pageId='".$pageVO->pageId."'");
			$tpl->setVariable('FOTOTOTAL',$fItems->getCount());
			/*
			$itemRenderer = new FItemsRenderer();
			$itemRenderer->setCustomTemplate( 'item.galery.edit.tpl.html' );
			
			$fItems = new FItems('galery',false,$itemRenderer);
			$fItems->setWhere("pageId='".$pageVO->pageId."'");
			$tpl->setVariable('FOTOTOTAL',$fItems->getCount());
			if($pageVO->getPageParam('enhancedsettings/orderitems') == 1) {
				$tpl->touchBlock('gorddate');
				$fItems->setOrder($pageVO->itemsOrder());
			} else {
				$fItems->setOrder('enclosure');
			}
			$tpl->setVariable('FOTOLIST',$fItems->render());
			*/
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
		if(!empty($arrTmp)) $tpl->setVariable('CATEGORYOPTIONS',FSystem::getOptions($arrTmp,$categoryId));

		//---if pageParam = sa - more options to edit on page
		//--- nameShort,template,menuSecondaryGroup,categoryId,dateContent,locked,authorContent
		if($user->pageParam=='sa') {
			$arrTmp = FDBTool::getAll('select menuSecondaryGroup,menuSecondaryGroup from sys_menu_secondary group by menuSecondaryGroup order by menuSecondaryGroup');
			$tpl->setVariable('MENUSECOPTIONS',FSystem::getOptions($arrTmp,$pageVO->menuSecondaryGroup));

			$tpl->setVariable('LOCKEDOPTIONS',FSystem::getOptions(FLang::$ARRLOCKED,$pageVO->locked));
			$tpl->setVariable('PAGEAUTHOR',$pageVO->authorContent);

			$tpl->setVariable('PAGENAMESHORT',$pageVO->nameshort);
			$tpl->setVariable('PAGETEMPLATE',$pageVO->template);
		}
		$date = new DateTime((!empty($pageVO->dateContent))?($pageVO->dateContent):(''));
		$tpl->setVariable('DATECONTENT',$date->format("d.m.Y"));

		if($pageVO->typeId=='blog' && $user->pageParam!='a') {
			$tpl->touchBlock('categorytab');
			$category = new FCategory('sys_pages_category','categoryId');
			$category->addWhere("typeId='".$pageVO->pageId."'");
			$category->arrSaveAddon = array('typeId'=>$pageVO->pageId);
			$tpl->setVariable('PAGECATEGORYEDIT',$category->getEdit());
		}

		//---left panels configure
		if($user->pageParam != 'a') {
			$tpl->touchBlock('leftpaneltab');
			$fLeft = new FLeftPanel($pageVO->pageId,0,$pageVO->typeId);
			$tpl->setVariable('LEFTPANELEDIT',$fLeft->showEdit());
		}

		FBuildPage::addTab(array("MAINHEAD"=>($user->pageParam == 'a')?(FLang::$LABEL_PAGE_NEW):(''),"MAINDATA"=>$tpl->get()));
	}
}