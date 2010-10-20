/**
 *MSG CHAT FUNCTIONS
 */
var Msg={
unreadSentList:[],timeout:0,
check:function(){
	var o=Msg;
	if(o.unreadSentList.length==0)$(".hentry.unread").each(function(){Msg.push($(this).attr('id').replace('mess',''));}); 
	if(o.unreadSentList.length>0) XMLReq.add('unreadedSent',o.unreadSentList.join(',')); 
	var d=Hash.data(); 
	if(d)if(d.p)XMLReq.add('p',d.p);
	sendAjax('post-hasNewMessage');
},
sentReaded:function(p){
	var o=Msg;
	o.unreadSentList=[];
	var postList=p.split(',');
	for(i=0;i<postList.length;i++){
		var d=$("#mess"+postList[i]);
		d.removeClass('unread');
		$(".unreadedLabel",d).remove();
	}
},
checkHandler:function(numMsgs,lastSender){
	var o=Msg,div=$("#messageNew");
	if(numMsgs>0){
		div.removeClass('hidden');
		$("#numMsg").text(numMsgs);
		$("#recentSender").text(lastSender);
	} else {
		if(!div.hasClass('hidden'))div.addClass('hidden');
	} 
	if(MESSCHECKING>0 && o.timeout)clearTimeout(o.timeout); o.timeout=setTimeout(o.check,MESSCHECKING);
},
};
/**
 * GALERY NEXT LOADING
 */
var ImgNext={
r:false,i:null,p:null,next:null,top:0,
click:function(){
	var o=ImgNext,m=gup('m',this.href);
	if(xhrList[m])return false;
	o.top=$(window).scrollTop();
	if(o.xhr){o.xhr.abort();o.xhr=null;}
	if(!o.r){o.r=true;o.i=$("#detailFoto");o.i.bind('load',o.loaded);o.p=$(".showProgress");}
	o.i.show();
	var h=o.p.height();o.p.css('height',(h>0?h:$(window).height())+'px');
	if(o.next){o.i.attr('src',o.next);o.next=null;}else{o.i.hide();}
	$(this).bind('click',fajaxaSend).unbind('click',o.click).click().unbind('click',fajaxaSend).bind('click',o.click);
	o.l=true;
	return false;
},
loaded:function(){
	var o=ImgNext;
	o.p.css('height','auto');
	if(o.state)imgResizeToFit(o.i);
	Slideshow.next();
	if(o.top>0)$(window).scrollTop(o.top);	
},
xhr:null,
xhrHand:function(currentUrl,nextUrl){
	var o=ImgNext;
	if(currentUrl && currentUrl!=o.i.attr('src'))o.i.show().attr('src',currentUrl);
	if(nextUrl)o.xhr=$.get(nextUrl,function(data){ImgNext.next=nextUrl;ImgNext.xhr=null;});
}
};
/**
 * FULLSCREEN
 */
var Slideshow={
f:function(){$("#nextButt").click();},
on:false,timeout:0,interval:5,
toggle:function(){
	var o=Slideshow;
	o.on=!o.on;
	o.next();
},
next:function(){
	var o=Slideshow;
	if(o.timeout)clearTimeout(o.timeout);
	if(o.on)o.timeout=setTimeout(o.f,o.interval*1000);
}
}
,Fullscreen={
state:null,
isSlideShow:false,
init:function(){
	var o=Fullscreen;
	listen('galeryFullSwitch','click',Fullscreen.click);
	$("#fullscreenLeave").click(o.leave);
	$("#fullscreenPrevious").click(function(){$("#prevButt").click();return false;});
	$("#fullscreenNext").click(function(){$("#nextButt").click();return false;});
	$("#fullscreenToolbar").hover(function(){$(this).fadeTo("slow",1.0);},function(){$(this).fadeTo("slow",0.2);});
	var fs=$("#fullscreenSlideshow");
	if(Slideshow.on) fs.addClass('fullscreenSlideshowOn'); else fs.removeClass('fullscreenSlideshowOn');
	fs.click(function(){
		$(this).toggleClass('fullscreenSlideshowOn');
		Slideshow.toggle();
		return false;
	});
},
go:function(div){
	var o=Fullscreen;
	if(!div && !o.state.el)return;
	if(!div) div=o.state.el;
  if (!div.hasClass('fullscreen')){
		o.state={el:div,parent:div.parent(),index:div.parent().children().index(div),x:$(window).scrollLeft(),y:$(window).scrollTop()};
		div.addClass('fullscreen');
		$('body').append(div).css('overflow','hidden');
		window.scroll(0,0);
		$(document.documentElement).bind('keyup',o.key);
	} else { 
		div.removeClass('fullscreen');
		if(o.state.index>=o.state.parent.children().length) o.state.parent.append(div);
		else div.insertBefore(o.state.parent.children().get(o.state.index));
		$('body').css('overflow', 'auto');
		window.scroll(o.state.x,o.state.y);
		$(document.documentElement).unbind('keyup',o.key);
		o.state=null; 
	}
},
click:function(){
	var o=Fullscreen;
	o.go($('#fullscreenBox'));
	$("#fullscreenToolbar").removeClass('hidden').delay(100).fadeTo("fast", 1).fadeTo("slow", 0.3);
	$(window).bind('resize',o.resize);
	imgResizeToFit($("#detailFoto"));
	return false;
},
resize:function(){
	if(Fullscreen.state)
		imgResizeToFit($("#detailFoto"));
},
key:function(event){
	if(event.keyCode==27){
		Fullscreen.leave();
	}
	if(event.keyCode==32){
		$("#nextButt").click();
	} 
},
leave:function(){
	var o=Fullscreen;
	Slideshow.on=false;
	$(window).unbind('resize',o.resize);
	$('#detailFoto').css('position','inherit').css('width','auto').css('height','auto').css('margin','0 auto');
	$('#fullscreenToolbar').addClass('hidden');
	o.go();
	return false;
}
};

function imgResizeToFit(img,fitTo,fit) {
	if(!fit)fit=0.9;if(!fitTo)fitTo=$(window);
	var ww=fitTo.width()*fit,wh=fitTo.height()*fit;
	img.css('width','auto').css('height','auto');
	var iw=img.width(),ih=img.height(),tw=ww,th=ih*ww/iw;
	if(th-wh>1){iw='auto';ih=wh;}else{iw=tw;ih='auto';} 
	img.css('width',iw).css('height',ih).css('position','absolute').css('left',((fitTo.width()-img.width())/2)+'px').css('top',((fitTo.height()-img.height())/2)+'px'); 
}

/**
 * GOOGLE MAPS
 */ 
