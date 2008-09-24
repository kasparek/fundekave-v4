<?php
class fCategory extends fQueryTool {
	var $ident = 'cat'; //---required
	var $tplObject;
	
	var $arrHead;
	var $arrInputType;
	var $arrClass;
	var $arrDbUsedCols;
	
	var $arrSaveAddon;
	var $arrRedirAddon;
	var $template = 'category.edit.tpl.html';
	var $templateList = 'category.list.tpl.html';
	var $requiredCol = '';
	var $arrPublic;
	var $isDel = true;
	var $arrDefaultValues;
	var $key = 0;
	var $formElement = ''; //'' nebo new
	var $perpage = 30;
	var $editlink = false;
	
	function __construct($tableName,$primaryCol,&$db=false) {
		global $user,$ARRPUBLIC;
		$this->arrPublic = $ARRPUBLIC;
		parent::__construct($tableName,$primaryCol,&$db);
		//---defaults for galery,culture,linx,audit,akce
		$this->arrHead=array(LABEL_CATEGORY_NAME,LABEL_CATEGORY_ORDER,LABEL_CATEGORY_PUBLIC);
		$this->arrInputType=array("text","text",'public');
		$this->arrClass=array('','small','');
		$this->arrDbUsedCols=array('name','ord','public');
		$this->requiredCol = 'name';
		$this->setOrder('ord');
		if(!$user->idkontrol) $this->addWhere('public=1');
	}
	/**
	 * print list of categories to select from - static html for frontend
	 *
	 * @param String $typeId - from sys_pages_category
	 * @return String - HTML
	 */
	function getList($typeId='') {
		global $user,$db;
		if(isset($_GET['kat'])) $selectedCat = (int) $_GET['kat'];
		else $selectedCat = 0;
		if(!empty($typeId)) $this->addWhere("typeId='".$typeId."'");
		$this->addOrder('name');
		$this->setSelect('categoryId,name,description');
		$arr = $this->getContent();
		if(!empty($arr)) {
			$this->tplObject = new fTemplateIT($this->templateList);
			foreach ($arr as $row) {
				if($row[0] == $selectedCat) $this->tplObject->setVariable('CATEGORYNAME',$row[1]);
				$this->tplObject->setCurrentBlock('category');
				$this->tplObject->setVariable('CATLINK',$user->getUri('kat='.$row[0]));
				$this->tplObject->setVariable('CATNAME',$row[1]);
				$this->tplObject->setVariable('DESC',$row[2]);
				$this->tplObject->parseCurrentBlock('category');
			}
			return $this->tplObject->get();
		}
	}
	
