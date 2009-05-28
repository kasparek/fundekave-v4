<?php
require(INIT_FILENAME);
//---process post
$processMessage = '';
$processArr=array();
$salt = 'fdk35';
if(isset($_GET['hash']) && $user->currentPage['typeId']=='forum') {
   $hash = $_GET['hash'];
   $localHash = md5($salt.$user->currentPageId);
  if($localHash == $hash) {
    //echo $_GET['data'];
    $paramsDecode = base64_decode($_GET['data']);
    $paramsDecode = urldecode($paramsDecode);
  	if($paramsDecode) {
    	$paramsArr = explode($hash, $paramsDecode);
    	$params['name'] = $paramsArr[0];
    	$params['text'] = $paramsArr[1];
    
    	if(empty($params['name'])) $processArr[]='E:nameEmpty';
    	
    	if(empty($processArr)) {
        	if($params['text']!='' && $params['name']!='') {
        		$arr['pageId'] = $user->currentPageId;
        		$arr['text'] = FSystem::textins($params['text']);
        		$arr['name'] = FSystem::textins($params['name'],array('plainText'=>1));
        		if(FForum::messWrite($arr)) $processArr[] = 'I:insertOK';
        	}
    	}
  	}
  }
}
//page type - forum,galery,blog,top - GLOBAL pageId - main
//if something else then send no feed
//---CREATE feed
require 'pheeder/pheeder.php';
$baseLink = 'http://fundekave.net/?k=';
$errorCommentsPageId = 'aaaaa'; //---TODO: put real pageid
$rssNow = date("Y-m-d H:i:s");
$perpage = 15;
$pageFrom = 0;
$pageNumber = 1;
if(isset($_GET['p'])) {
    $pageFrom = $_GET['p'] * $perpage;
    $pageNumber = $_GET['p']*1;
}

$items = array();

