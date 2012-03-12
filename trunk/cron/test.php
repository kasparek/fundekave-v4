<?php
$q = "select text from sys_pages_items where itemId='190188'";
$q = "select content from sys_pages where pageId='sail2'";
$text = FDBTool::getOne($q);
echo $text;
echo '<hr>';
echo FSystem::postText($text);