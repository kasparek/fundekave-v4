<?php
include_once('iPage.php');
class page_GaleryList implements iPage {

	static function process() {

	}

	static function build() {
		$user = FUser::getInstance();

		//category list
		$category = new FCategory('sys_pages_category','categoryId');
		FBuildPage::addTab(array("MAINDATA"=>$category->getList('galery')));

		$fPages = new FPages('galery',$user->userVO->userId);
		if(!empty($category->selected)) {
			$fPages->addWhere("p.categoryId='".$category->selected[0]."'");
		}

		$totalItems = $fPages->getCount();
		$from = 0;

		$tpl = new fTemplateIT('galery.list.tpl.html');

		if($totalItems > GALERY_PERPAGE) {
			$pager = FSystem::initPager($totalItems,GALERY_PERPAGE);
			$from =($pager->getCurrentPageID()-1) * GALERY_PERPAGE;
			$tpl->setVariable("PAGER",$pager->links);
		}

		$fPages->setSelect("p.pageId,p.name,p.userIdOwner,date_format(dateContent,'{#date_local#}') as datumcz,description,date_format(dateContent,'{#date_iso#}') as diso");
		$fPages->setOrder("dateContent desc,pageId desc");
		$fPages->setLimit($from,GALERY_PERPAGE);
		$arrgal = $fPages->getContent();

		if(!empty($arrgal)) {
			$fItems = new FItems();
			$fItems->initData('galery',$user->userVO->userId,true);
			$fItems->setOrder('i.hit desc');
			$fItems->showTooltip = false;
			$fItems->showText = false;
			$fItems->showTag = false;
			$fItems->showPageLabel = false;
			$fItems->showRating = false;
			$fItems->showHentryClass = false;
			$fItems->openPopup = false;
			$fItems->showPocketAdd = false;

			foreach ($arrgal as $gal) {
				$fItems->setWhere('p.pageId="'.$gal[0].'"');
				$fItems->getData(0,1);
				$fItems->parse();
				$fotoThumb = $fItems->show();
				$tpl->setCurrentBlock('galery');
				$tpl->setVariable("THUMB",$fotoThumb);
				$tpl->setVariable("PAGEID",$gal[0]);
				$tpl->setVariable("PAGELINK",'?k='.$gal[0]);
				$tpl->setVariable("PAGENAME",$gal[1]);
				$tpl->setVariable("DATELOCAL",$gal[3]);
				$tpl->setVariable("DATEISO",$gal[5]);
				$tpl->setVariable("GALERYTEXT",$gal[4]);
				$tpl->parseCurrentBlock();
			}
		}
		FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));
	}
}