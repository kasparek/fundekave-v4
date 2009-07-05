<?php
class FLeftPanelEdit {
function getAvailablePanels() {
		$user = FUser::getInstance();

		$this->load(true);
		//---1. not used, 2.user has access to use (system panels only sa,other by pageid)
		$this->setSelect('f.functionName,f.name,f.pageId');
		$this->setOrder('f.name');
		$arr = $this->getContent();
		foreach ($arr as $row) {
			if(!in_array($row[0],$this->panelsUsed)) {
				//not used panel
				if(!empty($row[2])) {
					//check for pageid
					if(FRules::get($user->userVO->userId,$row[2],2)) {
						$arrAvailable[] = $row;
					}
				} else {
					//must have sa access
					if(FRules::get($user->userVO->userId,'sadmi',1)) {
						$arrAvailable[] = $row;
					}
				}
			}
		}
		if(!empty($arrAvailable)) return $arrAvailable;
	}
	
function panelInsert($type,$functionName,$sequence,$visible=1) {
		 
		if($type=='page') {
			$dot = "insert into sys_leftpanel_pages (pageId,functionName,ord,visible) values ('".$this->pageId."','".$functionName."',".$sequence.",".$visible.")";
		}
		if(!empty($dot)) {
			return $this->query( $dot );
		}
	}
	function panelUpdate($type,$functionName,$arr) {

		//---get current settings
		foreach ($this->panels as $panel) {
			if($panel['functionName']==$functionName) {
				$currentPanel = $panel;
				break;
			}
		}
		if($type=='page') {
			$update = false;
			//---check sequence
			if(isset($arr['sequence'])) {
				$sequence = (int) $arr['sequence'];
				if($sequence != $panel['ord']) {
					$currentPanel['ord'] = $sequence;
					$update = true;
				}
			}
			if(isset($arr['visible'])) {
				if($arr['visible']==0) {
					$currentPanel['visible'] = 0;
					$update = true;
				}
			}
			if(isset($arr['delete'])) {
				if($arr['delete']==0) {
					$currentPanel['delete'] = 'page';
					$update = true;
				}
			}

			if($update===true) {
				if($currentPanel['origin']=='system') {
					//---insert
					if(!isset($currentPanel['visible'])) $currentPanel['visible'] = 1;
					$this->panelInsert('page',$functionName,$currentPanel['ord'],$currentPanel['visible']);
				} else {
					//---update
					if(isset($currentPanel['delete'])) {
						$this->query("delete from sys_leftpanel_pages where pageId='".$this->pageId."' and functionName='".$functionName."'");
					} else {
						$this->query("update sys_leftpanel_pages set sequence=".$currentPanel['ord'].",visible=".$currentPanel['visible']." where pageId='".$this->pageId."' and functionName='".$functionName."'");
					}
				}
			}

		}
	}
	function process($postArr) {
		$this->load(true);
		foreach ($postArr as $k=>$panel) {
			if($k=='new') {
				if(!empty($panel['name'])) {
					//---add new panel
					$this->panelInsert('page',$panel['name'],$panel['sequence']);
				}
			} else {
				//---update
				$this->panelUpdate('page',$k,$panel);
			}
		}

	}
	function showEdit() {

		//$tpl = new FTemplateIT('leftpanel.page.set.tpl.html');
		$this->tpl = new FHTMLTemplateIT(ROOT.ROOT_TEMPLATES);
			$this->tpl->loadTemplatefile('leftpanel.page.set.tpl.html');


		$this->load(true);

		foreach ($this->panels as $panel) {
			$tpl->setCurrentBlock('panelrow');
			$tpl->setVariable('NAMESYS',$panel['functionName']);
			$tpl->setVariable('NAME',$panel['name']);
			$tpl->setVariable('LEFTID',$panel['functionName']);
			$tpl->setVariable('SEQUENCE',$panel['ord']);
			if($panel['origin']!='system') {
				$tpl->setVariable('DELETEID',$panel['functionName']);
				$tpl->touchBlock('visible'.($panel['visible']*1));
			} else {
				$tpl->setVariable('VISIBLEID',$panel['functionName']);
				if($panel['visible']==0) $tpl->touchBlock('invisible');
			}
			$tpl->parseCurrentBlock();
		}

		$arr = $this->getAvailablePanels();
		if(!empty($arr)) {
			$tpl->setVariable('AVAILABLEPANELS',FCategory::getOptions($arr,'',true,''));
		}
    $rer = $tpl->get();
    $tpl=false;
		return $ret;
	}
}