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
function loadSidebarPanel($panel) {
	Fajax.add('panel', $panel.data('src'));
	Fajax.send('sidebar-get', _fdk.cfg.page);
}
function boot(){
	//load language
	var defaultLang = 'cs';
	Lazy.load([_fdk.cfg.jsUrl+'i18n/_fdk.lng.'+(_fdk.cfg.lang ? _fdk.cfg.lang : defaultLang)+'.js'],boot);
	
	if(isInit) return;
	isInit = true;
	
	$("img").unveil();
	//$("img").each(function(){var dsrc = $(this).data('src');if(dsrc) $(this).attr('src',dsrc);});
	
	$(".sidebar-content").each(function(){
		var $panel = $(this);
		var delay = parseInt($(this).data('delay'));
		if(delay>0) setTimeout(function(){loadSidebarPanel($panel);},delay);
		else loadSidebarPanel(this);
		
	});
	
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
		var h = $(e.target).attr("href"),t = $(e.target).data("target");if(!t) return;
		if ($(t).is(':empty')) {$.ajax({type: "GET",url: h.replace("show","show-x"),complete: function(a){if(a.responseText)$(t).html(a.responseText);}})};
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
	//TODO: fix galery detail ajax loading $("#galeryFeed a").each(function(){$(this).addClass('history');});

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
			Fajax.action(State.data.action+'/i='+State.data.i+'/0/'+State.data.eid);
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
		$("#selectAllCheckbox").change(function(){
			var checked = $(this).is(":checked")
            $(":checkbox").each(function(){
			if(checked) {
				$(this).attr('checked','checked');
			} else {
				$(this).removeAttr('checked');
			}
			});
		});
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
			if(rec) $("#recipient").val(rec.join(',')).change();
			avatarfrominput();
		});
		GaleryEdit.init(_fdk.cfg,_fdk.lng.galery);
		if(parseInt(_fdk.cfg.msgTi) > 0)
			Msg.startPoll();
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
	//$( '.markitup' ).ckeditor();
}
function calendarInit() {
	if($("#calendar-inline").length == 0) return
	if(!Lazy.load(_fdk.load.ui, calendarInit)) return;
	var date = $('#calendar-inline').data('dateset');
	var viewMode = $('#calendar-inline').data('minviewmode');
	var getDate = gup('date',window.location.href);
	if(getDate) {
		if(getDate.length==4) getDate += '-01-01';
		else if(getDate.length==7) getDate += '-01';
		date = getDate;
	}
	calendarDate = new Date(date);
	$('#calendar-inline').on('show',function(){
		$('th.datepicker-switch').on('click',calendarInlineInvalidate);
		$('th.next').on('click',calendarInlineInvalidate);
		$('th.prev').on('click',calendarInlineInvalidate);
		/*$('span.year').tooltip({container: 'body',placement: 'left'});
		$('span.month').tooltip({container: 'body',placement: 'left'});
		$('td.day').tooltip({container: 'body',placement: 'left'});*/
		calendarInlineInvalidate();
	}).datepicker({ date:date, language: "cs", minViewMode: viewMode ? viewMode : 0, weekStart: 1})
	.on('changeYear',calendarInlineInvalidate)
	.on('changeDate', function(e){
		var viewMode = calendarViewMode();
		var cat = gup('c',window.location.href);
		var uri = "?k="+_fdk.cfg.page+(cat?'&c='+cat:'')+"&date="+e.date.getFullYear() 
		+ (viewMode < 2 ? '-' + ('0' + (e.date.getMonth()+1)).slice(-2) : '' )
		+ (viewMode < 1 ? '-' + ('0' + e.date.getDate()).slice(-2) : '' );
		window.location.replace(uri);
	}).on('changeMonth', calendarInlineInvalidate);
	if(date) {
		var d=date.split('-'),da=new Date(parseInt(d[0]), parseInt(d[1])-1, parseInt(d[2]));
		$('#calendar-inline').datepicker('update', da);
	}
}
function calendarViewMode() {
	var cal = $('#calendar-inline')[0];
	if($(".datepicker-months",cal).css('display')=='block') return 1;
	if($(".datepicker-years",cal).css('display')=='block') return 2;
	return 0;
}
function calendarInlineInvalidate(e){
	if(e && e.date) calendarDate=e.date;
	if(calendarIsInvalid) return;
	calendarIsInvalid=true;
	setTimeout(calendarInlineUpdate,10);
}
var calendarDate;
var calendarFirstInit=true;
var calendarDataLoaded={};
var calendarIsInvalid=false;
function calendarInlineUpdate() {
	var year,month;
	calendarIsInvalid=false;
	var $cal = $('#calendar-inline'), viewMode = calendarViewMode();
	if(!calendarFirstInit && (viewMode==2 || calendarDate)) {
		if(calendarDate) { 
			year=calendarDate.getFullYear();
			month=parseInt(calendarDate.getMonth())+1;
		}
		var loading = viewMode+'-'+(viewMode<2?year+(viewMode<1?'-'+month:''):'');
		if(!calendarDataLoaded[loading]) {
			Fajax.add('loading', loading);
			if(viewMode) Fajax.add('viewmode', viewMode);
			if(viewMode<1) Fajax.add('month', month);
			if(viewMode<2) Fajax.add('year', year);
			Fajax.send('calendar-show', '');
		} else calendarLoading=null;
	}
	calendarFirstInit=false
	var dayEvents = $(".event",$cal[0]);
	$("td.day",$cal[0]).each(function(){$(this).removeClass('active');});
	$("span.month",$cal[0]).each(function(){$(this).removeClass('active');});
	$("span.year",$cal[0]).each(function(){$(this).removeClass('active');});
	dayEvents.each(function(){
		$("span",this).remove();
		var ed = String($(this).data('date'));
		var tooltip = $(this).html();
		if(viewMode==2) {
			$("span.year",$cal[0]).each(function(){
				$(this).removeClass('old');
				$(this).removeClass('new');
				if($(this).html()==ed) {
					$(this).addClass('active');
					$(this).attr('title',tooltip);
				}
			});
		} else if(viewMode==1) {
			var thisYear = $(".datepicker-switch",$cal[0]).html().substr(-4);
			if(ed.indexOf(thisYear)==0) {
				var edMonth = parseInt(ed.substr(5));
				var monthIndex=1;
				$("span.month",$cal[0]).each(function(){
					if(monthIndex==edMonth) {
						$(this).addClass('active');
						$(this).attr('title',tooltip);
					}
					monthIndex++;
				});
			}
		} else {
			var date = calendarDate ? calendarDate : $cal.datepicker('getDate');
			var thisDate = date.getFullYear()+'-'+('0' + (date.getMonth()+1)).slice(-2)+'-';
			if(ed.indexOf(thisDate)==0) {
				$("td.day",$cal[0]).each(function(){
					var thisDay = parseInt($(this).html());
					var edDay = parseInt(ed.substr(8));
					if(thisDay==edDay && !$(this).hasClass('new') && !$(this).hasClass('old')) {
						$(this).addClass('active');
						$(this).attr('title',tooltip);
					}
				});
			}
		}
	});
}
function datePickerInit(){
	if($(".date").length == 0) return;
	if(!Lazy.load(_fdk.load.ui, datePickerInit)) return;
	$('.date').datepicker({todayBtn: true,weekStart: 1,autoclose: true,language: "cs",calendarWeeks: true,todayHighlight: true,format: 'dd.mm.yyyy'});
};
function calendarLoaded(loading) {
calendarDataLoaded[loading]=true;
}
function calendarUpdate(data) {
	data = data.split("\n");
	for( var i in data){
		var id = $(data[i]).data('id');
		if($("#calendar-inline div.event[data-id='"+id+"']").length == 0) {
			$("#calendar-inline").append(data[i]);
		}
	}
	calendarInlineInvalidate();
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
function scrollToBottom(id) {
	$("#"+id).animate({ scrollTop: $('#'+id)[0].scrollHeight}, 1000);
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
	Fajax.add('username', $("#recipient").val());
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
	o.getData = function() {
		var p = gup('p',window.location.href), l = [],xs=[];
		$(".fitem.unread.sent").each(function(){ l.push($(this).attr('id').replace('mess', '')); });
		$(".msg-xs").each(function(){ xs.push($(this).attr('id').replace('messxs', '')); });
		if(l.length > 0) Fajax.add('unreadedSent', l.join(','));
		if(xs.length > 0) Fajax.add('xsShow', xs.join(','));
		if(_fdk.cfg.page=='fpost' && p>0) Fajax.add('p', p);
		return Fajax.request.get();
	}
	o.startPoll = function(){
		$.PeriodicalUpdater('?m=post-poll-x&k='+_fdk.cfg.page, {data:o.getData,minTimeout: _fdk.cfg.msgTi/2,maxTimeout: _fdk.cfg.msgTi,multiplier: 2}, function(remoteData, success, xhr, handle){Fajax.response.run(remoteData);});
	};
	o.chatInit = function() {
		$(".msg-text").off('keypress').on('keypress',function (e) {if (e.which == 13) {
		$('#msgBtnSubmit').click();
		e.preventDefault();
		$('#msgList').append('<div class="msg-xs" id="messxs0">'+$("#msgText").val()+'</div>');
		$("#msgText").val('');
		scrollToBottom('msgList');
		}});
	};
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