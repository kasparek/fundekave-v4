<?php
class fdkForum {
  var $forumLibsPath;
  var $templatePath;
  var $forumPageUri = ''; //---URL OF PAGE WITH GUESTBOOK
  var $serverPageId = ''; //---server side pageId
  var $salt = 'fdk35'; //---USE TO ENCODE control hash .. must be same as on server
  var $dateFormat = 'Y-m-d H:i:s';
  var $arrErrors = array('nameEmpty' => 'No name specified','captchaFail'=>'Captcha failed');
  var $arrData; //---RSS array of loaded data
    //
  var $displayForm = true;
  var $name; //---form value
  var $text; //---form value
  var $totalItems; //---total items in result
  var $page = 1; //---current page
  
  function fdkForum($confArray) {
  
    foreach($confArray as $k=>$v) {
      $this->$k = $v;
    }
    require($this->forumLibsPath.'fCaptcha.class.php');
    require($this->forumLibsPath."fError.class.php");
    require($this->forumLibsPath."xmlparser.class.php");
    $this->processPOST();
    $this->dispatchRss();
    $this->processRss();
  }
  function processPOST() {
    if(isset($_POST['name'])) {
      $captcha = fCaptcha::init(array (  'tempFolder' => $this->captchaTempFolderPath,  'libPath' => $this->forumLibsPath));
    	if(!$captcha->validate_submit($_POST['captchaimage'],$_POST['pcaptcha'])) {
    	    fError::addError($this->arrErrors['captchaFail']);
    	}
    	$hash = md5($this->salt.$this->serverPageId);
      $_SESSION['toSend'] = $_POST['name'].$hash.$_POST['text'];
    	require($this->forumLibsPath."fHTTP.class.php");
    	fHTTP::redirect($this->forumPageUri);
    }
  }
  function dispatchRss() {
    $sendStr = '';
    //---pagination
    $this->page = 1;
    if(isset($_GET['p'])) $this->page = $_GET['p']*1;
    if($this->page < 1) $this->page = 1;
    //---url
    $rssUrl = "http://fundekave.net/frss.php?k=".$this->serverPageId;
    //---add data to save
    if(isset($_SESSION['toSend']) && !fError::isError()) {
          $hash = md5($this->salt.$this->serverPageId);
          $rssUrl .= '&hash='.$hash.'&data='.base64_encode(urlencode($_SESSION['toSend']));
          $this->page = 1;
    }
    $rssUrl .= (($this->page > 1)?('&p='.$this->page):(''));
    
    //---RSS LOAD
    $lines = file($rssUrl);
    $xml = implode('',$lines);
   
    $xmlParser = new XMLToArray();
    $arr = $xmlParser->parse($xml);
    $arr = $arr['_ELEMENTS'][0]['_ELEMENTS'][0]['_ELEMENTS'];
    $arr = array_reverse($arr);
    
    $item = array_pop($arr);
    while($item['_NAME']!='item') {
      $name = $item['_NAME'];
      $data = $item['_DATA'];
      $obj->$name = $data;
      $item = array_pop($arr);
    }
    //---parse posts
    while($item) {
      $postObj = new stdClass();
      foreach($item['_ELEMENTS'] as $post) {
        $name = $post['_NAME'];
        $data = $post['_DATA'];
        $postObj->$name = $data;
      }
      $obj->item[] = $postObj;
      $item = array_pop($arr);
    }
    
    $this->arrData = $obj;  
    
  }
  function processRss() {
    $this->name = '';
    $this->text = '';
    //---PROCESS INFO MESSAGES FROM GENERATOR
    $this->page = 1;
    
    list($dummy,$inDataString) = explode('|',$this->arrData->generator);
    $inDataArrTmp = explode(';',$inDataString);
    if(!empty($inDataArrTmp)) {
        foreach ($inDataArrTmp as $val) {
        	list($type,$value) = explode(':',$val);
        	$inDataArr[$type][] = $value;
        }
    }
    if(!empty($inDataArr['D'])) {
      foreach($inDataArr['D'] as $data) {
        list($variable,$value) = explode("_",$data);
        if($variable=='t') $this->totalItems = $value;
        if($variable=='p') $this->page = $value;
      }
    }
    if(!empty($inDataArr['E'])) foreach($inDataArr['E'] as $err) fError::addError($this->arrErrors[$err]);
    //---IF SAVED
    if(isset($_SESSION['toSend']) && fError::isError()) {
        $hash = md5($this->salt.$this->serverPageId);
        $params = explode($hash,$_SESSION['toSend']);
        $this->name = $params[0];
        $this->text = $params[1];
        
    }
    unset($_SESSION['toSend']);
  }
  function display() {
    require($this->forumLibsPath.'fPager.class.php');
    require($this->forumLibsPath.'fTemplateIT.class.php');
    //---INIT PAGER
    $pager = new fPager(array('totalItems'=>$this->totalItems,'currentPage'=>$this->page));
    $pager->getPager();
    //---INIT TEMPLATE
    $tpl = new fTemplateIT('fdkForum.tpl.html',$this->templatePath);
    $tpl->printErrorMsg();
    //---FILL TEMPLATE WITH ITEMS: Form, Pager
    if($this->displayForm) {
      //---INIT CAPTCHA
      $captcha = fCaptcha::init(array (  'tempFolder' => $this->captchaTempFolderPath,  'libPath' => $this->forumLibsPath));
      $captchaImgSrc = $captcha->get_b2evo_captcha();
      $tpl->setVariable('CAPTCHAIMGSRC',$captchaImgSrc);
      $tpl->setVariable('FORMACTION',$this->forumPageUri);
      $tpl->setVariable('NAME',$this->name);
      $tpl->setVariable('TEXT',$this->text);
    }
    $tpl->setVariable('PAGER',$pager->links);
    
    //---set Items
    if(!empty($this->arrData->item)) {
      foreach($this->arrData->item as $item) {
        $tpl->setCurrentBlock('item');
        $tpl->setVariable('IDATE',date($this->dateFormat,strtotime($item->pubDate)));
        $tpl->setVariable('INAME',$item->title);
        $tpl->setVariable('ITEXT',$item->description);
        $tpl->parseCurrentBlock();
      }
    }
    
    //---PRINT RESULT
    echo $tpl->get();
  }
}