var mapHoldersList=null,infoWindow=null;
function mapHolder(mapEl){this.mapEl=mapEl;this.mapDataList=[];this.map = null;
this.geocoder=null;
this.init=function(){
if(!this.map) {
this.geocoder = new google.maps.Geocoder();
this.map = new google.maps.Map(this.mapEl, { mapTypeId:google.maps.MapTypeId.TERRAIN });
this.map.setCenter(new google.maps.LatLng(50, 0));
this.map.setZoom(5); 
} } }
function mapData() {this.parent=null;this.dataEl=null;this.title='';this.infoEl=null;this.overEl=null;this.map=null;this.marker=null;this.path=null;this.journey=false;this.distance = 0;
this.updateMarker = function(latLng){if(!this.marker)this.marker=new google.maps.Marker({position:latLng,map:this.map,title:this.title});else this.marker.setPosition(latLng);if(this.infoEl)this.marker.htmlInfo=$(this.infoEl).html();if(this.overEl)this.marker.htmlOver=$(this.overEl).html();}
this.resetWP=function(){if(this.path){this.path.setPath([]);}}
this.addWP=function(latLng){if(!this.path)this.path=new google.maps.Polyline({map:this.map,path:[],strokeColor:"#ff0000",strokeOpacity:1.0,strokeWeight:2,geodesic:true});var wpList=this.path.getPath();wpList.push(latLng);this.path.setPath(wpList);}
this.updateDistance = function(){this.distance=0;if(!this.path)return;var wpList=this.path.getPath();if(wpList.length>1){for(i=1;i<wpList.length;i++){this.distance+=distance(wpList.getAt(i-1).lat(),wpList.getAt(i-1).lng(),wpList.getAt(i).lat(),wpList.getAt(i).lng());}}this.distance = Math.round(this.distance*10)/10;}
}
function initMapData(){
	if(mapHoldersList) return;
	if(!mapHoldersList) mapHoldersList=[];
	$('.mapLarge').each(function(){
		var holder = new mapHolder(this);
		$(this).find ('.mapsData').each(function(){
			var data = new mapData();
			$(this.children).each(function(){
				switch($(this).attr("class")) {
				case "geoData":
					data.dataEl=this;
					data.title=$(this).attr('value'); 
					break;
				case "geoInfo":
					data.infoEl=this;
					break;
				case "geoOver":
					data.overEl=this;
				};
			});
			holder.mapDataList.push(data);
		});
		mapHoldersList.push(holder);
	});
}
function initMap() {
	$(".mapLarge").each(function(){
		if(!$(this).hasClass('hidden')) showMap($(this).attr('id'));
	});
	listen('mapThumbLink','click',mapThumbClick);
}
function mapThumbClick(){
	var id=$(this).attr('id').replace('mapThumb','');
	$(this).addClass('hidden');
	$('#map'+id).removeClass('hidden');
	showMap(id);
	return false;
}

var showMapCallback,mapItemId;
function showMap(itemId,callback) {
	if(itemId)mapItemId=itemId;
	if(callback)showMapCallback=callback;
	initMapData();
	if(!GooMapi.load(showMap))return;
	if(!mapHoldersList) return;
	for(var k=0;k<mapHoldersList.length;k++) {
		var holder = mapHoldersList[k];
		var use=true;
		if(mapItemId>0 && $(holder.mapEl).attr('id')!='map'+mapItemId) use=false;
		if(use){
			holder.init();
			var bounds=new google.maps.LatLngBounds(),boundNum=0;
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
				if(data.infoEl){
					data.marker.htmlInfo = data.marker.htmlInfo.replace('[[DISTANCE]]',data.distance);
					google.maps.event.addListener(data.marker, 'click', function(event) {if(!infoWindow)infoWindow=new google.maps.InfoWindow();infoWindow.setContent(this.htmlInfo);infoWindow.open(data.map,this);});
				}
				if(data.overEl){
					 google.maps.event.addListener(data.marker, 'mouseover', function(event){$(this.htmlOver).removeClass('hidden').css('position','absolute');});
					 google.maps.event.addListener(data.marker, 'mouseout', function(event) {$(this.htmlOver).addClass('hidden');});
				}
			}
			if(boundNum>0) {
				holder.map.setZoom(20);
				fitBounds.push({m:holder.map,b:bounds}); 
				setTimeout(fitBoundsLater,250);
			}
			break;
		}
	}
	
	if($.isFunction(showMapCallback)){showMapCallback(); showMapCallback=null; }
};
var fitBounds=[];
function fitBoundsLater(){
while(fitBounds.length>0) {
var o=fitBounds.pop();
o.m.fitBounds(o.b);
}
}
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
function geoSelector(e) {
	$(".geoselectorlabel").removeClass('geoselectorlabel');
	$(".positionSelectorBox").removeClass('hidden');
	$(".journeySelector").click();
	e.preventDefault();	
	return false;
}
function editorData() {
	initMapData();
	for(var k=0;k<mapHoldersList.length;k++) {
		if($(mapHoldersList[k].mapEl).attr('id')=='mapEditor') return mapHoldersList[k].mapDataList[0];
	}
	$("body").append('<div id="mapEditor"></div>');
	var holder = new mapHolder(document.getElementById('mapEditor'));
	var data = new mapData();
	data.parent=holder;
	holder.mapDataList=[data];
	mapHoldersList.push(holder);
	return data;
}
function journeySelectorCreate() {
	var data=editorData();
	data.journey = true;
	data.dataEl = this;
	mapEditor();
}
function mapSelectorCreate() {
	var data=editorData();
	data.journey = false;
	data.dataEl = this;
	mapEditor();
}
var mapEditorView=false,mapSearchHTML='<div id="mapSearch" style="float:left;"><input id="mapaddress" value="" style="width:300px;margin-right:5px;margin-top:6px;"/><button id="mapSearchButt">Find</button></div>';
function mapEditor() {
	if(!Lazy.load(LAZYLOAD.ui,mapEditor))return;
	$("#mapEditor").dialog({
			modal: true,
			minWidth:640,
			minHeight:200,
			width: $(window).width()*0.8, 
			height: $(window).height()*0.8,
			resizeStop:mapResize,
			buttons: {
				'Remove Last': function() {
					var data=editorData();
					data.path.getPath().pop();
					var path = data.path.getPath();
					data.updateMarker( path.getAt(path.getLength()-1) );
				},
				'Clear': function() {
					var data=editorData();
					data.marker.setMap(null);
					data.marker = null;
					data.path.setMap(null);
					data.path = null;
				},
				'Cancel': function() {
					var data=editorData();
					google.maps.event.clearListeners(data.parent.map, 'click');
					$("#mapSearchButt").unbind('click',findAddress);
					$("#mapaddress").unbind('keydown',addressCheckForEnter);
					$(this).dialog('close');
				},
				'Save': function() {
					var data=editorData();
					google.maps.event.clearListeners(data.parent.map, 'click');
					$("#mapSearchButt").unbind('click',findAddress);
					$("#mapaddress").unbind('keydown',addressCheckForEnter);
					$(this).dialog('close');
					$(data.dataEl).val('');
					if(data.journey===true) {
						if(data.path) {var list=[];
						data.path.getPath().forEach(function(latLng){list.push(latLng.toUrlValue(4));});
						$(data.dataEl).val( list.join("\n") ).keydown();
						}
					} else {
						if(data.marker) $(data.dataEl).val( data.marker.getPosition().toUrlValue(4) );
					}
				}
			}
		});
		
	$(".ui-dialog-buttonpane").prepend(mapSearchHTML);
	$("#mapSearchButt").button().click(findAddress);
	$("#mapaddress").keydown(addressCheckForEnter);
		
  showMap('Editor',function(){
		var data=editorData();
		google.maps.event.addListener(data.parent.map, 'click', mapClickHandler);
		data.updateDistance();
		if(data.distance>0) $("#mapEditor").dialog( "option", "title", data.distance+'NM' );
		mapResize();
	});
	
}
function mapResize(){
var m=$("#mapEditor"),d=m.dialog(),data=editorData();m.css('width',d.width()+'px').css('height',d.height()+'px');google.maps.event.trigger(data.parent.map, 'resize');
}
function mapClickHandler(event){
	var data=editorData();
	if(data.journey){
		data.addWP(event.latLng);
		data.updateMarker(event.latLng);
		data.updateDistance();
		if(data.distance>0) $("#mapEditor").dialog( "option", "title", data.distance+'NM' );
	} else {
		data.updateMarker(event.latLng);
	}
}
function addressCheckForEnter(event) {if (event.keyCode == 13) {findAddress();} }
function findAddress() {
	var data=editorData();
  var address = {'address': document.getElementById('mapaddress').value}; 
  data.parent.geocoder.geocode(address, function(results, status) {
    if (status == google.maps.GeocoderStatus.OK) {
    	var data=editorData();
      data.parent.map.setCenter(results[0].geometry.location);
      data.parent.map.setZoom(19);
      data.updateMarker(results[0].geometry.location);
    }
  });
}
//---GOOGLE MAPS END

