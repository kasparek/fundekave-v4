//TODO:handle back button in galery
/**
 * GOOGLE MAPS
 */ 
var mapHoldersList=null,infoWindow=null;

function mapHolder(mapEl) {
	this.mapEl=mapEl;
	this.mapDataList=[];
	this.map = null;
	this.geocoder = new google.maps.Geocoder();
}

function mapData() {
	this.dataEl=null;
	this.title='';
	this.infoEl=null;
	this.map=null;
	this.marker=null;
	this.path=null;
	this.journey=false;
	this.distance = 0;
	this.updateMarker = function(latLng) {
		if(!this.marker) this.marker = new google.maps.Marker( {position : latLng,map : this.map,title : this.title});
		else this.marker.setPosition(latLng);
		if(this.infoEl) this.marker.html = $(this.infoEl).html();
	}
	this.resetWP = function() {
		if(this.path) {
			this.path.setPath([]);
		}
	}
	this.addWP = function(latLng) {
		if(!this.path) this.path = new google.maps.Polyline( {map : this.map,path : [],strokeColor : "#ff0000",strokeOpacity : 1.0,strokeWeight : 2,geodesic : true});
		var wpList = this.path.getPath();
		wpList.push(latLng);
		this.path.setPath(wpList);
	}
	this.updateDistance = function() {
		this.distance = 0;
		if(!this.path) return;
		var wpList = this.path.getPath();
		if(wpList.length>1) {
			for(i=1;i<wpList.length;i++) {
				this.distance += distance(wpList.getAt(i-1).lat(),wpList.getAt(i-1).lng(),wpList.getAt(i).lat(),wpList.getAt(i).lng());
			}
		}
		this.distance = Math.round(this.distance*10)/10;
	}
}

function initMapData() {
	$('.mapLarge').each(function(){
		var holder = new mapHolder(this);
		$(this).find ('.mapsData').each(function(){
			var data = new mapData();
			$(this.children).each(function(){
				switch($(this).attr("class")) {
				case "mapData":
					data.dataEl = this;
					break;
				case "mapTitle":
					data.title = $(this).attr('value');
					break;
				case "mapInfo":
					data.infoEl = this;
					break;
				};
			});
			holder.mapDataList.push(data);
		});
		if(!mapHoldersList) mapHoldersList=[];
		mapHoldersList.push(holder);
	});
}

function initMap() {
	if(!mapHoldersList) return;
	for(var k=0;k<mapHoldersList.length;k++) {
		var holder = mapHoldersList[k];
		if(!holder.map) {
			holder.map = new google.maps.Map(holder.mapEl, { mapTypeId:google.maps.MapTypeId.TERRAIN });
			holder.map.setCenter(new google.maps.LatLng(50, 0))
			holder.map.setZoom(5);
		}
		var bounds = new google.maps.LatLngBounds(),boundNum=0;
		for(var i=0;i<holder.mapDataList.length;i++) {
			var data = holder.mapDataList[i];
			data.map = holder.map;
			var wpArr = mapSelectorProcessInput($(data.dataEl).val());
			if (wpArr.length > 0) {
				var markerPos = new google.maps.LatLng(wpArr[wpArr.length-1][0], wpArr[wpArr.length-1][1]);
				data.updateMarker(markerPos);
				bounds.extend(data.marker.getPosition()); boundNum++;
			} else {
				if(data.marker) {
					data.marker.setMap(null);
					data.marker = null;
				}
			}
			if (wpArr.length > 0) {
				data.resetWP();
				for(var j=0;j<wpArr.length;j++) {
					var latLng = new google.maps.LatLng(wpArr[j][0],wpArr[j][1]);
					data.addWP(latLng);
					bounds.extend(latLng); boundNum++;
				}
				data.updateDistance();
			} else {
				if(data.path) {
					data.path.setMap(null);
					data.path = null;
				}
			}
			if(data.infoEl) { 
				data.marker.html = data.marker.html.replace('[[DISTANCE]]',data.distance);
				google.maps.event.addListener(data.marker, 'click', function(event) {if(!infoWindow) infoWindow = new google.maps.InfoWindow();infoWindow.setContent(this.html);infoWindow.open(data.map,this);});
			}
		}
		if(boundNum>0) {
			holder.map.setZoom(24); 
			setTimeout(function() { holder.map.fitBounds(bounds); })
		}
	}
};
// degrees, mins, secs to decimal degrees
// 5 10 30W
function distance(lat1,lon1,lat2,lon2) {
	var R = 3440;//NM 6371KM;
	var dLat = (lat2-lat1) * Math.PI / 180;
	var dLon = (lon2-lon1) * Math.PI / 180;
	var a = Math.sin(dLat/2) * Math.sin(dLat/2) +
		Math.cos(lat1 * Math.PI / 180 ) * Math.cos(lat2 * Math.PI / 180 ) *
		Math.sin(dLon/2) * Math.sin(dLon/2);
	var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
	var d = R * c;
	return d;
}

