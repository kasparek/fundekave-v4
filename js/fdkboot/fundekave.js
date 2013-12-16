/** INITIALIZATION ON DOM */
var isInit=false;
var topBannerHeight = 0;
var topBannerMargin = 0;
function topBannerPosition() {
	if(topBannerHeight>0)return;
	var valign = $(".top-image").data('valign'),bh = $(".top-image").height(),ih = $(".top-image img").height(),iy = 0;
	if(valign=='middle') iy=(bh-ih)/2;
	else if(valign=='bottom') iy=bh-ih;
	iy += $(".top-image").data('margin');
	iy=iy>0?0:(iy+ih<bh?bh-ih:iy);
	$(".top-image img").stop().animate({'margin-top':iy+'px'},100);
}
function boot(){
	//load language
	var defaultLang = 'cs';
	Lazy.load([_fdk.cfg.jsUrl+'i18n/_fdk.lng.'+(_fdk.cfg.lang ? _fdk.cfg.lang : defaultLang)+'.js'],boot);
	
	if(isInit) return;
	isInit = true;
	
	if(window.location.pathname!='/') {
		_fdk.fuup.fuga.service.url = window.location.pathname + _fdk.fuup.fuga.service.url;
	}
	
	$("#head-banner").on('click',function(){
		var imgh = $("#head-banner img").height();
		if(topBannerHeight==0) { 
			topBannerHeight = $(".top-image").css('height');
			topBannerMargin = $(".top-image img").css('marginTop');
			$(".top-image").animate({height:imgh+'px'}, 500);
			$(".top-image img").animate({marginTop:'0px'}, 500);
			if($(this).attr('href').length>0) {
				if($("#topImageLink").length==0) {
$(".top-image").append('<div id="topImageLink" class="alert alert-info" style="top:'+(parseInt(topBannerHeight.replace('px',''))-60)+'px;"><a href="'+$(this).attr('href')+'">'+$(this).attr('title')+'</a>');
			}
			}
		} else {
			$(".top-image").animate({height:topBannerHeight}, 500);
			$(".top-image img").animate({marginTop:topBannerMargin}, 500);
			topBannerHeight=0;
		}
		return false;
	});
	if($("#head-banner").length>0) {
		$(window).resize(topBannerPosition).resize();
		$(".top-image img").on('load',topBannerPosition);
	}
	
	$('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
		var h = $(e.target).attr("href"),t = $(e.target).data("target");
		if(!t) return;
		if ($(t).is(':empty')) {$.ajax({type: "GET",url: h.replace("show","show-x"),error: function(data){
			alert("There was a problem loading tab content");
		},success: function(data){$(t).html(data);}})};
	});
	
	gaLoad();
	calendarInit();
	if($("#detailFoto").length>0) {
		ImgNext.init();
	}
	
	var w = $(window).width();
	if($("#sidebar").length == 0)
		$('body').addClass('bodySidebarOff');
	$(".expand").autogrow();
	$(".opacity").bind('mouseenter', function(){
		$(this).fadeTo("fast", 1);
	}).bind('mouseleave', function(){
		$(this).fadeTo("fast", 0.5);
	});
	fajaxInit();
	fconfirmInit();
	switchOpen();
	
	$(".thumbnail-xs a").each(function(){$(this).addClass('history');});

	$(".mapThumbLink").bind('click', gooMapiThumbClick);
	if($(".geoInput").length > 0 || $(".mapLarge").length > 0) gooMapiInit();

	History.Adapter.bind(window,'statechange',function(){ 
		var State = History.getState();
		History.log('statechange:', State.data, State.title, State.url);
		if(parseInt(State.data.i)>0) {
			if(Fajax.xhrList['item-show']) {
				History.log('cancel ajax:', 'item-show');
				Fajax.cancel('item-show');
			}
			ImgNext.start(State.data.i);
			Fajax.action(State.data.action+'/item:'+State.data.i+'/0/'+State.data.eid);
		}
	});
	listen('history', 'click', function(e){
		var i = gup("i", e.currentTarget.href);
		History.pushState({action:'item-show',eid:$(e.target).attr('id'),i:i}, "Loading ...", "?i="+i);
		return false;
	});
	
	
	slimboxInit();
	Fullscreen.init();
	fuupInit();
	datePickerInit();
	if(parseInt(_fdk.cfg.user) > 0){
		ckedInit();
		$("#recipient").change(avatarfrominput);
		$('#ppinput').hide();
		$("#saction").change(function(evt){
			if($("#saction option:selected").attr('value') == 'setpp')
				$('#ppinput').show();
			else
				$('#ppinput').hide();
		});
		$("#recipientList").change(function(evt){
			var rec = [];
			$("#recipientList option:selected").each(function(){rec.push($(this).text());});
			if(rec) $("#recipient").attr("value", rec.join(',')).change();
			avatarfrominput();
		});
		GaleryEdit.init(_fdk.cfg,_fdk.lng.galery);
		if(parseInt(_fdk.cfg.msgTi) > 0)
			Msg.check();
		var perm = $("#accessSel");
		if(perm.length > 0)
			perm.change(function(){
				var v = $(this).val();
				if(v == 0)
					$("#rule1").show();
				else
					$("#rule1").hide();
			}).change();
	}
};

