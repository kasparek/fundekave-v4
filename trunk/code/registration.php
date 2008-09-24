<?php
$user->register();
$tpl = new fTemplateIT('user.registration.tpl.html');
if (!$user->idkontrol) {
	$tpl->setVariable('FORMACTION',$user->getUri());
} else {
	$tpl->setVariable('DUMMYNO','');
}
$TOPTPL->addTab(array("MAINDATA"=>$tpl->get()));
?>