//---FEED for any new page - galery,blog,forum
if($user->currentPage['typeId']=='top') {
    $fPages = new FPages('',$user->gid,$db);
    $fPages->setLimit(0,15);
    $fPages->setOrder('dateCreated',true);
    $fPages->setSelect("p.pageId,name,description,authorContent,date_format(dateCreated,'%H:%i:%S %d.%m.%Y') as dcz,p.typeId");
    $fPages->setWhere("(p.typeId!='error' and p.typeId!='admin' and p.typeId!='top')");
    $arr = $fPages->getContent();
    
    if(!empty($arr)) {
        foreach ($arr as $row) {
        	$item = array(
                'title'       => $row[1],
                'link'        => $baseLink.$row[0],
                'description' => (!$row[2])?('No decription'):($row[2]),
                'author'      => $row[3],
                'guid'        => md5($row[0]),
                'pubDate'     => $row[4]
            );
            if($row[5] == 'galery') {
                //---FIXME: use items
                $fItems = new FItems();
                $fItems->initData('galery');
                $fItems->addWhere("i.pageId='".$row[0]."'");
                $totalItems = $fItems->getCount();
                $fItems->getData(rand(0,$totalItems),1);
                $arrItem = $fItems->pop();
                if(!empty($thumbUrl)) $item['description'] .= '<p><a href="http://fundekave.net'.$arrItem['detailUrlToPopup'].'"><img src="'.$arrItem['thumbUrl'].'" /></p>'; //---random img from galery;
            }
            $items[] = $item;
        }
    }
}
//---FORUM feed
if($user->currentPage['typeId']=='forum' || $user->currentPage['typeId']=='blog') {
    $public = $db->getOne('select public from sys_pages where pageId="'.$user->currentPageId.'"');
    if($public==1) {
      $fForum = new fQueryTool('sys_pages_items');
      if($user->currentPage['typeId']=='forum') $fForum->setSelect("name,date_format(dateCreated,'%H:%i:%S %d.%m.%Y') as dcz,text,enclosure,itemId,name");
      else $fForum->setSelect("addon,date_format(dateCreated,'%H:%i:%S %d.%m.%Y') as dcz,text,enclosure,itemId,name");
      $fForum->setOrder('dateCreated',true);
      $fForum->setWhere("pageId='".$user->currentPageId."'");
      $fForum->addWhere("itemIdTop is null");
      $totalItems = $db->getOne($fForum->buildGetCount());
      $processArr[]='D:t_'.$totalItems;
      if($pageNumber<1) $pageNumber = 1;
      if($pageNumber>0) $processArr[]='D:p_'.$pageNumber;
      if($totalItems>0) {
          $fForum->setLimit(($pageNumber-1)*$perpage,$perpage);
          $dot = $fForum->buildQuery();
          $arr = $db->getAll($dot);
          foreach ($arr as $row) {
          	$item = array(
                  'title'       => $row[0],
                  'link'        => $baseLink.$user->currentPageId.(($user->currentPage['typeId']=='blog')?('&i='.$row[4]):('')),
                  'description' => $row[2],
                  'author'      => $row[5],
                  'comments'    => $baseLink.$user->currentPageId.(($user->currentPage['typeId']=='blog')?('&i='.$row[4]):('')),
                  'guid'        => md5($row[4]),
                  'pubDate'     => $row[1]
              );
              if(preg_match("/((.jpeg)|(.jpg)|(.gif)$)/",$row[3])) $item['description'] .= FSystem::textins('<p>[img]'.$row[3].'[/img]</p>');
              elseif(!empty($row[3])) $item['description'] .= FSystem::textins('<p>[link]'.$row[3].'[/link]</p>'); 
              $items[] = $item;
          	
          }
      }
    }
}
if($user->currentPage['typeId']=='galery') {
    //$galery->getFoto($user->currentPageId,true,' order by f.dateCreated desc,f.detail ');
    $galery = new FGalery();
    $galeryData = $galery->getGaleryData($user->currentPageId);
    $totalItems = count($galeryData);
    $processArr[]='D:t_'.$totalItems;
    if($pageNumber>0) $processArr[]='D:p_'.$pageNumber;
    if($totalItems>0) {
        
        $arr = array_slice($galeryData,($pageFrom * $perpage),$perPage);
        
        foreach ($arr as $fotoId) {
            $galery->getFoto($fotoId);
            $fdesc = $galery->get('fComment');
        	$item = array(
                'title'       => $user->currentPage['name'].' '.$galery->get('fDetail'),
                'link'        => $baseLink.$user->currentPageId,
                'description' => (!empty($fdesc))?($fdesc):('No description')
                . '<p><a href="'.$galery->getDetailUrl().'"><img src="'.$galery->get('fThumbDir').'" /></p>',
                'author'      => $user->currentPage['authorContent'],
                'comments'    => $baseLink.$user->currentPageId,
                'guid'        => md5($galery->get('fId')),
                'pubDate'     => $galery->get('fDate')
            );
            $items[] = $item;
        }
    }
}

//---ERROR handling
if(fError::isError()) {
    $arrErr = fError::getError();
    foreach ($arrErr as $err) {
        $items[] = array( 'title' => $user->currentPage['name'], 'link' => $baseLink.$user->currentPageId, 'description' => $err, 'author' => 'Fundekave.net', 'comments' => $baseLink.$errorCommentsPageId, 'guid' => $baseLink.$user->currentPageId, 'pubDate' => $rssNow );
    }
}
//---EMPTY feed information
if(empty($items)) $items[] = array('title' => 'NO RECORDS','link' => $baseLink.$user->currentPageId,'description' => 'No records for given feed','author' => 'Fundekave.net - Kasparek','comments' => $baseLink.$errorCommentsPageId, 'guid' => $baseLink.$user->currentPageId, 'pubDate' => $rssNow);

if(!empty($processArr)) $processMessage = '|'.implode(';',$processArr);

