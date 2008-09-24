<?php
//TODO: zapsat log - pocitat pocet v dump, zapsat
error_reporting(E_ALL);
define('MAXITEMS',2000);
set_time_limit('300');
header("Content-Type: text/html; charset=windows-1250");

if(!isset($_GET['import'])) { 
  set_include_path(get_include_path() . PATH_SEPARATOR . "/home/fundekave/www/fundekave/pear");
  require_once('DB.php');
  $db = & DB::connect("mysql://fundekave:funka@db/fundekave");
    if (PEAR::isError($db)) die($db->getMessage());
}

function dump($table,$queryTemp="SELECT {SELECT} from {TABLENAME}") {
  global $db;
  $tableName = $table['t'];
  if(isset($table['s']))$select = $table['s'];
  if(empty($select)) $select = '*';
  if(isset($table['f'])) $addId = $table['f'];
  if(isset($table['q'])) {
    $query = $table['q'];
  } else {
    $query = str_replace("{TABLENAME}",$tableName,$queryTemp);
    $query = str_replace("{SELECT}",$select,$query);
  }
  
  if(!empty($table['conv'])) {
      $arrNewIds[$table['conv']['file']] = unserialize(file_get_contents('/home/fundekave/www/fundekave/kasparek/export/'.$table['conv']['file'].'.sql.rel'));
      $newIdSource = $table['conv']['file'];
      $position = $table['conv']['position'];
  }
  if($tableName=='anketa') {
      $arrNewIds['sys_audit'] = unserialize(file_get_contents('/home/fundekave/www/fundekave/kasparek/export/sys_audit.sql.rel'));
  }
  if($tableName=='sys_perm') {
      $arrNewIds['sys_audit'] = unserialize(file_get_contents('/home/fundekave/www/fundekave/kasparek/export/sys_audit.sql.rel'));
      $arrNewIds['galerie'] = unserialize(file_get_contents('/home/fundekave/www/fundekave/kasparek/export/galerie.sql.rel'));
  }
  echo $query.'<br>';
  $queryParts = explode("from",$query);
  $dbCountArr = $db->getAll('select count(1) from '.$queryParts[count($queryParts)-1]);
  if(count($dbCountArr)>1) $dbCount = count($dbCountArr); else $dbCount=$dbCountArr[0][0];
      
  $itemcount = 0;
  $from = 0;
  $filepart=0;
  $strLen = 0;
  if($dbCount>0) {
      for($x=0;$x<$dbCount;$x++) {
          if($itemcount==2000) {
              $itemcount=0;
              $from++;
          }
          
          if($itemcount==0) {
            $que = $query.' limit '.($from*2000).',2000';
            $arr = $db->getAll($que);
            if(DB::iserror($arr)) {
                die('---'.$que.'---');
            }
          }
          $row = $arr[$itemcount];
          $itemcount++;
          $insert = true;
if(isset($addId)) {
         if(!is_numeric($addId) && !empty($addId)) {
          $newId = $row[0];
          while (strlen($newId)<4) {
            	$newId = 'l' . $newId;
          }
          $newId = $addId . $newId;
          $arrId[$row[0]] = $newId;
          $row[0] = $newId;
        } elseif($addId > 0) {
          $newId = $row[0]+$addId;
          $arrId[$row[0]] = $newId;
          $row[0] = $newId;
        }
}
        if(!empty($newIdSource)) {
            $oldId = $row[$position];
            $row[$position] = $arrNewIds[$newIdSource][$row[$position]];
            if($row[$position]=='') {
                echo '<pre><b>'.$oldId.'</b>';
                print_r($row);
                echo '</pre><hr>';
                $insert = false;
            }
        }
        if($tableName=='anketa') {
            $oldId = $row[1];
            if($row[1] < 33000) $row[1] = 'maina';
            else $row[1] = $arrNewIds['sys_audit'][$row[1]-33000];
            
            if($row[1]=='') {
                $row[1]='maina';
            }
            if(empty($row[6])) $row[6]=1;
        }
        if($tableName=='sys_perm') {
            $oldId = $row[1];
            if($row[1]<33000) {
                $row[1] = $arrNewIds['galerie'][$row[1]-23000];
            } else {
                $row[1] = $arrNewIds['sys_audit'][$row[1]-33000];
            }
            if($row[1]=='') {
                echo '<pre><b>'.$oldId.'</b>';
                print_r($row);
                echo '</pre><hr>';
                $insert = false;
            }
        }
        if($tableName=='pratele') {
            if($row[1]==0) $insert=false;
        }
        if($insert) {
            foreach($row as $k=>$v) {
                $row[$k] = trim($v);
                if($row[$k]=='0' || $row[$k]=='' || $row[$k]=='null') $row[$k] = 'null';
                elseif($row[$k]!='now()') {
                    $row[$k] = str_replace('\\',"",$row[$k]);
                    $row[$k] = str_replace("'","\'",$row[$k]);
                    $row[$k] = "'".$row[$k]."'";
                }
            }
            
            $item = '('.implode(',',$row).')';
            $strLen += strlen($item);
            $items[] = $item;
            
          }
          if(count($items) > MAXITEMS || ($dbCount-1)==$x || $strLen>600000) {
              $data = 'insert into '.$table['n'].' values '.implode(','."\r\n",$items).';';
              $backupFile = '/home/fundekave/www/fundekave/kasparek/export/' . $tableName . $filepart . '.sql';
              $data = iconv("windows-1250","utf-8",$data);
              $ret = file_put_contents($backupFile,$data);
              $data = '';
              $items = array();
              $filepart++;
              $strLen = 0;
            }
      }
  }
    
  if(!empty($arrId)) {
    $strId = serialize($arrId);
    $backupFile = '/home/fundekave/www/fundekave/kasparek/export/' . $tableName . '.sql'.'.rel';
    file_put_contents($backupFile,$strId);
  }
  echo $tableName.';'.$dbCount.';'.'<br>';
}

