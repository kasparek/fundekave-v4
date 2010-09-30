<?php
class FAjax_item extends FAjaxPluginBase {
	
	static function show($data) {
		$itemId = $data['item'];
		if($data['__ajaxResponse']===true) {
			$user = FUser::getInstance();
			$user->itemVO = new ItemVO($itemId,true);
			page_ItemDetail::build($data);
				
			$breadcrumbs = FBuildPage::getBreadcrumbs();
			$tpl = FSystem::tpl(TPL_MAIN);
			foreach($breadcrumbs as $crumb) {
				$tpl->setVariable('BREADNAME',$crumb['name']);
				if(isset($crumb['url'])) {
					$tpl->setVariable('BREADURL',$crumb['url']);
					$tpl->touchBlock('breadlinkend');
				}
				$tpl->parse('bread');
			}
			$tpl->parse('breadcrumbslist');
			FAjax::addResponse('breadcrumbs','$html',$tpl->get('breadcrumbslist'));
		} else {
			FHTTP::redirect(FSystem::getUri('i='.$itemId,'',''));
		}
	}

	static function edit($data,$itemVO=null) {
    if(empty($itemVO)) $itemVO = new ItemVO($data['item'],true);
  	$ret = FItemsForm::show($itemVO);
		if($data['__ajaxResponse']===true) {
			FAjax::addResponse('editForm', '$html', $ret);
      FAjax::addResponse('function','call','datePickerInit');
			FAjax::addResponse('function','call','markItUpSwitchInit');
			FAjax::addResponse('function','call','fajaxformInit');
			FAjax::addResponse('function','call','tabsInit');
			FAjax::addResponse('function','call','draftInit');
			FAjax::addResponse('function','call','fconfirmInit');
		} else {
			FBuildPage::addTab(array("TABID"=>'editForm',"MAINDATA"=>$ret));
		}
	}
	
	static function submit($data) {
		$itemVO = new ItemVO();
		$typeList = array('forum','event','galery','blog');
		$itemVO->typeId = $data['t']; 
		if(!in_array($itemVO->typeId,$typeList)) return; //TODO: validate type - give feedback?

		FItemsForm::process($itemVO, $data );
		
		//TODO: check that itemVO gets repopulated
		if(FAjax::isRedirecting()===false) {
			//refresh item preview
			//TODO: refactor get item detail - this version os only for blog
			$user = FUser::getInstance();
			if($itemVO->itemId>0) { 
				FAjax_item::edit( array('__ajaxResponse'=>true),$itemVO);
				$user->itemVO = $itemVO;
				FAjax::addResponse('i'.$itemId, '$replaceWith', page_ItemDetail::build($data));
			}
			/*
			$extraParams = array('type'=>'blog','showDetail'=>true);
			$itemVO = new ItemVO($itemId,true,$extraParams);
			FAjax::addResponse('i'.$itemId, '$replaceWith', $itemVO->render());
			*/
		}
	}
	
	static function delete($data) {
		$itemVO = new ItemVO($data['item']);
		if($itemVO->load) {
			$user = FUser::getInstance();
		  if(FRules::get($user->userVO->userId,$user->pageVO->pageId,2)===true 
			|| (FRules::get($user->userVO->userId,$user->pageVO->pageId,1)===true 
					&& $itemVO->typeId=='forum' && $itemVO->userId==$user->userVO->userId)) { 
				$itemVO->delete();
				if($itemVO->typeId=='forum') {
					FAjax::addResponse('function','call','remove;i'.$data['item']);
				}
			}
		}
	}

}