//possible format 20.5468,15.1568 or 20 10 30 N,15 23 40 W
function mapSelectorPositionCheckFormat(position) {
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
//f checked
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

function initJourneySelector() { setListeners('journeySelector','click',journeySelectorCreate); }
function initPositionSelector() { setListeners('positionSelector','click',mapSelectorCreate); }

function journeySelectorCreate() {
	var data;
	if(mapHoldersList) data = mapHoldersList[0].mapDataList[0];  
	else data = new mapData();
	data.journey = true;
	data.dataEl = this;
	mapEditor(data);
}
function mapSelectorCreate() {
	var data;
	if(mapHoldersList) data = mapHoldersList[0].mapDataList[0];  
	else data = new mapData();
	data.dataEl = this;
	mapEditor(data);
}

function mapEditor(data) {
	var setListener = false; // style="margin:0 3px 3px 3px;padding:0;"  style="margin:0 0 3px 0;width:100%;"
	var mapSearchHTML = '<div id="mapSearch" style="float:left;"><input id="mapaddress" value="" style="width:300px;margin-right:5px;margin-top:6px;"/><button class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only" onclick="findAddress()">Find</button></div>';
	if (!mapHoldersList) {
		$("body").append('<div id="mapEditor" style="width:100%;"></div>');
		var holder = new mapHolder(document.getElementById('mapEditor'));
		holder.mapDataList = [data];
		mapHoldersList = [holder];
		setListener = true;
	}
	
	initMap();
	
	
	$("#mapEditor").dialog({
			modal: true,
			minWidth:640,
			minHeight:200,
			width: $(window).width()*0.8, 
			height: $(window).height()*0.8,
			//resizeStop: function(event,ui){ $("#map").css('height',(ui.size.height-110)+'px'); },
			//open: function(event,ui){ $("#map").css('height',(ui.size.height-110)+'px'); },
			buttons: {
				Save: function() {
					$(this).dialog('close');
					data = mapHoldersList[0].mapDataList[0];
					$(data.dataEl).val('');
					if(data.journey===true) {
						var list=[];
						data.path.getPath().forEach(function(latLng){list.push(latLng.toUrlValue(4));});
						$(data.dataEl).val( list.join("\n") );
					} else {
						$(data.dataEl).val( data.marker.getPosition().toUrlValue(4) );
					}
				},
				Cancel: function() {
					$(this).dialog('close');
				},
				Clear: function() {
					  mapHoldersList[0].mapDataList[0].marker.setMap(null);
					  mapHoldersList[0].mapDataList[0].marker = null;
					  mapHoldersList[0].mapDataList[0].path.setMap(null);
					  mapHoldersList[0].mapDataList[0].path = null;
				},
				'Remove Last': function() {
					data = mapHoldersList[0].mapDataList[0];
					data.path.getPath().pop();
					var path = data.path.getPath();
					data.updateMarker( path.getAt(path.getLength()-1) );
				}
			}
		});
		
		$(".ui-dialog-buttonpane").prepend(mapSearchHTML);
		$("#mapaddress").keydown(addressCheckForEnter);
		
	if(setListener) {	
	google.maps.event.addListener(holder.map, 'click', function(event) {
		data = mapHoldersList[0].mapDataList[0];
		if(data.journey) { 
			data.addWP(event.latLng);
			data.updateMarker(event.latLng);
			data.updateDistance();
			if(data.distance>0) $("#mapEditor").dialog( "option", "title", data.distance+'NM' );
		} else {
			data.updateMarker(event.latLng);
		}
	});
	}
}
function addressCheckForEnter(event) {
if (event.keyCode == 13) {
	findAddress();
} 
}
function findAddress() {
  var address = {'address': document.getElementById('mapaddress').value};
  holder = mapHoldersList[0]; 
  holder.geocoder.geocode(address, function(results, status) {
    if (status == google.maps.GeocoderStatus.OK) {
    	holder = mapHoldersList[0];
    	data = mapHoldersList[0].mapDataList[0];
      holder.map.setCenter(results[0].geometry.location);
      holder.map.setZoom(19);
      data.updateMarker(results[0].geometry.location);
    }
  });
}
//---GOOGLE MAPS END

//TODO: when hit back to original and hash is "" load first image
//TODO: use base function with progress somehow - fajaxaSend
var hashOld='';
function hashchangeInit() {
	$(window).hashchange( function(){
		if(location.hash=='' && hashOld.length>0) window.location.reload();
		if(location.hash != hashOld) {
			hashOld = location.hash;
			fajaxaAction(location.hash.replace('#',''));
		} 
  });
};

/**
 * IMAGE UPLOADING TOOL HANDLERS - FUUP
 */ 
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
	addXMLRequest('call', 'fconfirmInit');
	sendAjax('page-avatar',gup('k',$(".fajaxform").attr('action')));
};
function fuupUploadEventComplete() {
	var item = $('#item').attr('value');
	if(item>0) addXMLRequest('item', item);
  addXMLRequest('result', "flyerDiv");
	addXMLRequest('resultProperty', '$html');
	addXMLRequest('call', 'slimboxInit');
	addXMLRequest('call', 'fconfirmInit');
	addXMLRequest('call', 'fajaxformInit');
	sendAjax('event-flyer',gup('k',$(".fajaxform").attr('action')));	
}
//---IMAGE UPLOADING END

/**
 *AJAX FORM SUBMIT HANDLING
 */ 
var buttonClicked = '', preventAjax = false, draftdrop = false, formSent=null;
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
//---AJAX FORM END

/**
 * AJAX LINK HANDLING
 */
