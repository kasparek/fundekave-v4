/**GOOGLE MAPS*/
var GooMapi={loading:false,loaded:false,call:[],load:function(f){var o=GooMapi;if(o.loaded)return true;if(o.call.indexOf(f)==-1)o.call.push(f);if(o.loading)return;o.loading=true;var d=window.document,script=d.createElement('script');script.setAttribute('src','http://maps.google.com/maps/api/js?v=3&sensor=false&callback=GooMapi.c');d.documentElement.firstChild.appendChild(script);},c:function(){var o=GooMapi;o.loading=false;o.loaded=true;while(o.call.length>0){var f=o.call.shift();f();}}
,init:function(){
	var o=GooMapi;
	$(".geoInput").hide().change(o.staticSelector);
	$(".geoInput").each(function(){if($(this).val().length>0)$(this).change();});
	listen('geoselector','click',o.geoSelectorClick);
	$(".mapLarge").each(function(){
	var id=$(this).attr('id').replace('map','');
	if(!$(this).hasClass('hidden') && $("map"+id+"holder",this).length==0 )
		o.show(id);
	});
	listen('mapThumbLink','click',o.thumbClick);
}
,thumbClick:function (){
	var id=$(this).attr('id').replace('mapThumb','');$(this).addClass('hidden');$('#map'+id).removeClass('hidden');GooMapi.show(id);return false;
}
,geoSelectorClick:function() {
	var o=GooMapi,data=o.editorData(),rel=$(this).attr('rel');
	data.journey=$(this).hasClass('journey');
	data.dataEl=$('#'+rel);
	o.mapEditor();
	return false;
}
,staticSelector:function(e){
	var p=$.trim($(this).val()).split("\n"),id=$(this).attr('id'),w=$(this).width(),h=$(this).height();
	if(p.length>0)if(p[0]=='')p=[];
	var url='http://maps.google.com/maps/api/staticmap?size='+w+'x'+h+'&markers='+p[p.length-1]+'&maptype=terrain&sensor=false'+(p.length>1?'&path='+p.join('|'):'');
	if($('#'+id+'Thumb').length>0) {
		if(p.length==0){$('#'+id+'Thumb').remove();$('#'+id+'Source').remove();$('#'+id).hide();}
		else $('#'+id+'Thumb').attr('src',url);
	} else {
		$(this).after('<a href="#" id="'+id+'Source" class="leftbox">S</a><img id="'+id+'Thumb" src="'+url+'" width="'+w+'" height="'+h+'" alt="Google Maps" />');
		$('#'+id+'Thumb').click(function(){$('.geoselector[rel='+id+']').click()});
		$('#'+id+'Source').click(function(){$('#'+id+'Thumb').toggle();$('#'+id).toggle();return false;});
	}
}
,editorData:function() {
	var o=GooMapi,id='Editor';
	if(o.li[id])return o.li[id].li[0];
	$("body").append('<div id="map'+id+'"></div>');
	o.li[id]=new o.hold(document.getElementById("map"+id));
	var data=new o.data();
	data.parent=o.li[id];
	o.li[id].li=[data];
	return data;
}
,info:null
,li:{}
/* distance R -3440NM 6371KM */
,distance:function(lat1,lon1,lat2,lon2,R){if(!R)R=3440;var pr=Math.PI/180,dLat=(lat2-lat1)*pr,dLon=(lon2-lon1)*pr,a=Math.sin(dLat/2)*Math.sin(dLat/2)+Math.cos(lat1*pr)*Math.cos(lat2*pr)*Math.sin(dLon/2)*Math.sin(dLon/2),c=2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a)),d=R*c;return d;}
/* degrees, mins, secs to decimal degrees - possible format 20.5468,15.1568 or 20 10 30 N,15 23 40 W */
,posFormat:function(p){
	p=$.trim(p);
	var dir=p.charAt(p.length-1).toUpperCase();
	if(dir=='W' || dir=='E' || dir=='N' || dir=='S'){
		var posArr=p.substr(0,p.length-2).split(' '),d=posArr[0]-0,m=posArr.length>1?posArr[1]-0:0,s=posArr.length>2?posArr[2]-0:0,sign=(dir=='W' || dir=='S')?-1:1;
		return (((s/60+m)/60)+d)*sign;
	}
	return p-0;
}
,hold:function(mapEl){
	this.mapEl=mapEl;
	this.li=[];
	this.map = null;
	this.geocoder=null;
	this.init=function(){if(!this.map){
		this.geocoder=new google.maps.Geocoder();
		this.map=new google.maps.Map(this.mapEl,{mapTypeId:google.maps.MapTypeId.TERRAIN});
		this.map.setCenter(new google.maps.LatLng(50,0));this.map.setZoom(5);}
	}
}
,data:function(){
	this.parent=null;
	this.dataEl=null;
	this.title='';
	this.infoEl=null;
	this.overEl=null;
	this.marker=null;
	this.path=null;
	this.journey=false;
	this.distance=0;
	this.updateMarker = function(latLng){
		if(!this.marker)this.marker=new google.maps.Marker({title:this.title});
		this.marker.setPosition(latLng);
		this.marker.setMap(this.parent.map);
		if(this.infoEl)this.marker.htmlInfo=$(this.infoEl).html();
		if(this.overEl)this.marker.htmlOver=$(this.overEl).html();
	};
	this.resetWP=function(){if(this.path){this.path.setPath([]);}};
	this.addWP=function(latLng){
		if(!this.path)this.path=new google.maps.Polyline({map:this.parent.map,path:[],strokeColor:"#ff0000",strokeOpacity:1.0,strokeWeight:2,geodesic:true});
		if(!this.path.getMap())this.path.setMap(this.parent.map);
		var wpList=this.path.getPath();
		wpList.push(latLng);
		this.path.setPath(wpList);
	};
	this.updateDistance=function(){
		this.distance=0;
		if(!this.path)return;
		var wpList=this.path.getPath();
		if(wpList.length>1){
			for(i=1;i<wpList.length;i++){
				this.distance+=GooMapi.distance(wpList.getAt(i-1).lat(),wpList.getAt(i-1).lng(),wpList.getAt(i).lat(),wpList.getAt(i).lng());
			}
		}
		this.distance = Math.round(this.distance*10)/10;
	};
	this.get=function(){
		var result=[],val=$(this.dataEl).val();
		if(val.length>0) {
			arr = val.split("\n");
			for(i=0;i<arr.length;i++) {
				arr[i] = arr[i].split(','); 
				if(arr[i].length==2) {
					arr[i][0]=GooMapi.posFormat(arr[i][0]);
					arr[i][1]=GooMapi.posFormat(arr[i][1]);
					if(arr[i][0]==0 && arr[i][1]==0) arr[i] = false;
				} else {
					arr[i] = false;
				}
			}
			for(i=0;i<arr.length;i++) {
				if(arr[i]!==false)result.push(arr[i]);
			}
		}
		return result;
	};
}

