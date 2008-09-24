<?php
$category = new fCategory('sys_leftpanel_functions','functionId');
$category->ident = 'rhfunctions';

$category->arrHead=array(LABEL_CATEGORY_FUNCTION,LABEL_CATEGORY_NAME,LABEL_CATEGORY_PUBLIC);
$category->arrInputType=array("text","text",'public');
$category->arrClass=array('','','');
$category->arrDbUsedCols=array('function','name','public');
$category->requiredCol = 'function';
$category->setOrder('name');

$tmptext = $category->getEdit();

$TOPTPL->addTab(array("MAINDATA"=>$tmptext));
?>