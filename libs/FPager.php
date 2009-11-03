<?php
class FPager {
    var $totalItems = 0;
    var $maybeMore = false;
    var $currentPage = 1;
    var $manualCurrentPage = 0;
    var $perPage = 20;
    var $urlVar = 'p';
    var $itemData = array();
    var $extraVars = array();
    var $bannvars = array();
    var $nextText = "&gt;&gt;";
    var $previousText = "&lt;&lt;";
    var $adjacents = 1;
    //result
    var $links;
    function __construct($totalItems=0,$perPage=20,$conf=array()) {
    	//---defaults from config
		$params = FConf::get('pager');
		$params['prevImg'] = FLang::$PAGER_PREVIOUS;
		$params['nextImg'] = FLang::$PAGER_NEXT;
		$params['totalItems'] = $totalItems;
		$params['perPage'] = $perPage;
		
		if(!empty($conf)) $params = array_merge($params,$conf);
    	
        if(!empty($params)){
	        foreach ($params as $k=>$v) {
	        	$this->$k = $v;
	        }
        }
        foreach($_GET as $k=>$v) {
        	$vArr = explode(SEPARATOR,$v);
        	if(count($vArr)>1) {
        		$getAdd[$k]=$vArr[0];
        		while(count($vArr)>1) {
        			list($kl,$vl) = explode('=',array_pop($vArr));
        			$getAdd[$kl]=$vl;
        		}
        		unset($_GET[$k]);
        	} 
        }
        $localGet = $_GET;
        if(!empty($getAdd)) $localGet = array_merge($localGet,$getAdd);
         
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
    //function to return the pagination string
    function getPager()
    {
        //---check page validity
        if($this->currentPage < 1 || $this->currentPage > ceil($this->totalItems / $this->perPage)) $this->currentPage = 1;
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
        
        $pagestring = '?' . $extraVarsStr . $this->urlVar."=";

        //other vars
        $prev = $page - 1;									//previous page is page - 1
        $next = $page + 1;									//next page is page + 1
        $lastpage = ceil($totalitems / $limit);				//lastpage is = total items / items per page, rounded up.
        $lpm1 = $lastpage - 1;								//last page minus 1

        /*
        Now we apply our rules and draw the pagination object.
        We're actually saving the code to a variable in case we want to draw it more than once.
        */
        $pagination = "";
        if($lastpage > 1)
        {
            $pagination .= '<div class="pagination">';

            //previous button
            if ($page > 1)
                $pagination .= '<a href="'.$targetpage.$pagestring.$prev.'">'.$this->previousText.'</a>';
            else
                $pagination .= '<span class="disabled">'.$this->previousText.'</span>';

            //pages
            if ($lastpage < 7 + ($adjacents * 2))	//not enough pages to bother breaking it up
            {
                for ($counter = 1; $counter <= $lastpage; $counter++)
                {
                    if ($counter == $page) $pagination .= '<span class="current">'.$counter.'</span>';
                    else $pagination .= '<a href="' . $targetpage . $pagestring . $counter . '">'.$counter.'</a>';
                }
            }
            elseif($lastpage >= 7 + ($adjacents * 2))	//enough pages to hide some
            {
                //close to beginning; only hide later pages
                if($page < 2 + ($adjacents * 2))
                {
                    for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++)
                    {
                        if ($counter == $page) $pagination .= '<span class="current">'.$counter.'</span>';
                        else $pagination .= '<a href="' . $targetpage . $pagestring . $counter . '">'.$counter.'</a>';
                    }
                    $pagination .= '...'
                    . '<a href="' . $targetpage . $pagestring . $lpm1 . '">'.$lpm1.'</a>'
                    . '<a href="' . $targetpage . $pagestring . $lastpage . '">'.$lastpage.'</a>';
                }
                //in middle; hide some front and some back
                elseif($lastpage - ($adjacents * 2) > $page && $page > ($adjacents * 2))
                {
                    $pagination .= '<a href="' . $targetpage . $pagestring . '1">1</a>'
                    . '<a href="' . $targetpage . $pagestring . '2">2</a>'
                    . "...";
                    for ($counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++)
                    {
                        if ($counter == $page) $pagination .= '<span class="current">'.$counter.'</span>';
                        else $pagination .= '<a href="' . $targetpage . $pagestring . $counter . '">'.$counter.'</a>';
                    }
                    $pagination .= "..."
                    . '<a href="' . $targetpage . $pagestring . $lpm1 . '">'.$lpm1.'</a>'
                    . '<a href="' . $targetpage . $pagestring . $lastpage . '">'.$lastpage.'</a>';
                }
                //close to end; only hide early pages
                else
                {
                    $pagination .= '<a href="' . $targetpage . $pagestring . '1">1</a>'
                    . '<a href="' . $targetpage . $pagestring . '2">2</a>'
                    . "...";
                    for ($counter = $lastpage - (1 + ($adjacents * 2)); $counter <= $lastpage; $counter++)
                    {
                        if ($counter == $page) $pagination .= '<span class="current">'.$counter.'</span>';
                        else $pagination .= '<a href="' . $targetpage . $pagestring . $counter . '">'.$counter.'</a>';
                    }
                }
            }

            //next button
            if($this->maybeMore==true) $pagination .= ' ... ';
            if ($page < $counter - 1) $pagination .= '<a href="' . $targetpage . $pagestring . $next . '">'.$this->nextText.'</a>';
            else $pagination .= '<span class="disabled">'.$this->nextText.'</span>';
            $pagination .= "</div>\n";
        }
        return $this->links = $pagination;
    }
}