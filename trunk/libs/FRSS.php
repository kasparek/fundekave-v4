<?php
class FRSS {
  function __construct() {
			
	}
	
	static function process($data) {
	
		$salt = 'fdk35';
		if(isset($data['hash']) && $user->currentPage['typeId']=='forum') {
		   $hash = $data['hash'];
		   $localHash = md5($salt.$user->currentPageId);
		  if($localHash == $hash) {
		    
		    $paramsDecode = base64_decode($data['data']);
		    $paramsDecode = urldecode($paramsDecode);
		  	if($paramsDecode) {
		    	$paramsArr = explode($hash, $paramsDecode);
		    	$params['name'] = $paramsArr[0];
		    	$params['text'] = $paramsArr[1];
		    
		    	if(empty($params['name'])) FError::addError('E:nameEmpty');
		    	
		    	if(empty($processArr)) {
		        	if($params['text']!='' && $params['name']!='') {
		        		$itemVO = new ItemVO();
		        		$itemVO->text = FSystem::textins($params['text']);
		        		$itemVO->name = FSystem::textins($params['name'],array('plainText'=>1));
		        		if(FForum::messWrite($itemVO)) FError::addError('I:insertOK');
		        	}
		    	}
		  	}
		  }
		}
		
	}
	
	static function build() {
		//typeId - galery|forum|blog page feed
		//pageId event - event feed
		//rest live feed
		//no access - exit;
		
	} 

}