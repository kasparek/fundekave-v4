<?php
class sidebar_page {
	static function show() {
		$user = FUser::getInstance();
		if(!$user->pageVO) return false;
		$sideData = '';
		if(Fconf::get('settings','sidebar_inherit')) {
			$pageVOTop = FactoryVO::get('PageVO',$user->pageVO->pageIdTop);
			$sideData = $pageVOTop->prop('sidebar');
		} 
		$sideData .= $user->pageVO->prop('sidebar');
		if(empty($sideData)) return false;
		return $sideData; 
	}
}