function fajaxaSend(event) {
	event.preventDefault();
	var k = gup('k', this.href),id = $(this).attr("id");
	if(!k) k = 0;
	var action = gup('m',this.href)+'/'+gup('d',this.href)+'/'+k;
	if(id) { action += '/'+id; } 
	if($(event.currentTarget).hasClass('confirm')) { action += '/confirm='; }
	if($(this).hasClass('hash')) {
		document.location.hash = action;
		return;
	}
	fajaxaAction(action);
}
//action = m/d/k|0/linkElId/confirm
function fajaxaAction(action) {
	actionList = action.split('/');
	if(!actionList[4]) actionList[4] = 'void';
	var m=actionList[0],d=actionList[1],k=actionList[2],id=actionList[3],confirm=actionList[4]=='confirm'?true:false,result = false, resultProperty = false;
	if(k==0) k=null;
	
	if($(".showProgress").length>0) {
		$(".showProgress").addClass('lbLoading');
		$(".showProgress").css('height',$(".showProgress img").height()+'px');
		$(".showProgress").css("marginLeft","auto");
		$(".showProgress").css("marginRight","auto");
		$(".showProgress img").hide();
		$(".showProgress img").bind('load',onImgLoaded)
	}
	
	if(confirm && id) {
		if(!confirm($("#"+id).attr("title"))) { 
			preventAjax = true; 
			event.preventDefault();
			return;
		}
	} 
	if (preventAjax == true) { preventAjax = false; return; }
	if(d) { 
		var arr = d.split(';');
		while (arr.length > 0) {
			var rowStr = arr.shift();
			var row = rowStr.split(':');
			addXMLRequest(row[0], row[1]);
			if (row[0] == 'result') result = true;
			if (row[0] == 'resultProperty') resultProperty = true;
		}
	}
	if(id) {
		if (result == false) addXMLRequest('result', id);
		if (resultProperty == false) addXMLRequest('resultProperty', '$html');
	}
	sendAjax(m,k);
};
function onImgLoaded() {
	setTimeout(function(){$(".showProgress").css('height','auto');},500);
	$(".showProgress img").show('fast');
	$(".showProgress img").unbind('load',onImgLoaded)
	$(".showProgress").removeClass('lbLoading');
};
//---AJAX LINK END

/**
 * MARKITUP SETUP - rich textarea
 */ 
var markitupSettings = {	
	onShiftEnter:  	{keepDefault:false, replaceWith:'<br />\n'},
	onCtrlEnter:  	{keepDefault:false, openWith:'\n<p>', closeWith:'</p>'},
	onTab:    		{keepDefault:false, replaceWith:'    '},
	markupSet:  [
		{name:'Heading', key:'H', openWith:'(!(<h3>|!|<h2>)!)', closeWith:'(!(</h3>|!|</h2>)!)' }, 	
		{name:'Bold', key:'B', openWith:'(!(<strong>|!|<b>)!)', closeWith:'(!(</strong>|!|</b>)!)' },
		{name:'Italic', key:'I', openWith:'(!(<em>|!|<i>)!)', closeWith:'(!(</em>|!|</i>)!)'  },
		{name:'Stroke through', key:'S', openWith:'<del>', closeWith:'</del>' },
		{name:'Align left', openWith:'<div class="alignLeft">', closeWith:'</div>' },
		{name:'Align center', openWith:'<div class="alignCenter">', closeWith:'</div>' },
		{name:'Align right', openWith:'<div class="alignRight">', closeWith:'</div>' },
		{separator:'---------------' },
		{name:'Picture', key:'P', openWith:'<img src="', closeWith:'" />' },
		{name:'Link', key:'L', openWith:'<a href="[![Link:!:http://]!]"(!( title="[![Title]!]")!)>', closeWith:'</a>', placeHolder:'Your text to link...' },
		{separator:'---------------' },
		{name:'Clean', className:'clean', replaceWith:function(markitup) { return markitup.selection.replace(/<(.*?)>/g, "") } }
	]
}

var waitingTA = null;
function markItUpInit(textareaId) {
	if(textareaId) waitingTA=textareaId;
 	if(!getScript(JS_URL+'markitup/jquery.markitup.pack.js', markItUpInit)) return;
	if(!getCSS(JS_URL+'markitup/sets/default/style.css', markItUpInit)) return;
	if(!getCSS(JS_URL+'markitup/skins/simple/style.css', markItUpInit)) return;
	var textid='.markitup'; 
	if(waitingTA) { textid = '#'+waitingTA; waitingTA = null; } 
	$(textid).markItUp(markitupSettings); 
};

function markItUpSwitchInit() {
	$('.markitup').each( function() { $(this).before('<span class="textAreaResize"><a href="?textid='+$(this).attr('id')+'" class="toggleToolSize"></a></span>'); });
	setListeners('toggleToolSize','click',function(e) {
		var TAId = gup("textid", e.target.href);
		if ( $("#" + TAId).hasClass('markItUpEditor') ) { $("#" + TAId).markItUpRemove();
		} else { if(waitingTA===null) { markItUpInit( TAId ); } }
		e.preventDefault();
	});
}
//---MARKITUP SETUP END

/**
 * INITIALIZATION ON DOM
 */ 