,showCallback:null,showId:null
,show:function(id,f){
	var o=GooMapi;
	if(id)o.showId=id;
	if(f)o.showCallback=f;
	if(!o.load(o.show))return;
	var md=document.getElementById("map"+o.showId);
	if(!md)return;
	if(!o.li[o.showId]){
		$(md).append('<div id="map'+o.showId+'holder" style="width: 100%;height: '+$(md).height()+'px;"></div>');
		o.li[o.showId]=new o.hold(document.getElementById("map"+o.showId+"holder"));
		o.li[o.showId].init();
		$('.mapsData',md).each(function(){
			var data=new o.data();
			data.parent=o.li[o.showId];
			data.dataEl=$('.geoData',this);
			data.title=$(data.dataEl).attr('title');
			data.infoEl=$('.geoInfo',this);
			data.overEl=$('.geoOver',this);
			o.li[o.showId].li.push(data);
		});
	}
	
	var h=o.li[o.showId],bounds=new google.maps.LatLngBounds();
	for(var i=0;i<h.li.length;i++) {
		var data=h.li[i],l=data.get(),ll=l.length;
		if (ll>0) {
			var p=new google.maps.LatLng(l[ll-1][0],l[ll-1][1]);
			data.updateMarker(p);bounds.extend(p);
			data.resetWP();for(var j=0;j<ll;j++){p=new google.maps.LatLng(l[j][0],l[j][1]);data.addWP(p);bounds.extend(p);}
			data.updateDistance();
		} else { 
			if(data.marker){data.marker.setMap(null);data.marker=null;}
			if(data.path){data.path.setMap(null);data.path=null;}
		}
		if(data.marker) {
			google.maps.event.clearListeners(data.marker);
			if(data.infoEl){
				data.marker.htmlInfo=data.marker.htmlInfo.replace('[[DISTANCE]]',data.distance);
				google.maps.event.addListener(data.marker,'click',function(e){
				var o=GooMapi;
				if(!o.info)o.info=new google.maps.InfoWindow();o.info.setContent(this.htmlInfo);o.info.open(this.getMap(),this);});
			}
			if(data.overEl){
				 google.maps.event.addListener(data.marker,'mouseover',function(event){$(this.htmlOver).removeClass('hidden').css('position','absolute');});
				 google.maps.event.addListener(data.marker,'mouseout',function(event){$(this.htmlOver).addClass('hidden');});
			}
		}
	}
	if(!bounds.isEmpty()) {
		o.fit.push({m:h.map,b:bounds}); 
		setTimeout(o.fitLater,50);
	}
	if($.isFunction(o.showCallback)){o.showCallback();o.showCallback=null;}
}
,fit:[]
,fitLater:function(){var o=GooMapi;while(o.fit.length>0){var b=o.fit.pop();b.m.fitBounds(b.b);}}

