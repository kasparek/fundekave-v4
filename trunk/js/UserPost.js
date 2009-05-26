$(document).ready(function(){
	//global vars
	var recCombo = $("#recipientcombo");
	var recInput = $("#prokoho");
	var racAvatarDiv = $("#recipientavatar");
	                                      alert('aaa');
	recInput.change(function (evt) {
	alert('bbb');
    var username = recInput.attr("value");
    $.ajax({
				type: "POST", url: "index.php", data: "m=post&amp;a=avatarfrominput&amp;username=" + username,
				complete: function(data){
					racAvatarDiv.html(data.responseText);
				}
			 });


  });
  
  
  
});