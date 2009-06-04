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
}