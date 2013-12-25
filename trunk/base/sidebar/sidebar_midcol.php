<?php
class sidebar_midcol {
	static function show() {
		$pageListOut = page_PagesList::build(array(),array('typeId'=>'galery','return'=>true,'nopager'=>true));
		if(!empty($pageListOut)) {
			return $pageListOut;
		}
	}
}