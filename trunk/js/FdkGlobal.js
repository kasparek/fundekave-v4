function gup(name, url) {
  name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
  var regex = new RegExp( "[\\?&]"+name+"=([^&#]*)" ), results = regex.exec( url );
  return (results === null)?(0):(results[1]);
};

function initSupernote() {
  supernote = new SuperNote('supernote', {});
}

function initSwitchFriend() {
  $(".switchFriend").click(function (evt) {
    evt.preventDefault();
    addXMLRequest('userId', gup('switchUser',$(this).attr("href")));
    addXMLRequest('result', $(this).attr("id"));
    addXMLRequest('resultProperty', 'html');
    addXMLRequest('call', 'initSwitchFriend');
    sendAjax('user-switchFriend');
  });
}

function sendAjax(action) {
  $.ajax({
			type: "POST", url: "index.php", cache:false, data: "m="+action+"-x&data=" + getXMLRequest(),
			complete: function(data){
        $(data.responseText).find("Item").each(function() {
          var item = $(this);
          switch($(item).attr('property')) {
            case 'callback':
              eval( $(item).text() + "( data.responseText );" );
            break;
            case 'call':
              eval( $(item).text() + "();" );
            break;
            case 'html':
              eval( $(item).attr('target')+'.'+$(item).attr('property')+' = $(item).text();' );
            break;
            default:
              eval( $(item).attr('target')+'.attr("'+$(item).attr('property')+'", $(item).text());' );
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
  //return jQuery.createXMLDocument(str);
}

// Name: createXMLDocument
// Input: String
// Output: XML Document
jQuery.createXMLDocument = function(string)
{
var browserName = navigator.appName;
var doc;
if (browserName == 'Microsoft Internet Explorer')
{
doc = new ActiveXObject('Microsoft.XMLDOM');
doc.async = 'false'
doc.loadXML(string);
} else {
doc = (new DOMParser()).parseFromString(string, 'text/xml');
}
return doc;
}