<?php
class FBlog {
	 
	function __construct() {
		 
	}

	static function process($data) {
		$user = FUser::getInstance();
		$returnItemId = 0;
		$pageId = $user->pageVO->pageId;
		if(FRules::get($user->userVO->userId,$pageId,2) === true) {
			if(!isset($data['del'])) {
				$itemVO = new ItemVO();
				$itemVO->addon = FSystem::textins($data['nadpis'],array('plainText'=>1));
				$itemVO->text = FSystem::textins($data['textclanku']);
				$author = FSystem::textins($data['autor'],array('plainText'=>1));
				$itemVO->name = ((empty($author))?($user->userVO->name):($author));

				$data['datum'] = FSystem::textins($data['datum'],array('plainText'=>1));
				$data['datum'] = FSystem::switchDate($data['datum']);
				if(FSystem::isDate($data['datum'])) $itemVO->dateCreated = $data['datum'];

				if(isset($data['nid'])) {
					if($data['nid']>0) {
						$itemVO->itemId = (int) $data['nid'];
					}
				}

				if(isset($data['category'])) $itemVO->categoryId = (int) $data['category'];

				if($data['public'] == 1) $itemVO->public = 1;

				if(empty($itemVO->itemId)) {
					$itemVO->userId = $user->userVO->userId;
					$itemVO->pageId = $pageId;
					$itemVO->typeId = 'blog';
				}
				$returnItemId = $itemVO->save();

				///properties
				ItemVO::setProperty($returnItemId,'forumSet',(int) $data['forumset']);

				FUserDraft::clear(FBlog::textAreaId());
			} else {
				$itemVO = new ItemVO();
				$itemVO->itemId = (int) $aFormValues['nid'];
				$itemVO->delete();
				$returnItemId = 0;
			}
			$cache = FCache::getInstance('f');
			$cache->invalideteGroup('lastBlogPost');
			 
		} else {
			//---DO AJAX ERROR - cant save data - no rules
			//echo 'error::rules';
		}
		return $returnItemId;
	}
	static function textAreaId() {
		$user = FUser::getInstance();
		return 'Blog'.$user->pageVO->pageId;
	}
	static function getEditForm($itemId) {
		$user = FUser::getInstance();
	  
		$textAreaId = fBlog::textAreaId();
	  
		$tpl = new FTemplateIT('blog.editform.tpl.html');
		$tpl->setVariable('FORMACTION',FUser::getUri('m=blog-submit'));
		$tpl->setVariable('PAGEID',$user->pageVO->pageId);
		if($itemId > 0) {
			$itemVO = new ItemVO($itemId,false,array('type'=>'blog'));

			if($itemVO->load()) {
				$tpl->setVariable('EDITADDON',$itemVO->addon);
				$tpl->setVariable('EDITDATE',$itemVO->dateCreatedLocal);
				$tpl->setVariable('EDITTEXT',$itemVO->text);
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
				$tpl->touchBlock('fforum'.ItemVO::getProperty($itemVO->itemId,'forumSet',FPages::getProperty($user->pageVO->pageId,'forumSet',2)));
				///categories
				if($opt = FCategory::getOptions($user->pageVO->pageId,$itemVO->categoryId,true,''))
				$tpl->setVariable('CATEGORYOPTIONS',$opt);
			}
		} else {
			$tpl->setVariable('EDITDATE',Date("d.m.Y"));
			if($draft = FUserDraft::get($textAreaId)) $tpl->setVariable('EDITTEXT',$draft);
		}

		$tpl->setVariable('TEXTID',$textAreaId);
		//---have to be called js functions: draftSetEventListeners, initInsertToTextarea

		return $tpl->get();
	}
	static function listAll($itemId = 0,$editMode = false) {
		$user = FUser::getInstance();
		$itemId = (int) $itemId;
		$perPage = BLOG_PERPAGE;
	  
		if(FRules::getCurrent(2)) {
			if(empty($user->pageParam) && !$itemId) {
				FSystem::secondaryMenuAddItem(FUser::getUri('m=blog-edit&d=item:0',$user->pageVO->pageId,'a'), FLang::$LABEL_ADD, 1, '', 'fajaxa');
			}
		}
	  
		$tpl = new FTemplateIT('blog.list.tpl.html');
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

		if(!empty($user->pageVO->content)) $tpl->setVariable('CONTENT',$user->pageVO->content);

		if($itemId > 0) {

			$itemVO = new ItemVO($itemId,true,array('type'=>'blog','showComments'=>true));
			$tpl->setVariable('ITEMS', $itemVO->render());
			 
		} else {

			$itemRenderer = new FItemsRenderer();

			$fItems = new FItems('blog',false,$itemRenderer);
			$fItems->addWhere("pageId='".$user->pageVO->pageId."'");
			$fItems->addWhere('itemIdTop is null');
			$fItems->setOrder("dateCreated desc");

			$render = $fItems->render($currentPage * $perPage, $perPage);
			
			if(!empty($render)){
				FItems::aFav($user->pageVO->pageId,$user->userVO->userId,$user->pageVO->cnt);
				$tpl->setVariable('ITEMS', $render);
				//TODO:refactor title, label, desc manipulation dependency on detail
				//if($itemId>0) $user->pageVO->name = $fItems->currentHeader;
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