/**
 * HASH HANDLING
 */
var Hash={
old:'',
init:function(){$(window).hashchange(function(){var o=Hash,h=location.hash.replace('#','');if(h!=o.old){if(h=='' && o.old.length>0){window.location.reload();return;}h.old=h;
//Fajax.action(h);
fajaxaAction(h);//TODO:change when done
}});},
set:function(h){document.location.hash=h;},
reset:function(hash){document.location.hash=Hash.old=hash;},
data:function(){var h=document.location.hash.replace('#','').split('/'),d=h[1];if(d){var arr=d.split(';'),data={};while(arr.length>0){var v=arr.shift(),kv=v.split(':');data[kv[0]]=kv[1];} return data;}}
}

/**
 * IMAGE UPLOADING TOOL HANDLERS - FUUP
 */ 
function galeryCheck() {sendAjax('page-fuup',gup('k',$(".fajaxform").attr('action')));};
function fuupUploadComplete() {
var item = $('#item').attr('value');
if(item>0) XMLReq.add('item', item);
XMLReq.add('call', 'jUIInit');
sendAjax('item-image',gup('k',$(".fajaxform").attr('action')));
}
//---IMAGE UPLOADING END

/**
 *AJAX FORM SUBMIT HANDLING
 */ 
var preventAjax = false, formSent=null;
function onFajaxformButton(event) {
	event.preventDefault();
	if (preventAjax == true){preventAjax = false;	return;	}
	if($(event.currentTarget).hasClass('confirm')) {
		if(!confirm($(event.currentTarget).attr("title"))){return; }
	} 
	if($(event.currentTarget).hasClass('draftdrop')) Draft.hasDropAll=true;
	$('.errormsg').hide('slow',function(){ $(this).html(''); } );
	$('.okmsg').hide('slow',function(){ $(this).html(''); } );
	formSent = event.currentTarget.form;
	$('.button',formSent).attr('disabled',true);
	var arr = $(formSent).formToArray(false), action, result = false, resultProperty = false;
	while (arr.length > 0) {
		var obj = arr.shift();
		if(obj.name=='m') action = obj.value;
		else XMLReq.add(obj.name, obj.value);
		if (obj.name == 'result') result = true;
		if (obj.name == 'resultProperty') resultProperty = true;
	}
	if (result == false) XMLReq.add('result', $(formSent).attr("id"));
	if (resultProperty == false) XMLReq.add('resultProperty', '$html');
	XMLReq.add('action', event.currentTarget.name);
	XMLReq.add('k', gup('k', formSent.action));
	sendAjax(!action ? gup('m', formSent.action) : action,gup('k', formSent.action));
};
//---AJAX FORM END

/**
 * AJAX LINK HANDLING
 */
function fajaxpager(){
Hash.set('post-page/p:'+gup('p',this.href)+'/fpost');
return false;
}
var scrollTop;
function fajaxaSend(event) {
	scrollTop=null
	event.preventDefault();
	if($(event.currentTarget).hasClass('confirm')) {if(!confirm($(event.currentTarget).attr("title"))) return false;}
	var k=gup('k',this.href),id=$(this).attr("id"),m=gup('m',this.href);
	if(!k) k = 0;
	var action = m+'/'+gup('d',this.href)+'/'+k;
	if(id){action += '/'+id; } 
	if($(this).hasClass('keepScroll')) scrollTop = $(window).scrollTop();
	if($(this).hasClass('progress')) {var bar=$(".showProgress"),h=bar.height();bar.addClass('lbLoading').css('height',(h>0?h:$(window).height())+'px').css("margin","0 auto").children().hide();}
	if($(this).hasClass('hash')){Hash.set(action);return false;}
	fajaxaAction(action);
	return false;
};
function fajaxaAction(action) {//action = m/d/k|0/linkElId
	actionList = action.split('/');
	var m=actionList[0],d=actionList[1],k=actionList[2],id=actionList[3],result = false, resultProperty = false;
	if(k==0) k=null;
	if(d){
		var arr = d.split(';');
		while (arr.length > 0) {
			var rowStr = arr.shift();
			var row = rowStr.split(':');
			XMLReq.add(row[0], row[1]);
			if (row[0] == 'result') result = true;
			if (row[0] == 'resultProperty') resultProperty = true;
		}
	}
	if(id) {
		if (result == false) XMLReq.add('result', id);
		if (resultProperty == false) XMLReq.add('resultProperty', '$html');
	}
	sendAjax(m,k);
};
//---AJAX LINK END

