<?php
include_once('iPage.php');
class page_SysEditBanner implements iPage {

	static function process() {


		//upload
		if(!empty($_FILES['bann'])) {
			if($ak=upload($_FILES["bann"],WEB_REL_BANNER)) {
				fError::addError(FLang::$MESSAGE_UPLOAD_SUCCESS);
				fHTTP::redirect(FUser::getUri());
			}
		}
		//delete file from ftp
		if(isset($_GET['ibd'])) {
			$banner =  WEB_REL_BANNER.trim($_GET['ibd']);
			if(file_exists($banner)) {
				if($ak = @unlink($banner)){
					fError::addError(FLang::$LABEL_DELETED_OK);
					fHTTP::redirect(FUser::getUri());
				} else {
					fError::addError(FLang::$LABEL_FILE.' '.FLang::$LABEL_NOTEXISTS.': '.$banner);
				}
			}
		}
		//save banner
		if(isset($_POST['bid'])) {
			$sBanner = new fSqlSaveTool('sys_banner','bannerId');

			if($_POST['bid']>0) {
				$arr['bannerId']= $_POST['bid'] * 1;
				$arr['dateUpdated']='now()';
			}	else {
				$user = FUser::getInstance();
				$arr['userId']=$user->userVO->userId;
				$arr['dateCreated']='now()';
			}

			if(fSystem::isDate($_POST['eddatefrom'])) $arr['dateFrom']=$_POST['eddatefrom'];
			else fError::addError(FLang::$ERROR_DATE_FORMAT);

			if(fSystem::isDate($_POST['eddateto'])) $arr['dateTo']=$_POST['eddateto'];
			else fError::addError(ERROR_DATE_FORMAT);

			if(isset($_POST['edstrict'])) $arr['strict'] = 1; else $arr['strict'] = 0;

			$arr['linkUrl'] = Trim($_POST['edurl']);
			if(empty($arr['linkUrl'])) fError::addError(FLang::$ERROR_BANNER_TARGETEMPTY);
			$arr['imageUrl'] = Trim($_POST['edhtml']);
			if(empty($arr['imageUrl'])) fError::addError(FLang::$ERROR_BANNER_EMPTY);


			if(!fError::isError()) {
				$bannerId = $sBanner->save($arr,array('dateCreated','dateUpdated'));
				fError::addError(FLang::$MESSAGE_SUCCESS_SAVED);
			}

			fHTTP::redirect(FUser::getUri('ebe='.$bannerId));
		}

		//delete banner from db
		if(isset($_GET['ebd'])) {
			$bannerId = $_GET['ebd'] * 1;
			if($bannerId>0) {
				$db->query("delete from sys_banner where bannerId=".$bannerId);
				fError::addError(FLang::$LABEL_DELETED_OK);
				fHTTP::redirect(FUser::getUri());
			}
		}
	}

	static function build() {

		//-----novy banner
		$tpl = new fTemplateIT('sys.edit.banners.tpl.html');
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

		$arrFiles = fSystem::fileList(WEB_REL_BANNER,"swf|gif|jpg|JPG|GIF|SWF|JPEG|jpeg");

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