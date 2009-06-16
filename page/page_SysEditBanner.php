<?php
//TODO: refactor - sqlsavetool, _POST
include_once('iPage.php');
class page_SysEditBanner implements iPage {

	static function process($data) {


		//upload
		if(!empty($data['__files']['bann'])) {
			if($ak=upload($data['__files']["bann"],WEB_REL_BANNER)) {
				FError::addError(FLang::$MESSAGE_UPLOAD_SUCCESS);
				FHTTP::redirect(FUser::getUri());
			}
		}
		
		//delete file from ftp
		if(isset($data['__get']['ibd'])) {
			$banner =  WEB_REL_BANNER.trim($data['__get']['ibd']);
			if(file_exists($banner)) {
				if($ak = @unlink($banner)){
					FError::addError(FLang::$LABEL_DELETED_OK);
					FHTTP::redirect(FUser::getUri());
				} else {
					FError::addError(FLang::$LABEL_FILE.' '.FLang::$LABEL_NOTEXISTS.': '.$banner);
				}
			}
		}
		//save banner
		if(isset($data['__files']['bid'])) {
			$sBanner = new fSqlSaveTool('sys_banner','bannerId');

			if($data['__files']['bid']>0) {
				$arr['bannerId']= $data['__files']['bid'] * 1;
				$arr['dateUpdated']='now()';
			}	else {
				$user = FUser::getInstance();
				$arr['userId']=$user->userVO->userId;
				$arr['dateCreated']='now()';
			}

			if(FSystem::isDate($_POST['eddatefrom'])) $arr['dateFrom']=$data['__files']['eddatefrom'];
			else FError::addError(FLang::$ERROR_DATE_FORMAT);

			if(FSystem::isDate($_POST['eddateto'])) $arr['dateTo']=$_POST['eddateto'];
			else FError::addError(ERROR_DATE_FORMAT);

			if(isset($_POST['edstrict'])) $arr['strict'] = 1; else $arr['strict'] = 0;

			$arr['linkUrl'] = Trim($_POST['edurl']);
			if(empty($arr['linkUrl'])) FError::addError(FLang::$ERROR_BANNER_TARGETEMPTY);
			$arr['imageUrl'] = Trim($_POST['edhtml']);
			if(empty($arr['imageUrl'])) FError::addError(FLang::$ERROR_BANNER_EMPTY);


			if(!FError::isError()) {
				$bannerId = $sBanner->save($arr,array('dateCreated','dateUpdated'));
				FError::addError(FLang::$MESSAGE_SUCCESS_SAVED);
			}

			FHTTP::redirect(FUser::getUri('ebe='.$bannerId));
		}

		//delete banner from db
		if(isset($_GET['ebd'])) {
			$bannerId = $_GET['ebd'] * 1;
			if($bannerId>0) {
				$db->query("delete from sys_banner where bannerId=".$bannerId);
				FError::addError(FLang::$LABEL_DELETED_OK);
				FHTTP::redirect(FUser::getUri());
			}
		}
	}

