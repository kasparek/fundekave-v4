<?php
//--column locked
//--1-locked,
//--2-lokcked,invisible(visible only for owner),
//--3-looks like deleted(visible only in adminsection)
$arrType = array('forum'=>'Klub','galery'=>'Galerie','blog'=>'Blog');

$arrParamsDefs =  array(
'type' => '',
'cate' => -1,
'orde' => 0,
'lock' => -1,
'sear' => ''
);
if(!isset($_SESSION['syseditpages'])) $_SESSION['syseditpages'] = $arrParamsDefs;
$arrParams = & $_SESSION['syseditpages'];

if(isset($_REQUEST['type'])) if(array_key_exists($_REQUEST['type'],$arrType)) $arrParams['type'] = $_REQUEST['type']; else $arrParams['type'] = '';
if(isset($_REQUEST['cate'])) $arrParams['cate'] = $_REQUEST['cate']*1;
if(isset($_REQUEST['orde'])) $arrParams['orde'] = $_REQUEST['orde']*1;
if(isset($_REQUEST['lock'])) $arrParams['lock'] = $_REQUEST['lock']*1;
if(isset($_REQUEST['sear'])) $arrParams['sear'] = trim($_REQUEST['sear']);

$typeLength = strlen($arrParams['type']);
$dot = "select categoryId,".(($typeLength==0)?("concat(typeId,' - ',name)"):('name'))." from sys_pages_category where typeId".(($typeLength>0)?("='".$arrParams['type']."'"):(" in ('".implode("','",array_keys($arrType))."')"))." order by ord,name";
$arrKat = $db->getAll($dot);

//---POST section
if(isset($_POST['nav'])) {
    
    if(!empty($_POST['pcatn'])) {
        foreach($arrKat as $kat) $arrCategoryKeys[] = $kat[0]; 
		foreach ($_POST['pcatn'] as $k=>$v) {
		    $oldCat = (int) $_POST['pcato'][$k];
		    $newCat = (int) $v;
		    if(in_array($newCat,$arrCategoryKeys) && $oldCat!=$newCat) {
		      //if($newCat == 0) $newCat = 'null';
		      $dot = "update sys_pages set categoryId=".$newCat." where pageId='".$k."'";
			  $db->query($dot);
		    }
		}
	}
	fHTTP::redirect($user->getUri());
}

//---SHOW PART
$tpl = new fTemplateIT('sys.edit.pages.tpl.html');

$fPages = new fPages($type,$user->userVO->userId);
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
  $pager = fSystem::initPager($totalItems,$perPage);
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

$tpl->setVariable('LISTFORMACTION',$user->getUri());
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
	$tpl->setVariable('LLOCKED',$ARRLOCKED[$row[2]*1]);
	$tpl->parseCurrentBlock();
}

$TOPTPL->addTab(array("MAINDATA"=>$tpl->get()));
?>