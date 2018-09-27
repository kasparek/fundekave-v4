<?php
include_once 'iPage.php';
class page_Registration implements iPage
{

    public static function process($data)
    {
        if (isset($data['addusr'])) {
            $user = FUser::getInstance();
            if (!FUser::logon()) {
                if(!$user->register($data)) {
                    FHTTP::redirect(FSystem::getUri());
                }
            }
        }

    }

    public static function build($data = array())
    {
        $cache = FCache::getInstance('s');
        if (($data = $cache->getData('reg', 'form')) !== false) {
            //$cache->invalidateData('reg', 'form');
        } else {
            $data = array('jmenoreg' => '', 'pwdreg1' => '', 'pwdreg2' => '', 'email' => '');
        }

        $tpl = FSystem::tpl('user.registration.tpl.html');

        if (!FUser::logon()) {
            $tpl->setVariable('FORMACTION', FSystem::getUri());
            $tpl->setVariable('NAME', $data['jmenoreg']);
            $tpl->setVariable('PWD1', $data['pwdreg1']);
            $tpl->setVariable('PWD2', $data['pwdreg2']);
            $tpl->setVariable('EMAIL', $data['email']);
            
            $captcha_x1 = mt_rand(1,5);
            $captcha_x2 = mt_rand(1,5);
            $sum = $captcha_x1 + $captcha_x2;
            $captcha_ref = md5($sum . FUser::LO);
            $tpl->setVariable('captcha_ref', $captcha_ref);
            $tpl->setVariable('captcha_x1', $captcha_x1);
            $tpl->setVariable('captcha_x2', $captcha_x2);
        } else {
            $tpl->setVariable('DUMMYNO', '');
        }

        FBuildPage::addTab(array("MAINDATA" => $tpl->get()));

    }
}
