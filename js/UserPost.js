$(document).ready(function(){
	//global vars
	var recCombo = $("#recipientcombo");
	var recInput = $("#prokoho");
	var recAvatarDiv = $("#recipientavatar");

	recInput.change(avatarfrominput);
  
  recCombo.change(function (evt) {
    var str = "";
    $("#recipientcombo option:selected").each(function () { str += $(this).text() + " "; });
    recInput.attr("value", str);
    recCombo.attr("selectedIndex", 0);
    avatarfrominput(evt);
  });
  
  
});

function avatarfrominput(evt) {
  var username = $("#prokoho").attr("value");
  $.ajax({
			type: "POST", url: "index.php", data: "m=post-avatarfrominput&username=" + username,
			complete: function(data){
				$("#recipientavatar").html(data.responseText);
				initSupernote();
				initSwitchFriend();
			}
		 });
}