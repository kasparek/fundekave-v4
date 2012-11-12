/**GOOGLE MAPS*/
var GooMapi=new function(){var o=this;
o.icons={'sail':'http://fotobiotic.net/css/skin/sail/img/sailing.png'
,'blue':'http://maps.google.com/mapfiles/ms/icons/blue-dot.png'
,'red':'http://maps.google.com/mapfiles/ms/icons/red-dot.png'};
o.uid=function(){o.uidc++;return 'goomapi'+o.uidc.toString(16)};o.uidc=0;
o.li={};o.poLi={};o.popid=null;o.info=null;
o.unitR=3440;o.unit='NM';o.units=[{'id':'nm','n':'NM','R':3440},{'id':'km','n':'Km','R':6371}];
o.setUnitHandler=function(e){for(var i=0;i<o.units.length;i++){if($(this).attr('rel')==o.units[i].id){o.setUnit(o.units[i].n,o.units[i].R,this.data.id);return false;}}};
o.setUnit=function(n,R,id){o.unitR=R;o.unit=n;if(id)o.editorData(id).updateDistance();};
o.distance=function(lat1,lon1,lat2,lon2){var pr=Math.PI/180,dLat=(lat2-lat1)*pr,dLon=(lon2-lon1)*pr,a=Math.sin(dLat/2)*Math.sin(dLat/2)+Math.cos(lat1*pr)*Math.cos(lat2*pr)*Math.sin(dLon/2)*Math.sin(dLon/2),c=2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a)),d=o.unitR*c;return d;};
/* degrees, mins, secs to decimal degrees - possible format 20.5468,15.1568 or 20 10 30 N,15 23 40 W */
o.posFormat=function(p){var n=parseFloat(p);if(isNaN(n) || n==0)return 0;p=$.trim(p);var dir=p.charAt(p.length-1).toUpperCase();if(dir=='W' || dir=='E' || dir=='N' || dir=='S'){var posArr=p.substr(0,p.length-1).split(' '),d=posArr[0]-0,m=posArr.length>1?posArr[1]-0:0,s=posArr.length>2?posArr[2]-0:0,sign=(dir=='W' || dir=='S')?-1:1;p = sign*(d+((m+(s/60))/60));}return p-0;};
o.parsePos=function(v){v=$.trim(v);var r=[],l;if(!v)return r;
if(v.length>0){
l=v.split("\n");for(i=0;i<l.length;i++){l[i]=l[i].split(',');if(l[i].length==2){l[i][0]=o.posFormat(l[i][0]);l[i][1]=o.posFormat(l[i][1]);if(l[i][0]==0 && l[i][1]==0)l[i]=false;}else{l[i]=false;}}for(i=0;i<l.length;i++){if(l[i]!==false)r.push(l[i]);}}return r;};
o.locale={};o.loading=false;o.loaded=false;o.call=[];
o.load=function(f){if(o.loaded)return true;if(indexOf(o.call,f)==-1)o.call.push(f);if(o.loading)return;o.loading=true;LazyLoad.js('http://maps.google.com/maps/api/js?v=3&sensor=false&libraries=geometry&callback=GooMapi.c');};
o.c=function(){o.loading=false;o.loaded=true;while(o.call.length>0){var f=o.call.shift();f();}};
o.sourceTOut=null;o.sourceT=1000;o.sourceTF=function(el){$('.'+$(el).attr('class').replace("ThumbA","")+'Source').show();};
o.sourceOver=function(e){o.sourceTOut=setTimeout(o.sourceTF,o.sourceT,this);};
o.sourceOut=function(){clearTimeout(o.sourceTOut);};
o.init=function(){o.locale=Sett.locale.goomapi;
$(".geoInput").each(function(){var id=$(this).attr('id'),w=$(this).width(),h=$(this).height();if(!id){id=o.uid();$(this).attr('id',id);}if($(this).is("textarea")){if($('.'+id+'Thumb').length==0){$(this).change(o.staticSel).hide().after('<a href="#" class="'+id+'ThumbA" title="Ukaz na mape"><img class="'+id+'Thumb" src="" width="'+w+'" height="'+h+'" alt="Google Maps" /></a><a href="#" class="'+id+'Source" title="Source waypoints"><img src="'+Sett.skinUrl+'/img/source.png" alt="Waypoints source" /></a>');$('.'+id+'Source').click(function(){clearTimeout(o.sourceTOut);$('#'+$(this).attr('class').replace('Source','')).toggle().change();return false;}).hide();$('.'+id+'Thumb').click(function(){o.geoInputClick(null,$(this).attr('class').replace('Thumb',''));});$('.'+id+'ThumbA').hover(o.sourceOver,o.sourceOut).click(function(){return false;});}} else $(this).unbind('click',o.geoInputClick).click(o.geoInputClick);}).change();
$(".mapLarge").each(function(){var id=$(this).attr('id');if(!id){id=o.uid();$(this).attr('id',id);}if(!$(this).hasClass('hidden') && $("map"+id+"holder",this).length==0)if($(this).hasClass('editor')){var d=o.editorData(id,this),rel=$(this).attr('rel');if(rel)d.dataEl=document.getElementById(rel);d.parent.pop=false;o.mapEditor(id);} else o.show(id);});
listen('mapThumbLink','click',o.thumbClick);
};
o.thumbClick=function(){var id=$(this).attr('id').replace('Thumb','');$(this).remove();$('#'+id).removeClass('hidden');o.show(id);return false;};
o.geoInputClick=function(e,id){if(e)id=$(this).attr('id');if(o.popid)o.close();var d=o.editorData();d.dataEl=null;if(id){var el=document.getElementById(id);if(el){if($(el).is('textarea'))d.dataEl=el;}}if(!d.dataEl)$(d.parent.eli.mapSaveB).hide(); else $(d.parent.eli.mapSaveB).show();o.mapEditor('Editor');return false;};
o.staticSel=function(e){var p=o.parsePos($(this).val()),id=$(this).attr('id'),w=$(this).width(),h=$(this).height();

var u='http://maps.google.com/maps/api/staticmap?size='+w+'x'+h+(p.length>0?'&markers='+p[p.length-1]:'&zoom=3&center=51.477222,0&maptype=terrain')+'&sensor=false'+(p.length>1?'&path='+p.join('|'):'');
$('.'+id+'Thumb').css("width",(p.length>0 ? w : 64)+"px").css("height",(p.length>0 ? h : (h/w)*64)+"px").attr('src',u);
if(!$(this).is(':visible')){$('.'+id+'Thumb').show();} else $('.'+id+'Thumb').hide();};
o.editorData=function(id,parent){if(!id)id='Editor';if(o.li[id]){$(o.li[id].eli.mainEl).show();return o.li[id].li[0];};
var style='border: 1px solid #707070;background-color:#d0d0d0;';if(parent)style+='position:relative;width:100%;height:100%;';else style+='position:fixed;z-index:10000;';
$(parent?parent:"body").append('<div style="'+style+'" id="map'+id+'Overlay">'
+'<div style="position:absolute;width:100%;height:20px;background-color:#e78f08;color:#ffffff;font-weight: bold;font-family: Trebuchet MS,Tahoma,Verdana,Arial,sans-serif;font-size:11px;"><div style="padding:0 10px;line-height:20px;">'
+(!parent?'<a id="mapCloseB" href="#" role="button" style="display:block;float:right;color:#ffffff;">'+o.locale.close+'</a>':'')
+'<span class="mapTitle">'+o.locale.title+'</span> <span class="mapUnitsBox"><a class="mapUnitNM" href="#" rel="nm" style="color:#ffffff;" title="'+o.locale.unitTitle+'">NM</a> <a class="mapUnitKm" href="#" rel="km" style="color:#ffffff;" title="'+o.locale.unitTitle+'">Km</a></span></div></div>'
+'<div class="map"></div>'
+'<div style="bottom:2px;left:5px;position:absolute;"><input class="mapSearchI" value="" style="width:200px;"/><button class="mapSearchB" style="">'+o.locale.search+'</button></div>'
+'<div style="bottom:2px;right:5px;position:absolute;"><button class="mapClearB" title="'+o.locale.clearTitle+'">'+o.locale.clear+'</button><button class="mapSaveB" title="'+o.locale.saveTitle+'">'+o.locale.save+'</button></div></div>');
var topEl=document.getElementById('map'+id+'Overlay'),h=new o.hold($('.map',topEl)[0]);h.id=id;h.editor=true;h.eli.mainEl=topEl;h.eli.mapUnitsBox = $('.mapUnitsBox',topEl)[0];$(h.eli.mapUnitsBox).hide();h.eli.mapUnitBLi = [$('.mapUnitNM',topEl)[0],$('.mapUnitKm',topEl)[0]];for(var i=0;i<h.eli.mapUnitBLi.length;i++){h.eli.mapUnitBLi[i].data=h;$(h.eli.mapUnitBLi[i]).click(o.setUnitHandler)};h.eli.mapSearchI = $('.mapSearchI',topEl)[0];h.eli.mapSearchI.data=h;h.eli.mapSearchB = $('.mapSearchB',topEl)[0];h.eli.mapSearchB.data=h;$(h.eli.mapSearchB).click(o.search);$(h.eli.mapSearchI).keydown(o.searchKey);h.eli.mapTitle = $('.mapTitle',topEl)[0];if(parent){h.pop=false;}else{o.popid=id;h.eli.mapCloseB = document.getElementById("mapCloseB");h.eli.mapCloseB.data=h;$(h.eli.mapCloseB).click(o.close);}h.eli.mapClearB = $('.mapClearB',topEl)[0];h.eli.mapClearB.data=h;$(h.eli.mapClearB).click(o.clear);h.eli.mapSaveB = $('.mapSaveB',topEl)[0];h.eli.mapSaveB.data=h;$(h.eli.mapSaveB).click(o.save);h.li=[new o.data(h)];o.li[id]=h;o.resize();return h.li[0];};
o.hold=function(mapEl){var h=this;h.eli={};h.li=[];h.pop=true;h.editor=false;h.mapType='terrain';h.map=null;h.geocoder=null;h.cluster=null;h.mapEl=mapEl;h.id=null;
h.init=function(){if(!h.map){h.geocoder=new google.maps.Geocoder();h.map=new google.maps.Map(h.mapEl,{mapTypeId:h.mapType});h.map.data=h;h.map.setCenter(new google.maps.LatLng(30,35));h.map.setZoom(2);if(!h.editor)h.cluster=new MarkerClusterer(h.map,[],{'maxZoom':10,'zoomOnClick':true});}};};
o.data=function(p){var d=this;d.pathColor="#0000ff";d.strokeWeight=4;d.pathAlpha="0.5";d.parent=p;d.dataEl=null;d.title='';d.infoEl=null;d.ico=null;d.markers=[];d.path=null;d.distance=0;
d.addListeners=function(){if(!d.parent.editor)return;var ge=google.maps.event;ge.clearListeners(d.parent.map,'click');ge.addListener(d.parent.map,'click',o.editorClick);if(d.markers.length==0)return;ge.clearListeners(d.path);ge.addListener(d.path,'click',function(e){var min=999,mi,ms=this.data.markers;for(var i=0;i<ms.length-1;i++){var m1=ms[i],m2=ms[i+1],diff=Math.abs(google.maps.geometry.spherical.computeDistanceBetween(m1.getPosition(),m2.getPosition())-(google.maps.geometry.spherical.computeDistanceBetween(e.latLng, m1.getPosition())+google.maps.geometry.spherical.computeDistanceBetween(e.latLng, m2.getPosition())));if(diff<min){min=diff;mi=i;}}ms.splice(mi+1,0,null);this.getPath().insertAt(mi+1,e.latLng);this.data.updateMarker(e.latLng,mi+1);this.data.updateDistance();});for(var i=0;i<d.markers.length;i++){var m=d.markers[i];ge.clearListeners(m);ge.addListener(m,'drag',function(){this.data.path.getPath().setAt(indexOf(this.data.markers,this),this.getPosition());});ge.addListener(m,'dragend',function(){this.data.path.getPath().setAt(indexOf(this.data.markers,this),this.getPosition());this.data.updateDistance();});ge.addListener(m,'dblclick',function(){var i=indexOf(this.data.markers,this);this.data.path.getPath().removeAt(i);this.data.markers.splice(i,1);this.setMap(null);this.data.updateDistance();this.data=null;});}};
d.updateMarker=function(latLng,i){var l=d.markers.length;if(typeof(i)=='undefined')i=l>0?l-1:0; else if(i>l)i=l;if(!d.markers[i]){d.markers[i]=new google.maps.Marker(d.parent.editor?{title:d.title,draggable:true,raiseOnDrag:false}:{title:d.title});var m=d.markers[i];
m.data=d;if(d.parent.editor){d.addListeners();m.setTitle(o.locale.markerTitle);}if(d.parent.cluster)d.parent.cluster.addMarker(m);}if(d.ico){m.setIcon(o.icons[d.ico]?o.icons[d.ico]:d.ico);m.setZIndex(1);}m.setPosition(latLng);m.setMap(d.parent.map);if(d.infoEl)if(d.infoEl.length>0)m.htmlInfo=$(d.infoEl).html();$(d.infoEl).hide();};
d.resetWP=function(){if(d.path)d.path.setPath([]);while(d.markers.length>0)d.markers.pop().setMap(null);};
d.addWP=function(latLng,wm){if(!d.path){d.path=new google.maps.Polyline({map:d.parent.map,path:[],strokeColor:d.pathColor,strokeOpacity:d.pathAlpha,strokeWeight:d.strokeWeight,geodesic:true});d.path.data=d;}d.path.setMap(d.parent.map);d.path.getPath().push(latLng);if(wm)d.updateMarker(latLng,d.markers.length);};
d.updateDistance=function(){d.distance=0;if(!d.path)return;var l=d.path.getPath();if(l.length>1){for(i=1;i<l.length;i++){d.distance+=o.distance(l.getAt(i-1).lat(),l.getAt(i-1).lng(),l.getAt(i).lat(),l.getAt(i).lng());}}d.distance=Math.round(d.distance*10)/10;if(d.parent.editor){$(d.parent.eli.mapTitle).html(d.distance>0?o.locale.distance+d.distance+o.unit:o.locale.title);if(d.distance>0)$(d.parent.eli.mapUnitsBox).show(); else $(d.parent.eli.mapUnitsBox).hide();}};
d.get=function(){if(!d.dataEl)return [];return o.parsePos($(d.dataEl).val());};
};
o.showQueue=[];o.show=function(id,f){if(id)o.showQueue.push({id:id,f:f});if(!o.load(o.show))return;if(!Lazy.load(Sett.ll.goomapi,o.show))return;if(o.showQueue.length==0)return;while(o.showQueue.length>0){var q=o.showQueue.pop(),id=q.id,f=q.f;if(!o.li[id]){var md=document.getElementById(id);if(!md)return;$(md).append('<div id="map'+id+'holder" style="width:100%;height:'+$(md).height()+'px;"></div>');o.li[id]=new o.hold(document.getElementById("map"+id+"holder"));o.li[id].id=id;o.li[id].init();$('.mapsData',md).each(function(){var d=new o.data(o.li[id]);

d.dataEl=$('.geoData',this);
if($('.pathColor',this).length>0)d.pathColor=$('.pathColor',this).val();
if($('.strokeWeight',this).length>0)d.strokeWeight=$('.strokeWeight',this).val();
d.title=$(d.dataEl).attr('title');d.infoEl=$('.geoInfo',this);d.ico=$('.geoIco',this).val();o.li[id].li.push(d);});}var h=o.li[id],bounds=new google.maps.LatLngBounds();for(var i=0;i<h.li.length;i++){var d=h.li[i],l=d.get(),ll=l.length;d.resetWP();if(ll>0){po=(Math.round(l[ll-1][0]*1000))+','+(Math.round(l[ll-1][1]*1000));if(o.poLi[po]){var a=Math.ceil(o.poLi[po]/4),b=4-((a*4)-o.poLi[po]);if(b==1)l[ll-1][0]+=a*0.0001;else if(b==2)l[ll-1][1]+=a*0.0002;else if(b==3)l[ll-1][0]-=a*0.0001;else l[ll-1][1]-=a*0.0002;o.poLi[po]++;}else o.poLi[po]=1;for(var j=0;j<ll;j++){var p=new google.maps.LatLng(l[j][0],l[j][1]);d.addWP(p,d.parent.editor || j==ll-1);bounds.extend(p);}d.updateDistance();};ll=d.markers.length;if(!d.parent.editor && ll>0){var m=d.markers[ll-1];google.maps.event.clearListeners(m);if(d.infoEl.length>0){m.htmlInfo=m.htmlInfo.replace('[[DISTANCE]]',d.distance);google.maps.event.addListener(m,'click',function(e){if(!o.info)o.info=new google.maps.InfoWindow({maxWidth:300});o.info.setContent(this.htmlInfo);o.info.open(this.getMap(),this);});}}};if(!bounds.isEmpty()){o.fit.push({m:h.map,b:bounds});setTimeout(o.fitLater,100);}$(window).unbind('resize',o.mapResize).resize(o.mapResize).resize();if($.isFunction(f))f(id);}};

