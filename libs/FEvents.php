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
}