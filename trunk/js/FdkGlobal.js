function gup(name, url) {
  name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
  var regex = new RegExp( "[\\?&]"+name+"=([^&#]*)" ), results = regex.exec( url );
  return (results === null)?(0):(results[1]);
};

function initSupernote() {
  //supernote = new SuperNote('supernote', {});
}

function initSwitchFriend() {
  $(".switchFriend").unbind('click', switchFriendRequest);
  $(".switchFriend").bind('click', switchFriendRequest);
}

function switchFriendRequest(evt) {
    evt.preventDefault();
    var data = gup('d',$(this).attr("href"));
    var dataArr = data.split(';');
    for(var i=0;i<dataArr.length;i++) {
    var valueArr = dataArr[i].split(':');
      addXMLRequest(valueArr[0], valueArr[1]);
    }
    addXMLRequest('result', $(this).attr("id"));
    addXMLRequest('resultProperty', 'html');
    addXMLRequest('call', 'initSwitchFriend');
    sendAjax(gup('m',$(this).attr("href")));
  }

function sendAjax(action) {
  var data = getXMLRequest();
  $.ajax({
			type: "POST", url: "index.php", dataType:'xml', dataProcess:false, cache:false, data: "m="+action+"-x&d=" + data,
			complete: function(data){
        $(data.responseXML).find("Item").each(function() {
          var item = $(this);
          switch(item.attr('property')) {
            case 'callback':
              eval( item.text() + "( data.responseText );" );
            break;
            case 'call':
              eval( item.text() + "();" );
            break;
            case 'html':
              eval( '$("#'+item.attr('target')+'").' + item.attr('property') + '( item.text() );' );
            break;
            default:
              eval( '$("#'+item.attr('target')+'").attr("' + item.attr('property') + '", item.text());' );
          }
        });
			}
		 });
}

/**
 *main init
 **/ 
$(document).ready(function(){

initSupernote();
initSwitchFriend();

});

//---jquery plugin
var xmlArray = [];
var xmlStr = '<Item name="{KEY}"><![CDATA[{DATA}]]></Item>';
function resetXMLRequest() {
  xmlArray = []
}
function addXMLRequest(key,value) {
  var str = xmlStr;
  str = str.replace('{KEY}',key);
  str = str.replace('{DATA}',value);
  xmlArray.push(str);
}
function getXMLRequest() {
  var str = '<FXajax><Request>' + xmlArray.join('') + '</Request></FXajax>';
  resetXMLRequest();
  return str;
}