o.mapEditorQueue=[];o.mapEditor=function(id){if(id)if(indexOf(o.mapEditorQueue,id)==-1)o.mapEditorQueue.push(id);if(!o.load(o.mapEditor))return;if(!Lazy.load(Sett.ll.goomapi,o.mapEditor))return;if(o.mapEditorQueue.length==0)return;while(o.mapEditorQueue.length>0){id=o.mapEditorQueue.pop();o.editorData(id).parent.init();o.show(id,function(id){var d=o.editorData(id);if(!d.dataEl)$(d.parent.eli.mapSaveB).hide(); else $(d.parent.eli.mapSaveB).show();d.addListeners();d.updateDistance();if(d.parent.pop){$(window).resize(o.resize).resize();$(window).keydown(o.wkey);}});}};
o.fit=[];o.fitLater=function(){while(o.fit.length>0){var b=o.fit.pop();b.m.fitBounds(b.b);}};
o.resize=function(e){var w=$(window).width(),h=$(window).height(),mw=w*0.9,mh=h*0.9,mx=(w-mw)/2,my=(h-mh)/2;for(k in o.li) if(o.li[k].pop) $("#map"+o.li[k].id+"Overlay").css('width',mw+'px').css('height',mh+'px').css('left',mx+'px').css('top',my+'px');};
o.mapResize=function(){for(k in o.li){if(o.li[k].editor)$(o.li[k].mapEl).css('width','100%').css('position','absolute').css('top','20px').css('bottom','30px');if(o.li[k].map)google.maps.event.trigger(o.li[k].map,'resize');}};
o.editorClick=function(e){var d=o.editorData(e.id?e.id:this.data.id),r=d.path?d.path.getPath():null,add=true,l=0;if(r)l=r.getLength();if(l>0)if(r.getAt(l-1).lat()==e.latLng.lat() && r.getAt(l-1).lng()==e.latLng.lng())add=false;if(add)d.addWP(e.latLng,true);d.updateDistance();};
o.close=function(e){var d=o.editorData(o.popid);$(d.parent.eli.mainEl).hide();$(window).unbind('resize',o.resize);$(window).unbind('keydown',o.wkey);o.popid=null;return false;};
o.save=function(e){var d=o.editorData(this.data.id);if(d.dataEl){var oldVal=$(d.dataEl).val(),newVal=o.toString(this.data.id);$(d.dataEl).val(newVal);if(oldVal!=newVal)$(d.dataEl).change();}o.close();};
o.toString=function(id){var d=o.editorData(id),r='';if(d.path){var l=[];d.path.getPath().forEach(function(latLng){l.push(latLng.toUrlValue(4));});r=l.join("\n");}return r;};
o.clear=function(e){var d=o.editorData(this.data.id);d.resetWP();d.updateDistance();$(d.parent.eli.mapTitle).html(o.locale.title);};
o.wkey=function(e){if(e.keyCode==27)o.close();}
o.searchKey=function(e){if(e.keyCode==13)o.search(e);};
o.searchResultHandler=function(g,id){var d=o.editorData(id);o.fit.push({m:d.parent.map,b:g.bounds});setTimeout(o.fitLater,100);o.editorClick({id:id,latLng:g.location});};
o.search=function(e){var d=o.editorData(e.target.data.id),valI=d.parent.eli.mapSearchI.value,pos=o.parsePos(valI);if(pos.length>0)o.searchResultHandler({bounds:new google.maps.LatLngBounds(new google.maps.LatLng(pos[0][0], pos[0][1]),new google.maps.LatLng(pos[0][0], pos[0][1])),location:new google.maps.LatLng(pos[0][0], pos[0][1])},e.target.data.id);else if(d.parent.geocoder)d.parent.geocoder.geocode({address:valI},function(results,status){if(status==google.maps.GeocoderStatus.OK) o.searchResultHandler(results[0].geometry,e.target.data.id);});};
};
/**INITIALIZATION ON DOM*/ 
function boot() {
  gaLoad();
	buttonInit();
	if($("#errormsgJS").is(':empty'))$("#errormsgJS").hide(0); 
	if($("#okmsgJS").is(':empty'))$("#okmsgJS").hide(0);
	$("#errormsgJS").css('padding','1em');
	$("#okmsgJS").css('padding','1em');
	var w=$(window).width();
	if($("#sidebar").length==0)$('body').addClass('bodySidebarOff');
	$(".expand").autogrow();
	$(".opacity").bind('mouseenter',function(){$(this).fadeTo("fast",1);}).bind('mouseleave',function(){$(this).fadeTo("fast",0.2);});
	fajaxInit();
	fconfirmInit();
	switchOpen();
	Resize.init();	 
	GooMapi.init();
	if($(".hash").length>0)Hash.init();
	slimboxInit();
	Fullscreen.init();
	tabsInit();
	fuupInit();
	datePickerInit();
	if(parseInt(Sett.user)>0){
		Richta.map();
		$("#recipient").change(avatarfrominput);
		$('#ppinput').hide();
		$("#saction").change( function(evt){if($("#saction option:selected").attr('value') == 'setpp') $('#ppinput').show(); else $('#ppinput').hide(); });
		$("#recipientList").change(function(evt){var str = "",combo = $("#recipientList");if(combo.attr("selectedIndex")>0)$("#recipientList option:selected").each(function(){str+=$(this).text()+" ";});$("#recipient").attr("value",str);combo.attr("selectedIndex",0);avatarfrominput();});
		GaleryEdit.init();	
		if(parseInt(Sett.msgTi)>0)Msg.check();
		var perm=$("#accessSel");if(perm.length>0)perm.change(function(){var v=$(this).val();if(v==0)$("#rule1").show();else $("#rule1").hide();}).change();
	}
  
   galeriaInit();
  
  
};

