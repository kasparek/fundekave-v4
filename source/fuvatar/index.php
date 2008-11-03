<?php

require('config.php');
require('fUvatar.class.php');

$fUvatar = new fUvatar($fuvatarConfig);
$fUvatar->process();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
	<head>
		<title>FUVATAR 0.3 alpha</title>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<script src="fdk-ondom.js" type="text/javascript"></script>
		<script type="text/javascript" src="swfobject.js"></script>
		
		
		<script language="JavaScript">
		    <!--
		    //TODO: when is offline - stop reloading image - when is online again start reloadinh
		    	//---optional - when is offline start to reload from different url
		    	function fuvatarSetRefresh() { 
				  var arr = elmsByClass('fuvatarimg'); var length = arr.length;
				  if(length > 0) { 
				    for(var z=0;z<length;z++) { 
				      arr[z].onload = function() { window.setTimeout(function (obj) { obj.src = gul(obj.src)+'?fure='+gup('fure',obj.src)+'&fuca=' + Math.random(); },gup('fure',this.src),this); } 
				    } 
				  }
				  var arr = elmsByClass('fuvatarstatus'); var length = arr.length;
				  if(length > 0) { 
				    for(var z=0;z<length;z++) { 
				      arr[z].onload = function() { window.setTimeout(function (obj) { obj.src = gul(obj.src)+'?fust='+gup('fust',obj.src)+'&fure='+gup('fure',obj.src)+'&fuca=' + Math.random(); },gup('fure',this.src),this); } 
				    } 
				  }
				}
				//DOMReady(fuvatarSetRefresh);
		--></script>
	</head>
	<body>
		
			<h1>FUVATAR</h1>
			
			<p><a href="fuvatar.html" onClick="window.open('fuvatar.html','fuvatarwindow','height=195,width=355,status=0,toolbar=0,location=0,manubar=0,resizable=0,scrollbars=0');return false;">Webcam</a></p>

<?php echo $fUvatar->getSwf(); ?>


<!-- 			<p>Online: <?php echo $fUvatar->getStatusIcon(); ?></p>
			<p><?php echo $fUvatar->getImg(); ?></p>-->


	</body>
</html>