//signed in users initialization
function userInit() {
	fajaxformInit();
	initJourneySelector();
	initPositionSelector();
  // ---ajax textarea / tools
	markItUpSwitchInit();
	draftInit();
	// ---message page
	$("#prokoho").change(avatarfrominput);
	//TODO:check what saction is for
	$("#saction").change( function(evt) { if($("#saction option:selected").attr('value') == 'setpp') $('#ppinput').show(); else $('#ppinput').hide(); });
	$("#recipientcombo").change( function(evt) {
		var str = "";
		$("#recipientcombo option:selected").each( function() { str += $(this).text() + " "; });
		$("#prokoho").attr("value", str);
		$("#recipientcombo").attr("selectedIndex", 0);
	});
	//galery edit	
	fotoTotal = $("#fotoTotal").text(); if(fotoTotal > 0 && $('#fotoList').length>0) galeryLoadThumb();
}
//all users initialization 
$(document).ready( function() {
	var w = $(window).width();
	if(w>800) $("#loginInput").focus();
	if ($("#sidebar").length == 0) { $('body').addClass('bodySidebarOff'); }
	$("textarea[class*=expand]").autogrow();
	$("textarea[class*=expand]").keydown();
	$(".opacity").bind('mouseenter',function(){ $(this).fadeTo("fast",1); });
	$(".opacity").bind('mouseleave',function(){ $(this).fadeTo("fast",0.2); });
	//---set default listerens - all links with fajaxa class - has to have in href get param m=Module-Function and d= key:val;key:val
	fajaxaInit();
	fconfirmInit();
	$("#errormsgJS").css('display','block');
	$("#errormsgJS").hide();
	switchOpen();
	setListeners('popupLink', 'click', function(evt) { openPopup(this.href); evt.preventDefault(); });
	$(window).resize(onResize);
	onResize();
	initMapData(); 
	initMap();
	if($(".hash").length>0) {
		hashchangeInit();
	}
	slimboxInit();
});
//datepicker init
function datePickerInit() {
if(!getScript(JS_URL+'i18n/ui.datepicker-cs.js', datePickerInit)) return;
if(!getCSS(CSS_URL+'themes/ui-lightness/jquery-ui-1.7.2.custom.css',datePickerInit)) return;
$.datepicker.setDefaults($.extend( { showMonthAfterYear : false }, $.datepicker.regional[''])); 
$(".datepicker").datepicker($.datepicker.regional['cs']); 
};
//slimbox init
function slimboxInit() { if (!/android|iphone|ipod|series60|symbian|windows ce|blackberry/i.test(navigator.userAgent)) { $("a[rel^='lightbox']").slimbox({overlayFadeDuration : 100, resizeDuration : 100, imageFadeDuration : 100, captionAnimationDuration : 100}, null, function(el) { return (this == el) || ((this.rel.length > 8) && (this.rel == el.rel)); }); } }
//fuup init
function fuupInit() { if(!getScript('http://ajax.googleapis.com/ajax/libs/swfobject/2.2/swfobject.js', fuupInit)) return; $(".fuup").each(function(i){ swfobject.embedSWF(ASSETS_URL+"load.swf", $(this).attr('id'), "120", "25", "10.0.12", ASSETS_URL+"expressInstall.swf", {file:ASSETS_URL+"Fuup.swf",config:"files.php?k="+gup('k',$(".fajaxform").attr('action'))+"|f=cnf|c="+$(this).attr('id').replace(/D/g,".").replace(/S/g,'/'),containerId:$(this).attr('id')},{wmode:'transparent',allowscriptaccess:'always'}); }); }
//tabs init
function tabsInit() { $("#tabs").tabs(); };
//request init
function friendRequestInit() { $('#friendrequest').show('slow'); fajaxformInit(); $('#cancel-request').bind('click',function(event){remove('friendrequest');event.preventDefault()}); };
//ajax form init
function fajaxformInit(event) { if($(".fajaxform").length>0) { setListeners('button', 'click', onButtonClick); setListeners('fajaxform', 'submit', fsubmit ); } };
//ajax link init
function fajaxaInit(event) { setListeners('fajaxa', 'click', fajaxaSend); };
function fconfirmInit(event) { $('.confirm').each(function(){ if(!$(this).hasClass('fajaxa')) { $(this).bind('click',onConfirm); } }); };
function onConfirm(e) { 
	if(!confirm($(e.currentTarget).attr("title"))) { 
		preventAjax = true; e.preventDefault(); 
	} };
