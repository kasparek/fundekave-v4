<?php
class fXajax {
  static function &init() {
  	global $xajax;
  	if(!is_object($xajax)) {
  		require_once(ROOT.'xajax_core/xajax.inc.php');
  		$xajax = new xajax('xajax.php');
  		$xajax->configure('javascript URI', './js/');
  		$xajax->configure('scriptLoadTimeout', 0);
  		if(isset($_GET['xdebug'])) $xajax->configure('debug', true);
  	}
  	return $xajax;
  }
  static function register($fcename) {
    global $xajaxRegisteredFunctions;
    if(!$xajaxRegisteredFunctions) $xajaxRegisteredFunctions = array();
    if(!in_array($fcename,array_keys($xajaxRegisteredFunctions))) {
      $xajax = fXajax::init();
      return $xajaxRegisteredFunctions[$fcename] =& $xajax->register(XAJAX_FUNCTION, $fcename);
    } else return $xajaxRegisteredFunctions[$fcename];
  }
}
?>