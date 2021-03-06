<?php
class FCategory extends FDBTool {
	var $ident = 'cat'; //---required
	var $tplObject;

	var $arrHead;
	var $arrInputType;
	var $arrClass;
	var $arrDbUsedCols;
	var $arrDefaults;
	var $arrOptions;

	//selected category - by GET parameter
	var $selected;

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

	function __construct($tableName,$primaryCol) {
		$this->arrPublic = FLang::$ARRPUBLIC;
		parent::__construct($tableName,$primaryCol);
		//---defaults for galery,culture,linx,audit,akce
		$this->arrHead=array(FLang::$LABEL_CATEGORY_NAME,FLang::$LABEL_CATEGORY_ORDER,FLang::$LABEL_CATEGORY_PUBLIC);
		$this->arrInputType=array("text","text",'public');
		$this->arrClass=array('form-control','form-control','form-control');
		$this->arrDbUsedCols=array('name','ord','public');
		$this->arrDefaults=array('','0','1');
		
		$this->requiredCol = 'name';
		$this->setOrder('ord');
		$user = FUser::getInstance();
		if(!$user->idkontrol) $this->addWhere('public=1');
	}

	/**
	 * get list of options for select HTML input
	 *
	 * @param unknown_type $arr
	 * @param unknown_type $selected
	 * @param unknown_type $firstEmpty
	 * @param unknown_type $firstText
	 * @return unknown
	 */
	static function getOptions($arr,$selected='',$firstEmpty=true,$firstText='') {
		if(!is_array($arr)) {
			$arr = FDBTool::getAll('select categoryId,name from sys_pages_category where typeId="'.$arr.'"');
		}
		$options = '';
		if(!empty($arr)) {
			$arrkeys = array_keys($arr);
			if(is_array($arr[$arrkeys[0]])) {
				foreach ($arr as $row) $newArr[$row[0]] = $row[1];
				$arr = $newArr;
			}
			if($firstEmpty==true) $options .= FText::options("",$firstText);
			foreach ($arr as $k=>$v) $options .= FText::options($k,$v,$selected);
		}
		return $options;
	}

	//STATIC FUNCTIONS
	static function getCategory($categoryId) {
		return FDBTool::getRow("select categoryId,typeId,name,ord,public from sys_pages_category where categoryId='".$categoryId."'");
	}

	function parseComboBox($blockname,$selectname,$arr,$selected=0){
		$tpl = &$this->tplObject;
		foreach ($this->arrDefaultValues as $k=>$v) $arrtmp['SELECT'.$k]=$v;
		$tpl->setVariable($arrtmp);
		if(!empty($arr))
		foreach ($arr as $k=>$v) {
			$tpl->setVariable("OPTIONVALUE",$k);
			if(!empty($selected)) $tpl->setVariable("OPTIONSELECTED",(($selected==$k)?(' selected="selected"'):('')));
			$tpl->setVariable("OPTIONTEXT",$v);
			$tpl->parse("arr".$blockname);

		}
		$tpl->setVariable("SELECTNAME",$selectname);
		$tpl->parse("kateg".$blockname);
		$tpl->parse("kategformelement".$this->formElement);
	}

	function getInput($blockname,$name,$value=''){
		$tpl = &$this->tplObject;
		foreach ($this->arrDefaultValues as $k=>$v) $arrtmp['INPUT'.$k]=$v;
		$tpl->setVariable($arrtmp);
		if($value!='') $tpl->setVariable("INPUTVALUE",$value);
		$tpl->setVariable("INPUTCOL",$name);
		$tpl->setVariable("INPUTCLASS",$this->arrClass[$this->key]." ");
		$tpl->setVariable("INPUTTYPE",$this->arrInputType[$this->key]);
		$tpl->parse("kategformelement".$this->formElement);
		$tpl->parse($blockname);
	}

	function getUriAddon() {
		$user = FUser::getInstance();
		$rediraddon = '';
		$urlVar  = FConf::get('pager','urlVar');
		if(!empty($_REQUEST[$urlVar])) $this->arrRedirAddon[$urlVar] = $_REQUEST[$urlVar];
		if(!empty($this->arrRedirAddon)) foreach ($this->arrRedirAddon as $k=>$v) $rediraddon .= '&'.$k.'='.$v;
		return $rediraddon;
	}

