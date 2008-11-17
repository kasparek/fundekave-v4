<?php
require(INIT_FILENAME);

$fuvatarConfig = array(
'targetFtp'=>'',
'targetUrl'=>'../fuvatar.php',
'targetJpg'=>'source.jpg',
'targetAnimGif'=>'source.gif',
'refresh'=>3000,
'width'=>320,
'height'=>240
);

$fUvatar = new fUvatar($fuvatarConfig);
$fUvatar->process();