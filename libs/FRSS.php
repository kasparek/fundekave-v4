<?php
class FRSS {
	function __construct() {
			
	}

	static function process($data) {
		$user = FUser::getInstance();
		$pageId = $user->pageVO->pageId;
		$typeId = $user->pageVO->typeId;

		$salt = 'fdk35';
		if(isset($data['hash']) && $typeId=='forum') {
			$hash = $data['hash'];
			$localHash = md5($salt.$user->pageId);
			if($localHash == $hash) {
				$paramsDecode = base64_decode($data['data']);
				$paramsDecode = urldecode($paramsDecode);
				if($paramsDecode) {
					$paramsArr = explode($hash, $paramsDecode);
					
					$params['name'] = $paramsArr[0];
					$params['text'] = trim($paramsArr[1]);

					if(empty($params['name'])) FError::addError('E:nameEmpty');
					if(empty($params['text'])) FError::addError('E:textEmpty');
					
					if(!FError::isError()) {
						if($params['text']!='' && $params['name']!='') {
							$itemVO = new ItemVO();
							$itemVO->pageId = $pageId;
							$itemVO->typeId = 'forum';
							$itemVO->text = FSystem::textins($params['text']);
							$itemVO->name = FSystem::textins($params['name'],array('plainText'=>1));
							$itemVO->save();
							FError::addError('I:insertOK');
							//invalidate global cache
							$cache = FCache::getInstance('f',0);
							$cache->invalidateGroup('pagelist');
						}
					} else {
						//invalidate only this rss
						$cache = FCache::getInstance('f',0);
						$cacheKey = 'rss-'.$user->pageVO->pageId;
						$cache->invalidateData($cacheKey, 'pagelist');
					}
				}
			}
		}

	}

	static function build($data) {
		//typeId - galery|forum|blog page feed
		//pageId event - event feed
		//rest live feed
		
		$user = FUser::getInstance();

		$pageNumber = 1;
		if(isset($_GET['p'])) {
			$pageNumber = (int) $data['p'];
		}

		$cache = FCache::getInstance('v',0);
		$cacheKey = 'rss-'.$user->pageVO->pageId.(($pageNumber>1)?('-'.$pageNumber):(''));
		$ret = $cache->getData($cacheKey,'pagelist');
					
		if($ret === false) {

			$perPage = 20;

			//load items
			$fi = new FItems('',-1);
			//TODO: decide ordering
			//$fi->addSelect('if(sys_pages_items.dateStart is not null, sys_pages_items.dateStart, sys_pages_items.dateCreated) as dateorder');
			
			if($user->pageVO->typeId != 'top') {
				$fi->setPage($user->pageVO->pageId);
				$fi->hasReactions(false);
				$pageName = $pageVO->name;
				$totalFoto = $pageVO->cnt;
			}
			//$fi->setOrder('dateorder desc');
			$fi->setOrder('sys_pages_items.dateCreated desc');
			
			if($user->pageVO->typeId!='top') {
				$totalItems = $user->pageVO->cnt;
			}
			
			if($user->pageVO->typeId=='galery') $perPage = $totalItems;
			
			$arr = $fi->getList(($pageNumber-1)*$perPage,$perPage);

			$hostSpecArr = array();
			//process errors and messages
			$errArr = FError::getError();
			FError::resetError();
			
			if(!empty($errArr)) {
				foreach($errArr as $err=>$v) {
					if(strpos($err,':')===1) {
						$hostSpecArr[] = $err;
					}
				}
			}
			
			if(isset($totalItems)) {
				//send totalitems
				$hostSpecArr[]='D:t_'.$totalItems;
				//send current page - from 1 not 0
				$hostSpecArr[]='D:p_'.$pageNumber;
			}

			$pageIdTop = $user->pageVO->pageIdTop ? $user->pageVO->pageIdTop : HOME_PAGE;
			$pageTop = new PageVO($pageIdTop,true);
			$homesite = $pageTop->prop('homesite');

			$rssNow = date("Y-m-d H:i:s");
			$tpl = FSystem::tpl('rss.xml');
			$tpl->setVariable('CHTITLE',$user->pageVO->name . (($homesite!=$user->pageVO->name)?(' - '. $homesite):('')));
			$tpl->setVariable('CHLINK','http://'.$homesite.'/'.FSystem::getUri('',$user->pageVO->pageId));
			$tpl->setVariable('CHDESCRIPTION',$user->pageVO->description);
			$tpl->setVariable('CHLANG','cz');
			$tpl->setVariable('CHPUBDATE',$user->pageVO->dateUpdated);
			$tpl->setVariable('CHBUILDDATE',$rssNow);
			$tpl->setVariable('CHGENERATOR','FUNDEKAVE.net::v5|'.implode(';',$hostSpecArr));
			
			if(!empty($arr)) {
				foreach($arr as $item) {
					if($item->typeId == 'galery' && empty($totalFoto)) {
						$pageVO = new PageVO($item->pageId,true);
						$pageName = $pageVO->name;
						$totalFoto = $pageVO->cnt;
					}
					
					$link = 'http://'.$homesite.'/'.FSystem::getUri('i='.$item->itemId,$item->pageId);
					$tpl->setVariable('LINK',$link);
					$tpl->setVariable('PUBDATE',$item->dateStart ? $item->dateStartIso : $item->dateCreatedIso);
					
					switch($item->typeId) {
						case 'galery':
							$tpl->setVariable('TITLE',$pageName.' '.($item->getPos()+1).'/'.$totalFoto);
							$tpl->setVariable('DESCRIPTION','<img src="'.$item->thumbUrl.'" /><br /> '.$item->text);
							break;
						case 'forum':
							$tpl->setVariable('TITLE',$item->name);
							$tpl->setVariable('DESCRIPTION',FSystem::postText($item->text . (!empty($item->enclosure) ? '<br /><br />' . "\n" . $item->enclosure : '')));
							break;
						default:
							$tpl->setVariable('TITLE',$item->addon);
							$tpl->setVariable('DESCRIPTION',$item->text);
					}
					$tpl->parse('item');
				}
			}
			$ret = $tpl->get();
			$cache->setData($ret,$cacheKey,'pagelist');
		}

		header('Content-Type: text/xml');
		echo $ret;

	}

}