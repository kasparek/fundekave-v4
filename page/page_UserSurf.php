<?php
include_once('iPage.php');
class page_UserSurf implements iPage {

	static function process() {
		$user = FUser::getInstance();
		$userId = $user->userVO->userId;
		$pageId = $user->pageVO->pageId;

		if(isset($_POST["insert"]) && $user->idkontrol) {
			$url = trim($_POST["surflink"]);
			if($url=='') fError::addError(FLang::$ERROR_SURF_URL);
			else {
				$sLinx = new FDBTool('sys_surfinie','surfId');
				$sLinx->addCol('userId',$userId);
				$sLinx->addCol('url',fSystem::textins($url,array('plainText'=>1)));
				$sLinx->addCol('name',fSystem::textins($_POST["surfdesc"],array('plainText'=>1)));
				$sLinx->addCol('public',($_POST["surfpublic"]*1));
				$sLinx->addCol('categoryId',($_POST['selcat']*1));
				$sLinx->addCol('dateCreated','NOW()',false);
				$dot = $sLinx->buildInsert();
				$db->query($dot);
				fHTTP::redirect(FUser::getUri());
			}
		}

		if(isset($_GET['d'])) {
			$deleteId = $_GET['d']*1;
			$doDelete = false;
			if($deleteId>0) {
				if(FRules::get($userId,$pageId,2)) $doDelete = true;
				elseif($db->getOne("select userId from sys_surfinie where surfId='".$deleteId."'")==$userId) $doDelete = true;
			}
			if($doDelete===true) {
	   $db->query('delete from sys_surfinie where surfId= "'.$deleteId.'"');
	   fHTTP::redirect(FUser::getUri());
			}
		}

	}

	static function build() {
		$user = FUser::getInstance();
		$userId = $user->userVO->userId;
		$pageId = $user->pageVO->pageId;

		if(isset($_REQUEST["sc"])) $kat = $_REQUEST["sc"]*1; else $kat=0;
		if(isset($_REQUEST["sm"])) $showAll = $_REQUEST["sm"]*1; else $showAll=0;

		$tpl = new fTemplateIT("user.surf.tpl.html");
		$tpl->setVariable('FORMACTION',FUser::getUri());
		$tpl->setVariable('SELECTEDCATEGORY',$kat);

		$options = '';
		$q = "select categoryId,name from sys_pages_category where typeId='surf' order by name";
		$arr = FDBTool::getAll($q, 'surf', 'categ', 's');
		foreach ($arr as $row) {
			$options .= '<option value="'.$row[0].'"'.(($row[0]==$kat)?(' selected="selected"'):('')).'>'.$row[1].'</option>';
			if($row[0]==$kat) $tpl->setVariable('CATEGORYNAME',$row[1]);
		}
		$tpl->setVariable('CATOPTIONS',$options);
		if($showAll==1) $tpl->touchBlock('showall');

		$qLinx = new FDBTool('sys_surfinie','surfId');
		$qLinx->setSelect('surfId,userId,url,name');
		$qLinx->setWhere("categoryId='".$kat."' and (userId='".$userId."'".(($showAll==1)?(' or public=1'):('')).")");
		$qLinx->setOrder('dateCreated desc');

		$total = $qLinx->getCount();

		if($total>0) {

			$od = 1;
			if($total>DEFAULT_PERPAGE) {
				$pager = fSystem::initPager($total,DEFAULT_PERPAGE,array('extraVars'=>array('sc'=>$kat,'sm'=>$showAll)));
				$od = ($pager->getCurrentPageID()-1) * DEFAULT_PERPAGE;
				$tpl->setVariable('BOTTOMPAGER',$pager->links);
			}

			$qLinx->setLimit($od,DEFAULT_PERPAGE);
			$arr = $qLinx->getContent();

			foreach ($arr as $row){
				$tpl->setCurrentBlock('result');
				if($userId==$row[1] || FRules::get($userId,$pageId,2)) {
					$tpl->setVariable('DELETELINK',FUser::getUri('d='.$row[0]));
				}
				if($userId!=$row[1]) {
					$tpl->setVariable('AUTHORLINK','?k=finfo&who='.$row[1]);
					$tpl->setVariable('AUTHORNAME',FUser::getgidname($row[1]));
				}
				$tpl->setVariable('DESCRIPTION',$row[3]);
				 
				$tpl->setVariable('LINKPRINT',((strlen($row[2]>30)?(substr($row[2],0,30).'...'):($row[2]))));
				$tpl->setVariable('LINKTITLE',$row[2]);
				$tpl->setVariable('LINKURL',$row[2]);
				$tpl->parseCurrentBlock();
				 
			}
		}

		FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));
	}
}