	function parseComboBox($blockname,$selectname,$arr,$selected=0){
		foreach ($this->arrDefaultValues as $k=>$v) $arrtmp['SELECT'.$k]=$v;
		$this->tplObject->setVariable($arrtmp);
		foreach ($arr as $k=>$v) {
			$this->tplObject->setCurrentBlock("arr".$blockname);
			$this->tplObject->setVariable("OPTIONVALUE",$k);
			if(!empty($selected)) $this->tplObject->setVariable("OPTIONSELECTED",(($selected==$k)?(' selected="selected"'):('')));
			$this->tplObject->setVariable("OPTIONTEXT",$v);
			$this->tplObject->edParseBlock("arr".$blockname);	
		}
		$this->tplObject->setCurrentBlock("kateg".$blockname);
		$this->tplObject->setVariable("SELECTNAME",$selectname);
		$this->tplObject->edParseBlock("kateg".$blockname);
		$this->tplObject->setCurrentBlock("kategformelement".$this->formElement);
		$this->tplObject->edParseBlock("kategformelement".$this->formElement);
	}
	function getInput($blockname,$name,$value=''){
		$this->tplObject->setCurrentBlock($blockname);
		foreach ($this->arrDefaultValues as $k=>$v) $arrtmp['INPUT'.$k]=$v;
		$this->tplObject->setVariable($arrtmp);
		if($value!='') $this->tplObject->setVariable("INPUTVALUE",$value);
		$this->tplObject->setVariable("INPUTCOL",$name);
		$this->tplObject->setVariable("INPUTCLASS",$this->arrClass[$this->key]." ");
		$this->tplObject->setVariable("INPUTTYPE",$this->arrInputType[$this->key]);
		$this->tplObject->setCurrentBlock("kategformelement".$this->formElement);
		$this->tplObject->edParseBlock("kategformelement".$this->formElement);
		$this->tplObject->edParseBlock($blockname);
	}
	function getEdit() {
		global $kam,$user,$conf;
		
		if(empty($this->ident) || empty($this->table) || empty($this->primaryCol)) return false;
		
		$rediraddon = '';
		if(!empty($_REQUEST[$conf['pager']['urlVar']])) $this->arrRedirAddon[$conf['pager']['urlVar']] = $_REQUEST[$conf['pager']['urlVar']];
		if(!empty($this->arrRedirAddon)) foreach ($this->arrRedirAddon as $k=>$v) $rediraddon .= '&'.$k.'='.$v;
		
		//---action part
		if(isset($_POST["save"])){
			if(!empty($_POST["kat".$this->ident])) 
				foreach ($_POST["kat".$this->ident] as $k=>$kat){
					foreach ($kat as $key=>$val) $kat[$key] = trim($val);
					if(isset($this->arrSaveAddon)) $kat=array_merge($kat,$this->arrSaveAddon);
					if($kat[$this->requiredCol]!='') {
						$sCat = new fSqlSaveTool($this->table,$this->primaryCol);
						if($k > 0){ //update
							$kat[$this->primaryCol] = $k;
							$dot = $sCat->buildUpdate($kat);
						} else { //insert
							$dot = $sCat->buildInsert($kat);
						}
						$this->db->query($dot);
					}
				}
			if (isset($_POST["del".$this->ident])) foreach ($_POST["del".$this->ident] as $gkid) $this->db->query('delete from '.$this->table.' where '.$this->primaryCol.'="'.$gkid.'"');
			$user->refresh();
			
			fHTTP::redirect($user->getUri($rediraddon));
		}
		//---show part
		//nazev,popis,poradi,public,delete
		$this->setSelect($this->primaryCol.','.implode(',',$this->arrDbUsedCols));
		$total = $this->getCount();
		
		$this->tplObject = new fTemplateIT($this->template);
		$this->tplObject->setCurrentBlock("kateg");
		
		$addToUrl = $rediraddon;
		if($total > $this->perpage) {
			$pager = fSystem::initPager($total,$this->perpage);
			$actualPid = $pager->getCurrentPageID();
			$from=($actualPid-1) * $this->perpage;
			$this->setLimit($from,$this->perpage);
			$this->tplObject->setVariable("PAGER",$pager->links);
		}
		
		$arr = $this->getContent();
		
		
		$this->tplObject->setVariable("FORMACTION",$user->getUri($addToUrl));

		$arrheadtmp = $this->arrHead;
		if ($this->editlink > 0) $arrheadtmp['akce']='';
		if($this->isDel == true) $arrheadtmp['del']=LABEL_CATEGORY_DELETE;
		
		$this->tplObject->setVariable("COLS",count($arrheadtmp));
		foreach ($arrheadtmp as $td) {
				$this->tplObject->setCurrentBlock("kateghead");
				$this->tplObject->setVariable("COLUMN",$td);
				$this->tplObject->edParseBlock("kateghead");	
		}
		$this->formElement = '';
		if(!empty($arr))
		foreach ($arr as $kat) {
			$this->arrDefaultValues = array("KATEG"=>$this->ident,"ID"=>$kat[0]);
			$arrColsSwitched = array_flip($this->arrDbUsedCols);
			
			foreach ($this->arrDbUsedCols as $this->key=>$col){
				if($this->arrInputType[$this->key]=='public'){
					//---select public
					$this->parseComboBox('select',$col,$this->arrPublic,$kat[$arrColsSwitched[$col]+1]);
				} elseif($this->arrInputType[$this->key]=='select') {
					$this->parseComboBox('select',$col,$this->arrOption[$col],$kat[$arrColsSwitched[$col]+1]);
				} else {
					$this->getInput("kateginput",$col,$kat[$arrColsSwitched[$col]+1]);
				}
			}
			if ($this->editlink > 0) {
					$this->tplObject->setVariable("EDITKAM",$this->editlink);
					$this->tplObject->setVariable("EDITID",$kat[$this->primaryCol]);
			}
			$this->tplObject->setCurrentBlock("arrkateg");
			$this->tplObject->setVariable($this->arrDefaultValues);		
			$this->tplObject->edParseBlock("arrkateg");
		}
		//--- new one
		
		$this->formElement = 'new';
		$this->arrDefaultValues=array("KATEG"=>$this->ident);
		
		foreach ($this->arrDbUsedCols as $this->key=>$col){
			if($this->arrInputType[$this->key]=='public'){
				//---select public
				$this->parseComboBox('selectnew',$col,$this->arrPublic);
			} elseif($this->arrInputType[$this->key]=='select') {
					$this->parseComboBox('selectnew',$col,$this->arrOption[$col]);
			} else {
				$this->getInput("kateginputnew",$col);
			}
		}
				
		return $this->tplObject->get();
	}
}
?>