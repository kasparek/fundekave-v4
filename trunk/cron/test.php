<?php
/*
$url = 'http://maps.google.com/maps/api/staticmap?size=170x170&markers=48.5988,-4.5614&markers=48.8266,-3.2017&markers=49.4559,-2.5311&markers=49.6464,-1.6217&markers=53.3193,-4.6455&markers=53.3197,-4.6443&markers=52.005,-4.9831&markers=51.7107,-5.0371&markers=50.1021,-5.5458&markers=50.154,-5.0657&markers=50.3113,-4.052&markers=50.5719,-2.4516&markers=50.6031,-2.4067&markers=50.7214,-2.0051&markers=50.7214,-2.0052&markers=50.645,-1.939&markers=49.4547,-2.5352&markers=48.8523,-3.0115&markers=49.7248,-2.1928&markers=50.7217,-2.006&sensor=false';
$md5 = md5($url);
$filename = 'tmp/gstatic/'.$md5.'.png';

if(!file_exists($filename)) {
  file_put_contents($filename,file_get_contents('http://maps.google.com/maps/api/staticmap?size=170x170&markers=48.5988,-4.5614&markers=48.8266,-3.2017&markers=49.4559,-2.5311&markers=49.6464,-1.6217&markers=53.3193,-4.6455&markers=53.3197,-4.6443&markers=52.005,-4.9831&markers=51.7107,-5.0371&markers=50.1021,-5.5458&markers=50.154,-5.0657&markers=50.3113,-4.052&markers=50.5719,-2.4516&markers=50.6031,-2.4067&markers=50.7214,-2.0051&markers=50.7214,-2.0052&markers=50.645,-1.939&markers=49.4547,-2.5352&markers=48.8523,-3.0115&markers=49.7248,-2.1928&markers=50.7217,-2.006&sensor=false'));
}

echo '<img src="http://fotobiotic.net/tmp/gstatic/'.$md5.'.png" />';

exit;
*/

$text = 'http://www.buzzfeed.com/toddvanluling/dead-bodies-on-mount-everest ahoj a dalsi link v jednom http://zpravy.idnes.cz/video-havajsky-vulkan-odemkl-brany-pekla-po-uboci-se-vali-more-lavy-1d5-/zahranicni.aspx?c=A110307_101223_zahranicni_aha a kt tomu neco

youtube link: http://www.youtube.com/watch?v=a44bcozEayY

<!-- <iframe width="560" height="315" src="http://www.youtube.com/embed/a44bcozEayY" frameborder="0" allowfullscreen></iframe> -->
externi link na obrazek <img src="http://inapcache.boston.com/universal/site_graphics/blogs/bigpicture/world_water_day_2012/bp1.jpg" />
text text http://fotobiotic.net/image/300x300/prop/idega/20091118_varime_I4UlP/dsc00718.jpg text text
text externi link: http://php.net/manual/en/domdocument.loadhtml.php
LInk na stranku  http://awake33.com/?k=sail1    jesti je to tab a dva
link na blog http://awake33.com/?i=189230-zavody-formosa-regatta
link na fotku http://fundekave.net/?i=189860
<div class="rightbox"><img src="http://fotobiotic.net/image/170x170/crop/idega/20091118_varime_I4UlP/dsc00718.jpg" /></div>
http://fotobiotic.net/image/170x170/crop/idega/20091118_varime_I4UlP/dsc00718.jpg ';
  
//$q = "select text from sys_pages_items where itemId='190188'";
$q = "select textLong from sys_pages_items where itemId='189495'"; //upsidedown s cestinou
$q = "select text from sys_pages_items where itemId='190323'"; 
//$q = "select content from sys_pages where pageId='sail2'";
echo $text = FDBTool::getOne($q);

echo "\n\n\n<hr>\n\n\n";

echo FSystem::postText($text);