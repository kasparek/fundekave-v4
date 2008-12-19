<?php
class fPagesRelations {
    var $pageId;
    function __construct($pageId) {
        $this->pageId = $pageId;
    }
    function setPageId($pageId) {
      $this->pageId = $pageId;
    }
    function update() {
        global $db,$user;
        $strRemovedRight = $_POST['removedRight'];
        $strAddedRight = $_POST['addedRight'];
        if(!empty($strRemovedRight)) {
            $arrRemoved = explode(';',$strRemovedRight);
            foreach ($arrRemoved as $pageIdRemoved) {
        	   $db->query('delete from sys_pages_relations where pageId="'.$this->pageId.'" and pageIdRelative="'.$pageIdRemoved.'"');
        	   $db->query('delete from sys_pages_relations where pageIdRelative="'.$this->pageId.'" and pageId="'.$pageIdRemoved.'"');
            }
        }
        if(!empty($strAddedRight)) {
            $arrAdded = explode(';',$strAddedRight);
            foreach ($arrAdded as $pageIdAdded) {
               $dot = 'insert into sys_pages_relations (pageId,pageIdRelative) values ("'.$this->pageId.'","'.$pageIdAdded.'")';
               $db->query($dot);
               $dot = 'insert into sys_pages_relations (pageIdRelative,pageId) values ("'.$this->pageId.'","'.$pageIdAdded.'")';
        	   $db->query($dot);
            }
        }
        $user->cacheRemove('pagesrelated');
    }
    function getForm() {
        global $db,$user;
        $arrPageIdRelatives = $db->getCol('select pageIdRelative from sys_pages_relations where pageId="'.$this->pageId.'"');
        
        if(!empty($arrPageIdRelatives)) $strPageIdRelatives = "'".implode("','",$arrPageIdRelatives)."'";
        else $strPageIdRelatives = '';
        $fpages = new fPages(array('galery','forum','blog'),$user->gid,$db);
        $fpages->setSelect('p.pageId,p.name,p.nameshort,p.authorContent');
        $fpages->setOrder('p.typeId,p.dateCreated desc,p.name');
        $fpages->setWhere('p.pageId!="'.$this->pageId.'"');
        if(!empty($strPageIdRelatives)) $fpages->addWhere('p.pageId not in ('.$strPageIdRelatives.')');
        $arrLeft = $fpages->getContent();
        
        if(!empty($arrPageIdRelatives)) {
            $fpages->setWhere('p.pageId!="'.$this->pageId.'"');
            $fpages->addWhere('p.pageId in ('.$strPageIdRelatives.')');
            $arrRight = $fpages->getContent();
        }
              
        $tpl = new fTemplateIT('pages.relations.tpl.html');
        $optionsLeft = '';
        $optionsRight = '';
        if(!empty($arrLeft))
        foreach ($arrLeft as $row) {
        	$optionsLeft .= '<option value="'.$row[0].'">'.$row[2].' - '.$row[1].' - '.$row[3].'</option>'."\r\n";
        }
        if(!empty($arrRight))
        foreach ($arrRight as $row) {
        	$optionsRight .= '<option value="'.$row[0].'">'.$row[2].' - '.$row[1].'</option>'."\r\n";
        }
        $tpl->setVariable('OPTIONSLEFT',$optionsLeft);
        $tpl->setVariable('OPTIONSRIGHT',$optionsRight);
    
        
        return $tpl->get();
    }
    function getRoamerXML($userId=0) {
        global $db;
        $fPages = new fPages(array('forum','galery','top','blog'),$userId,$db);
        $fPages->addJoin('left join sys_pages_relations as r1 on r1.pageId=p.pageId');
        $fPages->addJoin('left join sys_pages_relations as r2 on r2.pageIdRelative=p.pageId');
        $fPages->setGroup('p.pageId');
        $fPages->addWhere('r1.pageId is not null or r2.pageId is not null');
        $fPages->setSelect('p.pageId,name');
        $arr = $fPages->getContent();
        $xml = '';
        foreach ($arr as $row) {
        	$xml.='<Node id="'.$row[0].'" prop="'.$row[1].'" />'."\r\n";
        	$arrForumId[] = $row[0];
        }
        $strForumId = '"'.implode('","',$arrForumId).'"';
        $dot = "select pageId,pageIdRelative from sys_pages_relations where pageId in (".$strForumId.") or pageIdRelative in (".$strForumId.")";
        $arr = $db->getAll($dot);
        foreach ($arr as $row) {
        	$xml.='<Edge fromID="'.$row[0].'" toID="'.$row[1].'"/>'."\r\n";
        }
        echo $xml = '<Root>'."\r\n".$xml.'</Root>';
        //file_put_contents('roamerData.xml',$xml);
        return $xml;
    }
    var $nodes = array();
    var $edges = array();
    var $arrToDone = array();
    var $pr=0;
    function addNodes($userId) {
        //var_dump($this->arrToDone);
        if(!in_array($userId,$this->arrToDone)) {
            $this->arrToDone[$userId] = $userId;
            //echo $userId.'<br>';
            global $user;
            $arr = $user->getFriends($userId,true);
            $this->pr++;
            foreach ($arr as $row) {
                
                $this->nodes[$row['id']] = $row;
                $this->edges[] = array('from'=>$userId,'to'=>$row['id']);
                
                
                    /*var_dump($this->nodes);
                    var_dump($this->edges);
                    echo $row['id'];
                    die();*/
                	$this->addNodes($row['id']);
                
            }
            
        }
    }
    function getFriedsRoamerXML($userId=0) {
        global $db,$user;
        if($userId>0) {
            
            $this->addNodes($userId);
            $xml = '';
            foreach ($this->nodes as $node) {
            	$xml.='<Node id="'.$node['id'].'" prop="'.$node['name'].'" />'."\r\n";
            }
            foreach ($this->edges as $row) {
            	$xml.='<Edge fromID="'.$row['from'].'" toID="'.$row['to'].'"/>'."\r\n";
            }
            echo $xml = '<Root>'."\r\n".$xml.'</Root>';
            return $xml;
        }
    }
}
?>