$data = array(
  'title'          => BASEPAGETITLE.' '.$user->currentPage['name'],
  'link'           => $baseLink.$user->currentPageId,
  'description'    => (!$user->currentPage['description'])?('no description'):($user->currentPage['description']),
  'language'       => 'cz',
  'copyright'      => '2008, Fundekave, Frantisek Kaspar',
  'managingEditor' => 'web@fundekave.net (Frantise Kaspar)',
  'webMaster'      => 'web@fundekave.net (Frantisek Kaspar)',
  'pubDate'        => ((!empty($user->currentPage['dateContent']))?($user->currentPage['dateContent']):(((!empty($user->currentPage['dateUpdated']))?($user->currentPage['dateUpdated']):($user->currentPage['dateCreated'])))),
  'lastBuildDate'  => $rssNow,
  'generator'      => 'FUNDEKAVE.net::v4::THXto::pheeder.sourceforge.net'.$processMessage,
  'docs'           => 'http://www.rssboard.org/rss-specification',
  'ttl'            => '10',
  //'image'          => 'valid-rss.png', TODO: fundekave logo
  'rating'         => '(PICS-1.1 "http://www.classify.org/safesurf/" l r (SS~~000 1))',
  'items'          => $items
);

$feed = new RssFeed($data);
$feed->enableImageSize();
$feed->setEncoding(CHARSET);
$feed->setVersion('2.0');
$feed->setAtomLink('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);

$feed->run();

session_write_close();
$db->disconnect();


/**
 * The feed data array
 * 
 * This is the array, from which the feed is generated. It includes all the possible
 * RSS 2.0 tags, as well as two feed items.
 */
/*
$data = array(
  'title'          => 'Complete Example Feed',
  'link'           => 'http://www.example.org/',
  'description'    => 'A complete example, demonstrating all RSS 2.0 tags',
  'language'       => 'en',
  'copyright'      => 'Copyright © 2008, Jure Merhar',
  'managingEditor' => 'dev@merhar.si (Jure Merhar)',
  'webMaster'      => 'dev@merhar.si (Jure Merhar)',
  'pubDate'        => '2008-03-16',
  'lastBuildDate'  => '2008-03-16 21:37:26',
  'category'       => array(
    'Example category',
    array('domain' => 'http://www.example.org', 'value' => 'Domain category')
  ),
  'generator'      => 'Pheeder RSS Generator by Jure Merhar (http://pheeder.sourceforge.net/)',
  'docs'           => 'http://www.rssboard.org/rss-specification',
  'cloud'          => array(
    'url'               => 'http://www.example.org/rpc',
    'registerProcedure' => 'pingMe'
  ),
  'ttl'            => '60',
  'image'          => 'valid-rss.png',
  'rating'         => '(PICS-1.1 "http://www.classify.org/safesurf/" l r (SS~~000 1))',
/* DEPRECATED
  'textInput'      => array(
    'title'       => 'Text Input',
    'description' => 'A text input tag',
    'name'        => 'text_input',
    'link'        => 'http://www.example.org/process'
  ),
/**/
/*
  'skipHours'      => array(10, 11, 12, 21, 22),
  'skipDays'       => array('Saturday', 'Sunday'),
  'items'          => array(
    array(
      'title'       => 'First RSS Entry',
      'link'        => 'http://www.example.org/first',
      'description' => 'This is the first entry in this RSS feed',
      'author'      => 'dev@merhar.si (Jure Merhar)',
      'category'    => array('Example category'),
      'comments'    => 'http://www.example.org/first/comments',
      'enclosure'   => 'valid-rss.png',
      'guid'        => 'http://www.example.org/first',
      'pubDate'     => '2008-03-16 15:03:15',
      'source'      => array(
        'url'   => 'http://www.example.com/first-article',
        'value' => 'Example website'
      )
    ),
    array(
      'title'       => 'Second RSS Entry',
      'link'        => 'http://www.example.org/second',
      'description' => 'This is the second entry in this RSS feed',
      'author'      => 'dev@merhar.si (Jure Merhar)',
      'category'    => array(
        array('domain' => 'http://www.example.org', 'value' => 'Domain category')
       ),
      'comments'    => 'http://www.example.org/second/comments',
      'enclosure'   => array(
         'url'    => 'http://www.example.org/second-podcast.mp3',
         'length' => '12216320',
         'type'   => 'audio/mpeg'
       ),
      'guid'        => 'http://www.example.org/second',
      'pubDate'     => '2008-03-16 19:53:41',
      'source'      => array(
        'url'   => 'http://www.example.com/second-article',
        'value' => 'Example website'
      )
    )
  )
);
/**/