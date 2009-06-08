<?php
class FItemsToolbar {
	/**
	 * FILTER TOOLBAR FUNCTIONS
	 * */
	static function &getTagToolbarData() {
		$cache = FCache::getInstance('s',0);
		$user = FUser::getInstance();
		$arr = $cache->getData($user->pageVO->pageId,'tagToolData');
		if(!isset($arr['order'])) $arr['order'] = 0;
		if(!isset($arr['date'])) $arr['date'] = 0;
		if(!isset($arr['interval'])) $arr['interval'] = 1;
		if(($arr['order'] == 0 && $arr['interval'] == 1) && (empty($arr['searchStr']))) {
			$arr['enabled'] = 0;
		} else {
			$arr['enabled'] = 1;
		}
		return $arr;
	}

	static function setTagToolbarData($arr) {
		$cache = FCache::getInstance('s',0);
		$user = FUser::getInstance();
		$cache->setData($arr,$user->pageVO->pageId,'tagToolData');
	}

	static function isToolbarEnabled() {
		$toolbarData = &FItemsToolbar::getTagToolbarData();
		return $toolbarData['enabled'];
	}
	static function setTagToolbarDefaults($array) {
		$toolbarData = &FItemsToolbar::getTagToolbarData();
		if($toolbarData['enabled']==0) {
			foreach ($array as $k=>$v) {
				$toolbarData[$k] = $v;
			}
		}
		FItemsToolbar::setTagToolbarData($toolbarData);
	}
	static function getIntervalConf($par) {
		$arr = array(
		2=>(array('m'=>'1 year','f'=>'Y','p'=>'Y','db'=>'%Y')),
		3=>(array('m'=>'1 month','f'=>'Y-m','p'=>'Y m','db'=>'%Y-%m')),
		4=>(array('m'=>'1 week','f'=>'Y-W','p'=>'Y W','db'=>'%Y-%U')),
		5=>(array('m'=>'1 day','f'=>'Y-m-d','p'=>'d.m.Y','db'=>'%Y-%m-%d'))
		);
		return $arr[$par];
	}