,mapSearchHTML:'<div id="mapSearch" style="float:left;"><input id="mapaddress" value="" style="width:300px;margin-right:5px;margin-top:6px;"/><button id="mapSearchButt">Find</button></div>'
,mapEditor:function(){
	var o=GooMapi;
	if(!Lazy.load(Sett.ll.ui,o.mapEditor))return;
	if(!o.load(o.mapEditor))return;
	var data=o.editorData();
	data.parent.init();
	$("#mapEditor").dialog({
			modal: true,
			minWidth:640,
			minHeight:200,
			width: $(window).width()*0.8, 
			height: $(window).height()*0.8,
			resizeStop:o.resize,
			buttons: {
				'Remove Last':function() {
					var data=o.editorData();
					if(data.path){
						data.path.getPath().pop();
						var path=data.path.getPath();
						data.updateMarker(path.getAt(path.getLength()-1));
					}
				},
				'Clear': function() {
					var data=o.editorData();
					if(data.marker){data.marker.setMap(null);data.marker=null;}
					if(data.path){data.path.setMap(null);data.path=null;}
				},
				'Cancel': function() {
					$(this).dialog('close');
				},
				'Save': function() {
					var data=o.editorData();
					$(this).dialog('close');
					$(data.dataEl).val('');
					if(data.journey===true) {
						if(data.path) {var l=[];
						data.path.getPath().forEach(function(latLng){l.push(latLng.toUrlValue(4));});
						$(data.dataEl).val(l.join("\n"));
						}
					} else {
						if(data.marker)$(data.dataEl).val(data.marker.getPosition().toUrlValue(4));
					}
					$(data.dataEl).change();
				}
			}
		});
		
	$(".ui-dialog-buttonpane").prepend(o.mapSearchHTML);
	$("#mapSearchButt").unbind('click',o.address).button().click(o.address);
	$("#mapaddress").unbind('keydown',o.addressKey).keydown(o.addressKey);
		
  o.show('Editor',function(){
		var o=GooMapi,data=o.editorData();
		google.maps.event.clearListeners(data.parent.map, 'click');
		google.maps.event.addListener(data.parent.map,'click',o.editorClick);
		data.updateDistance();
		if(data.distance>0)$("#mapEditor").dialog("option","title",data.distance+'NM');
		o.resize();
	});
	
}
,resize:function(){
var o=GooMapi,m=$("#mapEditor"),d=m.dialog(),data=o.editorData();m.css('width',d.width()+'px').css('height',d.height()+'px');google.maps.event.trigger(data.parent.map, 'resize');
}
,editorClick:function(e){
	var o=GooMapi,data=o.editorData();
	if(data.journey){
		data.addWP(e.latLng);
		data.updateMarker(e.latLng);
		data.updateDistance();
		if(data.distance>0)$("#mapEditor").dialog("option","title",data.distance+'NM');
	} else data.updateMarker(e.latLng);
}
,addressKey:function(e){if(e.keyCode==13){GooMapi.address();} }
,address:function(){
	var o=GooMapi,data=o.editorData(),address={'address':document.getElementById('mapaddress').value}; 
	data.parent.geocoder.geocode(address,function(results,status){
		if (status == google.maps.GeocoderStatus.OK){
			var data=o.editorData(),g=results[0].geometry;
			o.fit.push({m:data.parent.map,b:g.bounds}); 
			setTimeout(o.fitLater,50);
			data.updateMarker(g.location);
			if(data.journey){
				data.resetWP();
				data.addWP(g.location);
			}
		}
	});
}

};

