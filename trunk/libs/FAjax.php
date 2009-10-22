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
			$dataXML = stripslashes($data);
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
		
		//---dealing with ajax requests
		$filename = ROOT.LIBSDIR.'FAjax/FAjax_'.$mod.'.php';
		require_once($filename);
		$className = 'FAjax_'.$mod;
		if(class_exists($className)) {
			call_user_func(array($className,$action), $dataProcessed);
			//---send response
			if($ajax==true) {
				$ret = FAjax::buildResponse();
			} else {
				FAjax::resetResponse();
			}
		}

		if($ajax==true) {
			header ("content-type: text/xml");
			echo $ret;
			exit();
		}
	}
	public $data;
	public $responseData; 
	static public function addResponse($target, $property, $value) {
		$fajax = FAjax::getInstance();
		$fajax->responseData[] = array('target'=>$target,'property'=>$property,'value'=>$value);
	}
	
	static function resetResponse() {
		$fajax = FAjax::getInstance();
		$fajax->responseData = array();
	}

	static function buildResponse() {
		$fajax = FAjax::getInstance();
		$data = $fajax->responseData;
		$originalData = $fajax->data;
		
		$tpl = new FHTMLTemplateIT(ROOT.ROOT_TEMPLATES);
		$tpl->loadTemplatefile('fajax.xml');
		
		//---process original data
		foreach($originalData as $k=>$v) {
			switch($k) {
				case 'call':
					if(!is_array($v)) $v = array($v);
					foreach($v as $funcName) {
						$tpl->setCurrentBlock('call');
						$tpl->setVariable('FUNCTION', $funcName);
						$tpl->parseCurrentBlock();
					}
					break;
				case 'callback':
					$tpl->setCurrentBlock('callback');
					$tpl->setVariable('FUNCTION', $v);
					$tpl->parseCurrentBlock();
					break;
			}
		}
		
		//---create new data
		if(!empty($data)) {
			foreach($data as $k=>$v) {
				$tpl->setCurrentBlock('data');
				$tpl->setVariable('TARGET', $v['target']);
				$tpl->setVariable('PROP', $v['property']);
				$tpl->setVariable('DATA', $v['value']);
				$tpl->parseCurrentBlock();
			}
		}
		
		$tpl->parse();
		FAjax::resetResponse();
		return $tpl->get();
	}

}