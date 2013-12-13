<?php
class FPager {
    var $totalItems = 0;
	var $hasMidButtons = true;
    var $maybeMore = false;
    var $currentPage = 1;
    var $manualCurrentPage = 0;
    var $perPage = 20;
    var $urlVar = 'p';
    var $urlSep = '=';
    var $itemData = array();
    var $extraVars = array();
    var $bannvars = array();
    var $nextText = "&gt;&gt;";
    var $previousText = "&lt;&lt;";
    var $adjacents = 1;
    var $hash = '';
    var $class;
    //result
    var $links;
    function __construct($totalItems=0,$perPage=20,$conf=array()) {
    	//---defaults from config
		$params = FConf::get('pager');
		$params['prevImg'] = FLang::$PAGER_PREVIOUS;
		$params['nextImg'] = FLang::$PAGER_NEXT;
		$params['totalItems'] = $totalItems;
		$params['perPage'] = $perPage;
		
		if(!empty($conf)) $params = array_merge($params, $conf);
		    	
        if(!empty($params)){
	        foreach ($params as $k=>$v) {
	        	$this->$k = $v;
	        }
        }
        $localGet = $_GET;
        foreach ($localGet as $k=>$v) {
        	if($k!=$this->urlVar && !in_array($k,$this->bannvars)) $this->extraVars[$k]=$v;
        }
        if(!empty($this->itemData)) $this->totalItems = count($this->itemData);
        if(isset($localGet[$this->urlVar])) $this->currentPage = $_GET[$this->urlVar] * 1;
        if($this->manualCurrentPage!=0) $this->currentPage = $this->manualCurrentPage;
        if(!isset($conf['noAutoparse'])) {
            $this->getPager();
        }
    }
    function getCurrentPageID() {
        return $this->currentPage;
    }
    function getPageData() {
        $begin = ($this->currentPage-1)*$this->perPage;
        return array_slice($this->itemData,$begin,$this->perPage);
    }
	function getLinkStart($classnames='') {
		$c = array();
		if(!empty($classnames)) $c[]=$classnames;
		if(!empty($this->class)) $c[]=$this->class;
		return '<li'.((!empty($c))?' class="'.implode(" ",$c).'"':'').'><a href="';
	}
    //function to return the pagination string
    function getPager()
    {
        //---check page validity
        if($this->perPage>0) $numPages = ceil($this->totalItems / $this->perPage); else $numPages=1;
        if($this->currentPage < 1 || $this->currentPage > $numPages) $this->currentPage = 1;
        //defaults
        $page = $this->currentPage;
        $totalitems = $this->totalItems;
        $adjacents = $this->adjacents;
        $limit = $this->perPage;
        $targetpage = "";
        
        $extraVarsStr = '';
        if(!empty($this->extraVars)) {
            foreach ($this->extraVars as $k=>$v) $extraVarsStr .= $k.'='.$v.'&';
        }
        $hash='';
        if($this->hash!='') {
        	$hash = '#'.$this->hash;
        }
        
        $pagestring = '?' . $extraVarsStr . $this->urlVar . $this->urlSep;

        //other vars
        $prev = $page - 1;									//previous page is page - 1
        $next = $page + 1;									//next page is page + 1
        $lastpage = 1;
        if($limit>0) $lastpage = ceil($totalitems / $limit);				//lastpage is = total items / items per page, rounded up.
        $lpm1 = $lastpage - 1;								//last page minus 1

        /*
        Now we apply our rules and draw the pagination object.
        We're actually saving the code to a variable in case we want to draw it more than once.
        */
        $pagination = "";
        if($lastpage > 1)
        {
            $pagination .= '<ul class="pagination">';

            //previous button
            
            $pagination .= $this->getLinkStart($page > 1 ? '' : 'disabled').$targetpage.$pagestring.$prev.$hash.'">'.$this->previousText.'</a></li>';
            
			if($this->hasMidButtons) {
            //pages
            if ($lastpage < 7 + ($adjacents * 2))	//not enough pages to bother breaking it up
            {
                for ($counter = 1; $counter <= $lastpage; $counter++)
                {
					$pagination .= $this->getLinkStart($counter == $page ? 'active' : '') . $targetpage . $pagestring . $counter . $hash .'">'.$counter.'</a></li>';
                }
            }
            elseif($lastpage >= 7 + ($adjacents * 2))	//enough pages to hide some
            {
                //close to beginning; only hide later pages
                if($page < 2 + ($adjacents * 2))
                {
                    for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++)
                    {
						$pagination .= $this->getLinkStart($counter == $page ? 'active' : '') . $targetpage . $pagestring . $counter . $hash .'">'.$counter.'</a></li>';
                    }
                    $pagination .= '<li class="disabled"><a>...</a></li>'
                    . $this->getLinkStart() . $targetpage . $pagestring . $lpm1 . $hash . '">'.$lpm1.'</a></li>'
                    . $this->getLinkStart() . $targetpage . $pagestring . $lastpage . $hash . '">'.$lastpage.'</a></li>';
                }
                //in middle; hide some front and some back
                elseif($lastpage - ($adjacents * 2) > $page && $page > ($adjacents * 2))
                {
                    $pagination .= $this->getLinkStart() . $targetpage . $pagestring . '1'.$hash.'">1</a></li>'
                    . $this->getLinkStart() . $targetpage . $pagestring . '2'.$hash.'">2</a></li>'
                    . '<li class="disabled"><a>...</a></li>';
                    for ($counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++)
                    {
                        $pagination .= $this->getLinkStart($counter == $page ? 'active' : '') . $targetpage . $pagestring . $counter . $hash .'">'.$counter.'</a></li>';
                    }
                    $pagination .= '<li class="disabled"><a>...</a></li>'
                    . $this->getLinkStart() . $targetpage . $pagestring . $lpm1 .$hash. '">'.$lpm1.'</a></li>'
                    . $this->getLinkStart() . $targetpage . $pagestring . $lastpage .$hash. '">'.$lastpage.'</a></li>';
                }
                //close to end; only hide early pages
                else
                {
                    $pagination .= $this->getLinkStart() . $targetpage . $pagestring . '1'.$hash.'">1</a></li>'
                    . $this->getLinkStart() . $targetpage . $pagestring . '2'.$hash.'">2</a></li>'
                    . '<li class="disabled"><a>...</a></li>';
                    for ($counter = $lastpage - (1 + ($adjacents * 2)); $counter <= $lastpage; $counter++)
                    {
                        $pagination .= $this->getLinkStart($counter == $page ? 'active' : '') . $targetpage . $pagestring . $counter . $hash .'">'.$counter.'</a></li>';
                    }
                }
            }
			}
            //next button
            if($this->maybeMore==true) $pagination .= '<li class="disabled"><a>...</a></li>';
			
			$pagination .= $this->getLinkStart($page < $lastpage ? '' : 'disabled').$targetpage.$pagestring.$next.$hash.'">'.$this->nextText.'</a></li>';
            $pagination .= "</ul>\n";
        }
//		var_dump($pagination);die();
        return $this->links = $pagination;
    }
}