<?php
class FForum extends FDBTool {
	var $aMessCount;
	var $aMessNewCount;
	var $aId;
	var $aName;
	var $aInfo;
	var $aOwner;
	var $aLocked = 0;
	var $aOwnerUsername;
	var $aIdLastVisit;
		
	static function setBooked($auditId,$userId,$book) {
		$this->query("update sys_pages_favorites set book='".$book."' where pageId='".($auditId)."' AND userId='" . $userId."'");
	}
	
	static function statAudit($pageId, $userId, $count=true){
		$db = FDBConn::getInstance();
		if($count) $str = $db->getOne("select count(1) from sys_pages_items where pageId='".$pageId."' AND userId='".$userId."'");
		else $str="ins+1";
		$db->query("update sys_pages_counter set ins=".$str." WHERE pageId='".$pageId."'and dateStamp=now() AND userId='".$userId."'");
	}
	
	static function messDel($id,$auditId=0) {
		if(!is_array($id)) $id= array($id);
		if(!empty($id)) {
		  $user = FUser::getInstance();
			if(empty($auditId)) $auditId = $this->getOne("select pageId from sys_pages_items where itemId='".$id[0]."'");
			$fItems = new FItems();
			foreach ($id as $delaud) {
					if(FRules::getCurrent(2) 
					|| $user->userVO->userId == $this->getOne("SELECT userId FROM sys_pages_items WHERE itemId='".$delaud."'")) {
					$fItems->deleteItem($delaud);
        }
			}
			$this->query("update sys_pages set cnt=".$this->getOne("select count(1) from sys_pages_items where pageId='".$auditId."'")." where pageId='".$auditId."'");
			FForum::statAudit($auditId, $user->userVO->userId);
		}
	}
	static function messWrite($arr) {
			$fSave = new FDBTool('sys_pages_items','itemId');
			$arrNotQuoted = array('dateCreated');
			$arr['dateCreated'] = 'NOW()';
			if(empty($arr['itemIdTop'])) {
			 $arr['itemIdTop'] = 'null';
			 $arrNotQuoted[] = 'itemIdTop';
      } else {
        $cache = FCache::getInstance('f');
        $cache->invalidateGroup('lastBlogPost');
      }
      if(empty($arr['userId'])) {
			 $arr['userId'] = 'null';
			 $arrNotQuoted[] = 'userId';
      }
      if(empty($arr['enclosure'])) {
			 $arr['enclosure'] = 'null';
			 $arrNotQuoted[] = 'enclosure';
      }
      if(empty($arr['addon'])) {
			 $arr['addon'] = 'null';
			 $arrNotQuoted[] = 'addon';
      }
      //$fSave->debug = 1;
      $arr['typeId'] = 'forum';
      
			$ret = $fSave->save($arr,$arrNotQuoted);
			
			if($arr['itemIdTop'] > 0) FForum::incrementReactionCount($arr['itemIdTop']);
			else {
			 $dot = "update sys_pages set cnt=cnt+1 where pageId='".$arr['pageId']."'";
        $this->query($dot);
      }
			$user = FUser::getInstance(); 
			if($user->userVO->userId > 0) {
				FForum::statAudit($arr['pageId'], $user->userVO->userId, false);
			}
			
			return $ret;
	}
	static function updateReadedReactions($itemId,$userId) {
	    return $this->query("insert into sys_pages_items_readed_reactions (itemId,userId,cnt,dateCreated) values ('".$itemId."','".$userId."',(select cnt from sys_pages_items where itemId='".$itemId."'),now()) on duplicate key update cnt=(select cnt from sys_pages_items where itemId='".$itemId."')");
	}
  
