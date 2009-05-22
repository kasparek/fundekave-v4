<?php
class FXajax {
	
  private static $instance;
	static function &getInstance() {
  	if (!isset(self::$instance)) {
  		require_once(ROOT.'xajax_core/xajax.inc.php');
  		self::$instance = new xajax('xajax.php');
  		self::$instance->configure('javascript URI', './js/');
  		self::$instance->configure('scriptLoadTimeout', 0);
  		if(isset($_GET['xdebug'])) self::$instance->configure('debug', true);
  	}
  	return self::$instance;
  }
  
  static function register($fcename) {
    global $xajaxRegisteredFunctions;
    if(!$xajaxRegisteredFunctions) $xajaxRegisteredFunctions = array();
    if(!in_array($fcename,array_keys($xajaxRegisteredFunctions))) {
      $xajax = FXajax::getInstance();
      return $xajaxRegisteredFunctions[$fcename] =& $xajax->register(XAJAX_FUNCTION, $fcename);
    } else return $xajaxRegisteredFunctions[$fcename];
  }
  
}