// degrees, mins, secs to decimal degrees
// 5 10 30W
function mapSelectorPositionCheckFormat(position)
{
	position = jQuery.trim(position);
	var dir = position.charAt(position.length-1).toUpperCase(); 
	if(dir=='W' || dir=='E' || dir=='N' || dir=='S') {
		var posArr = position.substr(0,position.length-2).split(' ');
		var d = posArr[0]-0;
		var m = posArr.length>1 ? posArr[1]-0 : 0;
		var s = posArr.length>2 ? posArr[2]-0 : 0;
		var sign = ( dir=='W' || dir=='S' ) ? -1 : 1;
		return (((s/60+m)/60)+d)*sign;
	}
	return position-0;
}

function mapSelectorProcessInput(val) {
	var result = [];
	if(val.length>0) {
		arr = val.split("\n");
		for(i=0;i<arr.length;i++) {
			arr[i] = arr[i].split(','); 
			if(arr[i].length==2) {
				arr[i][0] = mapSelectorPositionCheckFormat(arr[i][0]);
				arr[i][1] = mapSelectorPositionCheckFormat(arr[i][1]);
				if(arr[i][0]==0 && arr[i][1]==0) arr[i] = false;
			} else {
				arr[i] = false;
			}
		}
		for(i=0;i<arr.length;i++) {
			if(arr[i]!==false) result.push(arr[i]);
		}
	}
	return result;
}

function mapSelectorCreateMarks(arr) {
	if (journey === false) {
		if(marker) marker.setMap(null);
		marker = new google.maps.Marker( {
			position : new google.maps.LatLng(arr[0][0], arr[0][1]),
			map : map,
			title : "Current position"
		});
	} else {
		if(journeyPath) journeyPath.setMap(null);
		var path = [];
		for (i = 0; i < arr.length; i++) {
			path.push(new google.maps.LatLng(arr[i][0], arr[i][1]));
		}
		journeyPath = new google.maps.Polyline( {
			map : map,
			path : [ path ],
			strokeColor : "#ff0000",
			strokeOpacity : 1.0,
			strokeWeight : 2,
			geodesic : true
		});
	}
}


function initJourneySelector() {
	setListeners('journeySelector','click',journeySelectorCreate);
	setListeners('journeySelector','change',mapSelectorUpdate);
	
}
function initPositionSelector() {
	setListeners('positionSelector','click',mapSelectorCreate);
	setListeners('positionSelector','change',mapSelectorUpdate);
}

var mapElTarget;
var marker;
var journeyPath;
var mapBox;
var map;
var journey = false;

function journeySelectorCreate() {
	journey = true;
	mapElTarget = this;
	mapCreate();
}
function mapSelectorCreate() {
	journey = false;
	mapElTarget = this;
	mapCreate();
}

function mapCreate() {
	var i = 0
	var position = $(mapElTarget).position();
	var left = position.left + $(mapElTarget).outerWidth();
	var top = position.top;
	var sw = [90,180]; 
	var ne = [-90,-180];

	var wpArr = mapSelectorProcessInput($(mapElTarget).val());
	if (wpArr.length > 0) {
		// set bounds and auto zoom
		for(i=0;i<wpArr.length;i++) {
		    if(wpArr[i][0] < sw[0]) sw[0] = wpArr[i][0];
		    if(wpArr[i][0] > ne[0]) ne[0] = wpArr[i][0];
		    if(wpArr[i][1] < sw[1]) sw[1] = wpArr[i][1];
		    if(wpArr[i][1] > ne[1]) ne[1] = wpArr[i][1];
		 }
	} else {
		// set some default position and zoom
	}

	if (!mapBox) {
		$("body").append('<div id="mapsel" class="mapselector"></div>');
		mapBox = $("#mapsel");
		$(document).bind('click', mapSelectorRemove);
	}
	$(mapBox).css( {
		position : "absolute",
		marginLeft : 0,
		marginTop : 0,
		top : top,
		left : left
	});

	map = new google.maps.Map(document.getElementById('mapsel'), { mapTypeId : google.maps.MapTypeId.TERRAIN });
	if(wpArr.length>0) map.setCenter(new google.maps.LatLng(wpArr[0][0], wpArr[0][1]))
	map.fitBounds(new google.maps.LatLngBounds(new google.maps.LatLng(sw[0],sw[1]), new google.maps.LatLng(ne[0],ne[1])));
	if(wpArr.length > 0) {
		mapSelectorCreateMarks(wpArr);
	}
	google.maps.event.addListener(map, 'click', function(event) {
		$(mapElTarget).val(
				(journey === true ? ($(mapElTarget).val().length > 0 ? $(
						mapElTarget).val()
						+ "\n" : '') : '')
						+ event.latLng.toUrlValue(4));
		mapSelectorUpdate();
	});

}