function galeriaInit(){if($('.galeria').length>0){
if(!Lazy.load(Sett.ll.galeria,galeriaInit))return;
$(".galeria").height(600);
Galleria.loadTheme('/js6/galleria.theme/galleria.classic.min.js');
Galleria.run('.galeria');
}
}

/**INIT jQuery UI and everything possibly needed for ajax forms and items*/
function jUIInit(){if(!Lazy.load(Sett.ll.ui,jUIInit))return;buttonInit();tabsInit();datePickerInit();Richta.map();fajaxInit();fconfirmInit();GooMapi.init();fuupInit();slimboxInit();$(".expand").autogrow();};
function juilater(){ $(".expand").change(); }
function datePickerInit(){if($(".datepicker").length>0){if(!Lazy.load(Sett.ll.ui,datePickerInit))return;$.datepicker.setDefaults($.extend({showMonthAfterYear:false},$.datepicker.regional['cs']));$(".datepicker").datepicker();}};
function slimboxInit(){if(!/android|iphone|ipod|series60|symbian|windows ce|blackberry/i.test(navigator.userAgent)){if($("a[rel^='lightbox']").length>0){if(!Lazy.load(Sett.ll.slim,slimboxInit))return;$("a[rel^='lightbox']").slimbox({overlayFadeDuration:100,resizeDuration:100,imageFadeDuration:100,captionAnimationDuration:100},null,function(el){return(this==el)||((this.rel.length > 8)&&(this.rel==el.rel));});}}};
function fuupInit(){if($(".fuup").length>0){if(!Lazy.load(Sett.ll.swf,fuupInit))return;$(".fuup").each(function(i){var id=$(this).attr('id');swfobject.embedSWF(Sett.assUrl+"load.swf",id,"100","25","10.0.12",Sett.assUrl+"expressInstall.swf",{file:Sett.assUrl+"Fuup.swf",config:"fuup."+id+"."+Sett.page+".xml",containerId:id},{wmode:'transparent',allowscriptaccess:'always'});});$("#uploadTip").removeClass('hidden');}}
function tabsInit(){if($("#tabs").length>0){if(!Lazy.load(Sett.ll.ui,tabsInit))return;$("#tabs").tabs({select:function(event, ui){window.location.hash = '';}});juilater();}};
function buttonInit(){if($('.uibutton').length>0){if(!Lazy.load(Sett.ll.ui,buttonInit))return;$('.uibutton').button();}}
/**request init*/
function friendRequestInit(text){$('#friendrequest').remove();$("#menu-secondary-holder").after(text);$('#friendrequest').removeClass('hidden').show('slow'); fajaxInit(); $('#cancel-request').unbind('click',Fajax.form).bind('click',function(event){remove('friendrequest');event.preventDefault();return false;}); };
/**ajax link init*/
function fajaxInit(){Fajax.init();listen('galerynext','click',ImgNext.click);};
function fconfirmInit(event){$('.confirm').each(function(){var pf=false;if(this.form)pf=$(this.form).hasClass('fajaxform');if(!$(this).hasClass('fajaxa') && !pf){$(this).bind('click',onConfirm);}});};
function onConfirm(e){if(!confirm($(e.currentTarget).attr("title"))){preventAjax=true;e.preventDefault();}};
/**simple functions*/
function shiftTo(y){if(!y) y=0;$(window).scrollTop(y);}
function enable(id){$('#'+id).removeAttr('disabled');};
function remove(id){$('#'+id).remove();};
function switchOpen(){$('.switchOpen').click(function(){$('#'+this.rel).toggleClass('hidden');return false;});};
function listen(c,e,f){$("."+c).unbind(e,f).bind(e,f);};
function gup(n,url){n=n.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");var r=new RegExp("[\\?&|]"+n+"=([^&#|]*)"),res=r.exec(url);return res===null?0:res[1];};
var msgOkTime=0,msgErrorTime=0;
function msg(type,text){if(type=='ok'){clearTimeout(msgOkTime);msgOkTime=setTimeout(function(){$("#okmsgJS").hide('slow')},5000);}else{clearTimeout(msgErrorTime);msgErrorTime=setTimeout(function(){$("#errormsgJS").hide('slow')},10000);}$("#"+type+"msgJS").hide(0).html(text).show();};
function redirect(dir){window.location.replace(dir);};
/**AVATAR FROM input IN fpost*/
function avatarfrominput(evt){Fajax.add('username', $("#recipient").attr("value"));Fajax.add('call', 'fajaxInit');Fajax.send('post-avatarfrominput','');}
/**IMAGE UPLOADING TOOL HANDLERS - FUUP*/ 
function fuupUploadComplete(){var i=$('#i').attr('value');if(i>0)Fajax.add('i',i);Fajax.add('call','jUIInit');Fajax.send('item-image',Sett.page);}
function tempStoreDeleteInit(){$("#tempStoreButt").click(function(e){$("#imageHolder").html('');Fajax.send('item-tempStoreFlush',Sett.page);e.preventDefault();return false;});}
/**AJAX GALLERY EDITING THUMBNAILS LOADING AND REFRESHING*/
var GaleryEdit=new function(){var o=this;o.numTotal=0;o.numLoaded=0;o.newLi=[];o.updLi=[];o.run=false;
o.init=function(){o.numLoaded=0;o.numTotal=parseInt($("#fotoTotal").text());if(o.numTotal>0 && $('#fotoList').length>0)o.load(0,10);};
o.check=function(){Fajax.send('page-fuup',Sett.page);};
o.refresh=function(n,u,t){o.numTotal=parseInt(t);$("#fotoTotal").text(o.numTotal);if(n.length>0)o.newLi=o.newLi.concat(n.split(';'));if(u.length>0)o.updLi=o.updLi.concat(u.split(';'));if(!o.run)o.next();};
o.next=function(){if(o.updLi.length>0)o.load(o.updLi.pop(),1,'U');else if(o.newLi.length>0)o.load(o.newLi.pop(),1);else if(o.numLoaded<o.numTotal)o.load(0,10);else o.run=false;};
o.load=function(item,offset,type){var f=Fajax;o.run=true;if(item>0){f.add('item', item);if(type=='U'){f.add('result','foto-'+item);f.add('resultProperty','$replaceWith');}}else{f.add('total',o.numTotal);f.add('seq',o.numLoaded);}f.add('offset', offset);f.add('call','jUIInit');f.add('call','GaleryEdit.bindDelete');f.send('galery-editThumb',Sett.page);};
o.loadHandler=function(num){var n=parseInt(num);if(n>0)o.numLoaded+=n;o.next();};
o.bindDelete=function(){listen('deletefoto','click',GaleryEdit.del);};
o.del=function(e){var f=Fajax,l=$(this).attr("id").split("-");if(confirm($(this).attr("title"))){f.add('item', l[1]);f.send('item-delete');f.formStop=true;$('#foto-'+l[1]).hide('slow',function(){$('#foto-'+l[1]).remove()});o.numTotal--;$("#fotoTotal").text(o.numTotal);}return false;};}; 
/**GALERY NEXT WITH PRELOADING*/
var ImgNext=new function(){var o=this;o.r=false;o.i=null;o.il=null;o.p=null;o.next=null;o.top=0;o.xhr=null;
o.init=function(){if(!o.r){o.r=true;o.i=$("#detailFoto");o.i.bind('load',o.loaded);o.p=$(".showProgress");$("body").append('<img id="imgNextLoader" class="noscreen" />');o.il=$("#imgNextLoader").load(o.preloaded);}};
o.click=function(e){var m=gup('m',this.href);if(Fajax.xhrList[m])return false;o.top=$(window).scrollTop();o.init();o.i.show();var h=o.p.height();o.p.css('height',(h>0?h:$(window).height())+'px');if(o.next){o.i.attr('src',o.next);o.next=null;}else o.i.hide();Fajax.a(e);return false;};
o.loaded=function(){o.init();o.i.show();o.p.css('height','auto');if(Fullscreen.state)imgResizeToFit(o.i);Slideshow.next();if(o.top)$(window).scrollTop(o.top);};
o.xhrHand=function(currentUrl,nextUrl){o.init();if(currentUrl && currentUrl!=o.i.attr('src'))o.i.attr('src',currentUrl);if(nextUrl)o.il.attr('src',nextUrl);};
o.preloaded=function(e){o.next=o.il.attr('src');}
};
/**SLIDESHOW*/
var Slideshow=new function(){var o=this;o.on=false;o.t=0;o.s=5;o.f=function(){if(o.on)$("#nextButt").click();};o.toggle=function(){o.on=!o.on;o.next();};o.next=function(){clearTimeout(o.t);if(o.on)o.t=setTimeout(o.f,o.s*1000);};};
/**FULLSCREEN*/
var Fullscreen=new function(){var o=this;o.el=null;o.tool=null;o.state=null;
o.init=function(){listen('galeryFullSwitch','click',o.go);$("#fullscreenLeave").click(o.go);$("#fullscreenPrevious").click(function(){$("#prevButt").click();return false;});$("#fullscreenNext").click(function(){$("#nextButt").click();return false;});o.to=$("#fullscreenToolbar");o.to.hover(function(){$(this).fadeTo("slow",1.0);},function(){$(this).fadeTo("slow",0.2);});var fs=$("#fullscreenSlideshow");if(Slideshow.on) fs.addClass('fullscreenSlideshowOn'); else fs.removeClass('fullscreenSlideshowOn');fs.click(function(){$(this).toggleClass('fullscreenSlideshowOn');Slideshow.toggle();return false;});o.el=$('#fullscreenBox');};
o.go=function(div){var d=$(document.documentElement),w=$(window);if(o.el)div=o.el;if(!div && !o.state.el)return;if(!div)div=o.state.el;if(!o.state){o.state={el:div,parent:div.parent(),index:div.parent().children().index(div),x:w.scrollLeft(),y:w.scrollTop()};div.addClass('fullscreen');$('body').append(div).css('overflow','hidden');w.scrollTop(0).scrollLeft(0);d.bind('keyup',o.key);o.to.removeClass('hidden').delay(100).fadeTo("fast", 1).fadeTo("slow", 0.3);w.bind('resize',ImgNext.loaded).resize();var hint=$("#fullscreenHint").removeClass('hidden'),hh=hint.height(),wc=(w.height()-hh)/2;hint.css('top',wc).show().delay(1000).fadeOut('slow');}else{div.removeClass('fullscreen');Slideshow.on=false;w.unbind('resize',ImgNext.loaded);if(o.state.index>=o.state.parent.children().length) o.state.parent.append(div);else div.insertBefore(o.state.parent.children().get(o.state.index));$('body').css('overflow', 'auto');d.unbind('keyup',o.key);$('#detailFoto').css('position','inherit').css('width','auto').css('height','auto').css('margin','0 auto');o.to.addClass('hidden');w.scrollTop(o.state.x).scrollLeft(o.state.y);o.state=null;}return false;};
o.key=function(e){if(e.keyCode==27)o.go();if(e.keyCode==32)$("#nextButt").click();}};
/**IMAGE RESIZE TO FIT*/
function imgResizeToFit(img,fitTo,fit){if(!fit)fit=0.9;if(!fitTo)fitTo=$(window);var ww=fitTo.width()*fit,wh=fitTo.height()*fit;img.css('width','auto').css('height','auto');var iw=img.width(),ih=img.height(),tw=ww,th=ih*ww/iw;if(th-wh>1){iw='auto';ih=wh;}else{iw=tw;ih='auto';}img.css('width',iw).css('height',ih).css('position','absolute').css('left',((fitTo.width()-img.width())/2)+'px').css('top',((fitTo.height()-img.height())/2)+'px');};
/**RESIZE HANDLER-CLIENT INFO TO SERVER*/
var Resize=new function(){var o=this;o.t=0;o.init=function(){$(window).resize(o.on).resize();};o.on=function(){clearTimeout(o.t);o.t=setTimeout(o.send,500);};o.send=function(){var w=$(window),ww=w.width(),wh=w.height(),cw=parseInt(Sett.cw)*1,ch=parseInt(Sett.ch)*1;if(w!=cw || h!=ch){Fajax.add('size',ww+'x'+wh);Fajax.send('user-clientInfo',-1,true);}}};
/**CUSTOM AJAX REQUEST BUILDER/HANDLER * send and process ajax request - if problems with %26 use encodeURIComponent*/
var Fajax=new function(){var o=this;o.xhrList={};o.top=0;o.formStop=false;o.formSent=null;
o.init=function(){if($(".fajaxform").length>0){Lazy.load(Sett.ll.form);listen('button','click',Fajax.form);}if($(".fajaxpager").length>0)listen('fajaxpager','click',o.pager);listen('fajaxa','click',o.a);};
o.pager=function(){Hash.set('post-page/p:'+gup('p',this.href)+'/fpost');return false;};
o.a=function(e){var t=$(e.currentTarget),href=t.attr('href');o.top=null;if(t.hasClass('confirm')){if(!confirm(t.attr("title")))return false;}var k=gup('k',href),id=t.attr("id"),m=gup('m',href);if(!k)k=0;var action=m+'/'+gup('d',href)+'/'+k;if(id)action+='/'+id;if(t.hasClass('keepScroll'))o.top=$(window).scrollTop();if(t.hasClass('progress')){var bar=$(".showProgress"),h=bar.height();bar.addClass('lbLoading').css('height',(h>0?h:$(window).height())+'px').children().hide();}if(t.hasClass('hash')){Hash.set(action);return false;}o.action(action);return false;};
o.action=function(action){var l=action.split('/'),m=l[0],d=l[1],k=l[2],id=l[3],res=false,prop=false;if(k==0)k=null;if(d){l=d.split(';');while(l.length>0) {var row=l.shift().split(':');o.add(row[0], row[1]);if(row[0]=='result')res=true;if(row[0]=='resultProperty')prop=true;}}if(id){if(!res)o.add('result',id);if(!prop)o.add('resultProperty','$html');}o.send(m,k);return false;};
o.form=function(e){var t=e.currentTarget,jt=$(t);e.preventDefault();if(o.formStop==true){o.formStop=false;return false;}if(jt.hasClass('confirm'))if(!confirm(jt.attr("title")))return false;$('#errormsgJS').hide(0).html('');$('#okmsgJS').hide(0).html('');o.formSent=t.form;var arr=$(o.formSent).formToArray(false),action,res=false,prop=false;while(arr.length>0){var v=arr.shift();if(v.name=='m')action=v.value;else o.add(v.name,v.value);if(v.name=='result')res=true;if(v.name=='resultProperty')prop=true;}if(!res)o.add('result',$(o.formSent).attr("id"));if(!prop)o.add('resultProperty','$html');o.add('action',t.name);o.add('k',gup('k',o.formSent.action));o.send(!action?gup('m',o.formSent.action):action,gup('k',o.formSent.action));$('.button').attr("disabled", true);if($('.ui-button').length>0)$('.ui-button').button('disable');return false;};
o.XMLReq=new function(){var x=this;x.a=[];x.s='<Item name="{KEY}"><![CDATA[{DATA}]]></Item>';x.reset=function(){o.XMLReq.a=[];};x.add=function(k,v){x.a.push(x.s.replace('{KEY}',k).replace('{DATA}',v));};x.get=function(){var s='<FXajax><Request>'+x.a.join('')+'</Request></FXajax>';x.a=[];return s;}};
o.add=function(k,v){o.XMLReq.add(k,v)};
o.send=function(action,k,silent){var data=o.XMLReq.get();if(k==0)k=null;if(!k)k=Sett.page;if(k==-1)k='';$.ajaxSetup({scriptCharset:"utf-8",contentType:"text/xml; charset=utf-8"});o.xhrList[action]=$.ajax({type:"POST",url:"index.php?m="+action+"-x"+((k)?("&k="+k):('')),dataType:'xml',processData:false,cache:false,data:data,complete:function(a,s){o.xhrList[action]=null;if(o.formSent){$('.button').removeAttr('disabled');if($('.ui-button').length>0)$('.ui-button').button('enable');o.formSent=null;}if(s!='success'){if(!silent)msg('error',Sett.ajaxErr);return;}$(a.responseXML).find("Item").each(function(){var item=$(this),c='',target=item.attr('target'),prop=item.attr('property'),text=item.text();switch(target){case 'document':c=target+'.'+prop+'=text;';break;case 'call':var par=text.split(','),p=[];for(var i=0;i<par.length;i++)p.push("par["+i+"]");c=prop+"("+(p.length>0?p.join(","):"")+");";break;default:switch(prop){case'void': break;default:if(prop[0]=='$'){c='$("#'+target+'").'+prop.replace('$','')+'(text);';}else{c='$("#'+target+'").attr("'+prop+'",text);';}};};if(c.length>0)eval(c);})}});}};
/**HASH HANDLING*/
var Hash=new function(){var o=this;o.old='';o.init=function(){$(window).hashchange(function(){var h=location.hash.replace('#','');if(h!=o.old){if(h=='' && o.old.length>0){window.location.reload();return;}h.old=h;Fajax.action(h);}});};o.set=function(h){document.location.hash=h;};o.reset=function(hash){document.location.hash=o.old=hash;};o.data=function(k){var h=document.location.hash.replace('#','').split('/'),d=h[1];if(d){var arr=d.split(';'),data={};while(arr.length>0){var v=arr.shift(),kv=v.split(':');data[kv[0]]=kv[1];} if(data)if(data[k])return data[k];}}};
/**MARKITUP SETUP - rich textarea*/ 
var Richta=new function(){var o=this;o.w=null;o.init=function(ta){if(ta)o.w=ta;if(!Lazy.load(Sett.ll.richta,o.init))return;if(!o.w)o.w=$('.markitup');o.w.markItUp(markitupSettings);o.w=null;};o.map=function(){$('.textAreaResize').remove();$('.markitup').each( function(){$(this).before('<span class="textAreaResize"><a href="?textid='+$(this).attr('id')+'" class="toggleToolSize"></a></span>');});listen('toggleToolSize','click',o.click);};o.click=function(e){var id=gup("textid",e.target.href),ta=$("#"+id);if(ta.hasClass('markItUpEditor'))ta.markItUpRemove();else if(!o.w)o.init(ta);$("#"+id).autogrow();return false;}};
/**MSG CHAT FUNCTIONS*/
var Msg=new function(){var o=this;o.t=0;o.check=function(){var p=Hash.data('p'),l=[];$(".hentry.unread.sent").each(function(){l.push($(this).attr('id').replace('mess',''));});if(l.length>0)Fajax.add('unreadedSent',l.join(','));if(p)Fajax.add('p',p);Fajax.send('post-hasNewMessage',Sett.page=='fpost'?'fpost':-1,true);};o.sentReaded=function(p){var l=p.split(',');for(var i in l){$("#mess"+l[i]).removeClass('unread');$("#unreadedLabel"+l[i]).remove();}};o.checkHandler=function(num,name){var d=$("#messageNew"),p=parseInt(Sett.msgTi);if(num>0){d.removeClass('hidden');$("#numMsg").text(num);$("#recentSender").text(name);}else if(!d.hasClass('hidden'))d.addClass('hidden');if(p>0){clearTimeout(o.t);o.t=setTimeout(o.check,p);}}};
/**LAZYLOADER*/
var Lazy=new function(){var o=this;o.r={};o.f=null;o.q=[];o.loading=false;o.load=function(l,f){var c=true;for(var i=0;i<l.length;i++)if(!o.r[l[i]]){c=false;break}if(c)return c;o.q.push({l:l.concat(),f:f});if(!o.loading)return o.p();};o.p=function(){while(o.q[0].l.length>0){var f=o.q[0].l.shift();if(!o.r[f]){o.loading=true;o.f=f;if(f.indexOf('.css')>-1){LazyLoad.css(f,function(){Lazy.c()});}else{LazyLoad.js(f,function(){Lazy.c()});}return;}}o.qc();return true;};o.c=function(){o.r[o.f]=true;if(o.q[0].l.length>0)o.p();else o.qc();};o.qc=function(){if(o.q[0].f)o.q[0].f();o.q.shift();if(o.q.length>0)o.p();else o.loading=false;}};
/* autogrow */ 
;(function($){$.fn.autogrow = function(options){this.filter('textarea').each(function(){var $this=$(this),minHeight=$this.height(),u=function(){var h=$this.height(),sh=$this.attr('scrollHeight');if(sh>h+5)$this.css('height',sh+60);};$this.unbind('change',u).unbind('keydown',u).change(u).keydown(u).change();});return this;}})(jQuery);
/* jQuery hashchange event - v1.3 - 7/21/2010 http://benalman.com/projects/jquery-hashchange-plugin/ Copyright (c) 2010 "Cowboy" Ben Alman, Dual licensed under the MIT and GPL licenses. http://benalman.com/about/license/ */
;(function($,e,b){var c="hashchange",h=document,f,g=$.event.special,i=h.documentMode,d="on"+c in e&&(i===b||i>7);function a(j){j=j||location.href;return"#"+j.replace(/^[^#]*#?(.*)$/,"$1")}$.fn[c]=function(j){return j?this.bind(c,j):this.trigger(c)};$.fn[c].delay=50;g[c]=$.extend(g[c],{setup:function(){if(d){return false}$(f.start)},teardown:function(){if(d){return false}$(f.stop)}});f=(function(){var j={},p,m=a(),k=function(q){return q},l=k,o=k;j.start=function(){p||n()};j.stop=function(){p&&clearTimeout(p);p=b};function n(){var r=a(),q=o(m);if(r!==m){l(m=r,q);$(e).trigger(c)}else{if(q!==m){location.href=location.href.replace(/#.*/,"")+q}}p=setTimeout(n,$.fn[c].delay)}$.browser.msie&&!d&&(function(){var q,r;j.start=function(){if(!q){r=$.fn[c].src;r=r&&r+a();q=$('<iframe tabindex="-1" title="empty"/>').hide().one("load",function(){r||l(a());n()}).attr("src",r||"javascript:0").insertAfter("body")[0].contentWindow;h.onpropertychange=function(){try{if(event.propertyName==="title"){q.document.title=h.title}}catch(s){}}}};j.stop=k;o=function(){return a(q.location.href)};l=function(v,s){var u=q.document,t=$.fn[c].domain;if(v!==s){u.title=h.title;u.open();t&&u.write('<script>document.domain="'+t+'"<\/script>');u.close();q.location.hash=v}}})();return j})()})(jQuery,this);
;function indexOf(arr,obj,start){for(var i=(start || 0);i<arr.length;i++)if(arr[i]==obj)return i;return -1;}
/*lazy google analytics load*/
function gaLoad(){var ga=document.createElement('script');ga.type='text/javascript';ga.async=true;ga.src=('https:'==document.location.protocol?'https://ssl':'http://www')+'.google-analytics.com/ga.js';var s = document.getElementsByTagName('script')[0];s.parentNode.insertBefore(ga, s);}