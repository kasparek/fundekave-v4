<?php
require('fHTML_Template_IT.class.php');

class fTemplateIT extends fHTML_Template_IT {
	
	var $vars;
	
	function __construct($templatefile,$root = TEMPLATEROOT,$removeUnknownVariables=TRUE, $removeEmptyBlocks=TRUE){
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
}
?>