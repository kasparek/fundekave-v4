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

    addXMLRequest('username', $("#prokoho").attr("value"));
    addXMLRequest('result', "recipientavatar");
    addXMLRequest('resultProperty', 'html');
    addXMLRequest('call', 'initSupernote');
    addXMLRequest('call', 'initSwitchFriend');
    sendAjax('post-avatarfrominput');

}