function import($table) {
  global $db;
  
  
  $filepart = 0;
  $tableName = $table['n'];
  $filename = $table['t'] . $filepart . '.sql';
  
  $link = mysql_connect('mysql5-2', 'f4t_isam.97440', 'funka9') or die('Could not connect: ' . mysql_error());
        mysql_select_db('f4t_isam_97440',$link) or die('Could not select database: ' . mysql_error());
    
      
      
    mysql_unbuffered_query("set character_set_client = utf8");
    mysql_unbuffered_query("set character_set_connection = utf8");
    mysql_unbuffered_query("set character_set_results = utf8");
    mysql_unbuffered_query("set character_set_connection = utf8");
  
  while (file_exists($filename)) {
      echo $filename.'<br>';
      
    
      
    $fileContent = file_get_contents($filename);
    mysql_unbuffered_query($fileContent)or die('Query failed: ' . mysql_error());;
    echo 'inserted: '.mysql_affected_rows().'<br>';
    
    $fileContent = '';
    unset($fileContent);
    $filepart++;
    $filename = $table['t'] . $filepart . '.sql';
  } 
  mysql_close(); 
}
//do users first
//------------------------------------------------------------------------------
$tableArraySet1 = array(
array('t'=>'sys_id','n'=>'sys_users','q'=>'select u.id,u.skin,u.jmeno,u.heslo,u.ipcheck,u.registrace,"null",u.last,u.email,u.icq,concat("<user><personal><www><![CDATA[",u.www,"]]></www><motto><![CDATA[",u.motto,"]]></motto><place><![CDATA[",u.vyskyt,"]]></place><food><![CDATA[",u.jidlo,"]]></food><hobby><![CDATA[",u.hobby,"]]></hobby><about><![CDATA[",u.vse,"]]></about></personal></user>"),u.foto, u.weather_loc_id,u.zbanner,u.zidico,u.zaudico,u.galtype,u.deleted,h.hit from sys_id as u left join counterstat as h on u.id=h.usr'),
array('t'=>'akcekateg','n'=>'sys_pages_category','s'=>'id,"event",nazev,popis,poradi,public','f'=>400), //id,nazev,popis,poradi,public
//array('t'=>'akcemisto','n'=>'sys_events_place'), //id,nazev,poradi
array('t'=>'kulkat','n'=>'sys_pages_category','s'=>'id,"culture",nazev,popis,poradi,public','f'=>100),
array('t'=>'galkat','n'=>'sys_pages_category','s'=>'id,"galery",nazev,popis,poradi,public','f'=>200), 
array('t'=>'linxkat','n'=>'sys_pages_category','s'=>'id,"surf",nazev,popis,poradi,public'),
array('t'=>'sys_audit_kat','n'=>'sys_pages_category','s'=>'id,"forum",nazev,"null",poradi,public','f'=>300), //id,nazev,poradi,public
array('t'=>'banner','n'=>'sys_banner','s'=>'id,idusr,pic,url,datumod,datumdo,hit,look,1,now(),"null"'),
array('t'=>'skin','n'=>'sys_skin','s'=>'id,idusr,file,dir,nazev,datum'),
array('t'=>'pratele','n'=>'sys_users_friends','s'=>'id_usr,id_pra,datum,koment'),
//array('t'=>'sys_idtlac','n'=>'sys_users_buttons'),
array('t'=>'linxid','n'=>'sys_surfinie','s'=>'id,kategorie,usr,http,popis,datum,public'),
array('t'=>'diar','n'=>'sys_users_diary'),
array('t'=>'post','n'=>'sys_users_post','q'=>'select p1.id,p1.id_usr,p2.id,p1.komu,p1.odkoho,p1.datum,p1.text,p1.precteno from post as p1 left join post as p2 on p1.cas=p2.id where p1.odkoho!=0 and p1.id_usr!=0 and p1.komu!=0 order by p2.id,p1.id'),

//---new pageid
array('t'=>'kultura','f'=>'c','n'=>'sys_pages','s'=>'id,"null","culture","null",kategorie+100,"null",2,"culture.view.tpl.html",nazev,"KULTURA",popis,text,public,datum,"null",datum,idusr,"null",0,0,autor,"null","null"'),
array('t'=>'galerie','f'=>'g','n'=>'sys_pages','q'=>'select g.id,"null","galery","null",g.kategorie+200,8,8,"galery.detail.php",g.nazev,"Galerie",g.popis,"null",g.public,g.datumins,g.date_change,g.datum,g.autor,"null",(select count(1) from galfoto where galerie = g.id) as cnt,0,u.jmeno,g.adresar,concat("<galery><enhancedsettings><width>",g.sirka,"</width><height>",g.vyska,"</height><widthpx>",g.sirkapx,"</widthpx><heightpx>0</heightpx><thumbnailstyle>0</thumbnailstyle></enhancedsettings></galery>") from galerie as g left join sys_id as u on u.id=g.autor'),
array('t'=>'sys_audit','f'=>'f','n'=>'sys_pages','q'=>'SELECT a.id,"null","forum","null",a.kategorie+300,"null",9,"forum.view.php",a.nazev,"Klub",a.popis,"null",a.public,a.zalozenodatum,a.zalozeno,"null",a.majitel,a.ico,a.cnt,0,u.jmeno,"null",concat("<forum><home><![CDATA[",a.home,"]]></home></forum>") from sys_audit as a left join sys_id as u on u.id=a.majitel'),

//----after pages and userid stay same but must be transported
//---pageId on second position
array('t'=>'counteraud','conv'=>array('position'=>0,'file'=>'sys_audit'),'n'=>'sys_pages_counter','q'=>'select aud,"null",usr,cas,hit,ins from `counteraud` where 1 group by aud,usr'),
array('t'=>'oblibene','conv'=>array('position'=>1,'file'=>'sys_audit'),'n'=>'sys_pages_favorites','q'=>'select * from `oblibene` where idusr!=57 and idusr!=0 group by idusr,idaud'),
array('t'=>'anketa','n'=>'sys_poll','s'=>'id,idstr,aktiv,otazka,datum,"null",idusr,publicresults,votesperuser'),
array('t'=>'ankodp','n'=>'sys_poll_answers','s'=>'ankid,id,odpoved,poradi'),
array('t'=>'ankklik','n'=>'sys_poll_answers_users','s'=>'ankid,odpid,usr'),



array('t'=>'audit','conv'=>array('position'=>3,'file'=>'sys_audit'),'n'=>'sys_pages_items'
,'s'=>'id,"null","null","forum",id_aud,"null","null",id_usr,jmeno,"null","null",datum,text,http,"null","null","null","null",0,"null"'),

array('t'=>'novinky','n'=>'sys_pages_items','f'=>140000
,'s'=>'id,"null","null","blog","maina","null","null",usr,autor,datum,"null",datum,text,"null",nadpis,"null","null","null",0,"null"'),

array('t'=>'galfoto','conv'=>array('position'=>3,'file'=>'galerie'),'n'=>'sys_pages_items','f'=>150000
,'q'=>'SELECT gf.id,"null","null","galery",gf.galerie,"null","null",f.autor,u.jmeno,"null","null",f.datum,gf.popis,gf.detail,gf.nahled,"null","null",h.hit,0,"null" from galfoto as gf join galerie as f on f.id=gf.galerie left join sys_id as u on u.id=f.autor left join galfotohit as h on h.id=gf.id'),

array('t'=>'akce','n'=>'sys_pages_items','f'=>145000
,'q'=>'select a.id,"null","null","event","event","null",ac.id+400
,a.idusr,u.jmeno,a.datum,"null",a.datumins
,a.popis,a.letak,a.nazev,"null","null","null"
,0,concat(a.misto," ",m.nazev) from akce as a left join akcekateg as ac on ac.id=a.druhid join sys_id as u on a.idusr=u.id left join akcemisto as m on m.id=a.mistoid'
),

array('t'=>'galfotohittime','n'=>'sys_pages_items_hit','f'=>150000,'q'=>'select h.idfoto,"null",h.hittime from galfotohittime as h join galfoto as f on f.id=h.idfoto'),
array('t'=>'sys_perm','n'=>'sys_users_perm','q'=>'select * from sys_perm where idpage>20000'),

);
//itemid,itemIdTop,typeId,pageId,categoryId,userId,name,dateStart,dateEnd,dateCreated,text,enclosure,addon,filesize,hit,cnt,ta_weight,location

if(!isset($_GET['n'])) $n=0; else $n=$_GET['n'];

if($n<count($tableArraySet1)) {
  if(isset($_GET['import'])) import($tableArraySet1[$n]);
  else dump($tableArraySet1[$n]);
  $dalsi = $n+1;
  if($dalsi<count($tableArraySet1)) {
  echo '<hr><a href="?n='.$dalsi.((isset($_GET['import']))?('&import=1'):('')).'">dalsi '.$tableArraySet1[$dalsi]['t'].'</a>';
  } else { echo '<hr>to byla posledni'; }
}
  
?>