function recaptchaStart(){
	if(!Lazy.load([
		'http://www.google.com/recaptcha/api/js/recaptcha_ajax.js'
	], recaptchaStart))
		return;
	Recaptcha.create("6LexXNkSAAAAAE_BDWQHhapdx-XPHItdWgBvDTSm", 'recaptchaBox', {
		tabindex : 3
	});
}

/** INIT jQuery UI and everything possibly needed for ajax forms and items */
function jUIInit(){
	if(!Lazy.load(_fdk.load.ui, jUIInit))
		return;
	datePickerInit();
	ckedInit();
	fajaxInit();
	fconfirmInit();
	gooMapiInit();
	fuupInit();
	slimboxInit();
	GaleryEdit.init(_fdk.cfg,_fdk.lng.galery);
	$(".expand").autogrow();
};

function gooMapiInit(){
	if(!Lazy.load(_fdk.load.goomapi, gooMapiInit))
		return;
	GooMapi.init(_fdk.cfg,_fdk.lng.goomapi);
};

var gooMapiThumbClickElement = null;
function gooMapiThumbClick(){
	$(this).unbind('click', gooMapiThumbClick);
	if(!gooMapiThumbClickElement)
		gooMapiThumbClickElement = this;
	if(!Lazy.load(_fdk.load.goomapi, gooMapiInit))
		return;
	$(gooMapiThumbClickElement).click();
	gooMapiThumbClickElement = null;
}
function ckedInit() {
	if($(".markitup").length == 0) return
	if(!Lazy.load(_fdk.load.richta, ckedInit)) return;
	$( '.markitup' ).ckeditor();
}
function calendarInit() {
	if($("#calendar-inline").length == 0) return
	if(!Lazy.load(_fdk.load.ui, calendarInit)) return;
	$('#calendar-inline').datepicker({
    language: "cs",
	weekStart: 1,
    beforeShowDay: function (date){
		var cal = $("#calendar-inline")[0], day = date.getDate(), month = date.getMonth()+1, year= date.getFullYear(),dayEvents = $(".event",cal),ret = {};
		if(dayEvents.length > 0) {
			var tooltip = '';
			var cl = 'active';
			dayEvents.each(function(){
			$("span",this).remove();
				var ed = $(this).data('date').split('-');
				if(parseInt(ed[2]) == day && parseInt(ed[1]) == month && (parseInt(ed[0])==year || $(this).data('repeat')=='year')) {
					tooltip += $(this).html() + "\n";
					if($(this).data('repeat')=='year') cl='active';
			}});
			if(tooltip.length>0) {
				ret.tooltip = tooltip;
				ret.classes = cl;
			}
		}
		return ret;
    }}).on('changeDate', function(e){
		window.location.replace("?k="+_fdk.cfg.page+"&date="+e.date.getFullYear() + '-' + ('0' + (e.date.getMonth()+1)).slice(-2) + '-' + ('0' + e.date.getDate()).slice(-2));
	}).on('changeMonth', function(e){
		Fajax.add('month', ('0' + (e.date.getMonth()+1)).slice(-2));
		Fajax.add('year', e.date.getFullYear());
		Fajax.send('calendar-show', '');
	});
}
function datePickerInit(){
	if($(".date").length == 0) return;
	if(!Lazy.load(_fdk.load.ui, datePickerInit)) return;
	$('.date').datepicker({todayBtn: true,weekStart: 1,autoclose: true,language: "cs",calendarWeeks: true,todayHighlight: true,format: 'dd.mm.yyyy'});
};

