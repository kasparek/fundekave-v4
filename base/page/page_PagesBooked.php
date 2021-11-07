<?php
include_once 'iPage.php';
class page_PagesBooked implements iPage
{

    public static function process($data)
    {

    }

    public static function build($data = array())
    {
        FMenu::secondaryMenuAddItem(FSystem::getUri('t=forum', '', ''), FLang::$LABEL_FORUMS);
        FMenu::secondaryMenuAddItem(FSystem::getUri('t=blog', '', ''), FLang::$LABEL_BLOGS);
        FMenu::secondaryMenuAddItem(FSystem::getUri('t=galery', '', ''), FLang::$LABEL_GALERIES);

        $type = '';
        if (isset($data['__get']['t'])) {
            $type = $data['__get']['t'];
        }

        if (!isset(FLang::$TYPEID[$type])) {
            $type = 'forum';
        }

        $user                      = FUser::getInstance();
        $user->pageVO->showHeading = false;

        $user      = FUser::getInstance();
        $bookOrder = $user->userVO->getXMLVal('settings', 'bookedorder') * 1;

        //---template init
        $tpl = FSystem::tpl('pages.favorite.tpl.html');

        $userId = $user->userVO->userId;

        $fPages = new FPages($type, $user->userVO->userId);
        $fPages->setWhere('sys_pages.userIdOwner="' . $userId . '" and sys_pages.locked<3');
        if ($type != 'galery') {
            if ($bookOrder == 1) {
                $fPages->setOrder('name');
            } else {
                $fPages->setOrder('newCnt desc,sys_pages.name');
            }

        } else {
            $fPages->setOrder('dateCreated desc');
        }
        $arraudit = $fPages->getContent();
        if (count($arraudit) > 0) {
            $tpl->setVariable('PAGELINKSOWN', $fPages->printPagelinkList($arraudit, array('noitem' => true)));
            $newSum = 0;
            foreach ($arraudit as $forum) {
                $newSum += $forum->unreaded;
            }

            if ($newSum > 0) {
                $tpl->setVariable('OWNERNEW', $newSum);
            }

        } else {
            $tpl->setVariable('CREATETYPE', FSystem::getUri('t=' . $type, 'foalla'));
            $tpl->setVariable('TYPE', FLang::$TYPEID[$type]);
        }

        //vypis oblibenych
        $fPages = new FPages($type, $user->userVO->userId);
        $fPages->setWhere('f.book="1" and sys_pages.userIdOwner!="' . $userId . '" and sys_pages.locked<2');
        if ($bookOrder == 1) {
            $fPages->setOrder('sys_pages.name');
        } else {
            $fPages->setOrder('(sys_pages.cnt-favoriteCnt) desc,sys_pages.name');
        }

        $arraudit = $fPages->getContent();

        if (count($arraudit) > 0) {
            $tpl->setVariable('PAGELINKSBOOKED', $fPages->printPagelinkList($arraudit, array('noitem' => true)));
            $newSum = 0;
            foreach ($arraudit as $forum) {
                $newSum += $forum->unreaded;
            }

            if ($newSum > 0) {
                $tpl->setVariable('BOOKEDNEW', $newSum);
            }

        } else {
            $tpl->setVariable('FINDFAVORITE', FSystem::getUri('', 'foall', $type));
        }

        //vypis novych
        $fPages = new FPages('', $user->userVO->userId);
        $fPages->setWhere('f.pageId=sys_pages.pageId and f.book="0" and f.userId!=sys_pages.userIdOwner and sys_pages.userIdOwner!="' . $userId . '" and sys_pages.locked < 2');
        $fPages->addWhere('datediff(now(),sys_pages.dateCreated)<10');
        $fPages->setOrder('sys_pages.dateCreated desc');
        $fPages->setLimit(0, 7);
        $arraudit = $fPages->getContent();

        if (!empty($arraudit)) {
            $tpl->setVariable('PAGELINKSNEW', $fPages->printPagelinkList($arraudit, array('noitem' => true)));
        }
        $data = $tpl->get();

        FBuildPage::addTab(array("MAINDATA" => $data));
    }
}
