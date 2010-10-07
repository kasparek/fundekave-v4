<?php
class FAjax {
	
	public $template = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<FAjax><Response>\n{CONTENT}\n</Response></FAjax>";
	public $itemTemplate = '<Item target="{TARGET}" property="{PROP}"><![CDATA[{DATA}]]></Item>';
	
	public $data;
	public $responseData;
	public $errorsLater = false;
	public $redirecting = false;
	
	/**
	 * SINGLETON
	 * all functions are static
	 */
	private static $instance;
	static function &getInstance() {
		if (!isset(self::$instance)) {
			self::$instance = new FAjax();
		}
		return self::$instance;
	}

	static function process($actionStr,$data) {
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
			if(is_array($data)) $dataProcessed = $data;
			else {
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
		$className = 'FAjax_'.$mod;
		if(class_exists($className)) {
			if(call_user_func(array($className,'validate'), array_merge($dataProcessed,array('function'=>$action)))) {
				call_user_func(array($className,$action), $dataProcessed);
				if( $ajax === true )
				if( $fajax->errorsLater===false ) {
					$arrMsg = FError::get();
					if(!empty($arrMsg)){
						$arr = array();
						foreach ($arrMsg as $k=>$v) {
							$arr[] = $k . (($v>1)?(' ['.$v.']'):(''));
						}
						FAjax::addResponse('call','msg','error,'.implode('<br />',$arr));
						FError::reset();
					}
					$arrMsg = FError::get(1);
					if(!empty($arrMsg)){
						$arr = array();
						foreach ($arrMsg as $k=>$v) {
							$arr[] = $k . (($v>1)?(' ['.$v.']'):(''));
						}
						FAjax::addResponse('call','msg','ok,'.implode('<br />',$arr));
						FError::reset(1);
					}
				}
			} else {
				if($ajax === true) {
					//redirect to same page because of user does not have permission to access this page
					FAjax::addResponse('call','redirect',FSystem::getUri());
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
			//do super vars
			$ret = FSystem::superVars($ret);
			
			echo $ret;
			exit();
		}
	}

	static public function errorsLater() {
		$fajax = FAjax::getInstance();
		$fajax->errorsLater = true;
	}

	static public function redirect($url) {
		$fajax = FAjax::getInstance();
		if($fajax->data['__ajaxResponse']===true) {
			$fajax->errorsLater = true;
			$fajax->redirecting = true;
			$fajax->responseData[] = array('TARGET'=>'call','PROP'=>'redirect','DATA'=>$url);
		} else {
			FHTTP::redirect($url);
		}
	}

	static public function isRedirecting() {
		$fajax = FAjax::getInstance();
		return $fajax->redirecting;
	}

	static public function addResponse($target, $property, $value='') {
		$fajax = FAjax::getInstance();
		$fajax->responseData[] = array('TARGET'=>$target,'PROP'=>$property,'DATA'=>$value);
	}

	static function resetResponse() {
		$fajax = FAjax::getInstance();
		$fajax->responseData = array();
	}

	static function buildResponse() {
		$fajax = FAjax::getInstance();
		$rows = array();
		//---create new data
		if(!empty($fajax->responseData)) {
			foreach($fajax->responseData as $responseData) {
				$row = $fajax->itemTemplate;
				foreach($responseData as $k=>$v) $row=str_replace('{'.$k.'}',$v,$row);
				$rows[] = $row;
			}
		}
		//---process original data
		foreach($fajax->data as $k=>$v) {
			if($k=='call') {
				if(!is_array($v)) $v = array($v);
				foreach($v as $funcName) {
					$fArr = explode(';',$funcName);
					$rows[] = str_replace(array('{TARGET}','{PROP}','{DATA}'),array($k,$fArr[0],isset($fArr[1])?$fArr[1]:''),$fajax->itemTemplate);
				}
			}
		}
		FAjax::resetResponse();
		return str_replace('{CONTENT}',implode("\n",$rows),$fajax->template);
	}
}