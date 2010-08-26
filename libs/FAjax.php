<?php
class FAjax {

	private static $instance;
	static function &getInstance() {
		if (!isset(self::$instance)) {
			self::$instance = &new FAjax();
		}
		return self::$instance;
	}

	static function process($actionStr,$data) {
		if(strpos($data,'<')===false) {
			$data = base64_decode($data);
			$data = urldecode($data);
		}
		
		$arr = explode('-',$actionStr);
		$mod = $arr[0];
		$action = $arr[1];
		$ajax = (isset($arr[2]))?(true):(false);
		if(empty($mod) || empty($action)) {
			//---system parameters missing
			exit();
		}
		//---process
		$dataProcessed = array();
		if($ajax == true) {
			$dataXML = stripslashes( $data );
			 
			$xml = new SimpleXMLElement($dataXML);
			
			foreach($xml->Request->Item as $item) {
				$k = (String)$item['name'];
				$v = (String)$item;
				if(isset($dataProcessed[ $k ])) {
					if(is_array($dataProcessed[ $k ])) {
						$dataProcessed[ $k ][] = $v;
					} else {
						$dataProcessed[ $k ] = array( $dataProcessed[ $k ], $v);
					}
				} else {
					$dataProcessed[ $k ] = $v;
				}
			}
			
			$dataProcessed['__ajaxResponse'] = true;
		} else {
			if(is_array($data)) {
				$dataProcessed = $data;
			} else {
				$arr = explode(';',$data);
				foreach($arr as $row) {
					list($k,$v) = explode(':',$row);
					$dataProcessed[$k] = $v;
				}
			}
			$dataProcessed['__ajaxResponse'] = false;
		}

		$fajax = FAjax::getInstance();
		$fajax->data = $dataProcessed;

		if(isset($fajax->data['k'])) {
			//---process k - set pageparam on user if needed
			FSystem::processK($fajax->data['k']);
		}

		//---dealing with ajax requests
		$filename = ROOT.LIBSDIR.'FAjax/FAjax_'.$mod.'.php';
		require_once($filename);
		$className = 'FAjax_'.$mod;
		if(class_exists($className)) {
			if(call_user_func(array($className,'validate'), array_merge($dataProcessed,array('function'=>$action)))) {

				call_user_func(array($className,$action), $dataProcessed);

				if( $ajax === true )
				if( $fajax->errorsLater===false ) {
					$arrMsg = FError::getError();
					if(!empty($arrMsg)){
						$arr = array();
						foreach ($arrMsg as $k=>$v) {
							$arr[] = $k . (($v>1)?(' ['.$v.']'):(''));
						}
						FAjax::addResponse('function','call','msg;error;'.implode('<br />',$arr));
						FError::resetError();
					}
					$arrMsg = FError::getError(1);
					if(!empty($arrMsg)){
						$arr = array();
						foreach ($arrMsg as $k=>$v) {
							$arr[] = $k . (($v>1)?(' ['.$v.']'):(''));
						}
						FAjax::addResponse('function','call','msg;ok;'.implode('<br />',$arr));
						FError::resetError(1);
					}
				}
			} else {
				if($ajax === true) {
					FAjax::addResponse('function','call','redirect;'.FSystem::getUri());
				}
			}

			//---send response
			if($ajax === true) {
				$ret = FAjax::buildResponse();
			} else {
				FAjax::resetResponse();
			}
		}

		if($ajax === true) {
			header ("content-type: text/xml");
			echo $ret;
			exit();
		}
	}

	public $data;
	public $responseData;
	public $errorsLater = false;
	public $redirecting = false;

	static public function errorsLater() {
		$fajax = FAjax::getInstance();
		$fajax->errorsLater = true;
	}

	static public function redirect($url) {
		$fajax = FAjax::getInstance();
		if($fajax->data['__ajaxResponse']===true) {
			$fajax->errorsLater = true;
			$fajax->redirecting = true;
			$fajax->responseData[] = array('TARGET'=>'function','PROP'=>'call','DATA'=>'redirect;'.$url);
		} else {
			FHTTP::redirect($url);
		}
	}
	
	static public function isRedirecting() {
	  $fajax = FAjax::getInstance();
	  return $fajax->redirecting;
	}

	static public function addResponse($target, $property, $value) {
		$fajax = FAjax::getInstance();
		//if(strpos($value,'<![CDATA[')===false) $value = '<![CDATA['.$value.']]>';
		$fajax->responseData[] = array('TARGET'=>$target,'PROP'=>$property,'DATA'=>$value);
	}

	static function resetResponse() {
		$fajax = FAjax::getInstance();
		$fajax->responseData = array();
	}

	static function buildResponse() {
		$fajax = FAjax::getInstance();
		$data = $fajax->responseData;
		$originalData = $fajax->data;

		$tpl = FSystem::tpl('fajax.xml');

		//---create new data
		if(!empty($data)) {
			foreach($data as $k=>$v) {
				$tpl->setVariable($v);
				$tpl->parse('data');
			}
		}

		//---process original data
		foreach($originalData as $k=>$v) {
			switch($k) {
				case 'call':
				case 'callback':
					if(!is_array($v)) $v = array($v);
					foreach($v as $funcName) {
						$tpl->setVariable(array('TARGET'=>'function','PROP'=>(($k=='call')?('call'):('callback')),'DATA'=>$funcName));
						$tpl->parse('data');
					}
					break;
			}
		}



		$tpl->parse();
		FAjax::resetResponse();
		return $tpl->get();
	}

}