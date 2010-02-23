

function friendRequestInit() {
	$('#friendrequest').show('slow');
	fajaxform();
	$('#cancel-request').bind('click',function(event){remove('friendrequest');event.preventDefault()});
}

function enable(id) { $('#'+id).removeAttr('disabled'); };
function tabsInit() { $("#tabs").tabs(); };
function remove(id,notween) { if(notween==1) { $('#'+id).remove(); }else{ $('#'+id).hide('slow',function(){$('#'+id).remove()}); } };

function BBQinit() {
    var url = $.param.fragment();
    if(url) {
    var urlArr = url.split('/');
    if(urlArr[1]) {
    var arr = urlArr[1].split(';');
		while (arr.length > 0) {
			var rowStr = arr.shift();
			var row = rowStr.split(':');
			addXMLRequest(row[0], row[1]);
			if (row[0] == 'result') result = true;
			if (row[0] == 'resultProperty') resultProperty = true;
		}
		}
    sendAjax(urlArr[0]);
    }
};

//http://ajax.googleapis.com/ajax/libs/jquery/1.3/jquery.min.js
function fuupUploadOneComplete() {
	sendAjax('page-fuup',gup('k',$(".fajaxform").attr('action')));
};
function fuupUploadAvatarComplete() {
	addXMLRequest('result', "avatarBox");
	addXMLRequest('resultProperty', '$html');
  sendAjax('user-avatar',gup('k',$(".fajaxform").attr('action')));
};
function fuupUploadPageAvatarComplete() {
	addXMLRequest('result', "pageavatarBox");
	addXMLRequest('resultProperty', '$html');
	addXMLRequest('call', 'fconfirm');
	sendAjax('page-avatar',gup('k',$(".fajaxform").attr('action')));
};
function fuupUploadEventComplete() {
	var item = $('#item').attr('value');
	if(item>0) addXMLRequest('item', item);
  addXMLRequest('result', "flyerDiv");
	addXMLRequest('resultProperty', '$html');
	addXMLRequest('call', 'initSlimbox');
	addXMLRequest('call', 'fconfirm');
	addXMLRequest('call', 'fajaxform');
	sendAjax('event-flyer',gup('k',$(".fajaxform").attr('action')));	
}

function bindDeleteFoto() { setListeners('deletefoto', 'click', deleteFoto); }
function deleteFoto(event) {
	if (confirm($(this).attr("title"))) {
		//---send ajax
		var id = $(this).attr("id");
		idArr = id.split("-");
		addXMLRequest('itemId', idArr[1]);
		sendAjax('galery-delete');
		//---remove element
		$('#foto-'+idArr[1]).hide('slow',function(){$('#foto-'+idArr[1]).remove()});
		fotoTotal--;
		$("#fotoTotal").text(fotoTotal);
	}
	event.preventDefault();
	preventAjax = true;
};

function fajaxform(event) { setListeners('button', 'click', onButtonClick); setListeners('fajaxform', 'submit', fsubmit ); };
var buttonClicked = '';
var formSent;
var preventAjax = false;
var draftdrop = false;

function fconfirm(event) { 
$('.confirm').each(function(){ 
	if(!$(this).hasClass('fajaxa')) {
		$(this).bind('click',onConfirm);
	}
});
};
function onConfirm(e) { 
	if(!confirm($(e.currentTarget).attr("title"))) { 
	preventAjax = true; 
	e.preventDefault(); 
	} 
	};

function changeInit() {
 setListeners('change','change',fOnChange);
}
function fOnChange(e) {
	var value = $(e.target).attr('value');
	//sendAjax();
} 

