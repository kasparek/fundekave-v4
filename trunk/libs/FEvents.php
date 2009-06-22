<?php
class FEvents {
	static function thumbName($flyerName) {
		$arrTmp = explode('.',$flyerName);
		return str_replace($arrTmp[count($arrTmp)-1],'jpg',$flyerName);
	}
	static function thumbUrl($flyerName) {
		$conf = FConf::getInstance();
		return $conf->a['events']['flyer_cache'] . FEvents::thumbName($flyerName);
	}
	static function flyerUrl($flyerName) {
		$conf = FConf::getInstance();
		return $conf->a['events']['flyer_source'] . $flyerName;
	}
	static function createThumb($imageName) {
		
		$flyerFilename = FEvents::flyerUrl($imageName);
		$flyerFilenameThumb = FEvents::thumbUrl($imageName);
		
		if(!file_exists($flyerFilenameThumb)) {
			//---create thumb
			FImgProcess::process($flyerFilename,$flyerFilenameThumb
			,array('quality'=>FConf::get('events','thumb_quality')
			,'width'=>FConf::get('events','thumb_width'),'height'=>0));
			return true;
		} else {
			return true;
		}
	}
	
	static function editForm() {
		$cache = FCache::getInstance('s');
		$user = FUser::getInstance();
		$itemVO = $user->itemVO;
		
		if($user->itemVO->itemId > 0) {
			$itemVO->typeId = 'event';
			$itemVO->load();
		} elseif(false !== ($itemVO = $cache->getData('event','form'))) {
			$cache->invalidateData('event','form');
		} else {
			$itemVO = new ItemVO();
			$itemVO->itemId = 0;
			$itemVO->categoryId = 0;
			$itemVO->dateStartLocal = Date("d.m.Y");
		}
		
		//print_r($itemVO);
		//die();

		$tpl = new FTemplateIT('events.edit.tpl.html');
		$tpl->setVariable('FORMACTION',FUser::getUri());
		$tpl->setVariable('HEADING',(($itemVO->itemId>0)?($itemVO->addon):(FLang::$LABEL_EVENT_NEW)));

		$q = 'select categoryId,name from sys_pages_category where typeId="event" order by ord,name';
		$arrOpt = FDBTool::getAll($q,'event','categ','s');
		$options = '';
		if(!empty($arrOpt)) foreach ($arrOpt as $row) {
			$options .= '<option value="'.$row[0].'"'.(($row[0] == $itemVO->categoryId)?(' selected="selected"'):('')).'>'.$row[1].'</option>';
		}
		$tpl->setVariable('CATOPTIONS',$options);

		$tpl->setVariable('PLACE',$itemVO->location);
		$tpl->setVariable('NAME',$itemVO->addon);
		$tpl->setVariable('DATESTART',$itemVO->dateStartLocal);
		$tpl->setVariable('TIMESTART',$itemVO->timeStart);
		$tpl->setVariable('DATEEND',$itemVO->dateEndLocal);
		$tpl->setVariable('TIMEEND',$itemVO->timeEnd);
		$tpl->setVariable('DESCRIPTION',FSystem::textToTextarea( $itemVO->text ));
		$tpl->addTextareaToolbox('DESCRIPTIONTOOLBOX','event');
		if($itemVO->itemId > 0) {
			$tpl->touchBlock('delakce');
		}

		if(!empty( $itemVO->enclosure )) {
			$tpl->setVariable('FLYERURL',FEvents::flyerUrl( $itemVO->enclosure ));
			$tpl->setVariable('FLYERTHUMBURL',FEvents::thumbUrl( $itemVO->enclosure ));
		}
		return $tpl->get();
	}
	
	static function processForm($data) {
		
	}
}