	static function setUnreadedMess($arrMessId){
		if(empty($_SESSION['aNotReadedMess'])) $_SESSION['aNotReadedMess'] = array();
		if(is_array($arrMessId)) $_SESSION['aNotReadedMess']=array_merge($_SESSION['aNotReadedMess'],$arrMessId);
	}
	static function isUnreadedMess($messId,$unset=true){
		$ret=false;
		if(empty($_SESSION['aNotReadedMess'])) $_SESSION['aNotReadedMess'] = array();
		if(in_array($messId,$_SESSION['aNotReadedMess'])) {
			$ret=true;
			if($unset) {
				unset($_SESSION['aNotReadedMess'][array_search($messId, $_SESSION['aNotReadedMess'])]);
			}
		}
		return $ret;
	}
	static function clearUnreadedMess() {
		$_SESSION['aNotReadedMess'] = array();
	}
	static function getSetUnreadedForum($id,$itemId){
		$user = FUser::getInstance(); 
		if($itemId == 0) $unreadedCnt = $user->pageVO->cnt - $user->pageVO->favoriteCnt;
		else {
		    $dot = 'select i.cnt-r.cnt from sys_pages_items as i join sys_pages_items_readed_reactions as r on i.itemId=r.itemId and r.userId="'.$user->userVO->userId.'" and i.itemId="'.$itemId.'"';
		    $unreadedCnt = $this->getOne($dot);
		}
		$unreadedCnt = (($unreadedCnt < POSTS_UNREAD_MAX)?($unreadedCnt):(POSTS_UNREAD_MAX));
		if($unreadedCnt > 0 && $user->idkontrol) {
			$arrIds = $this->getCol("select itemId from sys_pages_items 
			where pageId='".$id."'".(($itemId>0)?(" and itemIdTop='".$itemId."'"):(''))." order by itemId desc limit 0,".$unreadedCnt);
			if(!empty($arrIds)){
				if(empty($_SESSION['aNotReadedMess'])) $_SESSION['aNotReadedMess']=array();
				foreach ($arrIds as $messId) if(!in_array($messId,$_SESSION['aNotReadedMess']))$arrTmp[]=$messId;
				if(!empty($arrTmp)) FForum::setUnreadedMess($arrTmp);
			}
		}
		return $unreadedCnt;
	}
	//---aktualizace oblibenych
	/*.......aktualizace FAV KLUBU............*/
	static function aFavAll($usrId,$typeId='forum') {
		if(!empty($usrId)){
			$klo=FDBTool::getCol("SELECT f.pageId FROM sys_pages_favorites as f join sys_pages as p on p.pageId=f.pageId WHERE p.typeId='".$typeId."' and f.userId = '".$usrId."'");
			$kls=FDBTool::getCol("SELECT pageId FROM sys_pages where typeId = '".$typeId."'");
			if(!isset($klo[0])) $res=$kls;
			else $res = array_diff($kls,$klo);
			if(!empty($res)) {
				foreach($res as $r) {
					FDBTool::query('insert into sys_pages_favorites (userId,pageId,cnt) values ("'.$usrId.'","'.$r.'","0")');
				}
			} 
		}
	}
	static function aFav($pageId,$userId,$cnt,$booked=0) {
		if(!empty($userId)){
		    $dot = "insert into sys_pages_favorites values ('".$userId."','".$pageId."','".$cnt."','".$booked."') on duplicate key update cnt='".$cnt."'";
		    $this->query($dot);
		}
	}
	static function incrementReactionCount($itemId) {
	    $dot = "update sys_pages_items set cnt=cnt+1 where itemId='".$itemId."'";
	    return $this->query($dot);
	}
	static function process( $itemId = 0, $callbackFunction=false) {
	    $user = FUser::getInstance();
	    $pageId = $user->pageVO->pageId;
	    $logon = $user->idkontrol;
	    
        $redirect = false;
        
        if(isset($_POST["send"])) {
          
            $cache = FCache::getInstance('s',0);
            $cache->invalidateGroup('forumFilter');
            
        	if (!empty($_POST["del"])) {
        		FForum::messDel($_POST['del'],$pageId);
        		$redirect = true;
        	}
        	if(!$logon) {
        	    $captcha = fCaptcha::init();
        		if($captcha->validate_submit($_POST['captchaimage'],$_POST['pcaptcha'])) $cap = true; else $cap = false;
        	} else $cap = true;
        	
        	if(FUser::logon()) $jmeno = $user->userVO->name;
        	elseif(isset($_POST["jmeno"])) $jmeno = trim($_POST["jmeno"]);
        	
        	if(isset($_POST["zprava"])) $zprava = trim($_POST["zprava"]);
        	
        	if(isset($_POST["objekt"])) {
            $objekt = trim($_POST["objekt"]);
          }
        	
        	if($cap) {
                
                if(isset($objekt))
                {
                  
                  
                  if(preg_match("/^link:([0-9a-zA-Z]*)$/" , $objekt)) {
                      $itemIdBottom = str_replace('link:','',$objekt);
                      $objekt = '';
                      //check $itemIdBottom
                      $pageIdBottom = '';
                      if(strlen($itemIdBottom)==5) {
                        //check if it is page
                        if(FPages::page_exist('pageId',$itemIdBottom)) {
                          $pageIdBottom  = $itemIdBottom;
                          $itemIdBottom = '';
                        }
                      }
                      if($pageIdBottom=='') {
                        //check if it is item
                        if(!FItems::itemExists($itemIdBottom)) {
                          $itemIdBottom = '';
                        }
                      }
                      
                  }
                }
        		if((!empty($zprava) || !empty($objekt))) {
        			if(empty($jmeno)){
        				FError::addError("Nezadali jste jmeno");
        				$redirect = true;
        			}
        			if (FUser::isUsernameRegistered($jmeno) && !$logon){
        				FError::addError("Jmeno uz nekdo pouziva");
        				$redirect = true;
        			}
        			if(!FError::isError()) {
            			$jmeno = FSystem::textins($jmeno,array('plainText'=>1));
            			if($logon) {
            			 $zprava = FSystem::textins($zprava);
            			} else {
            			 $zprava = FSystem::textins($zprava,array('formatOption'=>0));
            			 if(isset($objekt)) {
            			   $objekt = FSystem::textins($objekt,array('plainText'=>1));
            			 }
            			}
            		
            		//---insert
            		    $arrSave = array('pageId'=>$pageId,'userId'=>$user->userVO->userId,'name'=>$jmeno,'text'=>$zprava);
            		    if(isset($objekt)) {
                      $arrSave['enclosure']=$objekt;
                    }
            		    if(!empty($itemIdBottom)) $arrSave['itemIdBottom'] = $itemIdBottom;
            		    if(!empty($pageIdBottom)) $arrSave['pageIdBottom'] = $pageIdBottom;
            		    if($itemId > 0) $arrSave['itemIdTop'] = $itemId;
            		    
                        FForum::messWrite($arrSave);
                        
                        $cache = FCache::getInstance('s',0);
                        $cache->invalidateData($pageId,'form');
                  
                      //---on success
                        if(FUser::logon()) fUserDraft::clear('forum'.$pageId);
                			$redirect = true;
                    }
                        
        		  }
        	} else {
        		FError::adderror(ERROR_CAPTCHA);
        	}
        	if(FError::isError()) {
              $formData = array("zprava"=>$zprava,"objekt"=>(isset($objekt))?($objekt):(''),"name"=>$jmeno);
              $cache = FCache::getInstance('s',0);
              $cache->setData($formData, $pageId, 'form');
            }
        }
        //---filtrovani
        if(isset($_POST["filtr"])) {
        	$cache = FCache::getInstance('s',0);
            $cache->setData(FSystem::textins($_POST["zprava"],array('plainText'=>1)), $pageId, 'filter');
        }
        //---per page
        $cache = FCache::getInstance('s',0);
        if($perPage = $cache->getData($pageId,'pp') ===false) $perPage = FORUM_PERPAGE;
        
        if (isset($_POST["perpage"]) && $_POST["perpage"] != $perPage) {
        	$perPage = $_POST["perpage"]*1;
        	if($perPage < 2) $perPage = 10;
        	$cache->setData($perPage, $pageId,'pp');
        }
        //---redirect
        if($redirect==true) {
        	$cache = FCache::getInstance('f');
            $cache->invalidateData('lastForumPost');
            if($callbackFunction) call_user_func($callbackFunction);
            FHTTP::redirect(FUser::getUri());
        }
	}
	
	/*
	 * forum Print
	 public write - 0:no write,1:public,2:only registered
	 */
	function show($itemId = 0,$publicWrite=1,$itemIdInside=0,$paramsArr=array()) {
	    $user = FUser::getInstance();
	    $pageId = $user->pageVO->pageId;
	    
	    $zprava = '';
	    //---available params
	    $formAtEnd = false;
	    $showHead = true;
	    extract($paramsArr);
	    
	    if(FUser::logon() === false && $publicWrite > 0) { $captcha = FCaptcha::init(); }
	    
	    $cache = FCache::getInstance('s',0);
        if($perPage = $cache->getData($pageId,'pp') ===false) $perPage = FORUM_PERPAGE;
	    
        if( FUser::logon() ) {
            $unreadedCnt = FForum::getSetUnreadedForum($user->pageVO->pageId,$itemId);
            if($unreadedCnt > 0) {
                if($unreadedCnt > 20 || $perPage <= $unreadedCnt) $perPage = $unreadedCnt + 5;
        	    elseif($unreadedCnt > 100) $perPage = 100;
            }
        }
        
        /* ........ vypis nazvu auditka .........*/
        //--FORM
        $tpl = new fTemplateIT('forum.view.tpl.html');
        if($showHead===true) {
            $desc = $user->pageVO->content;
            if(!empty($desc)) $tpl->setVariable('PAGEDESC',$desc);
        }
        if($user->pageVO->locked == 0 && $publicWrite > 0) {
            $tpl->setVariable('FORMACTION',FUser::getUri());
        	$name = "";
        	if($user->idkontrol) $zprava = fUserDraft::get('forum'.$user->pageVO->pageId);
        	
        	$cache = FCache::getInstance('s',0);
            $formData = $cache->getData( $user->pageVO->pageId, 'form');
        	if($formData !== false) {
        	   $zprava = $formData['zprava'];
        	   $objekt = $formData['objekt'];
        	   $name = $formData['name'];
        	   $cache->invalidateData($user->pageVO->pageId, 'form');
        	}
        	if ($user->idkontrol) {
        	   $tpl->setVariable('USERNAMELOGGED',$user->userVO->name);
        	} else {
          		$tpl->setVariable('USERNAMENOTLOGGED',$name);
        		$src = $captcha->get_b2evo_captcha();
        		$tpl->setVariable('CAPTCHASRC',$src);
        	}
        	$tpl->setVariable('TEXTAREAID','forum'.$user->pageVO->pageId);
        	$tpl->addTextareaToolbox('TEXTAREATOOLBOX','forum'.$user->pageVO->pageId);
       	
        	$cache = FCache::getInstance('s',0);
            $filter = $cache->getData( $user->pageVO->pageId, 'filter');
        	$tpl->setVariable('TEXTAREACONTENT',(($filter!==false)?($filter):($zprava)));

        	if ($user->idkontrol) {
            $tpl->touchBlock('userlogged');
        	    $tpl->touchBlock('userlogged2');
        	    $tpl->setVariable('PERPAGE',$perPage);
        	}
        } elseif($publicWrite == 2) {
            $tpl->setVariable('READONLY',MESSAGE_FORUM_REGISTEREDONLY);
        } else {
            $tpl->setVariable('READONLY',MESSAGE_FORUM_READONLY);
        }
        //---END FORM
        $fItems = new FItems();  
        $fItems->initData('forum');
        $fItems->addWhere("i.pageId='".$user->pageVO->pageId."'");
        if(!empty($itemId)) $fItems->addWhere("i.itemIdTop='".$itemId."'");
        if(!empty($filterTxt)) {
        	$fItems->addWhereSearch(array('i.name','i.text','i.enclosure','i.dateCreated'),$filterTxt,'or');
        }
        $fItems->setOrder("i.dateCreated DESC");
        FItems::setQueryTool(&$fItems);
        $manualCurrentPage = 0;
        if($user->itemVO->itemId > 0 || $itemIdInside > 0) {
            if($user->itemVO->itemId > 0) $itemIdInside = $user->itemVO->itemId;
            //---find a page of this item to have link to it
            if($itemIdInside > 0) $manualCurrentPage = FForum::getItemPage($itemIdInside,$user->pageVO->pageId,$perPage);
        }
        if(!empty($user->whoIs)) $arrPagerExtraVars = array('who'=>$who); else $arrPagerExtraVars = array();
        $pager = FSystem::initPager(0,$perPage,array('extraVars'=>$arrPagerExtraVars,'noAutoparse'=>1,'bannvars'=>array('i'),'manualCurrentPage'=>$manualCurrentPage));
        $from = ($pager->getCurrentPageID()-1) * $perPage;
        $fItems->getData($from,$perPage+1);
        $total = count($fItems->arrData);
        
        $maybeMore = false;
        if($total > $perPage) {
            $maybeMore = true;
            unset($fItems->arrData[(count($fItems->arrData)-1)]);
        }
        
        if($from > 0) $total += $from;
        
        if($total > 0) {
        	/*.........zacina vypis prispevku.........*/
        	$pager->totalItems = $total;
        	$pager->maybeMore = $maybeMore;
        	$pager->getPager();
        	if ($total > $perPage) {
        	 $tpl->setVariable('TOPPAGER',$pager->links);
        	 $tpl->setVariable('BOTTOMPAGER',$pager->links);
          }
          $mess = '';
        	while ($fItems->arrData) {
        	    $fItems->parse();
        	}
        	
        	$tpl->setVariable('MESSAGES',$fItems->show());
        	if($formAtEnd===true) {
        	    //---remove posts block and place it on POSTSONTOP
        	    $tpl->moveBlock('posts','POSTSONTOP');
        	    
        	}
        	/*......aktualizace novych a prectenych......*/
        	if($itemId>0) FForum::updateReadedReactions($itemId,$user->userVO->userId);
        	else FForum::aFav($user->pageVO->pageId,$user->userVO->userId,$user->pageVO->cnt);
        } else $tpl->touchBlock('messno');

        return $tpl->get();
	}
	
	private function getItemPage($itemId,$pageId,$perPage) {
	    $ret = 0;
	    $page = 0;
	    $k = 0;
	    
	    $query = 'pouzita problemova funkce';
	    $fname = '/home/www/fundekave.net/tmp/debug.txt';
	    if(file_exists($fname)) $queryWrite = file_get_contents($fname)."\n--------------------------------------------------------------------------------\n".$query;
      else $queryWrite = $query;
      file_put_contents($fname,$queryWrite);
	
      /*
	    while($ret==0) {
	        $k++;
	        $arr =$db->getCol("select itemId from sys_pages_items where pageId='".$pageId."' order by dateCreated desc limit ".$page.",".$perPage."");
	        if(in_array($itemId,$arr)) $ret = $k;
	        $page += $perPage; 
	    }
	    */
	    return $ret;
	}
}