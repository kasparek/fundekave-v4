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
	if(empty($targetConfirmed)) {
		$targetConfirmed = array('functionName' => $targetPanel,'name' => '','public' => 1,'userIdOwner' => 1,'pageIdOrigin'=>'','content' =>'','options'=>'');
	}
	
	$panelContent = $fsidebar->getDynamicBlockContent($targetConfirmed);

	if(!empty($panelContent)) {
		$result = (!empty($targetConfirmed['name'])?'<h3>'.$targetConfirmed['name'].'</h3>':'')
			.'<div id="'.$targetConfirmed['functionName'].'"'.(strpos($panelContent,'well')===false?' class="well':'').'">'.$panelContent.'</div>';
		
		FAjax::addResponse('panel'.$targetConfirmed['functionName'], '$html', $result);
		
		if($targetPanel=='calendar') {
				FAjax::addResponse('call','calendarInit','');
		}
	}
	
  }
}