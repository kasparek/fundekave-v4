<?php
class FAjax_sail extends FAjaxPluginBase {
	static function contact($data) {
    $user = FUser::getInstance();
    
    $to = 'erika@awake33.com';
    $name = FText::preProcess($data['name'],array('plainText'=>1));
    $email = FText::preProcess($data['email'],array('plainText'=>1));
    $text = FText::preProcess($data['text'],array('plainText'=>1));
    
    if(empty($text)) FError::add(FLang::$MESSAGE_EMPTY);
    
    require_once('Zend/Validate/EmailAddress.php');
		$validator = new Zend_Validate_EmailAddress();
		if(true!==$validator->isValid($data['email']))  FError::add(FLang::$ERROR_INVALID_EMAIL);
        
    if(!FError::is()) {
    
      $message = $name . "\n\n" . $email . "\n\n" . $text;
      $message = FText::wrap($message, 70);
      $headers = 'From: contact@awake33.com' . "\r\n" .
      'Reply-To: ' . $email . "\r\n" .
      'X-Mailer: PHP/' . phpversion();
      
      mail( $to , 'sail.awake33.com - contact' , $message,$headers);
      
      //insert into forum
      $item = new ItemVO();
      $item->set('typeId','forum');
      $item->set('pageId','jz3soe');
      $item->set('userId','75');
      $item->set('name','lama');
      $item->set('text',$message);
      $item->save();
      
      FError::add(FLang::$MESSAGE_SENT,1);
      
    } else {
      $cache = FCache::getInstance('s',0);
			$cache->setData($data, $user->pageId, 'form');
    }
    
    FAjax::redirect(FSystem::getUri('',$user->pageId,'',array('short'=>1)));
    
  }
}