function calendarUpdate(data) {
	data = data.split("\n");
	for( var i in data){
		var id = $(data[i]).data('id');
		if($("#calendar-inline div.event[data-id='"+id+"']").length == 0) {
			$("#calendar-inline").append(data[i]);
		}
	}
	$("#calendar-inline").datepicker('update');
}
function slimboxInit() {
	if($("a[rel^='lightbox']").length == 0 && $(".fotomashup").length == 0) return;
	if(!Lazy.load(_fdk.load.colorbox, slimboxInit)) return;
	$(".fotomashup a").colorbox({	rel:'grp1',
	title:function(){var url = $(this).attr('href');return '<a href="' + url + '">Album '+$(this).attr('title')+'</a>';},
	href:function(){return $('img',this).data('image');},
	scalePhotos:true,maxHeight:'100%',maxWidth:'100%'}); 
	$("a[rel^='lightbox']").colorbox({scalePhotos:true,maxHeight:'100%',maxWidth:'100%'}); 
};
function fuupInit(){
	if($(".fuup").length == 0) return
	if(!Lazy.load(_fdk.load.swf, fuupInit)) return;
	$(".fuup").each(function(i){
		var id = $(this).attr('id'), fuupConf = $.base64.encode(JSON.stringify(_fdk.fuup[id]));
		swfobject.embedSWF(_fdk.cfg.assUrl + "load.swf", id, "100", "25", "10.0.12", _fdk.cfg.assUrl + "expressInstall.swf", {
			file : _fdk.cfg.assUrl + "Fuup.swf",
			config : fuupConf,
			containerId : id
		}, {
			menu : "false",
			scale : "noScale",
			allowFullscreen : "true",
			allowScriptAccess : "always",
			bgcolor : "",
			wmode : "transparent"
		});
	});
}

/** request init */
function friendRequestInit(text){
	$('#friendrequest').remove();
	$("#sysmsgBox").after(text);
	$('#friendrequest').removeClass('hidden').show('slow');
	fajaxInit();
	$('#cancel-request').off('click').on('click', function(event){remove('friendrequest');return false;});
};
/** ajax link init */
function fajaxInit(){
	Fajax.init();
};
function fconfirmInit(event){
	$('.confirm').each(function(){
		var pf = false;
		if(this.form)
			pf = $(this.form).hasClass('fajaxform');
		if(!$(this).hasClass('fajaxa') && !pf){
			$(this).bind('click', onConfirm);
		}
	});
};
function onConfirm(e){
	if(!confirm($(e.currentTarget).attr("title"))){
		preventAjax = true;
		e.preventDefault();
	}
};
/** simple functions */
function shiftTo(y){
	if(!y)
		y = 0;
	$(window).scrollTop(y);
}
function enable(id){
	$('#' + id).removeAttr('disabled');
};
function remove(id){
	$('#' + id).remove();
};
function switchOpen(){
	$('.switchOpen').click(function(){
		$('#' + this.rel).toggleClass('hidden');
		return false;
	});
};
function listen(c, e, f){
	$("." + c).unbind(e, f).bind(e, f);
};
function gup(n, url){
	n = n.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
	var r = new RegExp("[\\?&|]" + n + "=([^&#|]*)"), res = r.exec(url);
	return res === null ? 0 : res[1];
};

