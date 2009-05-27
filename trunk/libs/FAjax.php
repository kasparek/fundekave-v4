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
				$dataProcessed[ (String)$item['name'] ] = (String)$item;
			}
			$fajax = FAjax::getInstance();
			$fajax->data = $dataProcessed;
		}
		//---dealing with ajax requests
		$filename = ROOT.LIBSDIR.'FAjax/FAjax_'.$mod.'.php';
		require_once($filename);
		if(class_exists('Fajax_'.$mod)) {
			$className = 'Fajax_'.$mod;
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
	public function addResponse($target, $property, $value) {
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
		
		$tpl = new FTemplateIT('fajax.xml');
		
		//---process original data
		foreach($originalData as $k=>$v) {
			switch($k) {
				case 'call':
					$tpl->setCurrentBlock('call');
					$tpl->setVariable('FUNCTION', $v);
					$tpl->parseCurrentBlock();
					break;
				case 'callback':
					$tpl->setCurrentBlock('callback');
					$tpl->setVariable('FUNCTION', $v);
					$tpl->parseCurrentBlock();
					break;
			}
		}
		
		//---create new data
		foreach($data as $k=>$v) {
			$tpl->setCurrentBlock('data');
			$tpl->setVariable('TARGET', $v['target']);
			$tpl->setVariable('PROP', $v['property']);
			$tpl->setVariable('DATA', $v['value']);
			$tpl->parseCurrentBlock();
		}
		
		$tpl->parse();
		FAjax::resetResponse();
		return $tpl->get();
	}

}