function onButtonClick(event) { buttonClicked = event.target.name; if($(event.target).hasClass('draftdrop')) draftdrop=true; };
function fsubmit(event) {
	event.preventDefault();
	$('.errormsg').hide('slow',function(){ if($(this).hasClass('static')) $(this).remove(); } );
	$('.okmsg').hide('slow',function(){ if($(this).hasClass('static')) $(this).remove(); } );
	$('.button',this).attr('disabled','disabled');
	formSent = this;
	if (preventAjax == true) { 
	preventAjax = false; 
	return; 
	}
	var arr = $(this).formToArray(false);
	var result = false;
	var resultProperty = false;
	while (arr.length > 0) {
		var obj = arr.shift();
		addXMLRequest(obj.name, obj.value);
		if (obj.name == 'result') result = true;
		if (obj.name == 'resultProperty') resultProperty = true;
	}
	if (result == false) addXMLRequest('result', $(this).attr("id"));
	if (resultProperty == false) addXMLRequest('resultProperty', '$html');
	if (buttonClicked.length > 0) addXMLRequest('action', buttonClicked);
	addXMLRequest('k', gup('k', this.action));
	sendAjax(gup('m', this.action),gup('k', this.action));
};

function fuupInit() { $(".fuup").each(function(i){ swfobject.embedSWF("http://fundekave.net/assets/Fuup.swf", $(this).attr('id'), "100", "25", "10.0.12", "expressInstall.swf", {config:"files.php?k="+gup('k',$(".fajaxform").attr('action'))+"|f=cnf|c="+$(this).attr('id').replace(/D/g,".").replace(/S/g,'/'),containerId:$(this).attr('id')},{wmode:'transparent',allowscriptaccess:'always'}); }); }

function fajaxa(event) { setListeners('fajaxa', 'click', fajaxaSend); };
function fajaxaSend(event) {
	if($(this).hasClass('hash')) document.location.hash = gup('m',this.href)+'/'+gup('d',this.href);
	if($(this).hasClass('showBusy')) $(".showProgress").attr('src','assets/loading.gif');
	if($(event.currentTarget).hasClass('confirm')) {
		if(!confirm($(event.currentTarget).attr("title"))) { 
			preventAjax = true; 
			event.preventDefault(); 
		}
	} 
	if (preventAjax == true) { preventAjax = false; return; }
	var href=$(this).attr("href") ,result = false, resultProperty = false, str = gup('d', href), id = $(this).attr("id");
	if(str) { 
		var arr = str.split(';');
		while (arr.length > 0) {
			var rowStr = arr.shift();
			var row = rowStr.split(':');
			addXMLRequest(row[0], row[1]);
			if (row[0] == 'result') result = true;
			if (row[0] == 'resultProperty') resultProperty = true;
		}
	}
	if(id) {
		if (result == false) addXMLRequest('result', $(this).attr("id"));
		if (resultProperty == false) addXMLRequest('resultProperty', '$html');
	}
	sendAjax(gup('m', href),gup('k', href));
	event.preventDefault();
};

function initUserPost() {
	$("#prokoho").change(avatarfrominput);
	$("#saction").change( function(evt) {
		  if($("#saction option:selected").attr('value') == 'setpp') $('#ppinput').show();
		  else $('#ppinput').hide(); 
	});
	$("#recipientcombo").change( function(evt) {
		var str = "";
		$("#recipientcombo option:selected").each( function() {
			str += $(this).text() + " ";
		});
		$("#prokoho").attr("value", str);
		$("#recipientcombo").attr("selectedIndex", 0);
		avatarfrominput(evt);
	});
}
function avatarfrominput(evt) {
	addXMLRequest('username', $("#prokoho").attr("value"));
	addXMLRequest('result', "recipientavatar");
	addXMLRequest('resultProperty', '$html');
	addXMLRequest('call', 'fajaxa');
	sendAjax('post-avatarfrominput');
}

function datePickerInit() { $.datepicker.setDefaults($.extend( { showMonthAfterYear : false }, $.datepicker.regional[''])); $(".datepicker").datepicker($.datepicker.regional['cs']); };

function initSlimbox() {
	$("a[rel^='lightbox']").slimbox( { overlayFadeDuration : 100, resizeDuration : 100, imageFadeDuration : 100, captionAnimationDuration : 100 }, null, function(el) { return (this == el) || ((this.rel.length > 8) && (this.rel == el.rel)); });
}

function initSupernote() {
	supernote = new SuperNote('supernote', {});
}

