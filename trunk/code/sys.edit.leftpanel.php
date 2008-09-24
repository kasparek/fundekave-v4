<?php
if(empty($_REQUEST['rh'])) $rh=0; else $rh = $_REQUEST['rh']*1;

if(!isset($_SESSION['syseditsidebar'])) $_SESSION['syseditsidebar'] = 0;
$sidebarGroup = & $_SESSION['syseditsidebar'];
if(isset($_GET['rh'])) $sidebarGroup = (int) $_GET['rh'];

$category = new fCategory('sys_leftpanel','leftpanelId');
$category->ident = 'rhleftbar';
$category->setWhere("leftpanelGroup in (0".(($sidebarGroup>0)?(','.$sidebarGroup):('')).")");

$category->arrHead=array(LABEL_CATEGORY_GROUP,LABEL_CATEGORY_FUNCTION,LABEL_CATEGORY_ORDER);
$category->arrInputType=array("select","select",'text');
$category->arrClass=array('','','small');
$category->arrDbUsedCols=array('leftpanelGroup','functionId','ord');
$category->requiredCol = 'ord';

$arrtmp = $db->getAll('select functionId,name,function from sys_leftpanel_functions order by name');
foreach ($arrtmp as $row) $arr[$row[0]] = $row[1].' - '.$row[2];
$category->arrOption['functionId']=$arr;

$arr=array();
$arrtmp = $db->getAll('select leftpanelGroup from sys_leftpanel group by leftpanelGroup order by leftpanelGroup');
foreach ($arrtmp as $row) $arr[$row[0]]=$row[0];
	
$category->arrOption['leftpanelGroup']=$arr;

$tmptext= '<p><label for="typefilter">Skupina</label><select id="typefilter" onchange="location = \'?k='.$user->currentPageId.'&rh=\' + this.options[this.selectedIndex].value;">';
foreach ($arr as $k=>$v) $tmptext.='<option value="'.$v.'"'.(($v==$sidebarGroup)?(' selected="selected" '):('')).'>'.$v.'</option>';
$tmptext.='</select></p>';

$tmptext .= $category->getEdit();

$TOPTPL->addTab(array("MAINDATA"=>$tmptext));

$tmptext='';
if($sidebarGroup>0){
	$arr=$db->getAll("select pageId,name from sys_pages where leftpanelGroup='".$sidebarGroup."'");
	if(count($arr)>0){
		foreach ($arr as $page) $arrLink[]='<a href="?k='.$page[0].'">'.$page[1].'</a>';
		$tmptext = implode('<br/>',$arrLink);
	}
} else {
	$tmptext = LABEL_PAGES_DEFAULTFORALL;
}
$TOPTPL->addTab(array("MAINHEAD"=>LABEL_PAGES_USEDON,"MAINDATA"=>$tmptext));
?>