/**INITIALIZATION ON DOM*/ 
function boot() {
	buttonInit();
	if($("#errormsgJS").html().length>0) $("#errormsgJS").css('padding','1em'); else $("#errormsgJS").hide(); 
	if($("#okmsgJS").html().length>0) $("#okmsgJS").css('padding','1em'); else $("#okmsgJS").hide();
	var w = $(window).width();
	if(w>800) $("#loginInput").focus();
	if ($("#sidebar").length == 0){$('body').addClass('bodySidebarOff'); }
	$("textarea[class*=expand]").autogrow().keydown();
	$(".opacity").bind('mouseenter',function(){ $(this).fadeTo("fast",1); }).bind('mouseleave',function(){ $(this).fadeTo("fast",0.2); });
	fajaxInit();
	fconfirmInit();
	switchOpen();
	$(',popupLink').click(function(){openPopup(this.href);return false;});
	Resize.init();	 
	GooMapi.init();
	if($(".hash").length>0){Hash.init();}
	slimboxInit();
	Fullscreen.init();
	if(Sett.gooAnal)gaSSDSLoad(Sett.gooAnal);
	tabsInit();
	fuupInit();
	datePickerInit();
	if(parseInt(Sett.user)>0) {
		Richta.map();
		Draft.init();
		$("#recipient").change(avatarfrominput);
		$('#ppinput').hide();
		$("#saction").change( function(evt){if($("#saction option:selected").attr('value') == 'setpp') $('#ppinput').show(); else $('#ppinput').hide(); });
		$("#recipientList").change(function(evt) {
			var str = "";
			var combo = $("#recipientList");
			if(combo.attr("selectedIndex")>0) $("#recipientList option:selected").each( function(){str += $(this).text() + " "; });
			$("#recipient").attr("value", str);
			combo.attr("selectedIndex", 0);
			avatarfrominput();
		});
		GaleryEdit.init();	
		if(parseInt(Sett.msgTi)>0)Msg.check();
		var perm = $("#accessSel");
		if(perm.length>0) {
			perm.change(function(){
			var v=$(this).val();
			if(v==0)$("#rule1").show();else $("#rule1").hide();}).change();
		}
	}
};
/**INIT jQuery UI and everything possibly needed for ajax forms and items*/
function jUIInit(){if(!Lazy.load(Sett.ll.ui,jUIInit))return;buttonInit();tabsInit();datePickerInit();Richta.map();fajaxInit();fconfirmInit();Draft.init();GooMapi.init();fuupInit();slimboxInit();$("textarea[class*=expand]").autogrow().keydown()};
function datePickerInit(){if($(".datepicker").length>0){if(!Lazy.load(Sett.ll.ui,datePickerInit))return;$.datepicker.setDefaults($.extend({showMonthAfterYear:false},$.datepicker.regional['cs']));$(".datepicker").datepicker();}};
function slimboxInit(){if(!/android|iphone|ipod|series60|symbian|windows ce|blackberry/i.test(navigator.userAgent)){$("a[rel^='lightbox']").slimbox({overlayFadeDuration : 100, resizeDuration : 100, imageFadeDuration : 100, captionAnimationDuration : 100}, null, function(el){return (this == el) || ((this.rel.length > 8) && (this.rel == el.rel)); }); } }
function fuupInit(){if($(".fuup").length>0){ if(!Lazy.load(Sett.ll.swf,fuupInit))return; $(".fuup").each(function(i){ swfobject.embedSWF(Sett.assUrl+"load.swf", $(this).attr('id'), "120", "25", "10.0.12", Sett.assUrl+"expressInstall.swf", {file:Sett.assUrl+"Fuup.swf",config:"fuup."+$(this).attr('id')+"."+gup('k',$(".fajaxform").attr('action'))+".xml",containerId:$(this).attr('id')},{wmode:'transparent',allowscriptaccess:'always'}); }); }}
function tabsInit(){if($("#tabs").length>0){if(!Lazy.load(Sett.ll.ui,tabsInit))return;$("#tabs").tabs();}};
function buttonInit(){if($('.uibutton').length>0){if(!Lazy.load(Sett.ll.ui,buttonInit))return;$('.uibutton').button();}}
/**request init*/
function friendRequestInit(text){$('#friendrequest').remove(); 
$("#menu-secondary-holder").after(text); 
$('#friendrequest').removeClass('hidden').show('slow'); fajaxInit(); $('#cancel-request').unbind('click',Fajax.form).bind('click',function(event){remove('friendrequest');event.preventDefault();return false;}); };
/**ajax link init*/
function fajaxInit(){Fajax.init();listen('galerynext','click',ImgNext.click);};
function fconfirmInit(event){$('.confirm').each(function(){ var fajaxaformParent=false; if(this.form) fajaxaformParent = $(this.form).hasClass('fajaxform'); if(!$(this).hasClass('fajaxa') && !fajaxaformParent){$(this).bind('click',onConfirm); } }); };
function onConfirm(e) {	if(!confirm($(e.currentTarget).attr("title"))){preventAjax = true; e.preventDefault();	} };
/**simple functions*/
function shiftTo(y){if(!y) y=0;$(window).scrollTop(y);}
function enable(id){$('#'+id).removeAttr('disabled');};
function remove(id,notween){if(notween==1){$('#'+id).remove(); }else{ $('#'+id).hide('slow',function(){$('#'+id).remove()}); } };
function switchOpen(){$('.switchOpen').click(function(){$('#'+this.rel).toggleClass('hidden');return false;});};
function openPopup(href){ window.open(href, 'fpopup', 'scrollbars=' + gup("scrollbars", href) + ',toolbar=' + gup("toolbar", href) + ',menubar=' + gup("menubar", href) + ',status=' + gup("status", href) + ',resizable=' + gup("resizable", href) + ',width=' + gup("width", href) + ',height=' + gup("height", href) + ''); };
function listen(c,e,f){$("."+c).unbind(e,f).bind(e,f);};
function gup(name,url){name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]"); var regex = new RegExp("[\\?&|]" + name + "=([^&#|]*)"), results = regex.exec(url); return (results === null) ? (0) : (results[1]); };
function msg(type,text){$("#"+type+"msgJS").css('padding','1em').html( text ).show('slow').delay(10000).hide('slow'); };
function redirect(dir){window.location.replace(dir);};
/**AVATAR FROM input IN fpost*/
function avatarfrominput(evt) {Fajax.add('username', $("#recipient").attr("value"));Fajax.add('call', 'fajaxInit');Fajax.send('post-avatarfrominput','');}
/**IMAGE UPLOADING TOOL HANDLERS - FUUP*/ 
function fuupUploadComplete(){var item=$('#item').attr('value');if(item>0)Fajax.add('item', item);Fajax.add('call','jUIInit');Fajax.send('item-image',gup('k',$(".fajaxform").attr('action')));}
/**AJAX GALLERY EDITING THUMBNAILS LOADING AND REFRESHING*/
var GaleryEdit={
numTotal:0,numLoaded:0,newLi:[],updLi:[],run:false
,init:function(){
	var o=GaleryEdit;
	o.numTotal=parseInt($("#fotoTotal").text());
	if(o.numTotal>0 && $('#fotoList').length>0)o.load(0,10);
}
,check:function(){Fajax.send('page-fuup',gup('k',$(".fajaxform").attr('action')));}
,refresh:function(n,u,t){
	var o=GaleryEdit;
	o.numTotal=parseInt(t);$("#fotoTotal").text(o.numTotal);
	if(n.length>0)o.newLi=o.newLi.concat(n.split(';'));
	if(u.length>0)o.updLi=o.updLi.concat(u.split(';'));
	if(!o.run)o.next();
}
,next:function(){
	var o=GaleryEdit;
	if(o.updLi.length>0)o.load(o.updLi.pop(),1,'U'); 
	else if(o.newLi.length>0)o.load(o.newLi.pop(),1); 
	else if(o.numLoaded<o.numTotal)o.load(0,10);
	else o.run=false;
}
,load:function(item,offset,type){
	var o=GaleryEdit,f=Fajax;
	o.run=true;
	if(item>0){
		f.add('item', item);
		if(type=='U'){f.add('result','foto-'+item);f.add('resultProperty','$replaceWith');}
	}else{f.add('total',o.numTotal);f.add('seq',o.numLoaded);}
	if(type!='U'){f.add('result','fotoList');f.add('resultProperty','$append');}
	f.add('offset', offset);
	f.add('call','jUIInit');	
	f.add('call','GaleryEdit.bindDelete');
	f.send('galery-editThumb',gup('k',$(".fajaxform").attr('action')));
}
,loadHandler:function(num){
	var o=GaleryEdit,n=parseInt(num);
	if(n>0)o.numLoaded+=n;
	o.next();
}
,bindDelete:function(){listen('deletefoto','click',GaleryEdit.del);}
,del:function(e) {
	var o=GaleryEdit,f=Fajax,l=$(this).attr("id").split("-");
	if(confirm($(this).attr("title"))) {
		f.add('item', l[1]);
		f.send('item-delete');
		$('#foto-'+l[1]).hide('slow',function(){$('#foto-'+l[1]).remove()});
		o.numTotal--;
		$("#fotoTotal").text(o.numTotal);
	}
	return false;
}
}; 

/**GALERY NEXT WITH PRELOADING*/
var ImgNext={r:false,i:null,p:null,next:null,top:0,xhr:null,init:function(){var o=ImgNext;if(!o.r){o.r=true;o.i=$("#detailFoto");o.i.bind('load',o.loaded);o.p=$(".showProgress");}},click:function(e){var o=ImgNext,m=gup('m',this.href);if(Fajax.xhrList[m])return false;o.top=$(window).scrollTop();if(o.xhr){o.xhr.abort();o.xhr=null;}o.init();o.i.show();var h=o.p.height();o.p.css('height',(h>0?h:$(window).height())+'px');if(o.next){o.i.attr('src',o.next);o.next=null;}else o.i.hide();Fajax.a(e);return false;},loaded:function(){var o=ImgNext;o.init();o.i.show();o.p.css('height','auto');if(Fullscreen.state)imgResizeToFit(o.i);Slideshow.next();if(o.top)$(window).scrollTop(o.top);},xhrHand:function(currentUrl,nextUrl){var o=ImgNext;o.init();if(currentUrl && currentUrl!=o.i.attr('src'))o.i.attr('src',currentUrl);if(nextUrl)o.xhr=$.get(nextUrl,function(data){o.next=nextUrl;o.xhr=null;});}};
/**SLIDESHOW*/
var Slideshow={f:function(){$("#nextButt").click();},on:false,t:0,s:5,toggle:function(){var o=Slideshow;o.on=!o.on;o.next();},next:function(){var o=Slideshow;clearTimeout(o.t);if(o.on)o.t=setTimeout(o.f,o.s*1000);}};
/**FULLSCREEN*/
var Fullscreen={el:null,tool:null,state:null,init:function(){var o=Fullscreen;listen('galeryFullSwitch','click',o.go);$("#fullscreenLeave").click(o.go);$("#fullscreenPrevious").click(function(){$("#prevButt").click();return false;});$("#fullscreenNext").click(function(){$("#nextButt").click();return false;});o.to=$("#fullscreenToolbar");o.to.hover(function(){$(this).fadeTo("slow",1.0);},function(){$(this).fadeTo("slow",0.2);});var fs=$("#fullscreenSlideshow");if(Slideshow.on) fs.addClass('fullscreenSlideshowOn'); else fs.removeClass('fullscreenSlideshowOn');fs.click(function(){$(this).toggleClass('fullscreenSlideshowOn');Slideshow.toggle();return false;});o.el=$('#fullscreenBox');},go:function(div){var o=Fullscreen,d=$(document.documentElement),w=$(window);if(o.el)div=o.el;if(!div && !o.state.el)return;if(!div)div=o.state.el;if(!o.state){o.state={el:div,parent:div.parent(),index:div.parent().children().index(div),x:w.scrollLeft(),y:w.scrollTop()};div.addClass('fullscreen');$('body').append(div).css('overflow','hidden');w.scrollTop(0).scrollLeft(0);d.bind('keyup',o.key);o.to.removeClass('hidden').delay(100).fadeTo("fast", 1).fadeTo("slow", 0.3);w.bind('resize',ImgNext.loaded).resize();var hint=$("#fullscreenHint").removeClass('hidden'),hh=hint.height(),wc=(w.height()-hh)/2;hint.css('top',wc).show().delay(1000).fadeOut('slow');}else{div.removeClass('fullscreen');Slideshow.on=false;w.unbind('resize',ImgNext.loaded);if(o.state.index>=o.state.parent.children().length) o.state.parent.append(div);else div.insertBefore(o.state.parent.children().get(o.state.index));$('body').css('overflow', 'auto');d.unbind('keyup',o.key);$('#detailFoto').css('position','inherit').css('width','auto').css('height','auto').css('margin','0 auto');o.to.addClass('hidden');w.scrollTop(o.state.x).scrollLeft(o.state.y);o.state=null;}return false;},key:function(e){if(e.keyCode==27)Fullscreen.go();if(e.keyCode==32)$("#nextButt").click();}};
/**IMAGE RESIZE TO FIT*/
function imgResizeToFit(img,fitTo,fit){if(!fit)fit=0.9;if(!fitTo)fitTo=$(window);var ww=fitTo.width()*fit,wh=fitTo.height()*fit;img.css('width','auto').css('height','auto');var iw=img.width(),ih=img.height(),tw=ww,th=ih*ww/iw;if(th-wh>1){iw='auto';ih=wh;}else{iw=tw;ih='auto';}img.css('width',iw).css('height',ih).css('position','absolute').css('left',((fitTo.width()-img.width())/2)+'px').css('top',((fitTo.height()-img.height())/2)+'px');};
/**RESIZE HANDLER-CLIENT INFO TO SERVER*/
var Resize={t:0,init:function(){$(window).resize(Resize.on).resize();},on:function(){var o=Resize;clearTimeout(o.t);o.t=setTimeout(o.send,500);},send:function(){var w=$(window),ww=w.width(),wh=w.height(),cw=parseInt(Sett.cw)*1,ch=parseInt(Sett.ch)*1;if(w!=cw || h!=ch){Fajax.add('size',ww+'x'+wh);Fajax.send('user-clientInfo',-1);}}};
/**CUSTOM AJAX REQUEST BUILDER/HANDLER * send and process ajax request - if problems with %26 use encodeURIComponent*/
var Fajax={xhrList:{},top:0,formStop:false,formSent:null
,init:function(){if($(".fajaxform").length>0)listen('button','click',Fajax.form);if($(".fajaxpager").length>0)listen('fajaxpager','click',Fajax.pager);listen('fajaxa','click',Fajax.a);}
,pager:function(){Hash.set('post-page/p:'+gup('p',this.href)+'/fpost');return false;}
,a:function(e){var o=Fajax,t=$(e.currentTarget),href=t.attr('href');o.top=null;if(t.hasClass('confirm')){if(!confirm(t.attr("title")))return false;}var k=gup('k',href),id=t.attr("id"),m=gup('m',href);if(!k)k=0;var action=m+'/'+gup('d',href)+'/'+k;if(id)action+='/'+id;if(t.hasClass('keepScroll'))o.top=$(window).scrollTop();if(t.hasClass('progress')){var bar=$(".showProgress"),h=bar.height();bar.addClass('lbLoading').css('height',(h>0?h:$(window).height())+'px').children().hide();}if(t.hasClass('hash')){Hash.set(action);return false;}o.action(action);return false;}
,action:function(action){var o=Fajax,l=action.split('/'),m=l[0],d=l[1],k=l[2],id=l[3],res=false,prop=false;if(k==0)k=null;if(d){l=d.split(';');while(l.length>0) {var row=l.shift().split(':');o.add(row[0], row[1]);if(row[0]=='result')res=true;if(row[0]=='resultProperty')prop=true;}}if(id){if(!res)o.add('result',id);if(!prop)o.add('resultProperty','$html');}o.send(m,k);return false;}
,form:function(e){var o=Fajax,t=e.currentTarget,jt=$(t);e.preventDefault();if(o.formStop==true){o.formStop=false;return false;}if(jt.hasClass('confirm'))if(!confirm(jt.attr("title")))return false;if(jt.hasClass('draftdrop'))Draft.hasDropAll=true;$('.errormsg').hide('slow',function(){$(this).html('');});$('.okmsg').hide('slow',function(){$(this).html('');});o.formSent=t.form;$('.button',o.formSent).attr('disabled',true);var arr=$(o.formSent).formToArray(false),action,res=false,prop=false;while(arr.length>0){var v=arr.shift();if(v.name=='m')action=v.value;else o.add(v.name,v.value);if(v.name=='result')res=true;if(v.name=='resultProperty')prop=true;}if(!res)o.add('result',$(o.formSent).attr("id"));if(!prop)o.add('resultProperty','$html');o.add('action',t.name);o.add('k',gup('k',o.formSent.action));o.send(!action?gup('m',o.formSent.action):action,gup('k',o.formSent.action));return false;}
,XMLReq:{a:[],s:'<Item name="{KEY}"><![CDATA[{DATA}]]></Item>',reset:function(){Fajax.XMLReq.a=[];},add:function(k,v){Fajax.XMLReq.a.push(Fajax.XMLReq.s.replace('{KEY}',k).replace('{DATA}',v));},get:function(){var s='<FXajax><Request>'+Fajax.XMLReq.a.join('')+'</Request></FXajax>';Fajax.XMLReq.a=[];return s;}}
,add:function(k,v){Fajax.XMLReq.add(k,v)}
,send:function(action,k){var data=Fajax.XMLReq.get();if(k==0)k=null;if(!k)k=gup('k',document.location);if(k==-1)k='';$.ajaxSetup({scriptCharset:"utf-8",contentType:"text/xml; charset=utf-8"});Fajax.xhrList[action]=$.ajax({type:"POST",url:"index.php?m="+action+"-x"+((k)?("&k="+k):('')),dataType:'xml',processData:false,cache:false,data:data,complete:function(ajaxRequest,textStatus){Fajax.xhrList[action]=null;$(ajaxRequest.responseXML).find("Item").each(function(){
var item=$(this),command='',target=item.attr('target'),prop=item.attr('property'),text=item.text();
switch(target){
case 'document':command=target+'.'+prop+'=text;';break;
case 'call':
	var par=text.split(','),p=[];for(var i=0;i<par.length;i++)p.push("par["+i+"]");
	command=prop+"("+(p.length>0?p.join(","):"")+");"; 
	break;
	default: 
	switch(prop){case'void': break;
	default:
	if(prop[0]=='$'){command='$("#'+target+'").'+prop.replace('$','')+'(text);';}
	else{command='$("#'+target+'").attr("'+prop+'",text);';}
	};
	};if(command.length>0){
	eval(command);
	} if(Fajax.formSent){$('.button',Fajax.formSent).removeAttr('disabled');Fajax.formSent=null;Draft.dropAll();}})}});}
};
/**DRAFT - temporary textarea data storing*/
var Draft={timer:3000,li:{},ta:function(id){this.id=id;this.old='';this.t=0;this.text=function(){return $.trim($('#'+this.id).val());};this.backup=function(){this.old=this.text();};this.restore=function(){$('#'+id).val(this.old);this.old='';};this.check=function(){this.backup();$(this.id).attr('disabled',true);Fajax.add('result', this.id);Fajax.send('draft-check');};this.setT=function(f,t){this.t=setTimeout(f,t);};this.clearT=function(){if(this.t)clearTimeout(this.t);this.t=null;};},init:function(){var o=Draft,l=[],f;listen('submit','click',o.submit);listen('draftable', 'keyup',o.key);if(window.location.hash=='#dd' || gup('dd',window.location)==1){o.dropAll(true);window.location.hash='';}$('.draftable').each(function(){var id=$(this).attr('id');if(!o.li[id])o.li[id]=new o.ta(id);o.li[id].check();});$('.draftable').each(function(){f=this.form;l.push($(this).attr('id'));});if(l.length>0) {$("#draftablesList").remove();$(f).append('<input id="draftablesList" type="hidden" name="draftable" value="'+l.join(',')+'" />');}},backup:function(id){var o=Draft;if(!o.li[id])o.li[id]=new o.ta(id);o.li[id].backup();},restore:function(id){var o=Draft;if(!o.li[id])o.li[id].restore();},hasdropAll:false,dropAll:function(override){var o=Draft;if(override)o.hasDropAll=true;if(!o.hasDropAll)return;o.hasDropAll=false;var l=[];$('.draftable').each(function(){var id=$(this).attr('id');$('#draftdrop'+id).remove();l.push(id);});if(l.length>0){Fajax.add('result', l.join(','));Fajax.send('draft-drop');}},dropClick:function(e){e.preventDefault();var o=Draft,id=gup('ta',$(e.currentTarget).attr('href'));if(o.li[id])o.li[id].restore();Fajax.add('result',id);Fajax.send('draft-drop');$(e.currentTarget).remove();return false;},unused:function(id){if($('#draftdrop'+id).length>0){Fajax.add('result',id);Fajax.send('draft-drop');$('#draftdrop'+id).remove();}},dropBackHandler:function(){Draft.hasDropAll=false;$('.draftable').each( function(){$('#draftdrop'+$(this).attr('id')).remove();});},submit:function(){var l=Draft.li;for(var id in l){l[id].clearT();$("#"+id).removeClass('draftNotSave').removeClass('draftSave');}},save:function(){for(var id in Draft.li){var t=Draft.li[id],text=t.text();if(text!=t.old && text.length>0){var f=Fajax;f.add('place',id);f.add('text',text);f.add('call','Draft.saveHandler;'+id);f.send('draft-save');t.old=text;}}},saveHandler:function(id){$("#"+id).removeClass('draftNotSave').addClass('draftSave');},key:function(){var o=Draft,id=$(this).attr('id');o.unused(id);if(o.li[id].text()!=o.li[id].old)$("#"+id).removeClass('draftSave').addClass('draftNotSave');o.li[id].clearT();o.li[id].setT(o.save,o.timer);}};
/**HASH HANDLING*/
var Hash={old:'',init:function(){$(window).hashchange(function(){var o=Hash,h=location.hash.replace('#','');if(h!=o.old){if(h=='' && o.old.length>0){window.location.reload();return;}h.old=h;Fajax.action(h);}});},set:function(h){document.location.hash=h;},reset:function(hash){document.location.hash=Hash.old=hash;},data:function(k){var h=document.location.hash.replace('#','').split('/'),d=h[1];if(d){var arr=d.split(';'),data={};while(arr.length>0){var v=arr.shift(),kv=v.split(':');data[kv[0]]=kv[1];} if(data)if(data[k])return data[k];}}};
/**MARKITUP SETUP - rich textarea*/ 
var Richta = {w:null,init:function(ta){var o=Richta;if(ta)o.w=ta;if(!Lazy.load(Sett.ll.richta,Richta.init))return;if(!o.w)o.w=$('.markitup');o.w.markItUp(markitupSettings);o.w=null;},map:function(){$('.textAreaResize').remove();$('.markitup').each( function(){$(this).before('<span class="textAreaResize"><a href="?textid='+$(this).attr('id')+'" class="toggleToolSize"></a></span>');});listen('toggleToolSize','click',Richta.click);},click:function(e){var ta=$("#"+gup("textid",e.target.href));if(ta.hasClass('markItUpEditor'))ta.markItUpRemove();else if(!Richta.w)Richta.init(ta);e.preventDefault();return false;}};
/**MSG CHAT FUNCTIONS*/
var Msg={t:0,check:function(){var o=Msg,p=Hash.data('p'),l=[];$(".hentry.unread.sent").each(function(){l.push($(this).attr('id').replace('mess',''));});if(l.length>0)Fajax.add('unreadedSent',l.join(','));if(p)Fajax.add('p',p);Fajax.send('post-hasNewMessage',-1);},sentReaded:function(p){var o=Msg,l=p.split(',');for(var i in l){$("#mess"+l[i]).removeClass('unread');$("#unreadedLabel"+l[i]).remove();}},checkHandler:function(num,name){var o=Msg,d=$("#messageNew"),p=parseInt(Sett.msgTi);if(num>0){d.removeClass('hidden');$("#numMsg").text(num);$("#recentSender").text(name);}else if(!d.hasClass('hidden'))d.addClass('hidden');if(p>0){clearTimeout(o.t);o.t=setTimeout(o.check,p);}}};
/**LAZYLOADER*/
var Lazy={r:{},f:null,q:[],loading:false,load:function(l,f){var o=Lazy,c=true;for(var i=0;i<l.length;i++)if(!o.r[l[i]]){c=false;break}if(c)return c;o.q.push({l:l.concat(),f:f});if(!o.loading)return o.p();},p:function(){var o=Lazy;while(o.q[0].l.length>0){var f=o.q[0].l.shift();if(!o.r[f]){o.loading=true;o.f=f;if(f.indexOf('.css')>-1){LazyLoad.css(f,o.c);}else{LazyLoad.js(f,o.c);}return;}}o.qc();return true;},c:function(){var o=Lazy;o.r[o.f]=true;if(o.q[0].l.length>0)o.p();else o.qc();},qc:function(){var o=Lazy;if(o.q[0].f)o.q[0].f();o.q.shift();if(o.q.length>0)o.p();else o.loading=false;}};
/**google anal*/
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
if(!Array.indexOf){Array.prototype.indexOf=function(obj,start){for(var i=(start || 0);i<this.length;i++)if(this[i]==obj)return i;return -1;}}