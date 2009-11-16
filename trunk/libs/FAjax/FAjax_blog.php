<?php
class FAjax_blog extends FAjaxPluginBase {
	static function edit($data) {

		$ret = FBlog::getEditForm($data['item']);

		if($data['__ajaxResponse']===true) {

			FAjax::addResponse('editnew', '$html', $ret);

			require('FAjax_void.php');
			FAjax_void::datepicker($data);

			FAjax::addResponse('function','call','addTASwitch');
			FAjax::addResponse('function','call','fajaxform');
			FAjax::addResponse('function','call','tabsInit');
				
			FAjax::addResponse('function','call','draftSetEventListeners');

				
		} else {

			FBuildPage::addTab(array("TABID"=>'bloged',"MAINDATA"=>$ret));

		}
	}
	static function submit($data) {

		$itemId = FBlog::process( $data );
		FAjax_blog::edit( array('__ajaxResponse'=>true,'item'=>$itemId) );

		//refresh item preview
		$extraParams = array('type'=>'blog','showComments'=>false,'showDetail'=>true);
		$itemVO = new ItemVO($itemId,true,$extraParams);

		FAjax::addResponse('i'.$itemId, '$replaceWith', $itemVO->render());
	}

}