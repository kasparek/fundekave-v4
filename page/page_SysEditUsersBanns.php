<?php
include_once('iPage.php');
class page_SysEditUsersBanns implements iPage {

	static function process() {
		if(isset($_GET['du']) && FRules::getCurrent()) FUser::invalidateUsers($_GET['du']);
		if(isset($_POST["usersstat"]) && FRules::getCurrent()) {
			foreach ($_POST["usersstat"] as $k=>$v){
				FDBTool::query("update sys_users set deleted=".$v." where userId=".$k);
			}
			FHTTP::redirect(FUser::getUri());
		}
		if(isset($_REQUEST['usrfilter'])) {
			$cache = FCache::getInstance('s');
			$cache->setData($_REQUEST['usrfilter'],'ubann','filtr');
		}
	}

	static function build() {
		$cache = FCache::getInstance('s');
		if(false !== ($filtr = $cache->getData('ubann','filtr'))) $usrfilter = $filtr;
		else $usrfilter=0;

		//-----------------SELECT
		$base = "FROM sys_users as s ".(($usrfilter!=3)?(' left join '):(' join '))." sys_users_logged as l on l.userId=s.userId ";
		if($usrfilter==1) $base.=" where s.dateUpdated is null";
		elseif($usrfilter==2) $base.=" where s.dateUpdated is not null";
		elseif($usrfilter==4) $base.=" where s.deleted = 0";
		elseif($usrfilter==5) $base.=" where s.deleted = 1";
		$base.=' order by s.userId desc ';
		$perpage = 40;
		$total = FDBTool::getOne("SELECT count(1) ".$base);

		$tpl = new fTemplateIT('sys.admin.bann.tpl.html');
		$tpl->setVariable('TOTALITEMS',$total);
		$dot = "SELECT s.userId,s.name,s.deleted,s.dateUpdated,s.dateCreated,s.hit,l.ip,s.ipcheck ".$base;
		if($total>$perpage) {
			$pager = fSystem::initPager($total,$perpage);
			$od=($pager->getCurrentPageID()-1) * $perpage;
			$dot .=" limit ".$od.",".$perpage;
			$tpl->setVariable('PAGER',$pager->links);
		}
		$tpl->touchBlock('filter'.$usrfilter);

		$users = FDBTool::getAll($dot);
		//-------------------SHOW users
		foreach ($users as $usr) {
			$tpl->setCurrentBlock('user');
			$tpl->setVariable('ID',$usr[0]);
			$tpl->setVariable('NAME',$usr[1]);
			$tpl->setVariable('URL','?k=finfo&who='.$usr[0]);
			$tpl->setVariable('CREATED',$usr[3]);
			$tpl->setVariable('UPDATED',$usr[4]);
			$tpl->setVariable('HIT',$usr[5]);
			$tpl->touchBlock('userlocked'.($usr[2]*1));
			if(!empty($usr[6])) {
				$tpl->setVariable('DISCONNECTURL',FUser::getUri('du='.$usr[0]));
				$tpl->setVariable('IP',$usr[6]);
			}
			$tpl->parseCurrentBlock();
		}

		FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));
	}
}