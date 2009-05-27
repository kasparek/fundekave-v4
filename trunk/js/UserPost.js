$(document).ready(function(){
	$("#prokoho").change(avatarfrominput);
  $("#recipientcombo").change(function (evt) {
    var str = "";
    $("#recipientcombo option:selected").each(function () { str += $(this).text() + " "; });
    $("#prokoho").attr("value", str);
    $("#recipientcombo").attr("selectedIndex", 0);
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