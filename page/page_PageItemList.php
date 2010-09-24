<?php
include_once('iPage.php');
class page_ForumView implements iPage {
	
	/**
	 *  PROCESS FUNCTION
	 */	 	
	static function process($data) {
		$user = FUser::getInstance();

		if(empty($user->pageParam)) {
			
			//TODO: process data depend on form used	
			
		}

	}
	
	/**
	 * VIEW FUNCTION
	 */
	 static function build($data=array()) {
	 	//var setup
	 	$user = FUser::getInstance();
	 	$output = '';
	 	$template = 'page.items.list.tpl.html';
	 	$touchedBlocks = array();
	 	$vars = array();
	 	$itemId = 0;
	 	
		$perPage = BLOG_PERPAGE; //TODO: get some global/local perpage
		$pageNumUrlVar = FConf::get('pager','urlVar');
	 	$categoryId=0;
	 	if(isset($data['c'])) $categoryId = (int) $data['c']; //for category filtering
	 	$arrPagerExtraVars = array();
	 	if(!empty($user->whoIs)) $arrPagerExtraVars = array('who'=>$who);
	 	
	 	//FORM MODE - if in edit mode show edit form - blog,event from will redirect to detail view, only forum/foto form will show here
	 	
	  //HEADER
	  if(!empty($user->pageVO->content)) $vars['CONTENT'] = FSystem::postText($user->pageVO->content);
	 	//LIST ITEMS
	 	$fItems = new FItems('',FUser::logon());
		$fItems->setPage($user->pageVO->pageId);
		$fItems->hasReactions(false); //TODO: fix forum reactions, do not display reactions - they'll be displayd in detail - PROBLEM with forum reactions
		if($categoryId > 0) {
			$fItems->addWhere("categoryId='". $categoryId ."'");
		}
		if($user->itemVO) {
			$itemId = $user->itemVO->itemId;
			$fItems->addWhere("itemIdTop='".$user->itemVO->itemId."'"); //displaying reactions
		}
		$fItems->setOrder("if(dateStart,dateStart,dateCreated) desc, itemId desc");
		
		if($itemId > 0) {
			$arrPagerExtraVars['k'] = $user->pageVO->pageId;
			$arrPagerExtraVars['i'] = $itemId;
			FForum::updateReadedReactions($itemId,$user->userVO->userId);//update readed reactions
		} else {
			FItems::aFav($user->pageVO->pageId,$user->userVO->userId);//update readed
		}
		$pager = new FPager(0,$perPage,array('extraVars'=>$arrPagerExtraVars,'noAutoparse'=>1,'bannvars'=>array('i'),'manualCurrentPage'=>$manualCurrentPage));
		$from = ($pager->getCurrentPageID()-1) * $perPage;
		$fItems->getList($from,$perPage+1);
		$pager->totalItems = count($fItems->data);
		
		if($total > $perPage) {
			$pager->maybeMore = true;
			array_pop($fItems->data);
		}
		if($from > 0) $pager->totalItems += $from;
	
	if($total>0) {
				$pager->getPager();
				if ($pager->totalItems > $perPage) {
					$vars['TOPPAGER'] = $pager->links;
					$vars['BOTTOMPAGER'] = $pager->links;
				}
				$vars['ITEMS'] = $fItems->render($pageNum * $perPage, $perPage);
		} else {
		   $touchedBlocks[]='feedempty';
		}
							
	 	
	 	//HEADER
	 	//FORM
	 	//TOPPAGER
	 	//ITEMS
	 	//BOTTOMPAGER
	 	//vcalendar
	 	//feedempty
	 	
	  //render to template
	  $tpl = FSystem::tpl($template);
	  if(!empty($touchedBlocks)) $tpl->touchedBlock( $touchedBlocks );
		$tpl->setVariable($vars);
	  $output .= $tpl->get();
	 	
	 	//output
	 	FBuildPage::addTab(array("MAINDATA"=>$output));
	 }	 	
}