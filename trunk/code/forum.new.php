<?php
$typeId = $user->currentPage['typeIdChild'];
$arrDefaultCategory = array('blog'=>318,'forum'=>301);
// blog or forum

if($user->idkontrol) {
	//--creating action
	$nazev = '';
	$ocem = '';
	if(isset($_REQUEST["add"])) {
		$ocem= fSystem::textins($_POST["ocem"]);
		$nazev= fSystem::textins($_POST["nazev"],0,0);
		if($nazev=='') fError::addError((($typeId=='forum')?(ERROR_FORUM_NAMEEMPTY):(ERROR_BLOG_NAMEEMPTY)));
		if(fPages::page_exist('name',$nazev)) fError::addError(($typeId=='forum')?(ERROR_FORUM_NAMEEXISTS):(ERROR_BLOG_NAMEEXISTS));
		if(!fError::isError()) {
			$fPageSave = new fPagesSaveTool($typeId);
			$newPageId = $fPageSave->savePage(array('name'=>$nazev,'categoryId'=>$arrDefaultCategory[$typeId],
      'description'=>$ocem,'userIdOwner'=>$user->gid));
			$user->cacheRemove('calendarlefthand');
			fError::addError(MESSAGE_SUCCESS_CREATE.': <a href="?k='.$newPageId.'">'.$nazev.'</a>');
			fHTTP::redirect($user->getUri());
		}
	}
	//--printing part
	$tpl = new fTemplateIT('forum.new.tpl.html');
	$tpl->setVariable('FORMACTION',$user->getUri());
	$tpl->setVariable('NAME',$nazev);
	$tpl->setVariable('DESC',$ocem);
	
} else {
	fError::addError(ERROR_RULES_CREATE);
	fHTTP::redirect($user->getUri());
}
$TOPTPL->addTab(array("MAINDATA"=>$tpl->get()));
?>