	static function getTagToolbar($showHits=true,$params=array()) {
		$toolbarData = &FItemsToolbar::getTagToolbarData();
		$tpl = new FTemplateIT("thumbup.toolbar.tpl.html");

		if(isset($toolbarData['search'])) {
			$tpl->touchBlock('search');
			$tpl->touchBlock('ordbydate');
		}
		if(isset($toolbarData['searchStr'])) $tpl->setVariable('SEARCHTEXT',$toolbarData['searchStr']);
		if(isset($toolbarData['usersWho'])) $tpl->setVariable('SEARCHWHO',$toolbarData['usersWho']);

		if($showHits==true) $tpl->touchBlock('hits');
		$orderBlocksArr = array(1=>'thumbdesc',2=>'thumbmydesc',3=>'hit',4=>'hitreg',5=>'bydate');
		$intervalBlocksArr = array(2=>'dateintyear',3=>'dateintmonth',4=>'dateintweek',5=>'dateintday');
		if($toolbarData['order']>0) $tpl->touchBlock($orderBlocksArr[$toolbarData['order']]);
		if($toolbarData['interval']>1) $tpl->touchBlock($intervalBlocksArr[$toolbarData['interval']]);
		if($toolbarData['interval'] > 1) {
			$intConfArr = FItems::getIntervalConf($toolbarData['interval']);
			if(empty($toolbarData['date'])) {
				$toolbarData['date'] = Date($intConfArr['f']);
			}
			//---prepare current to print
			global $MONTHS;
			$modify = $intConfArr['m'];
			$format = $intConfArr['p'];
			if($toolbarData['interval'] == 4) $date = str_replace('-','-W',$toolbarData['date']);
			elseif ($toolbarData['interval'] == 2) $date = $toolbarData['date'].'01-01';
			else $date = $toolbarData['date'];

			$dateNext = new DateTime($date);

			$current = $dateNext->format($format);
			if($toolbarData['interval'] == 3) {
				list($year,$month) = explode(" ",$current);
				$current = $year . ' ' . $MONTHS[$month];
			} elseif ($toolbarData['interval'] == 4) {
				$weekStart = new DateTime($date.'-1');
				$weekEnd = new DateTime($date.'-7');
				$current = $weekStart->format('d.m.Y').' - '.$weekEnd->format('d.m.Y');
			}
			$dateNext->modify("+".$modify);
			if($dateNext->format("Ymd")<=date("Ymd")) {
				$next = $dateNext->format($format);
				if($toolbarData['interval'] == 3) {
					list($year,$month) = explode(" ",$next);
					$next = $year . ' ' . $MONTHS[$month];
				} elseif ($toolbarData['interval'] == 4) {
					$next = '';
				}
			}
			$datePrev = new DateTime($date);
			$datePrev->modify("-".$modify);
			if($datePrev->format("Ymd")>'19800101') {
				$previous = $datePrev->format($format);
				if($toolbarData['interval'] == 3) {
					list($year,$month) = explode(" ",$previous);
					$previous = $year . ' ' . $MONTHS[$month];
				} elseif ($toolbarData['interval'] == 4) {
					$previous = '';
				}
			}
			if(isset($previous)) {
				$tpl->setVariable('PREVIOUSLINK',FUser::getUri('tuda=prev'));
				$tpl->setVariable('PREVIOUSTEXT',PAGER_PREVIOUS . (($previous!='')?(' ' .$previous):('')));
			}
			if(isset($current)) $tpl->setVariable('CURRENTDATE',$current);
			if(isset($next)) {
				$tpl->setVariable('NEXTLINK',FUser::getUri('tuda=next'));
				$tpl->setVariable('NEXTTEXT',(($next!='')?($next .' '):('')). PAGER_NEXT);
			}
		}
		if($toolbarData['enabled']==0) $tpl->touchBlock('tudis');
		$tpl->setVariable('FORMACTION',FUser::getUri());

		FItemsToolbar::setTagToolbarData($toolbarData);

		return $tpl->get();
	}

	static function setTagToolbar() {
		$toolbarData = &FItemsToolbar::getTagToolbarData();
		if(isset($_POST['thumbupreset'])) $toolbarData = array();
		else {
			if(isset($_POST['searchText'])) {
				//---add for fullsearch
				$toolbarData['searchStr'] = FSystem::textins($_POST['searchText'],array('plainText'=>1));
			}
			if(isset($_POST['searchUser'])) {
				$toolbarData['usersWho'] = FSystem::textins($_POST['searchUser'],array('plainText'=>1));
			}
			if(isset($_POST['tuorder'])) $toolbarData['order'] = (int) $_POST['tuorder'];
			if(isset($toolbarData['interval'])) $oldInterval = $toolbarData['interval']; else $oldInterval = -1;
			if(isset($_POST['tuint'])) $toolbarData['interval'] = (int) $_POST['tuint'];
			//---create next,prev links, show current date
			if(isset($toolbarData['interval'])) {
				if($toolbarData['interval']>1 && (empty($toolbarData['date']) || $oldInterval!=$toolbarData['interval'])) {
					//---create default - current date
					$intConfArr = FItems::getIntervalConf($toolbarData['interval']);
					if(!empty($toolbarData['date'])) {
						if($oldInterval==4) $date = str_replace('-','-W',$toolbarData['date']);
						else $date = $toolbarData['date'];
					} else $date='';
					$date = new DateTime($date);
					$toolbarData['date'] = $date->format($intConfArr['f']);

				} elseif ($toolbarData['interval']<2) unset($toolbarData['date']);
			}
			if(isset($_GET['tuda']) && $toolbarData['interval']>1) {
				$intConfArr = FItems::getIntervalConf($toolbarData['interval']);
				if($_GET['tuda']=='next') $modifyCourse = '+';
				if($_GET['tuda']=='prev') $modifyCourse = '-';
				if(isset($modifyCourse)) {
					if($toolbarData['interval']==4) $dateStr = str_replace('-','-W',$toolbarData['date']);
					elseif($toolbarData['interval']==2) $dateStr = $toolbarData['date'].'01-01';
					else $dateStr = $toolbarData['date'];
					$date = new DateTime($dateStr);
					$date->modify($modifyCourse.$intConfArr['m']);
					$toolbarData['date'] = $date->format($intConfArr['f']);
				}
			}
		}
		FItemsToolbar::setTagToolbarData($toolbarData);
	}

