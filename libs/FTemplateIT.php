<?php
class FTemplateIT extends FHTMLTemplateIT {

	var $vars;

	function __construct($templatefile,$root = '',$removeUnknownVariables=TRUE, $removeEmptyBlocks=TRUE){
		if($root == '') $root = ROOT.ROOT_TEMPLATES;
		parent::__construct($root);
		$this->loadTemplatefile($templatefile, $removeUnknownVariables, $removeEmptyBlocks);
	}

	static function templateExist($template) {
		return file_exists(ROOT.ROOT_TEMPLATES.$template);
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
		$arrerr = FError::getError();
		if(is_array($arrerr)){
			foreach ($arrerr as $err) $str[]=$err;
			if(isset($str)){
				$this->setCurrentBlock("errormsg");
				$this->setVariable("ERRORMSG",implode("<br />",$str));
				$this->setCurrentBlock("errormsg");
				FError::resetError();
			}
		}
	}

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
			$this->touchBlock('tabidclose');
		}
		$this->parseCurrentBlock();
	}

	function moveBlock($blockName,$variableName) {
		$tpl->parse($blockName);
		$block = $tpl->get($blockName);
		$tpl->setVariable($variableName,$block);
		$tpl->blockdata[$blockName] = '';
	}
}