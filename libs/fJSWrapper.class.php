<?php
class fJSWrapper {
    /**
     * contaion array('type'=>file|code,'content'=>text)
     *
     * @var array
     */
    var $systemRelCacheDir = '';
    var $webRelCacheDir = '';
    
    var $objectsArr = array();
    
    var $targetFilename = '';
    var $targetContent = '';
    
    function __construct($systemPathToWeb,$webCachePath,$filename) {
      $this->systemRelCacheDir = $systemPathToWeb;
      $this->webRelCacheDir = $webCachePath;
      $this->targetFilename = $filename;
    }
    function isCached() {
      return file_exists($this->systemRelCacheDir . $this->targetFilename);
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
                if(file_exists($item['content'])) $this->targetContent .= file_get_contents($item['content'])."\n";
            } else {
                $this->targetContent .= $item['content']."\n";
            }
        }    
    }
    function get() {
        if(!$this->isCached()){
            $this->parse();
            if(!empty($this->targetContent)) {
                //save file
                file_put_contents($this->systemRelCacheDir . $this->targetFilename,$this->targetContent);
                chmod($this->systemRelCacheDir . $this->targetFilename,0777);
                //return filename
                return $this->webRelCacheDir . $this->targetFilename;
            }
        } else return $this->webRelCacheDir . $this->targetFilename;
    }
}