//simple functions
function enable(id) { $('#'+id).removeAttr('disabled'); };
function remove(id,notween) { if(notween==1) { $('#'+id).remove(); }else{ $('#'+id).hide('slow',function(){$('#'+id).remove()}); } };
function switchOpen() { setListeners('switchOpen', 'click', function(evt){ $('#'+this.rel).toggleClass('hidden'); } ); };
function openPopup(href) { window.open(href, 'fpopup', 'scrollbars=' + gup("scrollbars", href) + ',toolbar=' + gup("toolbar", href) + ',menubar=' + gup("menubar", href) + ',status=' + gup("status", href) + ',resizable=' + gup("resizable", href) + ',width=' + gup("width", href) + ',height=' + gup("height", href) + ''); };
function setListeners(className, eventName, functionDefinition) { $("." + className).unbind(eventName, functionDefinition); $("." + className).bind(eventName, functionDefinition); };
function gup(name, url) { name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]"); var regex = new RegExp("[\\?&|]" + name + "=([^&#|]*)"), results = regex.exec(url); return (results === null) ? (0) : (results[1]); };
function msg(type, text) { $("#"+type+"msgJS").html( text ); $("#"+type+"msgJS").show('slow'); setTimeout(function(){ $("#"+type+"msgJS").hide('slow'); },5000) };
function redirect(dir) { window.location.replace(dir); };
//SCRIPT LOADER
var scriptsLoaded = [], scriptsTried={};
function getScript(filename,callback) { 
if(hasScript(filename)) return true;
if(!scriptsTried[filename]) scriptsTried[filename]=1; else scriptsTried[filename]++;
if(scriptsTried[filename]>3) { throw('404 - file not found - '+filename); return true;}  
loadScript(filename,callback); 
return false; 
}
function loadScript(filename,callback) { $.getScript(filename, function(){ scriptsLoaded.push(filename); if($.isFunction(callback)) setTimeout(callback,250); }); };
function hasScript(filename) { var i,ret = false, scripts = $("script"); for(i=0;i<scripts.length;i++) { if($(scripts[i]).attr('src') == filename) { ret = true; } }; for(i=0;i<scriptsLoaded.length;i++) { if(scriptsLoaded[i] == filename) { ret = true; } }; return ret; };
function getCSS(filename,callback) { if(hasCSS(filename)) return true; $.getCSS(filename,callback); return false; }
function hasCSS(filename) { var ret = false, sheets = $("link"); for(var i=0;i<sheets.length;i++) { if($(sheets[i]).attr('href') == filename) { ret = true; } }; return ret; };
//ajax simple functions
function avatarfrominput(evt) {addXMLRequest('username', $("#prokoho").attr("value"));addXMLRequest('result', "recipientavatar");addXMLRequest('resultProperty', '$html');addXMLRequest('call', 'fajaxaInit');sendAjax('post-avatarfrominput');}
/* RESIZE HANDLER - CLIENT INFO TO SERVER */ 
var resizeTimeout;
function onResize() { if(resizeTimeout) { clearTimeout( resizeTimeout ); }; resizeTimeout = setTimeout(sendClientInfo,500); };
function sendClientInfo() {	var w = $(window).width(); var h = $(window).height(); if(w!=CLIENT_WIDTH || h!=CLIENT_HEIGHT) { addXMLRequest('view-width', w); addXMLRequest('view-height', h); sendAjax('user-clientInfo'); } };
//---INITIALIZATION ON DOM END

/**
 * AJAX GALLERY EDITING THUMBNAILS LOADING AND REFRESHING
 */ 
var fotoTotal = 0, fotoLoaded = 0, itemsNewList =  [], itemsUpdatedList = [], galeryCheckRunning = false;

function galeryRefresh(itemsNew,itemsUpdated,total) {
	fotoTotal = parseInt( total );
	$("#fotoTotal").text(total);
	var itemsNewArr=[],itemsUpdatedArr=[];
	if(itemsNew.length>0) itemsNewArr = itemsNew.split(',');
	if(itemsUpdated.length>0) itemsUpdatedArr = itemsUpdated.split(',');
	while(itemsNewArr.length>0) { itemsNewList.push(itemsNewArr.shift()); }
	while(itemsUpdatedArr.length>0) { itemsUpdatedList.push(itemsUpdatedArr.shift()); }
	if(!galeryCheckRunning) galeryCheck();
}

function galeryCheck() {
	if(itemsUpdatedList.length>0) {	galeryLoadThumb(itemsUpdatedList.shift(),'U');
	} else if(itemsNewList.length>0) { galeryLoadThumb(itemsNewList.shift(),'N'); 
	} else if(fotoLoaded < fotoTotal) {	galeryLoadThumb(); }
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
	addXMLRequest('call', 'slimboxInit');
	addXMLRequest('call', 'fajaxformInit');
	addXMLRequest('call', 'datePickerInit');
	addXMLRequest('call', 'bindDeleteFoto');
	addXMLRequest('call', 'initPositionSelector');
	addXMLRequest('call', 'initJourneySelector');
	fotoLoaded++;
	if(fotoLoaded < fotoTotal) {
		addXMLRequest('call', 'galeryCheck');
	} else {
	  galeryCheckRunning = false;
	}
	sendAjax('galery-editThumb',gup('k',$(".fajaxform").attr('action')));
};

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
	event.preventDefault();preventAjax = true;
};
//---AJAX GALLERY EDITING END

/**
 * DRAFT - temporary textarea data storing
 **/
// ---textarea drafting
var arrDraft = [], TAOriginalArr = [],draftTimer = 3000; //arrDraft[0-id, 1-lastlength, 2-timeout]
//on server draft::check 
function draftableSaveTA(id) { 
	TAOriginalArr.push( [ id, $('#'+id).attr('value') ] ); 
}
//flush all saved data
function draftDropAll() {
$('.draftable').each( function() {
	$('#draftdrop'+$(this).attr('id')).remove();
 	addXMLRequest('result', $(this).attr('id'));
	sendAjax('draft-drop');
});
}
//on of textarea change handlers - once data changed and textarea has some data saved before old data are flushed
function unusedDraft(TAid) {
	$('#draftdrop'+TAid).each( function() {
	  addXMLRequest('result', TAid);
		sendAjax('draft-drop');
		$(this).remove();
	});
}
//click handler to flush data for one textarea
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
function draftInit(TAid) {
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
//ajax save function is called
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
//check ta length
function TAlength(TAid) { return $.trim($('#'+TAid).attr('value')).length; };
// set class - is saved - green - callback function from xajax
function draftSaved(textareaId) { $("#" + textareaId).removeClass('draftNotSave'); $("#" + textareaId).addClass('draftSave'); };
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
//---DRAFT END

/**
 * CUSTOM AJAX REQUEST BUILDER/HANDLER
 */  
// ---send and process ajax request - if problems with %26 use encodeURIComponent
function sendAjax(action,k) {
	var data = getXMLRequest();
	if(k==0) k=null;
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
		processData : false,
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
							var part0, callback = null;
							var arr = item.text().split(';');
							part0 = arr[0];
							if(arr[1]) callback = arr[1];
						switch (item.attr('property')) {
							case 'css':
								$.getCSS(part0, callback);
                break;
							case 'getScript':
								getScript(part0,callback);
								break;
							case 'callback':
								command = part0 + "( data.responseText );";
								break;
							case 'call':
								var par = '';if (arr.length > 1) {arr.splice(0,1);par = arr.join("','");}
								command = part0 + "('" + par + "');";
								break;
							case 'void':
									//just debug message
								break;
							case 'body':
								$("body").append( part0 );
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
//---build xml request
var xmlArray = [], xmlStr = '<Item name="{KEY}"><![CDATA[{DATA}]]></Item>';
function resetXMLRequest() { xmlArray = []; };
function addXMLRequest(key, value) { var str = xmlStr; str = str.replace('{KEY}', key); str = str.replace('{DATA}', value); xmlArray.push(str); };
function getXMLRequest() { var str = '<FXajax><Request>' + xmlArray.join('') + '</Request></FXajax>'; resetXMLRequest(); return str; };
//--- CUSTOM AJAX REQUEST BUILDER/HANDLER END

/* jQuery.getCSS plugin http://github.com/furf/jquery-getCSS Copyright 2010, Dave Furfero Dual licensed under the MIT or GPL Version 2 licenses. 
$.getCSS('http://sexyjs.com/css/sexy.css', function () {
  $('#description').show();
});
*/
(function(e){var c=document.getElementsByTagName("head")[0],a=/loaded|complete/,d={},b=0,f;e.getCSS=function(h,g,j){if(e.isFunction(g)){j=g;g={};}var i=document.createElement("link");i.rel="stylesheet";i.type="text/css";i.media=g.media||"screen";i.href=h;if(g.charset){i.charset=g.charset;}if(g.title){j=(function(k){return function(){i.title=g.title;k();};})(j);}if(i.readyState){i.onreadystatechange=function(){if(a.test(i.readyState)){i.onreadystatechange=null;j();}};}else{if(i.onload===null&&i.all){i.onload=function(){i.onload=null;j();};}else{d[i.href]=function(){j();};if(!b++){f=setInterval(function(){var p,m,o=document.styleSheets,k,l=o.length;while(l--){m=o[l];if((k=m.href)&&(p=d[k])){try{p.r=m.cssRules;throw"SECURITY";}catch(n){if(/SECURITY/.test(n)){p();delete d[k];if(!--b){f=clearInterval(f);}}}}}},13);}}}c.appendChild(i);};})(jQuery);
/* formToArray */ 
;(function($){$.fn.formToArray=function(semantic){var a=[];if(this.length==0)return a;var form=this[0];var els=semantic?form.getElementsByTagName('*'):form.elements;if(!els)return a;for(var i=0,max=els.length;i<max;i++){var el=els[i];var n=el.name;if(!n)continue;if(semantic&&form.clk&&el.type=="image"){if(!el.disabled&&form.clk==el){a.push({name:n,value:$(el).val()});a.push({name:n+'.x',value:form.clk_x},{name:n+'.y',value:form.clk_y})}continue}var v=$.fieldValue(el,true);if(v&&v.constructor==Array){for(var j=0,jmax=v.length;j<jmax;j++)a.push({name:n,value:v[j]})}else if(v!==null&&typeof v!='undefined')a.push({name:n,value:v})}if(!semantic&&form.clk){var$input=$(form.clk),input=$input[0],n=input.name;if(n&&!input.disabled&&input.type=='image'){a.push({name:n,value:$input.val()});a.push({name:n+'.x',value:form.clk_x},{name:n+'.y',value:form.clk_y})}}return a};$.fn.fieldValue=function(successful){for(var val=[],i=0,max=this.length;i<max;i++){var el=this[i];var v=$.fieldValue(el,successful);if(v===null||typeof v=='undefined'||(v.constructor==Array&&!v.length))continue;v.constructor==Array?$.merge(val,v):val.push(v)}return val};$.fieldValue=function(el,successful){var n=el.name,t=el.type,tag=el.tagName.toLowerCase();if(typeof successful=='undefined')successful=true;if(successful&&(!n||el.disabled||t=='reset'||t=='button'||(t=='checkbox'||t=='radio')&&!el.checked||(t=='submit'||t=='image')&&el.form&&el.form.clk!=el||tag=='select'&&el.selectedIndex==-1))return null;if(tag=='select'){var index=el.selectedIndex;if(index<0)return null;var a=[],ops=el.options;var one=(t=='select-one');var max=(one?index+1:ops.length);for(var i=(one?index:0);i<max;i++){var op=ops[i];if(op.selected){var v=op.value;if(!v)v=(op.attributes&&op.attributes['value']&&!(op.attributes['value'].specified))?op.text:op.value;if(one)return v;a.push(v)}}return a}return el.value};$.fn.clearForm=function(){return this.each(function(){$('input,select,textarea',this).clearFields()})};$.fn.clearFields=$.fn.clearInputs=function(){return this.each(function(){var t=this.type,tag=this.tagName.toLowerCase();if(t=='text'||t=='password'||tag=='textarea')this.value='';else if(t=='checkbox'||t=='radio')this.checked=false;else if(tag=='select')this.selectedIndex=-1})};$.fn.resetForm=function(){return this.each(function(){if(typeof this.reset=='function'||(typeof this.reset=='object'&&!this.reset.nodeType))this.reset()})};$.fn.enable=function(b){if(b==undefined)b=true;return this.each(function(){this.disabled=!b})};$.fn.selected=function(select){if(select==undefined)select=true;return this.each(function(){var t=this.type;if(t=='checkbox'||t=='radio')this.checked=select;else if(this.tagName.toLowerCase()=='option'){var$sel=$(this).parent('select');if(select&&$sel[0]&&$sel[0].type=='select-one'){$sel.find('option').selected(false)}this.selected=select}})}})(jQuery);
/* autogrow */ 
(function($){$.fn.autogrow=function(e){this.filter('textarea').each(function(){var b=$(this),minHeight=b.height(),lineHeight=b.css('lineHeight');var c=$('<div></div>').css({position:'absolute',top:-10000,left:-10000,width:$(this).width(),fontSize:b.css('fontSize'),fontFamily:b.css('fontFamily'),lineHeight:b.css('lineHeight'),resize:'none'}).appendTo(document.body);var d=function(){var a=this.value.replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/&/g,'&amp;').replace(/\n/g,'<br/>');c.html(a);$(this).css('height',Math.max(c.height()+20,minHeight))};$(this).change(d).keyup(d).keydown(d);d.apply(this)});return this}})(jQuery);
/* base64Encode/base64Decode //$.base64Encode("I'm Persian."); // return "SSdtIFBlcnNpYW4u" */
;(function($){var e="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";var f=function(a){a=a.replace(/\x0d\x0a/g,"\x0a");var b="";for(var n=0;n<a.length;n++){var c=a.charCodeAt(n);if(c<128){b+=String.fromCharCode(c)}else if((c>127)&&(c<2048)){b+=String.fromCharCode((c>>6)|192);b+=String.fromCharCode((c&63)|128)}else{b+=String.fromCharCode((c>>12)|224);b+=String.fromCharCode(((c>>6)&63)|128);b+=String.fromCharCode((c&63)|128)}}return b};var g=function(a){var b="";var i=0;var c=c1=c2=0;while(i<a.length){c=a.charCodeAt(i);if(c<128){b+=String.fromCharCode(c);i++}else if((c>191)&&(c<224)){c2=a.charCodeAt(i+1);b+=String.fromCharCode(((c&31)<<6)|(c2&63));i+=2}else{c2=a.charCodeAt(i+1);c3=a.charCodeAt(i+2);b+=String.fromCharCode(((c&15)<<12)|((c2&63)<<6)|(c3&63));i+=3}}return b};$.extend({base64Encode:function(a){var b="";var c,chr2,chr3,enc1,enc2,enc3,enc4;var i=0;a=f(a);while(i<a.length){c=a.charCodeAt(i++);chr2=a.charCodeAt(i++);chr3=a.charCodeAt(i++);enc1=c>>2;enc2=((c&3)<<4)|(chr2>>4);enc3=((chr2&15)<<2)|(chr3>>6);enc4=chr3&63;if(isNaN(chr2)){enc3=enc4=64}else if(isNaN(chr3)){enc4=64}b=b+e.charAt(enc1)+e.charAt(enc2)+e.charAt(enc3)+e.charAt(enc4)}return b},base64Decode:function(a){var b="";var c,chr2,chr3;var d,enc2,enc3,enc4;var i=0;a=a.replace(/[^A-Za-z0-9\+\/\=]/g,"");while(i<a.length){d=e.indexOf(a.charAt(i++));enc2=e.indexOf(a.charAt(i++));enc3=e.indexOf(a.charAt(i++));enc4=e.indexOf(a.charAt(i++));c=(d<<2)|(enc2>>4);chr2=((enc2&15)<<4)|(enc3>>2);chr3=((enc3&3)<<6)|enc4;b=b+String.fromCharCode(c);if(enc3!=64){b=b+String.fromCharCode(chr2)}if(enc4!=64){b=b+String.fromCharCode(chr3)}}b=g(b);return b}})})(jQuery);
/* jQuery hashchange event - v1.3 - 7/21/2010 http://benalman.com/projects/jquery-hashchange-plugin/ Copyright (c) 2010 "Cowboy" Ben Alman, Dual licensed under the MIT and GPL licenses. http://benalman.com/about/license/ */
;(function($,e,b){var c="hashchange",h=document,f,g=$.event.special,i=h.documentMode,d="on"+c in e&&(i===b||i>7);function a(j){j=j||location.href;return"#"+j.replace(/^[^#]*#?(.*)$/,"$1")}$.fn[c]=function(j){return j?this.bind(c,j):this.trigger(c)};$.fn[c].delay=50;g[c]=$.extend(g[c],{setup:function(){if(d){return false}$(f.start)},teardown:function(){if(d){return false}$(f.stop)}});f=(function(){var j={},p,m=a(),k=function(q){return q},l=k,o=k;j.start=function(){p||n()};j.stop=function(){p&&clearTimeout(p);p=b};function n(){var r=a(),q=o(m);if(r!==m){l(m=r,q);$(e).trigger(c)}else{if(q!==m){location.href=location.href.replace(/#.*/,"")+q}}p=setTimeout(n,$.fn[c].delay)}$.browser.msie&&!d&&(function(){var q,r;j.start=function(){if(!q){r=$.fn[c].src;r=r&&r+a();q=$('<iframe tabindex="-1" title="empty"/>').hide().one("load",function(){r||l(a());n()}).attr("src",r||"javascript:0").insertAfter("body")[0].contentWindow;h.onpropertychange=function(){try{if(event.propertyName==="title"){q.document.title=h.title}}catch(s){}}}};j.stop=k;o=function(){return a(q.location.href)};l=function(v,s){var u=q.document,t=$.fn[c].domain;if(v!==s){u.title=h.title;u.open();t&&u.write('<script>document.domain="'+t+'"<\/script>');u.close();q.location.hash=v}}})();return j})()})(jQuery,this);
/* Slimbox v2.04 (c) 2007-2010 Christophe Beyls <http://www.digitalia.be> MIT-style license.*/
;(function(w){var E=w(window),u,f,F=-1,n,x,D,v,y,L,r,m=!window.XMLHttpRequest,s=[],l=document.documentElement,k={},t=new Image(),J=new Image(),H,a,g,p,I,d,G,c,A,K;w(function(){w("body").append(w([H=w('<div id="lbOverlay" />')[0],a=w('<div id="lbCenter" />')[0],G=w('<div id="lbBottomContainer" />')[0]]).css("display","none"));g=w('<div id="lbImage" />').appendTo(a).append(p=w('<div style="position: relative;" />').append([I=w('<a id="lbPrevLink" href="#" />').click(B)[0],d=w('<a id="lbNextLink" href="#" />').click(e)[0]])[0])[0];c=w('<div id="lbBottom" />').appendTo(G).append([w('<a id="lbCloseLink" href="#" />').add(H).click(C)[0],A=w('<div id="lbCaption" />')[0],K=w('<div id="lbNumber" />')[0],w('<div style="clear: both;" />')[0]])[0]});w.slimbox=function(O,N,M){u=w.extend({loop:false,overlayOpacity:0.8,overlayFadeDuration:400,resizeDuration:400,resizeEasing:"swing",initialWidth:250,initialHeight:250,imageFadeDuration:400,captionAnimationDuration:400,counterText:"Image {x} of {y}",closeKeys:[27,88,67],previousKeys:[37,80],nextKeys:[39,78]},M);if(typeof O=="string"){O=[[O,N]];N=0}y=E.scrollTop()+(E.height()/2);L=u.initialWidth;r=u.initialHeight;w(a).css({top:Math.max(0,y-(r/2)),width:L,height:r,marginLeft:-L/2}).show();v=m||(H.currentStyle&&(H.currentStyle.position!="fixed"));if(v){H.style.position="absolute"}w(H).css("opacity",u.overlayOpacity).fadeIn(u.overlayFadeDuration);z();j(1);f=O;u.loop=u.loop&&(f.length>1);return b(N)};w.fn.slimbox=function(M,P,O){P=P||function(Q){return[Q.href,Q.title]};O=O||function(){return true};var N=this;return N.unbind("click").click(function(){var S=this,U=0,T,Q=0,R;T=w.grep(N,function(W,V){return O.call(S,W,V)});for(R=T.length;Q<R;++Q){if(T[Q]==S){U=Q}T[Q]=P(T[Q],Q)}return w.slimbox(T,U,M)})};function z(){var N=E.scrollLeft(),M=E.width();w([a,G]).css("left",N+(M/2));if(v){w(H).css({left:N,top:E.scrollTop(),width:M,height:E.height()})}}function j(M){if(M){w("object").add(m?"select":"embed").each(function(O,P){s[O]=[P,P.style.visibility];P.style.visibility="hidden"})}else{w.each(s,function(O,P){P[0].style.visibility=P[1]});s=[]}var N=M?"bind":"unbind";E[N]("scroll resize",z);w(document)[N]("keydown",o)}function o(O){var N=O.keyCode,M=w.inArray;return(M(N,u.closeKeys)>=0)?C():(M(N,u.nextKeys)>=0)?e():(M(N,u.previousKeys)>=0)?B():false}function B(){return b(x)}function e(){return b(D)}function b(M){if(M>=0){F=M;n=f[F][0];x=(F||(u.loop?f.length:0))-1;D=((F+1)%f.length)||(u.loop?0:-1);q();a.className="lbLoading";k=new Image();k.onload=i;k.src=n}return false}function i(){a.className="";w(g).css({backgroundImage:"url("+n+")",visibility:"hidden",display:""});w(p).width(k.width);w([p,I,d]).height(k.height);w(A).html(f[F][1]||"");w(K).html((((f.length>1)&&u.counterText)||"").replace(/{x}/,F+1).replace(/{y}/,f.length));if(x>=0){t.src=f[x][0]}if(D>=0){J.src=f[D][0]}L=g.offsetWidth;r=g.offsetHeight;var M=Math.max(0,y-(r/2));if(a.offsetHeight!=r){w(a).animate({height:r,top:M},u.resizeDuration,u.resizeEasing)}if(a.offsetWidth!=L){w(a).animate({width:L,marginLeft:-L/2},u.resizeDuration,u.resizeEasing)}w(a).queue(function(){w(G).css({width:L,top:M+r,marginLeft:-L/2,visibility:"hidden",display:""});w(g).css({display:"none",visibility:"",opacity:""}).fadeIn(u.imageFadeDuration,h)})}function h(){if(x>=0){w(I).show()}if(D>=0){w(d).show()}w(c).css("marginTop",-c.offsetHeight).animate({marginTop:0},u.captionAnimationDuration);G.style.visibility=""}function q(){k.onload=null;k.src=t.src=J.src=n;w([a,g,c]).stop(true);w([I,d,g,G]).hide()}function C(){if(F>=0){q();F=x=D=-1;w(a).hide();w(H).stop().fadeOut(u.overlayFadeDuration,j)}return false}})(jQuery);