<?php
if(isset($_POST['addusr'])) {
	$user = FUser::getInstance();
	$user->register();
}
$tpl = new fTemplateIT('user.registration.tpl.html');
if (!$user->idkontrol) {
	$tpl->setVariable('FORMACTION',FUser::getUri());
} else {
	$tpl->setVariable('DUMMYNO','');
}
$TOPTPL->addTab(array("MAINDATA"=>$tpl->get()));