var TAOriginalArr = [];
function draftableSaveTA(id) {
	TAOriginalArr.push( [ id, $('#'+id).attr('value') ] );
}

function draftDropAll() {
$('.draftable').each( function() {
	$('#draftdrop'+$(this).attr('id')).remove();
 	addXMLRequest('result', $(this).attr('id'));
	sendAjax('draft-drop');
});
}

function unusedDraft(TAid) {
	$('#draftdrop'+TAid).each( function() {
	  addXMLRequest('result', TAid);
		sendAjax('draft-drop');
		$(this).remove();
	});
}

function dropDraft(e) {
	e.preventDefault();
	var x, arrDraftLength = TAOriginalArr.length, arr=[];
	for (x = 0; x < arrDraftLength; x++) {
		var taArr = TAOriginalArr[x];
		if(taArr[0] == gup('ta',$(e.currentTarget).attr('href'))) {
		 $('#'+taArr[0]).attr('value',taArr[1]);
		 addXMLRequest('result', taArr[0]);
		 sendAjax('draft-drop');
		} else {
			arr.push( taArr );
		}
	}
	TAOriginalArr = arr;
	$(e.currentTarget).remove();
}

function draftOnSubmit(e) { 
var x, arrDraftLength = arrDraft.length;
	for (x = 0; x < arrDraftLength; x++) {
		if (arrDraft[x][2]) { clearTimeout(arrDraft[x][2]); }
	}
 };

//draftable initialization
function draftSetEventListeners(TAid) {
	setListeners('submit', 'click', draftOnSubmit);
	setListeners('draftable', 'keyup', draftEventHandler);
	if(window.location.hash=='#dd' || gup('dd',window.location)==1) {
		draftDropAll();
		window.location.hash='';
	} else {
		if(TAid) {
			draftCheck( TAid );
		} else {
			$('.draftable').each( function (){ draftCheck( $(this).attr('id') ); } );
		}
	}
};
//draftable check for draft exist
function draftCheck(TAid) {
	$(TAid).attr('disabled','disabled');
 	addXMLRequest('result', TAid);
	addXMLRequest('resultProperty', '$html');
	addXMLRequest('call','enable;'+TAid);
	sendAjax('draft-check');
};

var waitingTA;
var markitupScriptsLoaded = false;
function markItUpInit() { var textid='.markitup'; if(waitingTA) { textid = '#'+waitingTA; waitingTA = null; } $(textid).markItUp(mySettings); markitupScriptsLoaded = true; };

function addTASwitch() {
	$('.markitup').each( function() { $(this).before('<span class="textAreaResize"><a href="?textid='+$(this).attr('id')+'" class="toggleToolSize"></a></span>'); });
	setListeners(
			'toggleToolSize',
			'click',
			function(e) {
				var TAId = gup("textid", e.target.href);
				if ( $("#" + TAId).hasClass('markItUpEditor') ) {
					$("#" + TAId).markItUpRemove();
				} else {
					if(markitupScriptsLoaded===false) {
						waitingTA = TAId;
						sendAjax('void-markitup');
					} else {
						$("#" + TAId).markItUp(mySettings);
					}
				}
				e.preventDefault();
			});
}

function switchOpen() { setListeners('switchOpen', 'click', function(evt){ $('#'+this.rel).toggleClass('hidden'); } ); };

/**
 *main init
 **/
