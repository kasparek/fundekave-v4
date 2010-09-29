<?php
class FItemsForm {

	function process($data) {

	}

	//TODO: pass filter in data / content
	//TODO: pass perpage in data
	static function show($itemVO,$data) {
		if(!isset($data['simple'])) $data['simple']=false;
		$cache = FCache::getInstance('s',0);
		$tempData = $cache->getData( $itemVO->pageId.$itemVO->typeId, 'form');
		if($tempData !== false) {
			foreach($tempData as $k=>$v) {
				$data[$k] = $v;
			}
			$cache->invalidateData( $this->pageId.$this->typeId, 'form');
		}
		foreach($data as $k=>$v) {
			$itemVO->set($k,$v);
		}

		$tpl = FSystem::tpl('form.'.$itemVO->typeId.'.tpl.html');
		//GENERIC
		$tpl->setVariable('FORMACTION',FSystem::getUri('m=item-submit&t='.$itemVO->typeId));
		if(!empty($itemVO->itemId)) $tpl->setVariable('ITEMID',$itemVO->itemId);

		if(!empty($itemVO->addon)) {
			$tpl->setVariable('TITLE',$itemVO->addon);
		}
		$tpl->setVariable('CONTENTID',$itemVO->typeId.$itemVO->pageId.'text');
		$tpl->setVariable('CONTENTLONGID',$itemVO->typeId.$itemVO->pageId.'textLong');
		if(!empty($itemVO->text)) {
			$tpl->setVariable('CONTENT',$itemVO->text);
		}
		if(!empty($itemVO->textLong)) {
			$tpl->setVariable('CONTENTLONG',$itemVO->textLong);
		}
		if(!empty($itemVO->dateStartLocal)) {
			$tpl->setVariable('DATESTART',$itemVO->dateStartLocal);
			$tpl->setVariable('TIMESTART',$itemVO->dateStartTime);
		}
		if(!empty($itemVO->dateEndLocal)) {
			$tpl->setVariable('DATEEND',$itemVO->dateEndLocal);
			$tpl->setVariable('TIMEEND',$itemVO->dateEndTime);
		}

		if(!empty($itemVO->name)) {
			$tpl->setVariable('USERNAME',$itemVO->name);
		}

		//TYPE DEPEND
		$user = FUser::getInstance();
		switch($itemVO->typeId) {
			case 'forum':
				if ($user->idkontrol) {
					if($data['simple']===false) {
						$tpl->setVariable('PERPAGE',$data['perpage']);
					}
				} else {
					$tpl->setVariable('USERNAME','');
					$captcha = new FCaptcha();
					$tpl->setVariable('CAPTCHASRC',$captcha->get_b2evo_captcha());
				}
				break;
			case 'blog':
			case 'event':
				if($opt = FCategory::getOptions($itemVO->pageId,$itemVO->categoryId,true,'')) $tpl->setVariable('CATEGORYOPTIONS',$opt);

				break;
			case 'galery':
				$position = $itemVO->prop('position');
				if(!empty($data['position'])) $position = $data['position'];
				if(!empty($position)) {
					$tpl->setVariable('POSITION',str_replace(';',"\n",$position));
				}
				//TODO: comments not loaded from cache
				//comments settings
				$tpl->touchBlock('comments'.$itemVO->getProperty('forumSet',$user->pageVO->prop('forumSet'),true));
				//public settings
				if($itemVO->public == 0) {
					$tpl->touchBlock('classnotpublic');
					$tpl->touchBlock('headernotpublic');
				} else {
					$tpl->touchBlock('public'.$itemVO->public);
				}
				//delete block
				if($itemVO->itemId>0)
				$tpl->touchBlock('delete');
				
				//event specials
				$tpl->touchBlock('remindrepeat'.$itemVO->prop('reminderEveryday'));
				$tpl->touchBlock('remindbefore'.$itemVO->prop('reminder'));
				$tpl->touchBlock('repeat'.$itemVO->prop('repeat'));

				if(!empty($itemVO->enclosure)) {
					//TODO: change to item image rather than fevent::flyer
					$tpl->setVariable('IMAGEURL',FEvents::flyerUrl( $itemVO->enclosure ));
					$tpl->setVariable('IMAGETHUMBURL',FEvents::thumbUrl( $itemVO->enclosure ));
				}
		}

		return $tpl->get();
	}
}