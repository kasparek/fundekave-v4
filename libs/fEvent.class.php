<?php
class fEvents {
  static function thumbName($flyerName) {
    $arrTmp = explode('.',$flyerName);
    return str_replace($arrTmp[count($arrTmp)-1],'jpg',$flyerName);
  }
  static function thumbUrl($flyerName) {
    global $conf;
    return $conf['events']['flyer_cache'] . fEvents::thumbName($flyerName);
  }
  static function flyerUrl($flyerName) {
    global $conf;
    return $conf['events']['flyer_source'] . $flyerName;
  }
}