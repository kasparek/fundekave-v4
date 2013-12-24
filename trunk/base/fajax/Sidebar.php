<?php
class fajax_Sidebar extends FAjaxPluginBase {
  static function get($data) {
  
	if(empty($data['panel'])) return;
	
	$user = FUser::getInstance();
	
	$fsidebar = new FSidebar(($user->pageVO)?($user->pageVO->pageId):(''), $user->userVO->userId, ($user->pageVO)?( $user->pageVO->typeId ):(''));
	$fsidebar->load();
	
	$targetPanel = $data['panel'];
	
	$panemConfirmed = null;
	foreach($fsidebar->panels as $panel) {
		if($targetPanel == $panel['functionName']) {
			//we have match
			$targetConfirmed = $panel;
			break;
		}
	}
	if(!$targetConfirmed) return;
	
	$panelContent = $fsidebar->getDynamicBlockContent($targetConfirmed);
	
	if(!empty($panelContent)) {
		$result = (!empty($panel['name'])?'<h3>'.$panel['name'].'</h3>':'')
			.'<div id="'.$panel['functionName'].'" class="well">'.$panelContent.'</div>';
		
		FAjax::addResponse('panel'.$panel['functionName'], '$html', $result);
		
		if($targetPanel=='calendar') {
				FAjax::addResponse('call','calendarInit','');
		}
	}
	
  }
}