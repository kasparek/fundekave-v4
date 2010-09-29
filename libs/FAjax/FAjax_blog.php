<?php
class FAjax_blog extends FAjaxPluginBase {
	static function edit($data) {

		$ret = FBlog::getEditForm($data['item']);

		if($data['__ajaxResponse']===true) {

			FAjax::addResponse('entryForm', '$html', $ret);

      FAjax::addResponse('function','call','datePickerInit');
			FAjax::addResponse('function','call','markItUpSwitchInit');
			FAjax::addResponse('function','call','fajaxformInit');
			FAjax::addResponse('function','call','tabsInit');
			FAjax::addResponse('function','call','draftInit');
			FAjax::addResponse('function','call','fconfirmInit');
				
		} else {

			FBuildPage::addTab(array("TABID"=>'bloged',"MAINDATA"=>$ret));

		}
	}
	static function submit($data) {
		$itemId = FBlog::process( $data );
		if(FAjax::isRedirecting()===false) {
			FAjax_blog::edit( array('__ajaxResponse'=>true,'item'=>$itemId) );
			//refresh item preview
			$extraParams = array('type'=>'blog','showDetail'=>true);
			$itemVO = new ItemVO($itemId,true,$extraParams);
			FAjax::addResponse('i'.$itemId, '$replaceWith', $itemVO->render());
		}
	}

}