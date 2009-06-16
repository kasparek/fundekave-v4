<?php
include_once('iPage.php');
class page_ItemsTaggingRandom implements iPage {

	static function process($data) {

		if(isset($data['save'])) {
			if(!empty($data['item'])) {
				foreach ($data['item'] as $k=>$v) {
					$v = trim($v);
					if(!empty($v) && $k>0) {
						$arrV = explode(",",$v);
						$arrTestedTags = array();
						foreach ($arrV as $tag) {
							$tag = FSystem::textins($tag,array('plainText'=>1));
							if(!empty($tag)) $arrTestedTags[] = $tag;
						}
						if(!empty($arrTestedTags)) { $v = implode(",",$arrTestedTags);
						if(FItems::itemExists($k)) $itemTags[$k] = $v;
						}
					}
				}
				if(!empty($itemTags)) {
					//---tags save
					foreach ($itemTags as $k=>$v) {
						FItems::tag($k,FUser::logon(),0,$v);
					}
					FHTTP::redirect(FUser::getUri());
				}
			}
		}

	}

	static function build() {

		$fItems = new FItems();
		$fItems->showPageLabel = true;
		$fItems->showTag = false;
		$fItems->showRating = false;
		$fItems->initData('galery',false,true);
		$fItems->cacheResults = 's';

		$fItems->addJoin('left join sys_pages_items_tag as it on it.itemId=i.itemId');
		//$fItems->addSelect('(select count(1) from sys_pages_items_tag as att where att.tag is not null and att.itemId=i.itemId) as tags');
		$fItems->addSelect('count(it.itemId) as tags');
		$fItems->setGroup('i.itemId');
		$fItems->setOrder('tags,rand()');

		$tpl = new FTemplateIT('items.tagging.tpl.html');

		$fItems->getData(0,15);

		if(!empty($fItems->arrData)) {
			while ($fItems->arrData) {
				$arr = $fItems->parse();
				$tpl->setCurrentBlock('result');
				$tpl->setVariable('ITEMID',$arr['itemId']);
				$tpl->setVariable("ITEM",$fItems->show());
				$tpl->parseCurrentBlock();
			}
		}

		FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));
		
	}
}