function mapSelectorUpdate() {
	if(journey===true) {
		$(mapElTarget).keydown();
	}
	if (mapElTarget) {
		if (mapBox) {
			mapSelectorCreateMarks(mapSelectorProcessInput($(mapElTarget).val()));

		}
	}
}

function mapSelectorRemove(event) {
	if (event.target != mapElTarget && !event.target != mapBox && !$(event.target).parents().is('#mapsel')) {
		$(document).unbind('click', mapSelectorRemove);
		$(mapBox).remove();
		mapBox = null;
	}
}


function friendRequestInit() { $('#friendrequest').show('slow'); fajaxform(); $('#cancel-request').bind('click',function(event){remove('friendrequest');event.preventDefault()}); }
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

function fuupInit() { $(".fuup").each(function(i){ swfobject.embedSWF(ASSETS_URL+"load.swf", $(this).attr('id'), "120", "25", "10.0.12", ASSETS_URL+"expressInstall.swf", {file:ASSETS_URL+"Fuup.swf",config:"files.php?k="+gup('k',$(".fajaxform").attr('action'))+"|f=cnf|c="+$(this).attr('id').replace(/D/g,".").replace(/S/g,'/'),containerId:$(this).attr('id')},{wmode:'transparent',allowscriptaccess:'always'}); }); }



function fajaxa(event) { setListeners('fajaxa', 'click', fajaxaSend); };
function fajaxaSend(event) {
	if($(this).hasClass('hash')) document.location.hash = gup('m',this.href)+'/'+gup('d',this.href);
	if($(this).hasClass('showBusy')) $(".showProgress").attr('src',ASSETS_URL+'loading.gif');
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
	if (!/android|iphone|ipod|series60|symbian|windows ce|blackberry/i.test(navigator.userAgent)) {
		$("a[rel^='lightbox']").slimbox({overlayFadeDuration : 100, resizeDuration : 100, imageFadeDuration : 100, captionAnimationDuration : 100}, null, function(el) { return (this == el) || ((this.rel.length > 8) && (this.rel == el.rel)); });
	}
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

var waitingTA = null;
var markitupScriptsLoaded = false;
function markItUpInit() { 
setTimeout("markItUpInitLater()",250); 
};
function markItUpInitLater() { 
var textid='.markitup'; 
if(waitingTA) { textid = '#'+waitingTA; waitingTA = null; } 
$(textid).markItUp(mySettings); 
markitupScriptsLoaded = true; 
};

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
					if(waitingTA===null) {
						if(markitupScriptsLoaded===false) {
							waitingTA = TAId;
							sendAjax('void-markitup');
						} else {
							$("#" + TAId).markItUp(mySettings);
						}
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
	$("textarea[class*=expand]").autogrow();
	$("textarea[class*=expand]").keydown();
}

function userin() {
	$(".opacity").bind('mouseenter',function(){ $(this).fadeTo("fast",1); });
	$(".opacity").bind('mouseleave',function(){ $(this).fadeTo("fast",0.2); });
	initJourneySelector();
	initPositionSelector();
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
	$(window).resize(onResize);
	onResize();
});

var resizeTimeout;
function onResize() {
	if(resizeTimeout) clearTimeout( resizeTimeout );
	resizeTimeout = setTimeout(sendClientInfo,500);
}

function sendClientInfo() {
	addXMLRequest('view-width', $(window).width());
	addXMLRequest('view-height', $(window).height());
	sendAjax('user-clientInfo');
}


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

//---redirect
function redirect(dir) { 
	window.location.replace(dir);
};

//---build xml request
var xmlArray = [];
var xmlStr = '<Item name="{KEY}"><![CDATA[{DATA}]]></Item>';
function resetXMLRequest() { xmlArray = []; };
function addXMLRequest(key, value) { var str = xmlStr; str = str.replace('{KEY}', key); str = str.replace('{DATA}', value); xmlArray.push(str); };
function getXMLRequest() { var str = '<FXajax><Request>' + xmlArray.join('') + '</Request></FXajax>'; resetXMLRequest(); return str; };