/**
 * MARKITUP SETUP - rich textarea
 */ 
var markitupSettings = {	
	onShiftEnter:{keepDefault:false, replaceWith:'<br />\n'},
	onCtrlEnter:{keepDefault:false, openWith:'\n<p>', closeWith:'</p>'},
	onTab:{keepDefault:false, replaceWith:'    '},
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
		{name:'Clean', className:'clean', replaceWith:function(markitup){return markitup.selection.replace(/<(.*?)>/g, "") } }
	]
}
var Richta = {w:null,
init:function(ta){
var o=Richta;
if(ta)o.w=ta;
if(!Lazy.load(LAZYLOAD.richta,Richta.init))return;
if(!o.w)o.w=$('.markitup');  
o.w.markItUp(markitupSettings); 
o.w=null;
},
map:function(){
$('.markitup').each( function(){$(this).before('<span class="textAreaResize"><a href="?textid='+$(this).attr('id')+'" class="toggleToolSize"></a></span>'); });
listen('toggleToolSize','click',Richta.click);
},
click:function(e){
var ta=$("#"+gup("textid",e.target.href));
if(ta.hasClass('markItUpEditor'))ta.markItUpRemove();
else if(!Richta.w) Richta.init(ta);
e.preventDefault();return false;
}
};
//---MARKITUP SETUP END

/**
 * INITIALIZATION ON DOM
 */ 
