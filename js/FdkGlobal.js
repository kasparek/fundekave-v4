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
  
    var href = $(this).attr("href");
    var link = $(this);
    evt.preventDefault();
    
  $.ajax({
			type: "POST", url: "index.php", data: "m=user-switchFriend&userId=" + gup('switchUser',href),
			complete: function(data){
				link.html(data.responseText);
				initSwitchFriend();
			}
		 });
    
  });
  
  $(".testtest").click(function (evt) {
  
    addXMLRequest('dada','sdfsdfsdfsd');
    addXMLRequest('drdr','aaaaaa');
    addXMLRequest('111','dfgdfg');
    evt.preventDefault();
    
  $.ajax({
			type: "POST", url: "index.php", cache:false, processData:false, data: "m=user-TestTest-x&data=" + getXMLRequest(),
			complete: function(data){
				alert(data.responseText);
			}
		 });
    
  });
  
  
}

function sendAjax(action, data) {
  $.ajax({
			type: "POST", url: "index.php", data: "m=user-switchFriend&data=" + data,
			complete: function(data){
				link.html(data.responseText);
				initSwitchFriend();
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