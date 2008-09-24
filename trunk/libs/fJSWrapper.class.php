<?php
class fJSWrapper {
    /**
     * contaion array('type'=>file|code,'content'=>text)
     *
     * @var array
     */
    var $webRelCacheDir = './data/cache/js/';
    var $objectsArr = array();
    
    var $targetFilename = '';
    var $targetContent = '';
    
    function __construct($targetFileLocalUrl='') {
        if(!empty($targetFileLocalUrl)) $this->targetFilename = $targetFileLocalUrl;
        else $this->generateFilename();
    }
    function generateFilename() {
        global $user;
        $this->targetFilename = $this->webRelCacheDir.$user->currentPageId.($user->idkontrol*1).'.js';
    }
    function cleanCache() {
        //---delete all from cache directory
    }
    function addFile($fileUrl) {
        $this->objectsArr[]=array('type'=>'file','content'=>$fileUrl);
    }
    function addCode($text) {
        $this->objectsArr[]=array('type'=>'code','content'=>$text);
    }
    function parse() {
        if(!empty($this->objectsArr))
        foreach ($this->objectsArr as $item) {
            if($item['type']=='file') {
                $this->targetContent .= file_get_contents($item['content'])."\n";
            } else {
                $this->targetContent .= $item['content']."\n";
            }
        }    
    }
    function get() {
        if(!file_exists($this->targetFilename)){
            $this->parse();
            if(!empty($this->targetContent)) {
                //save file
                file_put_contents($this->targetFilename,$this->targetContent);
                chmod($this->targetFilename,0777);
                //return filename
                return $this->targetFilename;
            }
        } else return $this->targetFilename;
    }
}