	static function setQueryTool(&$fQuery) {
		$thumbupData = &FItemsToolbar::getTagToolbarData();
		if($thumbupData['enabled']==1) {

			if(isset($thumbupData['searchStr'])) {
				if(!empty($thumbupData['searchStr'])) {
					$fQuery->addFulltextSearch('i.text,i.enclosure,i.addon',$thumbupData['searchStr']);
				}
			}
			if(isset($thumbupData['usersWho'])) {
				if(!empty($thumbupData['usersWho'])) {
					$usersNameArr = explode(',',$thumbupData['usersWho']);
					foreach ($usersNameArr as $userName) {
						if($userId = FUser::getUserIdByName($userName)) {
							$validatedUserId[] = $userId;
						}
						else FError::addError(MESSAGE_USERNAME_NOTEXISTS.': '.$userName);
					}
					if(!empty($validatedUserId)) {
						if(count($validatedUserId)>1) {
							$fQuery->addWhere('userId in ('.implode(',',$validatedUserId).')');
						} else $fQuery->addWhere('userId = '.$validatedUserId[0]);
					}
				}
			}
			if($thumbupData['order']==5) $fQuery->setOrder('i.dateCreated desc');
			elseif ($thumbupData['order'] > 2) {
				if($thumbupData['interval']>1) {
					$fQuery->setOrder('ihistory.valueSum desc');
					$fQuery->replaceSelect('i.hit','ihistory.valueSum as hitsum');
					$fQuery->addWhere('ihistory.historyType = '.$thumbupData['order']);
				} else $fQuery->setOrder('i.hit desc');
			} elseif ($thumbupData['order'] > 0) {
				$fQuery->setOrder('thumbs desc');
				if($thumbupData['interval']>1) {
					$fQuery->replaceSelect('i.tag_weight','ihistory.valueSum as thumbs');
					$fQuery->addWhere('ihistory.historyType = 1');
				} else $fQuery->replaceSelect('i.tag_weight','i.tag_weight as thumbs');
				if(isset($thumbupData['filter']))
				if($thumbupData['filter'] == 2) {
					$user = FUser::getInstance();
					$fQuery->addWhere('it.userId="'.$user->userVO->userId.'"');
					$fQuery->addJoin('join sys_pages_items_tag as it on it.itemId=i.itemId');
				}
			}
			//-----------------------------
			//---by date
			if(!empty($thumbupData['date'])) {
				$intConfArr = FItems::getIntervalConf($thumbupData['interval']);
				$dateformat = $intConfArr['db'];
				$date = $thumbupData['date'];

				if($thumbupData['interval']==4) {
					list($year,$week) = explode("-",$date);
					$week--;
				}

				if($thumbupData['order']==0) {
					if($thumbupData['interval']==4) $date = sprintf("%04d-%02d",$year,$week);
					$fQuery->addWhere("date_format( i.dateCreated, '".$dateformat."' ) = '".$date."'");
				} else {
					if($thumbupData['interval']==4) $date = sprintf("%04d-W%02d",$year,$week);
					$fQuery->addWhere("ihistory.dateInt = '".$date."'");
				}
			}
			if($thumbupData['order']>0 && $thumbupData['interval']>1) {
				$fQuery->addJoin('join sys_pages_items_history as ihistory on ihistory.itemId=i.itemId');
			}
		}
	}
}