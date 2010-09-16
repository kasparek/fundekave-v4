//template extend
(function($){  
  
    //Attach this new method to jQuery  
    $.fn.extend({   
          
        //This is where you write your plugin's name  
        pluginname: function() {  
  
            //Iterate over the current set of matched elements  
            return this.each(function() {  
              
                //code to be inserted here  
              
            });  
        }  
    });  
})(jQuery);

//$.xmlService();
(function($){
		var xmlArray = [];
		var xmlStr = '<Item name="{KEY}"><![CDATA[{DATA}]]></Item>';
		var call = function(){ return true; };
		var createEl = function(type,attr) { var el = document.createElement(type); $.each(attr,function(key){ if(typeof(attr[key])!='undefined') el.setAttribute(key, attr[key]); }); return el; };
		var resetXMLRequest = function() { xmlArray = []; };
		var addXMLRequest = function(key, value) { var str = xmlStr; str = str.replace('{KEY}', key); str = str.replace('{DATA}', value); xmlArray.push(str); };
		var getXMLRequest = function() { var str = '<FXajax><Request>' + xmlArray.join('') + '</Request></FXajax>'; resetXMLRequest(); return str; };  
    $.extend({   
        xmlServiceSend: function(action,k) {  
				  var data = getXMLRequest();
					if(!k) k = gup('k',document.location);
					$.ajaxSetup({ 
				        scriptCharset: "utf-8" , 
				        //contentType: "application/x-www-form-urlencoded; charset=utf-8"
				        contentType: "text/xml; charset=utf-8"
					});
					$.ajax( {
						type : "POST",
						url : "index.php?m=" + action + "-x"+((k)?("&k="+k):('')),
						dataType : 'xml',
						dataProcess : false,
						cache : false,
						//data : "m=" + action + "-x"+((k)?("&k="+k):(''))+"&d=" + $.base64Encode(encodeURIComponent(data)),
						data : data,
						error: function(ajaxRequest, textStatus, error) { },
						success: function(data, textStatus, ajaxRequest) {  },
						complete : function(ajaxRequest, textStatus) {
							$(ajaxRequest.responseXML).find("Item").each(
									function() {
										var item = $(this);
										var command = '';
										switch (item.attr('target')) {
										case 'document':
										command =  item.attr('target') + '.' + item.attr('property') + ' = "'+item.text()+'"';
										break;
										default:
										switch (item.attr('property')) {
											case 'css':
												el = createEl('link',{'type':'text/css','rel':'stylesheet','href':item.text()});
										        if ($.browser.msie) el.onreadystatechange = function() { /loaded|complete/.test(el.readyState) && call(); };
										        else if ($.browser.opera) el.onload = call;
										        else { //FF, Safari, Chrome
										          (function(){
										            try {
											            el.sheet.cssRule;
										            } catch(e){
											            setTimeout(arguments.callee, 200);
											            return;
										            };
										            call();
										          })();
										        }
											  $('head').get(0).appendChild(el);
								                 break;
											case 'getScript':
												var arr = item.text().split(';');
												if(arr[1]) {
													command = "$.getScript('"+arr[0]+"',"+arr[1]+");";
												} else {
													command = "$.getScript('"+arr[0]+"');";
												}
												break;
											case 'callback':
												command = item.text() + "( data.responseText );";
												break;
											case 'call':
												var arr = item.text().split(';');
												var functionName = arr[0];
												var par = '';
												if (arr.length > 1) {
													arr.splice(0,1);
													par = arr.join("','");
												}
												command = functionName + "('" + par + "');";
												break;
											case 'void':
													//just debug message
												break;
											case 'body':
												command = '$("body").append( item.text() );';
												break;
											default:
												var property = item.attr('property');
												if(property[0]=='$') {
													property = property.replace('$','');
													command = '$("#' + item.attr('target') + '").' + property + '( item.text() );'
												} else { 
													command = '$("#' + item.attr('target') + '").attr("' + item.attr('property') + '", item.text());';
												}
										};
										};
										if(command.length>0) eval(command);
										if(formSent) { 
											$('.button',formSent).removeAttr('disabled'); formSent=null;
											if(draftdrop===true) draftDropAll(); 
										}
									});
						}
					});  
        }  
    });  
})(jQuery);