<?php
//TODO: refactoring due to galery
include_once('iPage.php');
class page_PageEdit implements iPage {

	static function process() {
		
		$user = FUser::getInstance();
		/**
		 * $user->currentPageParam == a - add from defaults, e - edit from user->currentPage
		 */
		
		$redirectAdd = '';
		if(($user->pageVO->pageId=='galed' || $user->pageVO->pageId=='paged') && $user->currentPageParam!='sa') {
			$user->pageParam = 'a' ;
			$redirectAdd = 'e';
		}
		
		$typeId = $user->pageVO->typeId;
		if($user->pageParam=='a') $typeId = $user->pageVO->typeIdChild;
		
		$deleteThumbs = false;
		
		$textareaIdDescription = 'desc'.$user->pageVO->pageId;
		$textareaIdContent =  'cont'.$user->pageVO->pageId;
		$textareaIdForumHome = 'home'.$user->pageVO->pageId;
				
		if(isset($_POST["save"])) {
			if($user->pageParam == 'a') {
				//---new page
				$pageVO = new PageVO();
				$pageVO->typeId = $typeId;
				$pageVO->setDefaults();
				$pageVO->nameshort = (isset(FLang::$$typeId))?(FLang::$$typeId):('');
			} else {
				$pageVO = $user->pageVO;
			}
			FError::resetError();
			
			//---categories
			if($typeId=='blog' && $user->pageParam!='a') {
				$category = new FCategory('sys_pages_category','categoryId');
				$category->addWhere("typeId = '".$pageVO->pageId."'");
				$category->arrSaveAddon = array('typeId'=>$pageVO->pageId);
				$category->process();
			}
			
			//---leftpanel
			if(isset($_POST['leftpanel'])) {
				$fLeft = new FLeftPanel($pageVO->pageId,0,$pageVO->typeId);
				$fLeft->process($_POST['leftpanel']);
			}
			
			$pageVO->name = FSystem::textins($_POST['name'],array('plainText'=>1));
			if(empty($pageVO->name)) FError::addError(ERROR_PAGE_ADD_NONAME);
			$pageVO->description = FSystem::textins($_POST['description'],array('plainText'=>1));
			$pageVO->content = FSystem::textins($_POST['content']);
			
			//TODO:save galery stuff on second run - need a pageid
			if($typeId == 'galery') {
				$pageVO->galeryDir = $user->name .'/'.'';
				$arr['galeryDir'] = Trim($_POST['galeryDir']);
				if($arr['galeryDir']=='') FError::addError(ERROR_GALERY_DIREMPTY);
				elseif (!FSystem::checkDirname($arr['galeryDir'])) FError::addError(ERROR_GALERY_DIRWRONG);
				elseif($user->currentPageParam=='e' && $user->currentPage['galeryDir'] != $arr['galeryDir']) {
					$deleteThumbs = true;
				}

				if(($xperpage = $_POST['xperpage']*1) < 1) $xperpage = $galery->get('thumbNumWidth');
				if(($xwidthpx = $_POST['xwidthpx']*1) < 10) $xwidthpx = $galery->get('widthThumb');
				if(($xheightpx = $_POST['xheightpx']*1) < 10) $xheightpx = $galery->get('heightThumb');

				$sPage->setXMLVal('enhancedsettings','perpage',$xperpage);
				$sPage->setXMLVal('enhancedsettings','widthpx',$xwidthpx);
				$sPage->setXMLVal('enhancedsettings','heightpx',$xheightpx);
				$sPage->setXMLVal('enhancedsettings','thumbnailstyle',(int) $_POST['xthumbstyle']);
				if(isset($_POST['galeryorder'])) $sPage->setXMLVal('enhancedsettings','orderitems',(int) $_POST['galeryorder']);
				if(isset($_POST['forumReact'])) $sPage->setXMLVal('enhancedsettings','fotoforum',(int) $_POST['forumReact']);

				if($user->currentPage['pageParams'] != $sPage->xmlProperties && $user->currentPageParam=='e') {
	    $deleteThumbs = true;
				}
			}

			if(isset($_POST['datecontent'])) {
				$dateContent = FSystem::switchDate($_POST['datecontent']);
				if(!empty($dateContent)) if(FSystem::isDate($dateContent)) $pageVO->dateContent = $dateContent;
			}

			if($user->currentPageParam=='sa') {
				$pageVO->nameShort = FSystem::textins($_POST['nameshort'],array('plainText'=>1));
				$pageVO->authorContent = FSystem::textins($_POST['authorcontent'],array('plainText'=>1));
				$pageVO->template = FSystem::textins($_POST['template'],array('plainText'=>1));
				if(isset($_POST['locked'])) {
					$pageVO->locked = (int) $_POST['locked'];
				}
				if(isset($_POST['menusec'])) {
					$menusec = $_POST['menusec'];
					if($menusec>0) {
						$pageVO->menuSecondaryGroup = (int) $menusec;
					}
				}
			}

			if(isset($_POST['category'])) {
				$cat = (int) $_POST['category'];
				if($cat > 0) {
					$pageVO->categoryId = $cat;
				}
			}

			if(isset($_POST['forumhome'])) {
				$pageVO->setXML('home', FSystem::textins($_POST['forumhome']));
			}

			if(!FError::isError()) {

				if($typeForSaveTool=='galery') {
					$adr = $galery->get('rootImg').$arr['galeryDir'];
					if(!file_exists($adr)) {
						if(mkdir ($adr, 0777)) {
							chmod ( $adr, 0777 );
						}
					}
				}

				if($user->currentPageParam == 'a') {
					$pageVO->userIdOwner = $user->userVO->userId;
					$user->cacheRemove('calendarlefthand');
				}
				
				
					
				if($deleteThumbs===true && $typeForSaveTool=='galery') {
					$galery->getGaleryData($user->pageVO->pageId);
					$cachePath = ROOT.ROOT_WEB.$galery->getThumbCachePath();
					FSystem::rm_recursive($cachePath);
					$systemCachePath = ROOT.ROOT_WEB.$galery->getThumbCachePath($galery->_cacheDirSystemResolution);
					FSystem::rm_recursive($systemCachePath);


				}

				//---first save - if new page to get pageId
				if(empty($pageVO->pageId)) {
					$pageVO->save();
				}
				
				/* PAGE AVATAR */
				if(!empty($_POST['audicourl'])) {
					$pageVO->pageIco = FPages::avatarFromUrl( $pageVO->pageId, $_POST['audicourl'] );
				}	
				if ($_FILES["audico"]['error']==0) {
					$pageVO->pageIco = FPages::avatarUpload( $pageVO->pageId, $_FILES['audico'] );
				}
				if(isset($_POST['delpic'])) {
					$pageVO->pageIco = FPages::avatarDelete( $pageVO->pageId );
				}

				//---second save to save pageId related stuff
				$pageVO->save();

				if($user->currentPageParam != 'a') {
					//---rules,relations update
					$rules = new FRules($pageVO->pageId,$pageVO->userIdOwner);
					$rules->public = $_POST['public'];
					$rules->ruleText = $_POST['rule'];
					$rules->update();
					$fRelations = new FPagesRelations($pageVO->pageId);
					$fRelations->update();
				}
				//---set properties
				if ($typeForSaveTool=='blog') {
					if(isset($_POST['forumReact'])) {
						FPages::setProperty($pageVO->pageId, 'forumSet',(int) $_POST['forumReact']);
					}
				}

				//CLEAR DRAFT
				FUserDraft::clear($textareaIdDescription);
				FUserDraft::clear($textareaIdContent);
				if($user->currentPage['typeId']=='forum' || $user->currentPage['typeId']=='blog') FUserDraft::clear($textareaIdForumHome);
				if(!empty($pageVO->pageId)) $user->pageVO->pageId = $pageVO->pageId;
				if($user->pageParam=='a') $user->pageParam = '';
				//CLEAR CACHE
				$user->cacheRemove('forumdesc');
				/**/

				/*galery foto upload*/

				if($user->pageParam!='a' && $typeId=='galery') {

					if(!empty($_FILES)) {
						if(!empty($user->currentPage['galeryDir'])) {
							$adr = $galery->get('rootImg').$user->currentPage['galeryDir'];

							foreach ($_FILES as $foto) {
								if ($foto["error"]==0) $up=FSystem::upload($foto,$adr,500000);
							}
						}
						
					}

					//---foto description, foto deleteing
					if(isset($_POST['delfoto'])) foreach ($_POST['delfoto'] as $dfoto) $galery->removeFoto($dfoto);

					if(isset($_POST['fot'])) {
						foreach ($_POST['fot'] as $k=>$v) {
							$changed = false;
							$newDesc = FSystem::textins($v['comm'],array('plainText'=>1));
							$galery->getFoto($k);
							$oldDesc = $galery->get('fComment');
							$oldDate = $galery->get('fDate');
							if($newDesc != $oldDesc) {
								$galery->set('fComment',$newDesc);
								$changed = true;
							}
							$newDate = $v['date'];
							if(!empty($newDate)) {
								if(strpos($newDate,'.')===true) $newDate = FSystem::den($newDate);
								elseif(!FSystem::isDate($newDate)) $newDate = '';
								if(empty($newDate)) FError::addError(ERROR_DATE_FORMAT);
								else {
									$galery->set('fDate',$newDate);
									$changed=true;
								}
							}

							if($changed) $galery->updateFoto();
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
				$cache->setData($pageVO, 'pageForm');
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

		/**
		 * $user->currentPageParam == a - add from defaults, e - edit from user->currentPage
		 */
		$typeForSaveTool = $user->pageVO->typeId;
		if($user->pageParam=='a') $typeForSaveTool = $user->pageVO->typeIdChild;

		if($typeForSaveTool == 'galery') {
			$galery = new FGalery();
		}

		//---SHOW TIME
		/***
		 *TODO:
		 *-kdyz je admin - tlacitko smazat
		 *- kdyz se maze top stranka tak se jen skryje
		 *-
		 *
		 *
		 *
		 *
		 **/
		$pageVO = new pageVO($typeForSaveTool); //---FIXME: set defaults	$pageData = $sPage->defaults[$typeForSaveTool];
		
		if(isset($_POST['save'])) {
			//---load from cache data - unsaved
			$cache = FCache::getInstance('l');
			$pageVO->set( $cache->getData('pageForm') ); //--- FIXME: new function
		} else if($user->pageParam!='a') {
			//edit page
			$pageVO->load(); //---FIXME: $pageData = $user->pageVO;
			//FIXME: -- load xml properties if($user->currentPageParam!='a') $sPage->xmlProperties = $user->currentPage['pageParams'];
		}

		$tpl=new FTemplateIT('page.edit.tpl.html');
		$tpl->setVariable('FORMACTION',$user->getUri());
		if(!empty($pageData['userIdOwner'])) {
			$tpl->setVariable('OWNERLINK','?k=finfo&who='.$pageData['userIdOwner']);
			$tpl->setVariable('OWNERNAME',$user->getgidname($pageData['userIdOwner']));
		}

		$pageDesc = '';
		$pageCont = '';

		if(isset($pageData['name'])) $tpl->setVariable('PAGENAME',$pageData['name']);
		if(isset($pageData['description'])) if(!$pageDesc = FUserDraft::get($textareaIdDescription)) $pageDesc = $pageData['description'];
		if(isset($pageData['content'])) if(!$pageCont = FUserDraft::get($textareaIdContent)) $pageCont = $pageData['content'];

		$tpl->setVariable('PAGEDESCRIPTIONID',$textareaIdDescription);
		$tpl->setVariable('PAGEDESCRIPTION',FSystem::textToTextarea($pageDesc));

		$tpl->setVariable('PAGECONTENTID',$textareaIdContent);
		$tpl->setVariable('PAGECONTENT',FSystem::textToTextarea($pageCont));
		$tpl->addTextareaToolbox('PAGECONTENTTOOLBOX',$textareaIdContent);

		if(!empty($pageData['pageIco'])) $tpl->setVariable('PAGEICOLINK',WEB_REL_PAGE_AVATAR.$pageData['pageIco']);

		if($user->pageParam!='a') {
			$tpl->touchBlock('permissionstab');
			$tpl->touchBlock('relatedtab');
			$rules = new fRules((($user->pageParam != 'a')?($user->pageVO->pageId):('')),$user->pageVO->userIdOwner);
			$tpl->setVariable('PAGEPERMISIONSFORM',$rules->printEditForm());
			$fRelations = new fPagesRelations($user->pageVO->pageId);
			$tpl->setVariable('RELATIONSFORM',$fRelations->getForm(($user->pageParam != 'a')?($user->pageVO->pageId):(0)));
		}

		$tpl->touchBlock('pageavatarupload');

		if($typeForSaveTool == 'forum') {
			//enable avatar
			$tpl->touchBlock('forumspecifictab');
			//FORUM HOME
			if(!$home = FUserDraft::get($textareaIdForumHome)) {
		  $home = FSystem::textToTextarea($sPage->getXMLVal('home'));
			}
			$tpl->setVariable('CONTENT',$home);
			$tpl->setVariable('HOMEID',$textareaIdForumHome);
			$tpl->addTextareaToolbox('CONTENTTOOLBOX',$textareaIdForumHome);
		}

		if($typeForSaveTool == 'galery' && $user->pageParam != 'a') {
			$galery->getGaleryData($user->pageVO->pageId);
			$galery->getFoto($user->pageVO->pageId,true,(($galery->gOrderItems==1)?('i.dateCreated desc'):('i.enclosure')));

			$pageData['galeryDir'] = $galery->gDir;
		}

		if($typeForSaveTool == 'galery') {
			if(isset($pageData['galeryDir'])) $tpl->setVariable('GDIR',$pageData['galeryDir']);
			$tpl->setVariable('PERPAGE',$sPage->getXMLVal('enhancedsettings','perpage'));
			$tpl->setVariable('GTHUMBWIDTH',$sPage->getXMLVal('enhancedsettings','widthpx'));
			$tpl->setVariable('GTHUMBHEIGHT',$sPage->getXMLVal('enhancedsettings','heightpx'));
			if($sPage->getXMLVal('enhancedsettings','thumbnailstyle') == 2) $tpl->touchBlock('galerythumbstyle2');
			//$tpl->touchBlock('fforum'.($sPage->getXMLVal('enhancedsettings','fotoforum')*1));
		} elseif ($typeForSaveTool=='blog') {
			$tpl->touchBlock('fforum'.(FPages::getProperty($user->pageVO->pageId,'forumSet',1)*1));
		}

		if($typeForSaveTool == 'galery' && $user->pageParam != 'a') {
			$tpl->touchBlock('galeryspecifictabs');

			$tpl->setVariable('FOTOTOTAL',count($galery->arrData));

			if($sPage->getXMLVal('enhancedsettings','orderitems') == 1) $tpl->touchBlock('gorddate');

			if(!empty($galery->arrData)) {
				foreach ($galery->arrData as $foto){
					list($date,$time) = explode('T',$foto['dateIso']);
					if($date=='0000-00-00') $date='';
					$exif = @exif_read_data(ROOT.ROOT_WEB.$foto['detailUrl']);
					if(!empty($exif)) {
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


			$numInputs=7;
			for ($x=1;$x<$numInputs;$x++) {
				$tpl->setCurrentBlock('uploadinput');
				$tpl->setVariable('UPLOADINPUTLABEL','Foto '.$x.'.');
				$tpl->setVariable('UPLOADINPUTID',$x);
				$tpl->parseCurrentBlock();
			}

		}

		$categoryId = (isset($pageData['categoryId']))?($pageData['categoryId']):(0);
		$arrTmp = FDBTool::getAll('select categoryId,name from sys_pages_category where typeId="'.$typeForSaveTool.'"');
		if(!empty($arrTmp)) $tpl->setVariable('CATEGORYOPTIONS',FSystem::getOptions($arrTmp,$categoryId));



		//---if pageParam = sa - more options to edit on page
		//--- nameShort,template,menuSecondaryGroup,categoryId,dateContent,locked,authorContent
		if($user->pageParam=='sa') {
			$arrTmp = FDBTool::getAll('select menuSecondaryGroup,menuSecondaryGroup from sys_menu_secondary group by menuSecondaryGroup order by menuSecondaryGroup');
			$tpl->setVariable('MENUSECOPTIONS',FSystem::getOptions($arrTmp,$pageData['menuSecondaryGroup']));

			$tpl->setVariable('LOCKEDOPTIONS',FSystem::getOptions(FLang::$ARRLOCKED,$pageData['locked']));
			$tpl->setVariable('PAGEAUTHOR',$pageData['authorContent']);

			$tpl->setVariable('PAGENAMESHORT',$pageData['nameshort']);
			$tpl->setVariable('PAGETEMPLATE',$pageData['template']);
		}
		$date = new DateTime((!empty($pageData['dateContent']))?($pageData['dateContent']):(''));
		$tpl->setVariable('DATECONTENT',$date->format("d.m.Y"));

		if($typeId=='blog' && $user->pageParam!='a') {
			$tpl->touchBlock('categorytab');
			$category = new FCategory('sys_pages_category','categoryId');
			$category->addWhere("typeId='".$user->pageVO->pageId."'");
			$category->arrSaveAddon = array('typeId'=>$user->pageVO->pageId);
			$tpl->setVariable('PAGECATEGORYEDIT',$category->getEdit());
		}

		//---left panels configure
		if($user->pageParam != 'a') {
			$tpl->touchBlock('leftpaneltab');
			$fLeft = new FLeftPanel($user->pageVO->pageId,0,$user->pageVO->typeId);
			$tpl->setVariable('LEFTPANELEDIT',$fLeft->showEdit());
		}



		FBuildPage::addTab(array("MAINHEAD"=>($user->pageParam == 'a')?(LABEL_PAGE_NEW):(''),"MAINDATA"=>$tpl->get()));
	}
}