var call = function(){ return true; };
var createEl = function(type,attr) { var el = document.createElement(type); $.each(attr,function(key){ if(typeof(attr[key])!='undefined') el.setAttribute(key, attr[key]); }); return el; };

(function($) {
	$.fn.autogrow = function(options) {
		this.filter('textarea').each( function() {
			var $this = $(this), minHeight = $this.height(), lineHeight = $this.css('lineHeight');
			var shadow = $('<div></div>').css( { position : 'absolute', top : -10000, left : -10000, width : $(this).width(), fontSize : $this.css('fontSize'), fontFamily : $this.css('fontFamily'), lineHeight : $this.css('lineHeight'), resize : 'none' }).appendTo(document.body);
			var update = function() {
				var val = this.value.replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/&/g,'&amp;').replace(/\n/g, '<br/>');
				shadow.html(val);
				$(this).css('height',Math.max(shadow.height() + 20,minHeight));
			}
			$(this).change(update).keyup(update).keydown(update);
			update.apply(this);
		});
		return this;
	}
})(jQuery);

(function($){
		
		var keyString = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
		
		var uTF8Encode = function(string) {
			string = string.replace(/\x0d\x0a/g, "\x0a");
			var output = "";
			for (var n = 0; n < string.length; n++) {
				var c = string.charCodeAt(n);
				if (c < 128) {
					output += String.fromCharCode(c);
				} else if ((c > 127) && (c < 2048)) {
					output += String.fromCharCode((c >> 6) | 192);
					output += String.fromCharCode((c & 63) | 128);
				} else {
					output += String.fromCharCode((c >> 12) | 224);
					output += String.fromCharCode(((c >> 6) & 63) | 128);
					output += String.fromCharCode((c & 63) | 128);
				}
			}
			return output;
		};
		
		var uTF8Decode = function(input) {
			var string = "";
			var i = 0;
			var c = c1 = c2 = 0;
			while ( i < input.length ) {
				c = input.charCodeAt(i);
				if (c < 128) {
					string += String.fromCharCode(c);
					i++;
				} else if ((c > 191) && (c < 224)) {
					c2 = input.charCodeAt(i+1);
					string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
					i += 2;
				} else {
					c2 = input.charCodeAt(i+1);
					c3 = input.charCodeAt(i+2);
					string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
					i += 3;
				}
			}
			return string;
		}
		//$.base64Encode("I'm Persian."); // return "SSdtIFBlcnNpYW4u"
		$.extend({
			base64Encode: function(input) {
				var output = "";
				var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
				var i = 0;
				input = uTF8Encode(input);
				while (i < input.length) {
					chr1 = input.charCodeAt(i++);
					chr2 = input.charCodeAt(i++);
					chr3 = input.charCodeAt(i++);
					enc1 = chr1 >> 2;
					enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
					enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
					enc4 = chr3 & 63;
					if (isNaN(chr2)) {
						enc3 = enc4 = 64;
					} else if (isNaN(chr3)) {
						enc4 = 64;
					}
					output = output + keyString.charAt(enc1) + keyString.charAt(enc2) + keyString.charAt(enc3) + keyString.charAt(enc4);
				}
				return output;
			},
			base64Decode: function(input) {
				var output = "";
				var chr1, chr2, chr3;
				var enc1, enc2, enc3, enc4;
				var i = 0;
				input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");
				while (i < input.length) {
					enc1 = keyString.indexOf(input.charAt(i++));
					enc2 = keyString.indexOf(input.charAt(i++));
					enc3 = keyString.indexOf(input.charAt(i++));
					enc4 = keyString.indexOf(input.charAt(i++));
					chr1 = (enc1 << 2) | (enc2 >> 4);
					chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
					chr3 = ((enc3 & 3) << 6) | enc4;
					output = output + String.fromCharCode(chr1);
					if (enc3 != 64) {
						output = output + String.fromCharCode(chr2);
					}
					if (enc4 != 64) {
						output = output + String.fromCharCode(chr3);
					}
				}
				output = uTF8Decode(output);
				return output;
			}
		});
	})(jQuery);