	function process($data) {
		if(!empty($data["kat".$this->ident]))
		foreach ($data["kat".$this->ident] as $k=>$kat){
			foreach ($kat as $key=>$val) $kat[$key] = trim($val);
			if(isset($this->arrSaveAddon)) $kat=array_merge($kat,$this->arrSaveAddon);
			if($kat[$this->requiredCol]!='') {
				$sCat = new FDBTool($this->table,$this->primaryCol);
				if($k > 0){ //update
					$kat[$this->primaryCol] = $k;
					$dot = $sCat->buildUpdate($kat);
				} else { //insert
					$dot = $sCat->buildInsert($kat);
				}
				$sCat->query($dot);
			}
		}
		if (isset($data["del".$this->ident])) {
			foreach ($data["del".$this->ident] as $gkid) {
				FDBTool::query('delete from '.$this->table.' where '.$this->primaryCol.'="'.$gkid.'"');	
			}	
		}
	}

	function getEdit() {
		$tpl = &$this->tplObject;
		if(empty($this->ident) || empty($this->table) || empty($this->primaryCol)) return false;

		$rediraddon = $this->getUriAddon();

		//---show part
		//nazev,popis,poradi,public,delete
		$this->setSelect($this->primaryCol.','.implode(',',$this->arrDbUsedCols));
		$total = $this->getCount();

		$tpl = FSystem::tpl($this->template);

		$addToUrl = $rediraddon;
		if($total > $this->perpage) {
			$pager = new FPager($total,$this->perpage);
			$actualPid = $pager->getCurrentPageID();
			$from=($actualPid-1) * $this->perpage;
			$this->setLimit($from,$this->perpage);
			$tpl->setVariable("PAGER",$pager->links);
		}

		$arr = $this->getContent();

		$user = FUser::getInstance();
		$tpl->setVariable("FORMACTION",FSystem::getUri($addToUrl));

		$arrheadtmp = $this->arrHead;
		if ($this->editlink > 0) $arrheadtmp['akce']='';
		if($this->isDel == true) $arrheadtmp['del']= FLang::$LABEL_CATEGORY_DELETE;

		$tpl->setVariable("COLS",count($arrheadtmp));
		foreach ($arrheadtmp as $td) {
			$tpl->setVariable("COLUMN",$td);
			$tpl->parse("kateghead");
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
					$this->parseComboBox('select',$col,$this->arrOptions[$col],$kat[$arrColsSwitched[$col]+1]);
				} else {
					$this->getInput("kateginput",$col,$kat[$arrColsSwitched[$col]+1]);
				}
			}
			if ($this->editlink > 0) {
				$tpl->setVariable("EDITKAM",$this->editlink);
				$tpl->setVariable("EDITID",$kat[$this->primaryCol]);
			}
			$tpl->setVariable($this->arrDefaultValues);
			$tpl->parse("arrkateg");
		}
		//--- new one

		$this->formElement = 'new';
		$this->arrDefaultValues=array("KATEG"=>$this->ident);

		foreach ($this->arrDbUsedCols as $this->key=>$col){
			if($this->arrInputType[$this->key]=='public'){
				//---select public
				$this->parseComboBox('selectnew',$col,$this->arrPublic);
			} elseif($this->arrInputType[$this->key]=='select') {
				$this->parseComboBox('selectnew',$col,$this->arrOptions[$col]);
			} else {
				$this->getInput("kateginputnew",$col);
			}
		}

		return $tpl->get();
	}

	static function tryGet( $newCat, $typeId, $pageIdTop=null ) {
		$newCat = trim($newCat);
		if(!empty($newCat)) {
			//check first if it does not exist
			$q = "select categoryId,name from sys_pages_category where typeId='".$typeId."'";
			$arrCat = FDBTool::getAll($q);
			$percentHighest = 0;
			$catIdHighest = 0;
			foreach($arrCat as $row) {
				$sim = similar_text(strtolower($newCat),strtolower($row[1]),$percent);
				if($percent > $percentHighest) {
					$percentHighest = $percent;
					$catIdHighest = $row[0];
				}
			}
			if($percentHighest > 79) {
				return $catIdHighest;
			} else {
				$catVO = new CategoryVO();
				$catVO->name = $newCat;
				$catVO->typeId = $typeId;
				if(!empty($pageIdTop)) $catVO->pageIdTop = $pageIdTop;  
				if($catVO->save()) {
					$newCategory = true;
					FMessages::send(1,'NEW CATEGORY CREATED ::' .$newCat. ' :: '.$typeId.' :: <a href="'.FSystem::getUri('who='.FUser::logon().'#tabs-profil','finfo').'">user</a>');
					FCommand::run(CATEGORY_UPDATED);
					return $catVO->categoryId;
				}
			}
		}
	}
}