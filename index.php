<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <link rel="stylesheet" type="text/css" href="main.css"/>
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js?ver=1.4.2" type="text/javascript"></script>
    <script type="text/javascript" src="chat.js"></script>
    

</head>
<body>
    <div id="page-wrap"> 
        
        
    </div>
        
</body>

<script type="text/javascript">

var chats = [];
function checkChat() {
$.ajax({url: "process.php",data: {'f':5},	  success: function(data) {
	for (var i = 0; i < data.length; i++) {
	 	 var openChat = true;
		 for (var j = 0; j < chats.length; j++) {
		 if(chats[j]) {
		 	if(chats[j].id==0) delete(chats[j]);
	 	 	else if(chats[j].id==data[i]) openChat = false;
	 	 }
		}
		if(openChat===true) { 
		chat = new Chat; 	
		chat.start(data[i]);
		chats.push(chat);	  
		}
	}
	setTimeout(checkChat, 5000);
}	});
}

checkChat();

</script>

</html>


