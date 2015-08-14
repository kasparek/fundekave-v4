/** INITIALIZATION ON DOM */
var isInit=false;
var isMobile=false;
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

	var check = false;
	(function(a){if(/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i.test(a)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0,4)))check = true})(navigator.userAgent||navigator.vendor||window.opera);
	isMobile = check;

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
				if($("#topImageLink").length===0) {
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
	if(!isMobile) setTimeout(function(){if($("#galeryFeed").length>0) {Fajax.send('page-thumbs', _fdk.cfg.page);}},1000);

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
	//if($("#detailFoto").length>0) {ImgNext.init();}
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
			if(!isMobile) {
				$('.gallery').flickity('select',$("div[data-itemid='" + State.data.i + "']").index());
			}
			//ImgNext.start(State.data.i);
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
	//http://www.tinymce.com/wiki.php/Controls - complete list of controls
	tinymce.remove();
	tinymce.init({selector:".markitup"
		,menubar: false
		,toolbar: "save | undo redo | styleselect | bold italic | link unlink image | alignleft aligncenter alignright alignjustify bullist blockquote | visualblocks code fullscreen"
		,mode:'textareas'
		,toolbar_items_size: 'small'
		,save_onsavecallback: function(a) { var form = $("#"+a.id)[0].form; console.log("TinyMCE Save"); Fajax.form(null,form); return false;}
		,plugins:["autoresize",
		"autosave save",
		"advlist autolink lists link image anchor",
        "searchreplace visualblocks code fullscreen",
        "media contextmenu paste"]});
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