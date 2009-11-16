<?php
class FBlog {

	function __construct() {
			
	}

	static function process($data) {
		$action = $data['action'];
		$user = FUser::getInstance();
		$returnItemId = 0;
		$pageId = $user->pageVO->pageId;
		if(FRules::get($user->userVO->userId,$pageId,2) === true) {
			if($action === 'save') {
				$itemVO = new ItemVO();
				$itemVO->addon = FSystem::textins($data['nadpis'],array('plainText'=>1));
				$itemVO->text = FSystem::textins($data['textshort']);
				$itemVO->textLong = FSystem::textins($data['textlong']);
				$author = FSystem::textins($data['autor'],array('plainText'=>1));
				$itemVO->name = ((empty($author))?($user->userVO->name):($author));

				$data['datum'] = FSystem::textins($data['datum'],array('plainText'=>1));
				$data['datum'] = FSystem::switchDate($data['datum']);
				if(FSystem::isDate($data['datum'])) $itemVO->dateCreated = $data['datum'];

				if(!empty($data['item'])) $itemVO->itemId = (int) $data['item'];

				if(!empty($data['categoryNew'])) {
					$data['category'] = FCategory::tryGet( $data['categoryNew'], $pageId);
				}
				if(!empty($data['category'])) $itemVO->categoryId = (int) $data['category'];

				$itemVO->public = (int) $data['public'];

				$newItem=false;
				if(empty($itemVO->itemId)) {
					$itemVO->userId = $user->userVO->userId;
					$itemVO->pageId = $pageId;
					$itemVO->typeId = 'blog';
					$newItem=true;
				}
				
				//commented = connectio
				if(!empty($data['commented'])) {
					
					$objekt = $data['commented'];
				//---check for item
					if(preg_match("/[&?|]i=([0-9]*)/" , $objekt, $matches)) {
						//check if it is item
						if(FItems::itemExists($matches[1])) {
							$itemIdBottom = $matches[1];
							$objekt = '';
						}
					} else if(preg_match("/[&?|]k=([0-9a-zA-Z]*)/" , $objekt, $matches)) {
						if(FPages::page_exist('pageId',$matches[1])) {
							$pageIdBottom = $matches[1];
							$objekt = '';
						}
					}
					
					if(!empty($itemIdBottom)) $itemVO->itemIdBottom = $itemIdBottom;
					if(!empty($pageIdBottom)) $itemVO->pageIdBottom = $pageIdBottom;
					
				}
				
				$returnItemId = $itemVO->save();

				///properties
				ItemVO::setProperty($returnItemId,'forumSet',(int) $data['forumset']);
				FError::addError(FLang::$MESSAGE_SUCCESS_SAVED,1);
				if($newItem===true) FAjax::redirect(FSystem::getUri('i='.$itemVO->itemId,$pageId,'u'));
			} else if($action==='delete') {
				$itemVO = new ItemVO();
				$itemVO->itemId = (int) $data['item'];
				$itemVO->delete();
				$returnItemId = 0;

				FError::addError(FLang::$LABEL_DELETED_OK,1);
				FAjax::redirect(FSystem::getUri('',$pageId,''));
			}
			$cache = FCache::getInstance('f');
			$cache->invalidateGroup('lastBlogPost');
				
				
				

		} else {
			FError::addError(FLang::$ERROR_RULES_CREATE);
		}



		return $returnItemId;
	}
	static function textAreaId() {
		$user = FUser::getInstance();
		return 'Blog'.$user->pageVO->pageId;
	}
	static function getEditForm($itemId) {
		$user = FUser::getInstance();
		 
		$textAreaIdShort = FBlog::textAreaId().'short';
		$textAreaIdLong = FBlog::textAreaId().'long';
		 
		$tpl = FSystem::tpl('blog.editform.tpl.html');
		$tpl->setVariable('FORMACTION',FSystem::getUri('m=blog-submit'));
		$tpl->setVariable('PAGEID',$user->pageVO->pageId);

		$textShort = '';
		$textLong = '';
			
		$selectedCategory = 0;
		if($itemId > 0) {
			$itemVO = new ItemVO($itemId,false,array('type'=>'blog'));
				

			if($itemVO->load()) {

				$tpl->setVariable('EDITADDON',$itemVO->addon);
				$tpl->setVariable('EDITDATE',$itemVO->dateCreatedLocal);

				$textShort = $itemVO->text;
				$textLong = $itemVO->textLong;

				$tpl->setVariable('EDITAUTOR',$itemVO->name);
				$tpl->touchBlock('newdelete');
				$tpl->setVariable('EDITID',$itemId);
				if($itemVO->public == 0) {
					$tpl->touchBlock('classnotpublic');
					$tpl->touchBlock('headernotpublic');
				} else {
					$tpl->touchBlock('statpublic');
				}
				///properties
				$tpl->touchBlock('fforum'.ItemVO::getProperty($itemVO->itemId,'forumSet',PageVO::getProperty($user->pageVO->pageId,'forumSet',2)));
				$selectedCategory = $itemVO->categoryId;
				
				if(!empty($itemVO->itemIdBottom)) {
					$bottom = new ItemVO($itemVO->itemIdBottom,true);
					$tpl->setVariable('COMMENTEDITEM',$bottom->render());
				}
			}
		} else {
				
			$tpl->setVariable('EDITDATE',Date("d.m.Y"));
			$tpl->setVariable('EDITAUTOR',$user->userVO->name);
				
		}
		
		///categories
		if($opt = FCategory::getOptions($user->pageVO->pageId,$selectedCategory,true,'')) $tpl->setVariable('CATOPTIONS',$opt);

		$tpl->setVariable('EDITTEXTSHORT',$textShort);
		$tpl->setVariable('EDITTEXT',$textLong);

		$tpl->setVariable('TEXTIDSHORT',$textAreaIdShort);
		$tpl->setVariable('TEXTID',$textAreaIdLong);

		return $tpl->get();
	}
	static function listAll($itemId = 0,$editMode = false) {
		$user = FUser::getInstance();
		$itemId = (int) $itemId;
		$perPage = BLOG_PERPAGE;
					 
		if(FRules::getCurrent(2)) {
			if(empty($user->pageParam) && !$itemId) {
				FMenu::secondaryMenuAddItem(FSystem::getUri('m=blog-edit&d=item:0',$user->pageVO->pageId,'a'), FLang::$LABEL_ADD);
			}
			if($user->pageParam=='a') return;
		}
		 
		$tpl = FSystem::tpl('blog.list.tpl.html');
		if($user->idkontrol) $tpl->touchBlock('logged');

		//--edit mode
		if($editMode === true) {
			if(FRules::get($user->userVO->userId,$user->pageVO->pageId,2)) {
				$tpl->setVariable('EDITFORM',FBlog::getEditForm($itemId));
			}
		}

		$currentPage = 0;
		if(empty($itemId)) {
			if($user->pageVO->cnt > $perPage) {
				$pager = new FPager($user->pageVO->cnt,$perPage);
				$tpl->setVariable('BOTTOMPAGER',$pager->links);
				$currentPage = $pager->getCurrentPageID()-1;
			}
		}

		if($itemId > 0) {
			
			$extraParams = array('type'=>'blog','showComments'=>true); 
			if($user->pageParam=='u') {
				$extraParams['showComments'] = false;
			}
			$extraParams['showDetail'] = true;
			$itemVO = new ItemVO($itemId,true,$extraParams);
			$itemVO->saveOnlyChanged = true;
			$itemVO->set('hit',$itemVO->hit+1);
			$itemVO->save();
			$user->pageVO->title = $user->pageVO->name;
			$user->pageVO->name = $itemVO->addon;
			$tpl->setVariable('ITEMS', $itemVO->render());
			
		} else {

			if(!empty($user->pageVO->content)) $tpl->setVariable('CONTENT',$user->pageVO->content);
			$itemRenderer = new FItemsRenderer();
			$fItems = new FItems('blog',false,$itemRenderer);
			$fItems->addWhere("pageId='".$user->pageVO->pageId."'");
			if(isset($_REQUEST['c'])) {
				$fItems->addWhere("categoryId='". (int) $_REQUEST['c'] ."'");	
			} 
			$fItems->addWhere('itemIdTop is null');
			$fItems->setOrder("dateCreated desc");

			$render = $fItems->render($currentPage * $perPage, $perPage);
				
			if(!empty($render)){
				FItems::aFav($user->pageVO->pageId,$user->userVO->userId,$user->pageVO->cnt);
				$tpl->setVariable('ITEMS', $render);
			}
			
		}

		return $tpl->get();
			
	}

	/**
	 * callback function when processing forum attached to gallery
	 * @return void
	 */
	static function callbackForumProcess() {
		//---clear cache
		$cache = FCache::getInstance('f');
		$cache->invalidateGroup('lastForumPost');
		$cache->invalidateGroup('lastBlogPost');
	}
}