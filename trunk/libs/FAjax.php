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
		//---process $data['data']
		$dataXML = stripslashes($data['data']);
		$xml = new SimpleXMLElement($dataXML);
		foreach($xml->Request->Item as $item) {
			$dataProcessed[ (String)$item['name'] ] = (String)$item;
		}

		//---dealing with ajax requests
		$filename = ROOT.LIBSDIR.'FAjax/FAjax_'.$mod.'.php';
		require_once($filename);
		if(class_exists('Fajax_'.$mod)) {
			$className = 'Fajax_'.$mod;
			$ret = call_user_func(array($className,$action), $dataProcessed);
		}

		if($ajax==true) {
			header ("content-type: text/xml");
			echo $ret;
			exit();
		}
	}
	
	static function buildResponse($data,$originalData) {
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
		return $tpl->get();
	}

}