//$(function (){ boot() });
function boot() {
	if($("#errormsgJS").html().length>0) $("#errormsgJS").css('padding','1em'); else $("#errormsgJS").hide(); 
	if($("#okmsgJS").html().length>0) $("#okmsgJS").css('padding','1em'); else $("#okmsgJS").hide();
	var w = $(window).width();
	if(w>800) $("#loginInput").focus();
	if ($("#sidebar").length == 0){$('body').addClass('bodySidebarOff'); }
	$("textarea[class*=expand]").autogrow().keydown();
	$(".opacity").bind('mouseenter',function(){ $(this).fadeTo("fast",1); }).bind('mouseleave',function(){ $(this).fadeTo("fast",0.2); });
	//---set default listerens - all links with fajaxa class - has to have in href get param m=Module-Function and d= key:val;key:val
	fajaxaInit();
	fconfirmInit();
	switchOpen();
	$(',popupLink').click(function(){openPopup(this.href);return false;});
	$(window).resize(onResize);
	onResize();
	 
	initMap();
	initMapSelector();
	if($(".hash").length>0){Hash.init();}
	slimboxInit();
	Fullscreen.init();
	initPager();
	if(GOOGLEANALID) gaSSDSLoad(GOOGLEANALID);
	tabsInit();
	fuupInit();
	datePickerInit();
	if(USER>0) {
		fajaxformInit();
		// ---ajax textarea / tools
		Richta.map();
		Draft.init();
		// ---message page
		$("#recipient").change( avatarfrominput );
		$('#ppinput').hide();
		$("#saction").change( function(evt){if($("#saction option:selected").attr('value') == 'setpp') $('#ppinput').show(); else $('#ppinput').hide(); });
		$("#recipientList").change( function(evt) {
			var str = "";
			var combo = $("#recipientList");
			if(combo.attr("selectedIndex")>0) $("#recipientList option:selected").each( function(){str += $(this).text() + " "; });
			$("#recipient").attr("value", str);
			combo.attr("selectedIndex", 0);
			avatarfrominput();
		});
		//galery edit	
		fotoTotal = $("#fotoTotal").text(); if(fotoTotal > 0 && $('#fotoList').length>0) galeryLoadThumb();
		if(MESSCHECKING>0) Msg.check();
	}
};
//INIT jQuery UI and everything possibly needed for ajax forms and items
function jUIInit() {
	if(!Lazy.load(LAZYLOAD.ui,jUIInit))return;
	tabsInit();
	datePickerInit();
	Richta.map();
	fajaxaInit();
	fconfirmInit();
	fajaxformInit();
	draftInit();
	initMapSelector();
	fuupInit();
	slimboxInit();
	$("textarea[class*=expand]").autogrow().keydown()
}
//pager
function initPager() {if($(".fajaxpager").length > 0) listen('fajaxpager','click',fajaxpager);}
//init map selectors
function initMapSelector(){listen('geoselector','click',geoSelector);listen('journeySelector','click',journeySelectorCreate);listen('positionSelector','click',mapSelectorCreate);}
//datepicker init
function datePickerInit(){if($(".datepicker").length>0){if(!Lazy.load(LAZYLOAD.ui,datePickerInit))return;$.datepicker.setDefaults($.extend({showMonthAfterYear:false},$.datepicker.regional['cs']));$(".datepicker").datepicker();}};
//slimbox init
function slimboxInit(){if(!/android|iphone|ipod|series60|symbian|windows ce|blackberry/i.test(navigator.userAgent)){$("a[rel^='lightbox']").slimbox({overlayFadeDuration : 100, resizeDuration : 100, imageFadeDuration : 100, captionAnimationDuration : 100}, null, function(el){return (this == el) || ((this.rel.length > 8) && (this.rel == el.rel)); }); } }
//fuup init
function fuupInit(){if($(".fuup").length>0){ if(!Lazy.load(LAZYLOAD['swf'],fuupInit))return; $(".fuup").each(function(i){ swfobject.embedSWF(ASSETS_URL+"load.swf", $(this).attr('id'), "120", "25", "10.0.12", ASSETS_URL+"expressInstall.swf", {file:ASSETS_URL+"Fuup.swf",config:"fuup."+$(this).attr('id')+"."+gup('k',$(".fajaxform").attr('action'))+".xml",containerId:$(this).attr('id')},{wmode:'transparent',allowscriptaccess:'always'}); }); }}
//tabs init
function tabsInit(){if($("#tabs").length>0){if(!Lazy.load(LAZYLOAD.ui,tabsInit))return;$("#tabs").tabs();}};
//request init
function friendRequestInit(){$('#friendrequest').show('slow'); fajaxformInit(); $('#cancel-request').bind('click',function(event){remove('friendrequest');event.preventDefault();return false;}); };
//ajax form init
function fajaxformInit(event){if($(".fajaxform").length>0){listen('button', 'click', onFajaxformButton); } };
//ajax link init
function fajaxaInit(event){listen('fajaxa', 'click', fajaxaSend); listen('galerynext','click',ImgNext.click); };
function fconfirmInit(event){$('.confirm').each(function(){ var fajaxaformParent=false; if(this.form) fajaxaformParent = $(this.form).hasClass('fajaxform'); if(!$(this).hasClass('fajaxa') && !fajaxaformParent){$(this).bind('click',onConfirm); } }); };
function onConfirm(e) {	if(!confirm($(e.currentTarget).attr("title"))){preventAjax = true; e.preventDefault();	} };
//simple functions
function shiftTo(y){if(!y) y=0;$(window).scrollTop(y);}
function enable(id){$('#'+id).removeAttr('disabled');};
function remove(id,notween){if(notween==1){$('#'+id).remove(); }else{ $('#'+id).hide('slow',function(){$('#'+id).remove()}); } };
function switchOpen(){$('.switchOpen').click(function(){$('#'+this.rel).toggleClass('hidden');return false;});};
function openPopup(href){ window.open(href, 'fpopup', 'scrollbars=' + gup("scrollbars", href) + ',toolbar=' + gup("toolbar", href) + ',menubar=' + gup("menubar", href) + ',status=' + gup("status", href) + ',resizable=' + gup("resizable", href) + ',width=' + gup("width", href) + ',height=' + gup("height", href) + ''); };
function listen(c,e,f){$("."+c).unbind(e,f).bind(e,f);};
function gup(name,url){name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]"); var regex = new RegExp("[\\?&|]" + name + "=([^&#|]*)"), results = regex.exec(url); return (results === null) ? (0) : (results[1]); };
function msg(type,text){$("#"+type+"msgJS").css('padding','1em').html( text ).show('slow').delay(10000).hide('slow'); };
function redirect(dir){window.location.replace(dir);};

//ajax simple functions
function avatarfrominput(evt) {XMLReq.add('username', $("#recipient").attr("value"));XMLReq.add('call', 'fajaxaInit');sendAjax('post-avatarfrominput','');}
/* RESIZE HANDLER - CLIENT INFO TO SERVER */ 
var resizeTimeout;
function onResize(){if(resizeTimeout){clearTimeout(resizeTimeout);}resizeTimeout=setTimeout(sendClientInfo,500);};
function sendClientInfo(){var w=$(window).width(),h=$(window).height();if(w!=CLIENT_WIDTH || h!=CLIENT_HEIGHT){XMLReq.add('size',w+'x'+h);sendAjax('user-clientInfo',-1);}};
//---INITIALIZATION ON DOM END
/**
 * AJAX GALLERY EDITING THUMBNAILS LOADING AND REFRESHING
 */ 
var fotoTotal = 0, fotoLoaded = 0, itemsNewList =  [], itemsUpdatedList = [], galeryCheckRunning = false;
function galeryRefresh(itemsNew,itemsUpdated,total) {
	fotoTotal = parseInt( total );
	$("#fotoTotal").text(total);
	var itemsNewArr=[],itemsUpdatedArr=[];
	if(itemsNew.length>0) itemsNewArr = itemsNew.split(';');
	if(itemsUpdated.length>0) itemsUpdatedArr = itemsUpdated.split(';');
	while(itemsNewArr.length>0){itemsNewList.push(itemsNewArr.shift()); }
	while(itemsUpdatedArr.length>0){itemsUpdatedList.push(itemsUpdatedArr.shift()); }
	if(!galeryCheckRunning) galeryRefreshNext();
}
function galeryRefreshNext() {
	if(itemsUpdatedList.length>0) {	
		galeryLoadThumb(itemsUpdatedList.shift(),'U');} 
	else if(itemsNewList.length>0){
		galeryLoadThumb(itemsNewList.shift(),'N'); } 
	else if(fotoLoaded < fotoTotal) {	galeryLoadThumb(); }
};
function galeryLoadThumb(item,type,offset) {
	var destSet=false;
	galeryCheckRunning = true;
	if(item > 0) {
		XMLReq.add('item', item);
		if(type=='U') {
			XMLReq.add('result', 'foto-'+item);
			XMLReq.add('resultProperty', '$replaceWith');
			destSet=true;
		}
	} else {
		XMLReq.add('total', fotoTotal);
		XMLReq.add('seq', fotoLoaded);
	}
	if(destSet===false){
		XMLReq.add('result', 'fotoList'); 
		XMLReq.add('resultProperty', '$append'); 
	}
	XMLReq.add('call', 'jUIInit');	
	XMLReq.add('call', 'bindDeleteFoto');
	if(!offset) offset = 10
	XMLReq.add('offset', offset);
	if(fotoLoaded+offset < fotoTotal) XMLReq.add('call', 'galeryCheck');
	sendAjax('galery-editThumb',gup('k',$(".fajaxform").attr('action')));
};
function fotoFeeded(num){
	fotoLoaded+=parseInt(num); 
	galeryCheckRunning=false;
}

function bindDeleteFoto(){
	listen('deletefoto', 'click', deleteFoto); 
}
function deleteFoto(event) {
	if (confirm($(this).attr("title"))) {
		//---send ajax
		var idArr = $(this).attr("id").split("-");
		XMLReq.add('item', idArr[1]);
		sendAjax('item-delete');
		//---remove element
		$('#foto-'+idArr[1]).hide('slow',function(){$('#foto-'+idArr[1]).remove()});
		fotoTotal--;
		$("#fotoTotal").text(fotoTotal);
	}
	event.preventDefault();preventAjax = true;return false;
};
//---AJAX GALLERY EDITING END

/** DRAFT - temporary textarea data storing **/
var Draft={
timer:3000,
li:{},
ta:function(id){
	this.id=id;
	this.old=null;
	this.t=0;
	this.text=function(){return $.trim($('#'+this.id).val());};
	this.backup=function(){this.old=this.text();};
	this.restore=function(){if(this.old)$('#'+id).val(this.old);this.old=null;};
	this.check=function(){
		this.backup();
		$(this.id).attr('disabled','disabled');
 		XMLReq.add('result', this.id);
		sendAjax('draft-check');
	};
	this.setT=function(f,t){this.t=setTimeout(f,t);};
	this.clearT=function(){if(this.t)clearTimeout(this.t);this.t=null;};
},
init:function(){
	var o=Draft,l=[],f;
	listen('submit','click',o.submit);
	listen('draftable', 'keyup',o.key);
	if(window.location.hash=='#dd' || gup('dd',window.location)==1){
		o.dropAll(true);
		window.location.hash='';
	} 
	$('.draftable').each(function(){
		var id=$(this).attr('id');
		if(!o.li[id])o.li[id]=new o.ta(id);
		o.li[id].check();
	});
	$('.draftable').each(function(){f=this.form;l.push($(this).attr('id'));});
	if(l.length>0) {
		$("#draftablesList").remove();
		$(f).append('<input id="draftablesList" type="hidden" name="draftable" value="'+l.join(',')+'" />');
	}
}
,backup:function(id){
	var o=Draft;
	if(!o.li[id])o.li[id]=new o.ta(id);
	o.li[id].backup(); 
},
restore:function(id){
	var o=Draft;
	if(!o.li[id])o.li[id].restore(); 
}
,hasdropAll:false
,dropAll:function(override){
	var o=Draft;
	if(override)o.hasDropAll=true; 
	if(!o.hasDropAll)return;
	o.hasDropAll=false;
	var l=[];
	$('.draftable').each( function() {
		var id=$(this).attr('id');
		$('#draftdrop'+id).remove();
 		l.push(id);
 	});
 	if(l.length>0) {
 		XMLReq.add('result', l.join(','));
		sendAjax('draft-drop');
	}
}
,dropClick:function(e){
	e.preventDefault();
	var o=Draft,id=gup('ta',$(e.currentTarget).attr('href'));
	if(o.li[id])o.li[id].restore();
  XMLReq.add('result',id);
	sendAjax('draft-drop');
  $(e.currentTarget).remove();
  return false;
}
,unused:function(id){
	if($('#draftdrop'+id).length>0) {
		XMLReq.add('result',id);
		sendAjax('draft-drop');
		$('#draftdrop'+id).remove();
	}
}
,dropBackHandler:function(){
	Draft.hasDropAll=false;
	$('.draftable').each( function(){$('#draftdrop'+$(this).attr('id')).remove();});
}
,submit:function(){
	for(var id in Draft.li)
		Draft.li[id].clearT(); 
}
,save:function(){
	for(var id in Draft.li){
		var t=Draft.li[id],text=t.text();
		if(text!=t.old && text.length>0) {
			XMLReq.add('place',id);
			XMLReq.add('text',text);
			XMLReq.add('call','Draft.saveHandler;'+id);
			sendAjax('draft-save');
			t.old=text;
		}
	}
}
,saveHandler:function(id){
	$("#"+id).removeClass('draftNotSave').addClass('draftSave');
}
,key:function(){
	var o=Draft,id=$(this).attr('id'); 
	o.unused(id);
	if (o.li[id].text()!=o.li[id].old)
		$("#"+id).removeClass('draftSave').addClass('draftNotSave');
	o.li[id].clearT();
	o.li[id].setT(o.save,o.timer); 
}
}

/**
 * CUSTOM AJAX REQUEST BUILDER/HANDLER
 * send and process ajax request - if problems with %26 use encodeURIComponent
 */
xhrList={};  
function sendAjax(action,k){var data=XMLReq.get();if(k==0)k=null;if(!k)k=gup('k',document.location);if(k==-1)k='';$.ajaxSetup({scriptCharset:"utf-8",contentType:"text/xml; charset=utf-8"});
xhrList[action]=$.ajax({type:"POST",url:"index.php?m="+action+"-x"+((k)?("&k="+k):('')),dataType:'xml',processData:false,cache:false,data:data
,complete:function(ajaxRequest,textStatus) {
xhrList[action]=null;
$(ajaxRequest.responseXML).find("Item").each(function() {var item = $(this),command = '',target=item.attr('target'),property = item.attr('property'),text=item.text();switch (target) {case 'document': command =  target + '.' + property + ' = "'+text+'"'; break;case 'call':command = property + "("+(text.length>0 ? "'" + text.split(',').join("','") + "'" : "")+");"; break;default: var arr=text.split(';'),part0=arr[0],callback=(arr[1]?arr[1]:null);switch (property) {case 'void': break;case 'css':case 'getScript':Lazy.load([part0],callback);break;case 'body': $("body").append(part0); break;default: if(property[0]=='$') {command = '$("#' + target + '").' + property.replace('$','') + '( text );'} else {command = '$("#' + target + '").attr("' + property + '", text);';}};};if(command.length>0){eval(command);}if(formSent){$('.button',formSent).removeAttr('disabled');formSent=null;Draft.dropAll();}})}});};
//---build xml request
var XMLReq={a:[],s:'<Item name="{KEY}"><![CDATA[{DATA}]]></Item>',reset:function(){XMLReq.a=[];},add:function(k,v){XMLReq.a.push(XMLReq.s.replace('{KEY}',k).replace('{DATA}',v));},get:function(){var s='<FXajax><Request>'+XMLReq.a.join('')+'</Request></FXajax>';XMLReq.a=[];return s;}};
//--- CUSTOM AJAX REQUEST BUILDER/HANDLER END
//LAZYLOADER
var Lazy={r:{},f:null,q:[],load:function(l,f){var o=Lazy,c=true;for(var i=0;i<l.length;i++)if(!o.r[l[i]]){c=false;break}if(c)return c;o.q.push({l:l.concat(),f:f});if(o.q.length==1)return o.p();},p:function(){var o=Lazy;while(o.q[0].l.length>0){var f=o.q[0].l.shift();if(!o.r[f]){o.f=f;if(f.indexOf('.css')>-1){LazyLoad.css(f,o.c);}else{LazyLoad.js(f,o.c);}return;}}o.q.shift();return true;},c:function(){var o=Lazy;o.r[o.f]=true;if(o.q[0].l.length>0){o.p();return;}if(o.q[0].f)o.q[0].f();o.q.shift();if(o.q.length>0)o.p();}};
//init google map API
var GooMapi={loading:false,loaded:false,call:[],load:function(f){var o=GooMapi;if(o.loaded)return true;if(o.call.indexOf(f)==-1)o.call.push(f);if(o.loading)return;o.loading=true;var d=window.document,script=d.createElement('script');script.setAttribute('src','http://maps.google.com/maps/api/js?v=3&sensor=false&callback=GooMapi.c');d.documentElement.firstChild.appendChild(script);},c:function(){var o=GooMapi;o.loading=false;o.loaded=true;while(o.call.length>0){var f=o.call.shift();f();}}};
//google anal
function gaSSDSLoad(acct){var gaJsHost=(("https:"==document.location.protocol)?"https://ssl.":"http://www."),pageTracker,d=window.document,s=d.createElement('script');s.setAttribute('src',gaJsHost+'google-analytics.com/ga.js');s.onloadDone=false;function init(){pageTracker=_gat._getTracker(acct);pageTracker._trackPageview();}s.onload=function(){s.onloadDone=true;init();};s.onreadystatechange=function(){if(('loaded'===s.readyState || 'complete'===s.readyState) && !s.onloadDone){s.onloadDone=true;init();}};d.documentElement.firstChild.appendChild(s);};
/* formToArray */ 
;(function($){$.fn.formToArray=function(semantic){var a=[];if(this.length==0)return a;var form=this[0];var els=semantic?form.getElementsByTagName('*'):form.elements;if(!els)return a;for(var i=0,max=els.length;i<max;i++){var el=els[i];var n=el.name;if(!n)continue;if(semantic&&form.clk&&el.type=="image"){if(!el.disabled&&form.clk==el){a.push({name:n,value:$(el).val()});a.push({name:n+'.x',value:form.clk_x},{name:n+'.y',value:form.clk_y})}continue}var v=$.fieldValue(el,true);if(v&&v.constructor==Array){for(var j=0,jmax=v.length;j<jmax;j++)a.push({name:n,value:v[j]})}else if(v!==null&&typeof v!='undefined')a.push({name:n,value:v})}if(!semantic&&form.clk){var$input=$(form.clk),input=$input[0],n=input.name;if(n&&!input.disabled&&input.type=='image'){a.push({name:n,value:$input.val()});a.push({name:n+'.x',value:form.clk_x},{name:n+'.y',value:form.clk_y})}}return a};$.fn.fieldValue=function(successful){for(var val=[],i=0,max=this.length;i<max;i++){var el=this[i];var v=$.fieldValue(el,successful);if(v===null||typeof v=='undefined'||(v.constructor==Array&&!v.length))continue;v.constructor==Array?$.merge(val,v):val.push(v)}return val};$.fieldValue=function(el,successful){var n=el.name,t=el.type,tag=el.tagName.toLowerCase();if(typeof successful=='undefined')successful=true;if(successful&&(!n||el.disabled||t=='reset'||t=='button'||(t=='checkbox'||t=='radio')&&!el.checked||(t=='submit'||t=='image')&&el.form&&el.form.clk!=el||tag=='select'&&el.selectedIndex==-1))return null;if(tag=='select'){var index=el.selectedIndex;if(index<0)return null;var a=[],ops=el.options;var one=(t=='select-one');var max=(one?index+1:ops.length);for(var i=(one?index:0);i<max;i++){var op=ops[i];if(op.selected){var v=op.value;if(!v)v=(op.attributes&&op.attributes['value']&&!(op.attributes['value'].specified))?op.text:op.value;if(one)return v;a.push(v)}}return a}return el.value};$.fn.clearForm=function(){return this.each(function(){$('input,select,textarea',this).clearFields()})};$.fn.clearFields=$.fn.clearInputs=function(){return this.each(function(){var t=this.type,tag=this.tagName.toLowerCase();if(t=='text'||t=='password'||tag=='textarea')this.value='';else if(t=='checkbox'||t=='radio')this.checked=false;else if(tag=='select')this.selectedIndex=-1})};$.fn.resetForm=function(){return this.each(function(){if(typeof this.reset=='function'||(typeof this.reset=='object'&&!this.reset.nodeType))this.reset()})};$.fn.enable=function(b){if(b==undefined)b=true;return this.each(function(){this.disabled=!b})};$.fn.selected=function(select){if(select==undefined)select=true;return this.each(function(){var t=this.type;if(t=='checkbox'||t=='radio')this.checked=select;else if(this.tagName.toLowerCase()=='option'){var$sel=$(this).parent('select');if(select&&$sel[0]&&$sel[0].type=='select-one'){$sel.find('option').selected(false)}this.selected=select}})}})(jQuery);
/* autogrow */ 
;(function($){$.fn.autogrow=function(e){this.filter('textarea').each(function(){var b=$(this),minHeight=b.height(),lineHeight=b.css('lineHeight');var c=$('<div></div>').css({position:'absolute',top:-10000,left:-10000,width:$(this).width(),fontSize:b.css('fontSize'),fontFamily:b.css('fontFamily'),lineHeight:b.css('lineHeight'),resize:'none'}).appendTo(document.body);var d=function(){var a=this.value.replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/&/g,'&amp;').replace(/\n/g,'<br/>');c.html(a);$(this).css('height',Math.max(c.height()+20,minHeight))};$(this).change(d).keyup(d).keydown(d);d.apply(this)});return this}})(jQuery);
/* base64Encode/base64Decode //$.base64Encode("I'm Persian."); // return "SSdtIFBlcnNpYW4u" */
;(function($){var e="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";var f=function(a){a=a.replace(/\x0d\x0a/g,"\x0a");var b="";for(var n=0;n<a.length;n++){var c=a.charCodeAt(n);if(c<128){b+=String.fromCharCode(c)}else if((c>127)&&(c<2048)){b+=String.fromCharCode((c>>6)|192);b+=String.fromCharCode((c&63)|128)}else{b+=String.fromCharCode((c>>12)|224);b+=String.fromCharCode(((c>>6)&63)|128);b+=String.fromCharCode((c&63)|128)}}return b};var g=function(a){var b="";var i=0;var c=c1=c2=0;while(i<a.length){c=a.charCodeAt(i);if(c<128){b+=String.fromCharCode(c);i++}else if((c>191)&&(c<224)){c2=a.charCodeAt(i+1);b+=String.fromCharCode(((c&31)<<6)|(c2&63));i+=2}else{c2=a.charCodeAt(i+1);c3=a.charCodeAt(i+2);b+=String.fromCharCode(((c&15)<<12)|((c2&63)<<6)|(c3&63));i+=3}}return b};$.extend({base64Encode:function(a){var b="";var c,chr2,chr3,enc1,enc2,enc3,enc4;var i=0;a=f(a);while(i<a.length){c=a.charCodeAt(i++);chr2=a.charCodeAt(i++);chr3=a.charCodeAt(i++);enc1=c>>2;enc2=((c&3)<<4)|(chr2>>4);enc3=((chr2&15)<<2)|(chr3>>6);enc4=chr3&63;if(isNaN(chr2)){enc3=enc4=64}else if(isNaN(chr3)){enc4=64}b=b+e.charAt(enc1)+e.charAt(enc2)+e.charAt(enc3)+e.charAt(enc4)}return b},base64Decode:function(a){var b="";var c,chr2,chr3;var d,enc2,enc3,enc4;var i=0;a=a.replace(/[^A-Za-z0-9\+\/\=]/g,"");while(i<a.length){d=e.indexOf(a.charAt(i++));enc2=e.indexOf(a.charAt(i++));enc3=e.indexOf(a.charAt(i++));enc4=e.indexOf(a.charAt(i++));c=(d<<2)|(enc2>>4);chr2=((enc2&15)<<4)|(enc3>>2);chr3=((enc3&3)<<6)|enc4;b=b+String.fromCharCode(c);if(enc3!=64){b=b+String.fromCharCode(chr2)}if(enc4!=64){b=b+String.fromCharCode(chr3)}}b=g(b);return b}})})(jQuery);
/* jQuery hashchange event - v1.3 - 7/21/2010 http://benalman.com/projects/jquery-hashchange-plugin/ Copyright (c) 2010 "Cowboy" Ben Alman, Dual licensed under the MIT and GPL licenses. http://benalman.com/about/license/ */
;(function($,e,b){var c="hashchange",h=document,f,g=$.event.special,i=h.documentMode,d="on"+c in e&&(i===b||i>7);function a(j){j=j||location.href;return"#"+j.replace(/^[^#]*#?(.*)$/,"$1")}$.fn[c]=function(j){return j?this.bind(c,j):this.trigger(c)};$.fn[c].delay=50;g[c]=$.extend(g[c],{setup:function(){if(d){return false}$(f.start)},teardown:function(){if(d){return false}$(f.stop)}});f=(function(){var j={},p,m=a(),k=function(q){return q},l=k,o=k;j.start=function(){p||n()};j.stop=function(){p&&clearTimeout(p);p=b};function n(){var r=a(),q=o(m);if(r!==m){l(m=r,q);$(e).trigger(c)}else{if(q!==m){location.href=location.href.replace(/#.*/,"")+q}}p=setTimeout(n,$.fn[c].delay)}$.browser.msie&&!d&&(function(){var q,r;j.start=function(){if(!q){r=$.fn[c].src;r=r&&r+a();q=$('<iframe tabindex="-1" title="empty"/>').hide().one("load",function(){r||l(a());n()}).attr("src",r||"javascript:0").insertAfter("body")[0].contentWindow;h.onpropertychange=function(){try{if(event.propertyName==="title"){q.document.title=h.title}}catch(s){}}}};j.stop=k;o=function(){return a(q.location.href)};l=function(v,s){var u=q.document,t=$.fn[c].domain;if(v!==s){u.title=h.title;u.open();t&&u.write('<script>document.domain="'+t+'"<\/script>');u.close();q.location.hash=v}}})();return j})()})(jQuery,this);
/* Slimbox v2.04 (c) 2007-2010 Christophe Beyls <http://www.digitalia.be> MIT-style license.*/
;(function(w){var E=w(window),u,f,F=-1,n,x,D,v,y,L,r,m=!window.XMLHttpRequest,s=[],l=document.documentElement,k={},t=new Image(),J=new Image(),H,a,g,p,I,d,G,c,A,K;w(function(){w("body").append(w([H=w('<div id="lbOverlay" />')[0],a=w('<div id="lbCenter" />')[0],G=w('<div id="lbBottomContainer" />')[0]]).css("display","none"));g=w('<div id="lbImage" />').appendTo(a).append(p=w('<div style="position: relative;" />').append([I=w('<a id="lbPrevLink" href="#" />').click(B)[0],d=w('<a id="lbNextLink" href="#" />').click(e)[0]])[0])[0];c=w('<div id="lbBottom" />').appendTo(G).append([w('<a id="lbCloseLink" href="#" />').add(H).click(C)[0],A=w('<div id="lbCaption" />')[0],K=w('<div id="lbNumber" />')[0],w('<div style="clear: both;" />')[0]])[0]});w.slimbox=function(O,N,M){u=w.extend({loop:false,overlayOpacity:0.8,overlayFadeDuration:400,resizeDuration:400,resizeEasing:"swing",initialWidth:250,initialHeight:250,imageFadeDuration:400,captionAnimationDuration:400,counterText:"Image {x} of {y}",closeKeys:[27,88,67],previousKeys:[37,80],nextKeys:[39,78]},M);if(typeof O=="string"){O=[[O,N]];N=0}y=E.scrollTop()+(E.height()/2);L=u.initialWidth;r=u.initialHeight;w(a).css({top:Math.max(0,y-(r/2)),width:L,height:r,marginLeft:-L/2}).show();v=m||(H.currentStyle&&(H.currentStyle.position!="fixed"));if(v){H.style.position="absolute"}w(H).css("opacity",u.overlayOpacity).fadeIn(u.overlayFadeDuration);z();j(1);f=O;u.loop=u.loop&&(f.length>1);return b(N)};w.fn.slimbox=function(M,P,O){P=P||function(Q){return[Q.href,Q.title]};O=O||function(){return true};var N=this;return N.unbind("click").click(function(){var S=this,U=0,T,Q=0,R;T=w.grep(N,function(W,V){return O.call(S,W,V)});for(R=T.length;Q<R;++Q){if(T[Q]==S){U=Q}T[Q]=P(T[Q],Q)}return w.slimbox(T,U,M)})};function z(){var N=E.scrollLeft(),M=E.width();w([a,G]).css("left",N+(M/2));if(v){w(H).css({left:N,top:E.scrollTop(),width:M,height:E.height()})}}function j(M){if(M){w("object").add(m?"select":"embed").each(function(O,P){s[O]=[P,P.style.visibility];P.style.visibility="hidden"})}else{w.each(s,function(O,P){P[0].style.visibility=P[1]});s=[]}var N=M?"bind":"unbind";E[N]("scroll resize",z);w(document)[N]("keydown",o)}function o(O){var N=O.keyCode,M=w.inArray;return(M(N,u.closeKeys)>=0)?C():(M(N,u.nextKeys)>=0)?e():(M(N,u.previousKeys)>=0)?B():false}function B(){return b(x)}function e(){return b(D)}function b(M){if(M>=0){F=M;n=f[F][0];x=(F||(u.loop?f.length:0))-1;D=((F+1)%f.length)||(u.loop?0:-1);q();a.className="lbLoading";k=new Image();k.onload=i;k.src=n}return false}function i(){a.className="";w(g).css({backgroundImage:"url("+n+")",visibility:"hidden",display:""});w(p).width(k.width);w([p,I,d]).height(k.height);w(A).html(f[F][1]||"");w(K).html((((f.length>1)&&u.counterText)||"").replace(/{x}/,F+1).replace(/{y}/,f.length));if(x>=0){t.src=f[x][0]}if(D>=0){J.src=f[D][0]}L=g.offsetWidth;r=g.offsetHeight;var M=Math.max(0,y-(r/2));if(a.offsetHeight!=r){w(a).animate({height:r,top:M},u.resizeDuration,u.resizeEasing)}if(a.offsetWidth!=L){w(a).animate({width:L,marginLeft:-L/2},u.resizeDuration,u.resizeEasing)}w(a).queue(function(){w(G).css({width:L,top:M+r,marginLeft:-L/2,visibility:"hidden",display:""});w(g).css({display:"none",visibility:"",opacity:""}).fadeIn(u.imageFadeDuration,h)})}function h(){if(x>=0){w(I).show()}if(D>=0){w(d).show()}w(c).css("marginTop",-c.offsetHeight).animate({marginTop:0},u.captionAnimationDuration);G.style.visibility=""}function q(){k.onload=null;k.src=t.src=J.src=n;w([a,g,c]).stop(true);w([I,d,g,G]).hide()}function C(){if(F>=0){q();F=x=D=-1;w(a).hide();w(H).stop().fadeOut(u.overlayFadeDuration,j)}return false}})(jQuery);