var msgId = 1;
function msg(type, text){
	$("#sysmsgBox").append('<div id="sysmsg'+msgId+'" class="alert alert-'+type+' alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>'+text+'</div>');
	$("#sysmsg"+msgId).delay('10000').hide('slow');
	msgId++;
};

function redirect(dir){
	window.location.replace(dir);
};

/** AVATAR FROM input IN fpost */
function avatarfrominput(evt){
	Fajax.add('username', $("#recipient").attr("value"));
	Fajax.add('call', 'fajaxInit');
	Fajax.send('post-avatarfrominput', '');
}

/** IMAGE UPLOADING TOOL HANDLERS - FUUP */
function fuupUploadComplete(k, v){
	if(k == 'error')
		alert(_fdk.lng.galery[v]);
	if(k != 'complete')
		return;
	var i = $('#i').attr('value');
	if(i > 0)
		Fajax.add('i', i);
	Fajax.add('call', 'jUIInit');
	Fajax.send('item-image', _fdk.cfg.page);
}

function tempStoreDeleteInit(){
	$("#tempStoreButt").click(function(e){
		$("#imageHolder").html('');
		Fajax.send('item-tempStoreFlush', _fdk.cfg.page);
		e.preventDefault();
		return false;
	});
}

/** MSG CHAT FUNCTIONS */
var Msg = new function(){
	var o = this;
	o.t = 0;
	o.check = function(){
		var p = 0, l = [];
		$(".hentry.unread.sent").each(function(){
			l.push($(this).attr('id').replace('mess', ''));
		});
		if(l.length > 0)
			Fajax.add('unreadedSent', l.join(','));
		if(p)
			Fajax.add('p', p);
		Fajax.send('post-hasNewMessage', _fdk.cfg.page == 'fpost' ? 'fpost' : -1, true);
	};
	o.sentReaded = function(p){
		var l = p.split(',');
		for( var i in l){
			$("#mess" + l[i]).removeClass('unread');
		}
	};
	o.checkHandler = function(num, name){
		var d = $("#message-new"), p = parseInt(_fdk.cfg.msgTi);
		if(num > 0){
			d.removeClass('hidden');
			$("#numMsg").text(num);
			$("#recentSender").text(name);
		}else if(!d.hasClass('hidden'))
			d.addClass('hidden');
		if(p > 0){
			clearTimeout(o.t);
			o.t = setTimeout(o.check, p);
		}
	}
};

/* autogrow */
(function($){
	$.fn.autogrow = function(options){
		this.filter('textarea').each(function(){
			var $this = $(this), minHeight = $this.height(), u = function(){
				var pt = parseInt($this.css('padding-top').replace('px','')),pb = parseInt($this.css('padding-bottom').replace('px','')),lh = parseInt($this.css("line-height").replace("px", "")),h = $this.height(), sh = $this.prop('scrollHeight')-(pt+pb);
				if(sh > h)
					$this.css('height', sh + (lh*2));
			};
			$this.off('change', u).on('keydown', u).change(u).keydown(u).change();
		});
		return this;
	}
})(jQuery);

function indexOf(arr, obj, start){
	for( var i = (start || 0); i < arr.length; i++)
		if(arr[i] == obj)
			return i;
	return -1;
}

/* lazy google analytics load */
function gaLoad(){
	var ga = document.createElement('script');
	ga.type = 'text/javascript';
	ga.async = true;
	ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
	var s = document.getElementsByTagName('script')[0];
	s.parentNode.insertBefore(ga, s);
}