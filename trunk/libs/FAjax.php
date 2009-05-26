<?php
class FAjax {
	
	static function process($actionStr,$data) {
  
  $arr = explode('-',$actionStr);
  $mod = $arr[0];
  $action = $arr[1];
  $ajax = (isset($arr[2]))?(true):(false);
	if(empty($mod) || empty($action)) {
		//---system parameters missing
		exit();	
	}
	//---dealing with ajax requests
	$filename = ROOT.LIBSDIR.'FAjax/FAjax_'.$mod.'.php';
	
	require_once($filename);
		  if(class_exists('Fajax_'.$mod)) {
		    $className = 'Fajax_'.$mod;
		    $class = new $className;
		    
		    
		    
					$ret = $class->$action($data);
					return $ret;
					
					
		  } 
      
  if($ajax==true) {
    header ("content-type: text/xml");
    echo $ret;
    exit();
  }
  }
	
}