function publicin() {
	$("#loginInput").focus();
}
function userin() {
	$(".opacity").bind('mouseenter',function(){ $(this).fadeTo("fast",1); });
	$(".opacity").bind('mouseleave',function(){ $(this).fadeTo("fast",0.2); });
	
	changeInit();
  // ---ajax textarea / tools
	addTASwitch();
	draftSetEventListeners();
	// ---message page
	$("#prokoho").change(avatarfrominput);
	$("#recipientcombo").change( function(evt) {
		var str = "";
		$("#recipientcombo option:selected").each( function() { str += $(this).text() + " "; });
		$("#prokoho").attr("value", str);
		$("#recipientcombo").attr("selectedIndex", 0);
	});
	//galery edit	
	fotoTotal = $("#fotoTotal").text();
	$('#fotoList').each(function() { if(fotoTotal > 0) { galeryLoadThumb(); } });
} 
$(document).ready( function() {
//---set default listerens - all links with fajaxa class - has to have in href get param m=Module-Function and d= key:val;key:val
fajaxa();
fconfirm();

$("#errormsgJS").css('display','block');
$("#errormsgJS").hide();
		switchOpen();
		setListeners('popupLink', 'click', function(evt) { openPopup(this.href); evt.preventDefault(); });
		// ---fuvatar
		$('.fuvatarswf').each(
				function() {
					var elmInst = $(this);
					var elmImgInst = $("#"
							+ elmInst.id.replace('fuplay', 'fuimg'));
					var width = gup('w', elmImgInst.attr('src'));
					var height = gup('h', elmImgInst.attr('src'));
					swfobject.embedSWF("/fuvatar/fuplay.swf", elmInst.id,
							width, height, "9.0.115", "expressInstall.swf", {
								u : elmInst.attr('id').replace('fuplay', ''),
								time : gup('t', elmImgInst.src)
							}, {
								allowFullScreen : "true"
							});
				});
		// ---round corners
		//DD_roundies.addRule('.radcon', 5);
		
	});

var fotoTotal = 0;
var fotoLoaded = 0;
var itemsNewList =  [];
var itemsUpdatedList = [];
var galeryCheckRunning = false;

function galeryRefresh(itemsNew,itemsUpdated,total) {
	fotoTotal = parseInt( total );
	$("#fotoTotal").text(total);
	
	var itemsNewArr=[],itemsUpdatedArr=[];
	if(itemsNew.length>0) itemsNewArr = itemsNew.split(',');
	if(itemsUpdated.length>0) itemsUpdatedArr = itemsUpdated.split(',');
	while(itemsNewArr.length>0) {
		itemsNewList.push(itemsNewArr.shift());
	}
	while(itemsUpdatedArr.length>0) { 
		itemsUpdatedList.push(itemsUpdatedArr.shift());
	}
	if(!galeryCheckRunning) galeryCheck();
}

function galeryCheck() {
	if(itemsUpdatedList.length>0) {
		galeryLoadThumb(itemsUpdatedList.shift(),'U');
	} else if(itemsNewList.length>0) {
	  galeryLoadThumb(itemsNewList.shift(),'N');
	} else if(fotoLoaded < fotoTotal) {
		galeryLoadThumb();
	}
}

function galeryLoadThumb(item,type) {
	var destSet=false;
	galeryCheckRunning = true;
	if(item > 0) {
		addXMLRequest('item', item);
		if(type=='U') {
			addXMLRequest('result', 'foto-'+item);
			addXMLRequest('resultProperty', '$replaceWith');
			destSet=true;
		}
	} else {
		addXMLRequest('total', fotoTotal);
		addXMLRequest('seq', fotoLoaded);
	}
	if(destSet===false) {
		addXMLRequest('result', 'fotoList');
		addXMLRequest('resultProperty', '$append');
	}
	addXMLRequest('call', 'initSlimbox');
	addXMLRequest('call', 'fajaxform');
	addXMLRequest('call', 'datePickerInit');
	addXMLRequest('call', 'bindDeleteFoto');
	fotoLoaded++;
	if(fotoLoaded < fotoTotal) {
		addXMLRequest('call', 'galeryCheck');
	} else {
	  galeryCheckRunning = false;
	}
	sendAjax('galery-editThumb',gup('k',$(".fajaxform").attr('action')));
};

//---drafting ----------------------------
// ---textarea drafting
var arrDraft = [],draftTimer = 3000; //arrDraft[0-id, 1-lastlength, 2-timeout]

// save function is called
function draftSave() {
	var x, arrDraftLength = arrDraft.length;
	for (x = 0; x < arrDraftLength; x++) {
		taText = $.trim($("#" + arrDraft[x][0]).attr('value'));
		if (taText.length != arrDraft[x][1] && taText.length > 0) {
			addXMLRequest('place', $("#" + arrDraft[x][0]).attr("id"));
			addXMLRequest('text', taText);
			addXMLRequest('call', 'draftSaved;' + $("#" + arrDraft[x][0]).attr("id"));
			sendAjax('draft-save');
			arrDraft[x][1] = taText.length;
      arrDraft[x][2] = 0; 
		}
	}
};

