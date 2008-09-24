<?php
/**
 * TODO: add textarea for editing category description
 * typeId - page types - top,blog,culture,galery,forum
 */
$arrType = $db->getCol('select typeId from sys_pages_category group by typeId order by typeId');

if(!isset($_SESSION['syseditcategory'])) $_SESSION['syseditcategory'] = 'forum';
$type = & $_SESSION['syseditcategory'];
if(isset($_GET['f'])) if(in_array($_GET['f'],$arrType)) $type = trim($_GET['f']);

$tmptext= '<p><label for="typefilter">Skupina</label><select id="typefilter" onchange="location = \'?k='.$user->currentPageId.'&f=\' + this.options[this.selectedIndex].value;">';
foreach ($arrType as $k=>$v) $tmptext.='<option value="'.$v.'"'.(($v==$type)?(' selected="selected" '):('')).'>'.$v.'</option>';
$tmptext.='</select></p>';

$category = new fCategory('sys_pages_category','categoryId');
$category->addWhere("typeId='".$type."'");
$category->arrSaveAddon = array('typeId'=>$type);

$tmptext .= $category->getEdit();

$TOPTPL->addTab(array("MAINDATA"=>$tmptext));
?>