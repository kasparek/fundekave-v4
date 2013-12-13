<?php
class fajax_Tab extends FAjaxPluginBase {
  static function show($data) {
		$output = '';
		if(strpos($data['__get']['k'],'finfo')===0) {
			$output = page_UserInfo::build($data);
		}
		
		if($data['__ajaxResponse']) {
			echo $output;
			exit;
		}
	}
}