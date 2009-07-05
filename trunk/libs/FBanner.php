<?php
class FBanner {
	static function getBanner() {
		$user = FUser::getInstance();
		 
		$cache = FCache::getInstance('s',0);
		$strictBanner = $cache->getData('list','banners');
		$strictBannerAllClicked = $cache->getData('allClick','banners');
		if($strictBannerAllClicked === false) $strictBannerAllClicked = 0;
		 
		if(empty($strictBanner)) {
			if($user->userVO->zbanner == 0 && $strictBannerAllClicked == 0) {
				$dot = "select b.bannerId,b.imageUrl,b.linkUrl,
    	        if(h.dateCreated is null or date_format(max(h.dateCreated),'%d') != date_format(now(),'%d'),0,1)
    	        from sys_banner as b left join sys_banner_hit as h on b.bannerId=h.bannerId and h.userId='".$user->userVO->userId."'
    	        where b.dateFrom <= NOW() AND b.dateTo > NOW() 
    	        and b.strict=1 
    	        group by b.bannerId";
				$arr = FDBTool::getAll($dot);
				$strictBannerAllClicked = 1;
				foreach ($arr as $row) {
					if($row[3]==0) {
						$strictBanner[] = $row;
						$strictBannerAllClicked = 0;
					}
				}
			} elseif($user->userVO->zbanner == 1) {
				$dot = "SELECT bannerId,imageUrl,linkUrl FROM sys_banner WHERE dateFrom <= NOW() AND dateTo > NOW() ORDER BY RAND()";
				$strictBanner = FDBTool::getAll($dot);
			}
			$cache->setData($strictBanner, 'list','banners');
			$cache->setData($strictBannerAllClicked, 'allClick','banners');
		}
		 
		if(!empty($strictBanner)) {
			if($user->userVO->zbanner == 0) {
				$banner = array_shift($strictBanner);
				$cache->setData($strictBanner, 'list','banners');
			} else {
				$banner = $strictBanner[rand(0,count($strictBanner)-1)];
			}
			$imgname = WEB_REL_BANNER . $banner[1];
			$imglink = 'bannredir.php?bid='.$banner[0];

			if(preg_match("/(.swf)$/",$imgname))
			$ret= '<object type="application/x-shockwave-flash" data="'.$imgname.'" width="468" height="60"><param name="movie" value="'.$imgname.'" /></object>';
			elseif(preg_match("/(jpg|gif)$/",$imgname))
			$ret= '<a href="'.$imglink.'" target="_blank"><img src="'.$imgname.'" width="468" height="60"></a>';
			else $ret = $banner[1];

			FDBTool::query("UPDATE sys_banner SET display=display+1 WHERE bannerId=".$banner[0]);
			 
			return($ret);
		}
	}

	static function bannerRedirect($bannerId) {
		$user = FUser::getInstance();
		$bid = $bannerId * 1;
		FDBTool::query("UPDATE banner SET hit = (hit+1) WHERE id='".$bid."'");
		if($user->idkontrol) {
			FDBTool::query("insert into sys_banner_hit (bannerId,userId,dateCreated) values ('".$bid."','".$user->userVO->userId."',now())");
			if($user->zbanner == 1)  {
				$cache = FCache::getInstance('s',0);
				$strictBanner = $cache->getData('list','banners');
				if(count($strictBanner > 1)) {
					foreach ($strictBanner as $banner) {
						if($banner[0]!=$bid) $newBannArr[] = $banner;
					}
					$cache->setData($newBannArr, 'list','banners');
				}
				else $cache->setData(1 , 'list','banners');
			}
		}
		header("Location: http://".str_replace("http://","",FDBTool::getOne("SELECT linkUrl FROM sys_banner WHERE bannerId='".$bid."'")));
	}
}