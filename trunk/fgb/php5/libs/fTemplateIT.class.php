<?php
require('fHTML_Template_IT.class.php');

class fTemplateIT extends fHTML_Template_IT {
	
	var $vars;
	
	function __construct($templatefile,$root = '',$removeUnknownVariables=TRUE, $removeEmptyBlocks=TRUE){
	    if($root == '') $root = ROOT.ROOT_TEMPLATES;
	    $this->fHTML_Template_IT($root);
		$this->loadTemplatefile($templatefile, $removeUnknownVariables, $removeEmptyBlocks); 
	}
	
	function rejoice(){
		$this->blockdata = '';
		$this->flagGlobalParsed = false;
	}
/*
* params fromArray()
* blockname - block to set
* arr - array of values with keys named as placeholder in template
*/ 
	function fromArray($blockname,$arr,$edParse = false){
		if(is_array($arr))
		foreach ($arr as $row){
			$this->setCurrentBlock($blockname);
			foreach ($row as $k=>$v) {
				if($v!='') $this->setVariable($k, $v);
			}
			if($edParse==true) $this->edParseBlock($blockname);
			else $this->parseCurrentBlock();
		}
	}
	function edParseBlock($block='__global__') {
		$vars = $this->vars;
		
		if (isset($this->blocklist[$block]) && !empty($this->vars)) {
			if(preg_match_all("{{([A-Za-z0-9]*)}}", $this->blocklist[$block], $arr)){
				foreach($arr[1] as $vartoset){
				    $vartosetLower = strtolower($vartoset);
					if(isset($this->vars[$vartoset])){
						$this->setVariable($vartoset,$vars[$vartoset]);
					} elseif (isset($this->vars[$vartosetLower])) {
					    $this->setVariable($vartoset,$vars[$vartosetLower]);
					}
				}
			}
		}
		$this->parse($block);
	}
	
	function printErrorMsg(){
		$arrerr = fError::getError();
		if(is_array($arrerr)){
			foreach ($arrerr as $err) $str[]=$err;
			if(isset($str)){
				$this->setCurrentBlock("errormsg");
				$this->setVariable("ERRORMSG",implode("<br />",$str));
				$this->setCurrentBlock("errormsg");
				fError::resetError();
			}
		}
	}
	var $arrTabs = array();
	function addRaw($data) {
    $this->setCurrentBlock('maincontent-raw');
    $this->setVariable('RAWDATA',$data);
    $this->parseCurrentBlock();
  }
	function addTab($arrVars) {
    $this->setCurrentBlock('maincontent-recurrent');
    foreach ($arrVars as $k=>$v)  {
      if($v!='') $this->setVariable($k, $v);
    }
    if(!empty($arrVars['TABID']) && !empty($arrVars['TABNAME'])) {
        //--add to tabs
        //--if tabs enabled - parse tab menu and include js
        $this->arrTabs[] = $arrVars;
        $this->touchBlock('tabidclose');
    }
    $this->parseCurrentBlock();
  }
  function createTabs() {
    if(!empty($this->arrTabs)) {
      foreach($this->arrTabs as $tab) {
        $this->setCurrentBlock('domtabmenuitem');
        $tpl->setVariable('TABMENUID', $tab['TABID']);
        $tpl->setVariable('TABMENUNAME', $tab['TABNAME']);
        $this->parseCurrentBlock();
      }
      $this->touchBlock('domtabclose');
    }
  }
  function addTextareaToolbox($key,$textareaId) {
      global $user;
      if($user->idkontrol) {
          $ftpl = new fTemplateIT('textarea.toolbox.tpl.html');
          $ftpl->setVariable('TEXTAREAID',$textareaId);
          $this->setVariable($key,$ftpl->get());
          unset($ftpl);
      }
  }
  function moveBlock($blockName,$variableName) {
    $tpl->parse($blockName);
    $block = $tpl->get($blockName);
    $tpl->setVariable($variableName,$block);
    $tpl->blockdata[$blockName] = '';
  }
}