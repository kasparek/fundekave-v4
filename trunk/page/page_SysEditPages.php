<?php
include_once('iPage.php');
class page_SysEditPages implements iPage {

	static function process($data) {
		$cache = FCache::getInstance('s');
		$arrParams = $cache->getData('Epages','filtr');
		if(isset($data['type'])) if(array_key_exists($data['type'],$arrType)) $arrParams['type'] = $data['type']; else $arrParams['type'] = '';
		if(isset($data['cate'])) $arrParams['cate'] = $data['cate']*1;
		if(isset($data['orde'])) $arrParams['orde'] = $data['orde']*1;
		if(isset($data['lock'])) $arrParams['lock'] = $data['lock']*1;
		if(isset($data['sear'])) $arrParams['sear'] = trim($data['sear']);
		$cache->setData($arrParams);

		$typeLength = strlen($arrParams['type']);
		$dot = "select categoryId,".(($typeLength==0)?("concat(typeId,' - ',name)"):('name'))." from sys_pages_category where typeId".(($typeLength>0)?("='".$arrParams['type']."'"):(" in ('".implode("','",array_keys($arrType))."')"))." order by ord,name";
		$arrKat = FDBTool::getAll($dot,'cat','Epages','l');

		if(isset($_POST['nav'])) {
			if(!empty($_POST['pcatn'])) {
				foreach($arrKat as $kat) $arrCategoryKeys[] = $kat[0];
				foreach ($_POST['pcatn'] as $k=>$v) {
					$oldCat = (int) $_POST['pcato'][$k];
					$newCat = (int) $v;
					if(in_array($newCat,$arrCategoryKeys) && $oldCat!=$newCat) {
						$dot = "update sys_pages set categoryId=".$newCat." where pageId='".$k."'";
						FDBTool::query($dot);
					}
				}
			}
			FHTTP::redirect(FUser::getUri());
		}

	}

	static function build() {
		//--column locked
		//--1-locked,
		//--2-lokcked,invisible(visible only for owner),
		//--3-looks like deleted(visible only in adminsection)
		$arrType = array('forum'=>'Klub','galery'=>'Galerie','blog'=>'Blog');

		$cache = FCache::getInstance('s');
		if(false === ($arrParams = $cache->getData('Epages','filtr'))) {
			$arrParams =  array('type' => '', 'cate' => -1, 'orde' => 0, 'lock' => -1, 'sear' => '' );
			$cache->setData($arrParams);
		}

		$typeLength = strlen($arrParams['type']);
		$dot = "select categoryId,".(($typeLength==0)?("concat(typeId,' - ',name)"):('name'))." from sys_pages_category where typeId".(($typeLength>0)?("='".$arrParams['type']."'"):(" in ('".implode("','",array_keys($arrType))."')"))." order by ord,name";
		$arrKat = FDBTool::getAll($dot,'cat','Epages','l');

		//---SHOW PART
		$user = FUser::getInstance();

		$tpl = new FTemplateIT('sys.edit.pages.tpl.html');

		$fPages = new FPages($type,$user->userVO->userId);
		$fPages->sa = true;
		if($arrParams['cate'] > 0) $fPages->addWhere("p.categoryId='".$arrParams['cate']."'");
		if($typeLength>0) $fPages->addWhere("p.typeId='".$arrParams['type']."'");
		else $fPages->addWhere("p.typeId in ('".implode("','",array_keys($arrType))."')");
		if(!empty($arrParams['sear'])) $fPages->addWhereSearch('name',$arrParams['sear']);
		if($arrParams['lock']>0) $fPages->addWhere("p.locked='".$arrParams['lock']."'");
		$fPages->setOrder(($arrParams['orde']==1)?('name'):('dateCreated desc'));
		$fPages->setSelect('p.pageId,p.categoryId,p.locked,p.name');
		$totalItems = $fPages->getCount();
		$perPage = ADMIN_PERPAGE;
		$from = 0;
		if($totalItems>$perPage) {
			$pager = new FPager($totalItems,$perPage);
			$from = ($pager->getCurrentPageID()-1) * $perPage;
			$tpl->setVariable('PAGER',$pager->links);
		}

		$arr = $fPages->getContent($from,$perPage);


		$tpl->setVariable('CURRENTPAGEID',$user->pageVO->pageId);

		$options='';
		foreach ($arrType as $k=>$v) {
			$options .= '<option value="'.$k.'"'.(($k==$arrParams['type'])?(' selected="selected"'):('')).'>'.$v.'</option>';
		}
		$tpl->setVariable('TYPEOPTIONS',$options);
		$tpl->setVariable('LISTFORMACTION',FUser::getUri());
		if($arrParams['order']==1) $tpl->touchBlock('sortabc');

		$options='';
		foreach ($arrKat as $kateg) {
			$options .= '<option value="'.$kateg[0].'"'.(($kateg[0]==$arrParams['cate'])?(' selected="selected"'):('')).'>'.$kateg[1].'</option>';
		}
		$tpl->setVariable('FILTROPTIONS',$options);
		$options='';
		foreach ($ARRLOCKED as $k=>$v) {
			$options .= '<option value="'.$k.'"'.(($k==$arrParams['lock'])?(' selected="selected"'):('')).'>'.$v.'</option>';
		}
		$tpl->setVariable('LOCKOPTIONS',$options);
		$tpl->setVariable('SEARCH',$arrParams['sear']);

		while($arr) {
			$row = array_shift($arr);
			$options='';
			foreach ($arrKat as $kat) {
				$options .= '<option value="'.$kat[0].'"'.(($kat[0]==$row[1])?(' selected="selected"'):('')).'>'.$kat[1].'</option>';
			}
			$tpl->setCurrentBlock('result');
			$tpl->setVariable('LPAGEID',$row[0]);
			$tpl->setVariable('LEDITLINK',$row[0].'sa');
			$tpl->setVariable('LCATEGORYID',$row[1]);
			$tpl->setVariable('LCATOPTIONS',$options);
			$tpl->setVariable('LNAME',$row[3]);
			$tpl->setVariable('LLOCKED',FLang::$ARRLOCKED[$row[2]*1]);
			$tpl->parseCurrentBlock();
		}

		FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));
	}
}