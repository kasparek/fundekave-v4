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
			$pageVO = FactoryVO::get('PageVO',$data['pageId'],true);
			$pageVO->setSaveOnlyChanged(true);
			$pageVO->set('pageIco','');
			$pageVO->save();
			FAjax::addResponse('pageavatarBox','$html','');
			return;
		}

		if($action == "save") {
			FProfiler::write('page_PageEdit::process - SAVING');
			FError::reset();

			if($user->pageParam == 'a') {
				$pageVO = FactoryVO::get('PageVO');
				//---new page
				if(isset(FLang::$TYPEID[$data['t']])) {
					$pageVO->typeId = FText::safeText($data['t']);
				} else {
					$pageVO->typeId = $user->pageVO->typeIdChild;
				}
				if(empty($pageVO->typeId)) {
					FError::add('missing page type');
				}
				$top = SITE_STRICT;
				$pageVO->pageIdTop = empty($top) ? HOME_PAGE : $top;
			} else {
				$pageVO = FactoryVO::get('PageVO',$data['pageId'],true);
			}
			FProfiler::write('page_PageEdit::process - DATA READY');

			//---categories
			if($pageVO->typeId=='blog' && $user->pageParam!='a') {
				$category = new FCategory('sys_pages_category','categoryId');
				$category->addWhere("typeId = '".$pageVO->pageId."'");
				$category->arrSaveAddon = array('typeId'=>$pageVO->pageId,'pageIdTop'=>HOME_PAGE);
				$category->process($data);
				FCommand::run(CATEGORIES_UPDATED);
			}

			//---leftpanel
			/*
			 if(isset($data['leftpanel'])) {
				$fLeft = new FLeftPanel($pageVO->pageId,0,$pageVO->typeId);
				$fLeft->process($data['leftpanel']);
				}
				*/

			$pageVO->set('name', FText::preProcess($data['name'],array('plainText'=>1)));
			
			if($user->pageParam!='sa' && empty($pageVO->name)) {
				FError::add(FLang::$ERROR_PAGE_ADD_NONAME);
			}
			if($pageVO->changed) {
				if(FPages::page_exist('name',$pageVO->name)) {
					FError::add(FLang::$ERROR_PAGE_NAMEEXISTS);
				}
			}
			
			if($user->pageParam=='sa') {
				$pageVO->template = FText::preProcess($data['template'],array('plainText'=>1));
				if(isset($data['locked'])) {
					$pageVO->locked = (int) $data['locked'];
				}
				if(empty($data['description'])) $data['description']='';
				$pageVO->description = FText::preProcess($data['description'],array('plainText'=>1));
				if(isset($data['pageIdTop'])) $pageVO->pageIdTop = $data['pageIdTop'];
			} else {
				if($pageVO->description==FText::preProcess($pageVO->content,array('plainText'=>1))) $pageVO->description=null;
			}

			if(empty($data['content'])) $data['content']='';
			$pageVO->content = FText::preProcess($data['content']);

			if($user->pageParam!='sa' && empty($pageVO->description) && !empty($pageVO->content)) {
				$pageVO->description=FText::preProcess($pageVO->content,array('plainText'=>1));
			}

			if(isset($data['datecontent'])) {
				$pageVO->set('dateContent',$data['datecontent'],array('type'=>'date'));
			}

			if(isset($data['category'])) {
				if($pageVO->categoryId>0) if($data['category']!=$pageVO->categoryId) $oldCategoryId=$pageVO->categoryId;
				$pageVO->set('categoryId', (int) $data['category']);
				if($pageVO->categoryId>0) {
					$categoryVO = new CategoryVO($pageVO->categoryId);
					if(!$categoryVO->load()) {
						$categoryVO=null;
						$pageVO->categoryId=0;
					}
				}
			}
			
			FProfiler::write('page_PageEdit::process - CHECKING FOR INPUT ERRORS');
			if(!FError::is()) {

				if($user->pageParam == 'a') {
					$pageVO->userIdOwner = $user->userVO->userId;
				}

				//---first save - if new page to get pageId
				if(empty($pageVO->pageId)) {
					$pageVO->pageId = FPages::newPageId();
					$pageVO->setForceInsert(true);
					$pageVO->save();
				}

				if(isset($data['forumhome'])) {
					$homeStr = FText::preProcess($data['forumhome']);
					$pageVO->setProperty('home', $homeStr);
				}
					
				if(isset($data['sidebar'])) {
					$pageVO->prop('sidebar', FText::preProcess($data['sidebar']));
				}
				
				if(isset($data['topbanner'])) {
					$pageVO->prop('topbanner', FText::preProcess($data['topbanner'],array('plainText'=>1)));
				}

				if(!empty($data['categoryNew'])) {
					$pageVO->categoryId = FCategory::tryGet( $data['categoryNew'],$pageVO->typeId,HOME_PAGE);
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

				FProfiler::write('page_PageEdit::process - starting galery settings');
				/* GALERY SETTINGS */
				if($pageVO->typeId == 'galery') {
					//---create folder string if not set
					if(empty($pageVO->galeryDir)) {
						$pageVO->galeryDir = FText::safeText(FUser::getgidname($pageVO->userIdOwner)) . '/' . date("Ymd") .'_'.FText::safeText($pageVO->name).'_'. $pageVO->pageId;
						//---create folder if not exits
						$file = new FFile(FConf::get("galery","ftpServer"));
						$file->makeDir(FConf::get("galery","sourceServerBase") .$pageVO->galeryDir);
					}
          if(isset($data['galeryorder'])) $pageVO->prop('order',(int) $data['galeryorder']);
        }

				$flush=false;

				//---load settings from defaults if not in limits
				$thumbCut = FConf::get('galery','thumbCut');
				list($xwidthpx,$xheightpx) = explode('x',substr($thumbCut,0,strpos($thumbCut,'/'))); //thumbCut = 170x170/crop
				if(isset($data['xwidthpx'])) if($data['xwidthpx'] > 20) $xwidthpx = (int) $data['xwidthpx'];
				if(isset($data['xheightpx'])) if($data['xheightpx'] > 20) $xheightpx = (int) $data['xheightpx'];

				$thumbStyleSelectedIndex = 2;
				if(isset($data['xthumbstyle'])) $thumbStyleSelectedIndex = (int) $data['xthumbstyle'];
				$thumbStyle = $thumbStyleSelectedIndex=='2' ? 'crop' : 'prop';
				$thumbCut=$xwidthpx.'x'.$xheightpx.'/'.$thumbStyle;
				if($pageVO->prop('thumbCut')!=$thumbCut) {
					$pageVO->prop('thumbCut',$thumbCut);
					$flush=true;
				}
				
				if(isset($data['galeryincluded'])) {
					$galeryincluded = (int) $data['galeryincluded'];
					if($pageVO->prop('galeryincluded') != $galeryincluded) {
						$pageVO->prop('galeryincluded',$galeryincluded==0?false:1);
						$flush=true;
					}
				}

				//---if setting changed on edited galery delete thumbs
				if($flush==true && $user->pageParam!='a') {
					$itemVO = new ItemVO();
					$itemVO->pageId = $pageVO->pageId;
					$itemVO->flush();
				}
				

				//---second save to save pageId related stuff
				$pageVO->save();
				
				FProfiler::write('page_PageEdit::process - saved');

				if(!empty($categoryVO)) $categoryVO->updateNum();
				if(!empty($oldCategoryId)) {
					$categoryVO = new CategoryVO($oldCategoryId);
					if($categoryVO->load()) {
						$categoryVO->updateNum();
					}
				}

				FCommand::run(PAGE_UPDATED,$pageVO);
				
				FProfiler::write('page_PageEdit::process - page cache flushed');

				//---page editing
				if($user->pageParam != 'a') {
					//---permissions update
					$rules = new FRules($pageVO->pageId,$pageVO->userIdOwner);
					$rules->update( $data );
					if(isset($data['permUpdateBlog'])) {
						$fp = new FPages('blog',$user->userVO->userId,2);
						$fp->setWhere("pageIdTop='".$pageVO->pageId."'");
						$pageList = $fp->getContent();
						foreach($pageList as $vo) {
							$rules->page = $vo->pageId;
							$rules->update($data);
						}
					}
					if(isset($data['permUpdateGalery'])) {
						$fp = new FPages('galery',$user->userVO->userId,2);
						$fp->setWhere("pageIdTop='".$pageVO->pageId."'");
						$pageList = $fp->getContent();
						foreach($pageList as $vo) {
							$rules->page = $vo->pageId;
							$rules->update($data);
						}
					}
					if(isset($data['permUpdateForum'])) {
						$fp = new FPages('forum',$user->userVO->userId,2);
						$fp->setWhere("pageIdTop='".$pageVO->pageId."'");
						$pageList = $fp->getContent();
						foreach($pageList as $vo) {
							$rules->page = $vo->pageId;
							$rules->update($data);
						}
					}
					$rules->updateAdminByPages();
					
					//---relations update
					/*
					 $fRelations = new FPagesRelations($pageVO->pageId);
					 $fRelations->update();
					 */
				} else {
					$rules = new FRules();
					$rules->updateAdminByPages();
				}

				//---set special properties
				if ($pageVO->typeId == 'blog' || $pageVO->typeId == 'galery') {
					if(isset($data['forumReact'])) {
						$pageVO->prop('forumSet',(int) $data['forumReact']);
					}
				}
				if(isset($data['homesite'])) {
					$pageVO->prop('homesite', FText::preProcess($data['homesite'],array('plainText'=>1)));
				}
				if(isset($data['position'])) {
					$posData = FSystem::positionProcess($data['position']);
					if($pageVO->setProperty('position', $posData)) {
						FCommand::run(POSITION_UPDATED);
					}
					if(strpos($posData,';')!==false) {
						$distance = FSystem::journeyLength($posData);
						$pageVO->setProperty('distance', $distance);
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
						$invalidateMap = false;
						foreach ($fotoArr as $k=>$v) {
							$itemVO = new ItemVO($k,true);
							$itemVO->setSaveOnlyChanged(true);
							
							FProfiler::write('page_PageEdit::process - foto loaded');
							
							$itemVO->set('text',FText::preProcess($v['desc'],array('plainText'=>1)));

							if(isset($v['position'])){
								$v['position'] = FSystem::positionProcess($v['position']);
								if($itemVO->setProperty('position', $v['position'])) $invalidateMap=true;
								if(strpos($v['position'],';')!==false) $itemVO->setProperty('distance', FSystem::journeyLength($v['position']));
							}

							if(!empty($v['date'])) {
								if(false === $itemVO->set('dateStart',$v['date'],array('type'=>'date'))) {
									FError::add(FLang::$ERROR_DATE_FORMAT);
								}
							}

							FProfiler::write('page_PageEdit::process - foto values updated');
							
							$itemVO->save();
							
							FProfiler::write('page_PageEdit::process - foto updated');
						}
						if($invalidateMap) FCommand::run(POSITION_UPDATED);
					}

				}

				FProfiler::write('page_PageEdit::process - page save complete');

				/* redirect */
				if($pageCreated === true) {
					FError::add(FLang::$MESSAGE_SUCCESS_CREATE.': <a href="'.FSystem::getUri('',$pageVO->pageId).'">'.$pageVO->name.'</a>',1);
				}
				if($data['__ajaxResponse']) {
					if($pageCreated === true) {
						//if new page redirect
						FAjax::errorsLater();
						FAjax::addResponse('call','redirect',FSystem::getUri('',$pageVO->pageId,$pageVO->typeId=='galery'?'u':$redirectAdd));
					} else {
						//if updating just message
						FError::add(FLang::$MESSAGE_SUCCESS_SAVED,1);
					}
				} else {
					FHTTP::redirect(FSystem::getUri($redirParam,'',$redirectAdd));
				}
			} else {
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

			$pageVO = FactoryVO::get('PageVO',$pageId,true);
			if($delete === false) {
				//---lock & hide
				$pageVO->locked = 3;
				$pageVO->save();
				FDBTool::query("update sys_pages_items set public=0 where pageId='".$pageVO->pageId."'");
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
			$pageVO = FactoryVO::get('PageVO');
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
		} else {
			$pageVO = $user->pageVO;
			if($pageVO->typeId=='galery') $pageVO->refreshImages();
		}

		//---SHOW TIME
		$tpl=FSystem::tpl('page.edit.tpl.html');
		$tpl->setVariable('FORMACTION',FSystem::getUri('m=page-edit&u='.$user->userVO->userId));
		if($pageVO->typeId!="top" && $user->pageParam!='a') $tpl->touchBlock('delpage');
		if($user->pageParam!='a') {
			$tpl->touchBlock('settingstab');
			$tpl->setVariable('PAGEID',$pageVO->pageId);
			$tpl->touchBlock('extendedtab');
			if(empty($pageVO->pageIdTop) || $pageVO->pageIdTop==$pageVO->pageId) {
				$tpl->touchBlock('site');
				$tpl->setVariable('HOMESITE',$pageVO->prop('homesite'));
			}
			$tpl->setVariable('POSITION',str_replace(';',"\n",$pageVO->prop('position')));
		} else {
			$tpl->setVariable('T',$pageVO->typeId);
		}
		
		if($pageVO->pageIdTop) {
			if($pageVOTop = FactoryVO::get('PageVO',$pageVO->pageIdTop,true)) {
				$tpl->setVariable('TOPPAGEURL',FSystem::getUri('',$pageVOTop->pageId));
				$tpl->setVariable('TOPPAGENAME',$pageVOTop->name);
			}
		}
		
		if(!empty($pageData['userIdOwner'])) {
			$tpl->setVariable('OWNERLINK',FSystem::getUri('who='.$pageVO->userIdOwner.'#tabs-profil','finfo'));
			$tpl->setVariable('OWNERNAME',FUser::getgidname($pageVO->userIdOwner));
		}

		$pageDesc = '';
		$pageCont = '';

		if(isset($pageVO->name)) $tpl->setVariable('PAGENAME',$pageVO->name);
			
		if(isset($pageVO->description)) $pageDesc = $pageVO->description;
		if(isset($pageVO->content)) $pageCont = $pageVO->content;

		$tpl->setVariable('PAGECONTENTID',$textareaIdContent);
		$tpl->setVariable('PAGECONTENT',FText::textToTextarea($pageCont));

		if($user->pageParam!='a') {
			$tpl->touchBlock('permissionstab');
			$rules = new FRules($pageVO->pageId, $pageVO->userIdOwner);
			$tpl->setVariable('PAGEPERMISIONSFORM',$rules->printEditForm());
			
			
			$fp = new FPages('blog',$user->userVO->userId,2);
			$fp->setWhere("pageIdTop='".$pageVO->pageId."'");
			$num = $fp->getCount();
			if($num>0) $tpl->setVariable('SUBBLOGNUM',$num);
			$fp = new FPages('galery',$user->userVO->userId,2);
			$fp->setWhere("pageIdTop='".$pageVO->pageId."'");
			$num = $fp->getCount();
			if($num>0) $tpl->setVariable('SUBGALERYNUM',$num);
			$fp = new FPages('forum',$user->userVO->userId,2);
			$fp->setWhere("pageIdTop='".$pageVO->pageId."'");
			$num = $fp->getCount();
			if($num>0) $tpl->setVariable('SUBFORUMNUM',$num);
			

			/*
			 $tpl->touchBlock('relatedtab');
			 $fRelations = new FPagesRelations($pageVO->pageId);
			 $tpl->setVariable('RELATIONSFORM',$fRelations->getForm($pageVO->pageId));
			 */

			/*
			if(FConf::get('settings','pageAvatars')==1) {
				if(!empty($pageVO->pageIco)) $tpl->setVariable('PAGEICOLINK',URL_PAGE_AVATAR.$pageVO->pageIco);
				$tpl->touchBlock('pageavatarupload');
			}
			*/
		}

		if($user->pageParam != 'a') {
			if($pageVO->typeId == 'forum') {
				//FORUM
				//enable avatar
				$tpl->touchBlock('forumspecifictab');
				//FORUM HOME
				$tpl->setVariable('CONTENT',FText::textToTextarea($pageVO->prop('home')));
				$tpl->setVariable('HOMEID',$textareaIdForumHome);
			} elseif($pageVO->typeId == 'galery') {
				//GALERY
				
				$tpl->touchBlock('fforum'.($user->pageVO->prop('forumSet')*1));

				$tpl->touchBlock('galeryspecifictabs');
				if($pageVO->itemsOrder()=='dateCreated desc') {
					$tpl->touchBlock('gorddate');
				}
				$fItems = new FItems('galery',false);
				$fItems->setWhere("pageId='".$pageVO->pageId."' and (itemIdTop is null or itemIdTop=0)");
				$fotoCnt = $fItems->getCount();
				$tpl->setVariable('FOTOTOTAL',$fotoCnt);

				/* UPLOAD INPUTS */
				$numInputs=3;
				for ($x=1;$x<$numInputs;$x++) {
					$tpl->setCurrentBlock('uploadinput');
					$tpl->setVariable('UPLOADINPUTLABEL','Foto '.$x.'.');
					$tpl->setVariable('UPLOADINPUTID',$x);
					$tpl->parseCurrentBlock();
				}
			} elseif ($pageVO->typeId=='blog') {
				//BLOG
				$tpl->touchBlock('fforum'.($user->pageVO->prop('forumSet')*1));
			}
      
			$thumbPropList = explode('/',$pageVO->getProperty('thumbCut',FConf::get('galery','thumbCut'),true));
			$thumbSizeList = explode('x',$thumbPropList[0]);
			$tpl->setVariable('GTHUMBWIDTH',$thumbSizeList[0]);
			$tpl->setVariable('GTHUMBHEIGHT',$thumbSizeList[1]);
			if($thumbPropList[1]=='crop') $tpl->touchBlock('galerythumbstyle2');
			
			$galeryincluded = $pageVO->getProperty('galeryincluded');
			if($galeryincluded) $tpl->touchBlock('galeryincluded');
			$tpl->touchBlock('galeryincludedblock');
				
			$tpl->setVariable('SIDEBAR',FText::textToTextarea($pageVO->prop('sidebar')));
			$tpl->setVariable('TOPBANNER',FText::textToTextarea($pageVO->prop('topbanner')));
				
		}

		$categoryId = (isset($pageVO->categoryId))?($pageVO->categoryId):(0);
		$arrTmp = FDBTool::getAll('select categoryId,name from sys_pages_category where typeId="'.$pageVO->typeId.'"');
		if(!empty($arrTmp)) $tpl->setVariable('CATOPTIONS',FCategory::getOptions($arrTmp,$categoryId));

		//---if pageParam = sa - more options to edit on page
		//--- template,categoryId,dateContent,locked
		if($user->pageParam=='sa') {
			$tpl->setVariable('LOCKEDOPTIONS',FCategory::getOptions(FLang::$ARRLOCKED,$pageVO->locked));
			$tpl->setVariable('PAGETEMPLATE',$pageVO->template);
			$tpl->setVariable('PAGEIDTOP',$pageVO->pageIdTop);
			//seo plain text description
			$tpl->setVariable('PAGEDESCRIPTIONID',$textareaIdDescription);
			$tpl->setVariable('PAGEDESCRIPTION',FText::textToTextarea($pageDesc));
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
		if($user->pageParam != 'a') {
			$tpl->touchBlock('leftpaneltab');
			$tpl->setVariable('SIDEBARID','sidebar');
		}
			
		if($data['__ajaxResponse']) {
			FAjax::addResponse('pageedit','$html',$tpl->get());
			FAjax::addResponse('call','jUIInit');
			if($pageVO->typeId=='galery')FAjax::addResponse('call','GaleryEdit.init','');
		} else {
			FBuildPage::addTab(array("MAINID"=>'pageedit',"MAINHEAD"=>'',"MAINDATA"=>$tpl->get()));
		}
	}
}