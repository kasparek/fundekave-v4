$.ajaxSetup({ 
				scriptCharset: "utf-8" , 
        contentType: "application/x-www-form-urlencoded; charset=utf-8",
        cache: false,
        dataType: "json",
        dataProcess : false
	});

function Chat () {
	var me = this;
	this.id = 0;
	this.state = 0;
	this.alert = false;
	this.sent = false;
	this.high = false;
	
	this.start = function(id) {
		chat.id = id;
		$('#page-wrap').append('<div id="chat'+chat.id+'" class="chat"><div class="chat-name">kasparek<a href="#" class="chat-close">x</a></div><div class="chat-wrap"><div class="chat-area"></div></div><form><textarea class="chat-message-area" maxlength="1000"></textarea></form></div>');
		chat.updateRequests = [];
		chat.settings();
		chat.update();
	}
	
	this.send = function(message) { 
		message = $.trim(message);       
		if(message.length>0) {
			$.ajax({url: "process.php",type:'POST',data:{'f':'2','i':this.id,'m':message}});
			this.sent = true;
		} 
	}
	
	this.read = function() {
		if(me.alert===true) { 
			me.alert = false; 
			$.ajax({url: "process.php",type:'POST',data:{'f':3,i:me.id,'s':me.state}});
		} 
	}
	
	this.settings = function() {
		$("#chat"+this.id+" .chat-message-area").click( this.read );
		$("#chat"+this.id+" .chat-area").click( this.read );
  	$("#chat"+this.id+" .chat-message-area").keyup( this.sendHandler );
  	$("#chat"+this.id+" .chat-message-area").keydown( function(event) { if (event.which >= 33) { if (this.value.length >= $(this).attr("maxlength")) { event.preventDefault(); } }  });
		$("#chat"+this.id+" .chat-close").click( this.destroy );  
	}
	
	this.alertShow = function() {
		if(me.alert===true) {
			$("#chat"+me.id).css('backgroundColor',me.high ? "#888888" : "#333333"); 
			me.high = !me.high; 
			setTimeout(me.alertShow,500);
		}
	}
	this.initialized = false;
	this.updateRequests = [];	
	this.update = function() {
		me.updateRequests.push( $.ajax({url: "process.php",data: {'f':me.initialized==true?1:4,i:me.id,'s':me.state?me.state:0},
	  success: function(data) {
	  	if(data) {
	  	var chatarea = $("#chat"+me.id+' .chat-area');
	  	if (data.text!=null) {
				for (var i = 0; i < data.text.length; i++) {  
					chatarea.append($("<p"+(data.text[i][0]==1?' class="chat-me"':'')+"><span>"+data.text[i][1]+"</span>"+ data.text[i][2] +"</p>"));
				}
				chatarea.animate({ scrollTop: chatarea.attr("scrollHeight") }, 3000);
	  	}
	  	if(data.readed) {
	  		me.state = data.readed; 
			}
	  	if(me.sent==false && me.alert==false && data.s!=0 && me.state!=data.s) {
	  		me.alert = true;
				me.alertShow();
			}
		  me.state = data.s;
		  me.sent = false;
		  me.initialized = true;
	  	}
	  	setTimeout(me.update, 500);
	  }
	}) );
	}
	
	this.sendHandler = function(event) {
		if (event.keyCode == 13) {
		var target = $(this);
	  var text = target.val();
	  var maxLength = target.attr("maxlength");  
	  var length = text.length; 
	  if (length <= maxLength + 1) {  
				me.send(text);	
				target.val("");
	  } else {
	      target.val(text.substring(0, maxLength));
	  }	
		}
	};
	
	this.destroy = function(event) {
		//cancel running requests
		if (me.updateRequests.length > 0) {
		    for (var i = 0; i < me.updateRequests.length; i++) { 
		        me.updateRequests[i].abort();
		    }
			me.updateRequests = [];
		}
		$.ajax({url: "process.php",type:'POST',data:{'f':3,i:me.id,'s':me.state,success: function(data) {
			$("#chat"+me.id).remove();
			me.id = 0;
		}}});
		event.preventDefault();
	}
	
}