	static function build() {

		//-----novy banner
		$tpl = new FTemplateIT('sys.edit.banners.tpl.html');
		$tpl->setVariable('FORMACTION',FUser::getUri());

		if(isset($_GET['ebe'])) {
			$bannerIdEdit = (int) $_GET['ebe'];
			if($bannerIdEdit>0) {
				$arr = FDBTool::getRow("select bannerId,
imageUrl,
linkUrl,
date_format(dateFrom,'%Y-%m-%d') as datumod,
date_format(dateTo,'%Y-%m-%d') as datumdo,
userId,
hit,
display,
strict from sys_banner where bannerId='".$bannerIdEdit."'");
			}
		}
		if(isset($_GET['ibn'])) $bannerName = $_GET['ibn'];

		if(!empty($arr)) {
			$tpl->setVariable('EDHTML',$arr[1]);
			$tpl->setVariable('EDTARGETURL',$arr[2]);

			$tpl->setVariable('EDDATEFROM',$arr[3]);
			$tpl->setVariable('EDDATETO',$arr[4]);

			if($arr[8]==1) $tpl->touchBlock('edstrict');
			$tpl->setVariable('EDBANNERID',$arr[0]);

			$bannerName = $arr[1];
		} elseif (!empty($bannerName)) {
			$tpl->setVariable('EDBANNERID',0);
			$tpl->setVariable('EDHTML',$bannerName);

			$tpl->setVariable('EDDATEFROM',Date('Y-m-d'));
			$tpl->setVariable('EDDATETO',((Date('Y')+10).'-'.Date("m-d")));
		}
		if(!empty($bannerName)) {
			$tpl->setVariable('EDACTION',FUser::getUri());
			if(preg_match("/(.swf)$/",$bannerName)) {
				$tpl->setVariable('EDSWFURL',WEB_REL_BANNER.$bannerName);
				$tpl->setVariable('EDSWFNAME',$bannerName);
			} elseif(preg_match("/(jpg|gif)$/",$bannerName)) {
				$tpl->setVariable('EDURL',WEB_REL_BANNER.$bannerName);
				$tpl->setVariable('EDNAME',$bannerName);
			} else {
				$tpl->setVariable('UNEDURL',WEB_REL_BANNER.$bannerName);
				$tpl->setVariable('UNEDNAME',$bannerName);
			}
		}

		//----stary bannerz
		$arr=FDBTool::getAll("SELECT bannerId,
imageUrl,
linkUrl,
date_format(dateFrom,'%d.%m.%Y') as datumodcz,
date_format(dateTo,'%d.%m.%Y') as datumdocz,
userId,
hit,
display,
strict 
FROM sys_banner ORDER BY dateFrom desc");
		$arrUsedFiles = array();
		if(!empty($arr)) {
			foreach ($arr as $row) {
				$tpl->setCurrentBlock('banner');
				if($row[8]==1) $tpl->touchBlock('strict');
				$arrUsedFiles[] = $file = $row[1];
				if(preg_match("/(.swf)$/",$file)) {
					$tpl->setVariable('BANNERSWFURL',WEB_REL_BANNER.$file);
					$tpl->setVariable('BANNERSWFNAME',$file);
				} elseif(preg_match("/(jpg|gif)$/",$file)) {
					$tpl->setVariable('BANNERURL',WEB_REL_BANNER.$file);
					$tpl->setVariable('BANNERNAME',$file);
				} else {
					$tpl->setVariable('UNBANNERURL',WEB_REL_BANNER.$file);
					$tpl->setVariable('UNBANNERNAME',$file);
				}

				$tpl->setVariable('URL',$row[2]);

				$tpl->setVariable('DATEFROM',$row[3]);
				$tpl->setVariable('DATETO',$row[4]);
				$tpl->setVariable('TIMESDISPLAY',$row[7]);
				$tpl->setVariable('TIMESHIT',$row[6]);
				$tpl->setVariable('OWNERLINK','?k=finfo&who='.$row[5]);
				$tpl->setVariable('OWNERNAME',FUser::getgidname($row[5]));
				$tpl->setVariable('EDITURL',FUser::getUri('ebe='.$row[0]));
				$tpl->setVariable('DELETEURL',FUser::getUri('ebd='.$row[0]));
				$tpl->parseCurrentBlock();
			}
		}

		$arrFiles = FSystem::fileList(WEB_REL_BANNER,"swf|gif|jpg|JPG|GIF|SWF|JPEG|jpeg");

		while($arrFiles) {
			$file = array_shift($arrFiles);
			if(!in_array($file,$arrUsedFiles)) {
				$tpl->setCurrentBlock('bannerimg');
				if(preg_match("/(.swf)$/",$file)) {
					$tpl->setVariable('SWFURL',WEB_REL_BANNER.$file);
					$tpl->setVariable('SWFNAME',$file);
				} elseif(preg_match("/(jpg|gif)$/",$file)) {
					$tpl->setVariable('IMGURL',WEB_REL_BANNER.$file);
					$tpl->setVariable('IMGNAME',$file);
				} else {
					$tpl->setVariable('UNIMGURL',WEB_REL_BANNER.$file);
					$tpl->setVariable('UNIMGNAME',$file);
				}

				$tpl->setVariable('NEWBANNERURL',FUser::getUri('ibn='.$file));
				$tpl->setVariable('IMGDELETEURL',FUser::getUri('ibd='.$file));
				$tpl->parseCurrentBlock();
			}
		}

		FBuildPage::addTab(array("MAINDATA"=>$tpl->get()));
	}
}