// set class - is saved - green - callback function from xajax
function draftSaved(textareaId) {
	$("#" + textareaId).removeClass('draftNotSave');
	$("#" + textareaId).addClass('draftSave');
};

function TAlength(TAid) {
	return $.trim($('#'+TAid).attr('value')).length;
};

// register ib keyup
function draftEventHandler() { 
	var TAid = $(this).attr('id'); 
	unusedDraft(TAid); 
	var x, add = 1, arrDraftLength = arrDraft.length, TAindex;
	for (x = 0; x < arrDraftLength; x++) {
		if (arrDraft[x][0] == TAid) { add = 0; TAindex=x; break; }
	}
	if (add == 1) {
		arrDraft.push( [ TAid, TAlength(TAid), 0 ]);
		TAindex = arrDraftLength;
	}
  if (arrDraft[TAindex][1] != TAlength(TAid)) {
		$("#" + TAid).removeClass('draftSave');
		$("#" + TAid).addClass('draftNotSave');
	}
	if(arrDraft[TAindex][2]) clearTimeout(arrDraft[TAindex][2]); 
	arrDraft[TAindex][2]=setTimeout(draftSave,draftTimer); 
};
// ---end-drafting------------------------

//---popup opening
function openPopup(href) { window.open(href, 'fpopup', 'scrollbars=' + gup("scrollbars", href) + ',toolbar=' + gup("toolbar", href) + ',menubar=' + gup("menubar", href) + ',status=' + gup("status", href) + ',resizable=' + gup("resizable", href) + ',width=' + gup("width", href) + ',height=' + gup("height", href) + ''); };

// ---util funcions
function setListeners(className, eventName, functionDefinition) { $("." + className).unbind(eventName, functionDefinition); $("." + className).bind(eventName, functionDefinition); };
//---extract parameter from url
function gup(name, url) { name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]"); var regex = new RegExp("[\\?&|]" + name + "=([^&#|]*)"), results = regex.exec(url); return (results === null) ? (0) : (results[1]); };

//---print message
function msg(type, text) { $("#"+type+"msgJS").html( text ); $("#"+type+"msgJS").show('slow'); setTimeout(function(){ $("#"+type+"msgJS").hide('slow'); },5000) };

// ---send and process ajax request - if problems with %26 use encodeURIComponent
function sendAjax(action,k) {
	var data = getXMLRequest();
	if(!k) k = gup('k',document.location);
	$.ajaxSetup({ 
        scriptCharset: "utf-8" , 
        contentType: "application/x-www-form-urlencoded; charset=utf-8"
	});
	$.ajax( {
		type : "POST",
		url : "index.php",
		dataType : 'xml',
		dataProcess : false,
		cache : false,
		data : "m=" + action + "-x"+((k)?("&k="+k):(''))+"&d=" + encodeURIComponent(data),
		complete : function(data) {
			$(data.responseXML).find("Item").each(
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
							            setTimeout(arguments.callee, 20);
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

//---redirect
function redirect(dir) { window.location.replace(dir); };

//---build xml request
var xmlArray = [];
var xmlStr = '<Item name="{KEY}"><![CDATA[{DATA}]]></Item>';
function resetXMLRequest() { xmlArray = []; };
function addXMLRequest(key, value) { var str = xmlStr; str = str.replace('{KEY}', key); str = str.replace('{DATA}', value); xmlArray.push(str); };
function getXMLRequest() { var str = '<FXajax><Request>' + xmlArray.join('') + '</Request></FXajax>'; resetXMLRequest(); return str; };

var call = function(){ return true; };
var createEl = function(type,attr) { var el = document.createElement(type); $.each(attr,function(key){ if(typeof(attr[key])!='undefined') el.setAttribute(key, attr[key]); }); return el; };