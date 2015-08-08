/** GALERY NEXT WITH PRELOADING */
//TODO: any image loaded - resize
/** RESIZE HANDLER */
var Resize = new function(){
	var o = this;
	o.t = 0;
	o.cw = 0;
	o.ch = 0;
	o.init = function(){
		$(window).resize(o.on).resize();
		$('.gallery img').load(function(){
			var w = $(window), ww = w.width(), wh = w.height();
			imgResizeToFit($(this), Fullscreen.isFullscreen ? ww : $('#fullscreenBox').width(), wh-(Fullscreen.isFullscreen ? 0 : 0), Fullscreen.isFullscreen);
		});
	};
	o.force = function () {
		o.cw = o.ch = 0;
		o.on();
	}
	o.on = function(){
		var w = $(window), ww = w.width(), wh = w.height();
		if(ww != o.cw || wh != o.ch) {
			o.cw = ww;
			o.ch = wh;
			//imgResizeToFit($('#detailFoto'), Fullscreen.isFullscreen ? ww : $('#fullscreenBox').width(), wh-(Fullscreen.isFullscreen ? 0 : 0), Fullscreen.isFullscreen);
			ImgNext.hud();
		}
		$('.gallery img').each(function(index) {
			imgResizeToFit($(this), Fullscreen.isFullscreen ? ww : $('#fullscreenBox').width(), wh-(Fullscreen.isFullscreen ? 0 : 0), Fullscreen.isFullscreen);
		});
	}
};

var ImgNext = new function(){
	var o = this;
	o.r = false;
	o.i = null;
	o.il = [];
	o.p = null;
	o.next = [];
	o.nav = [];
	o.top = 0;
	o.xhr = null;
	o.init = function(){
		if(!o.r){
			o.r = true;
			o.i = $("#detailFoto");
			o.i.bind('load', o.loaded);
			o.p = $(".showProgress");
		}
	};
	o.start = function(i){
		o.init();
		o.i.show();
		var h = o.p.height();
		o.p.css('height', (h > 0 ? h : $(window).height()) + 'px');
		o.i.removeAttr('src');
		if(o.next[i]){
			History.log('Galery showing:', i);
			o.i.attr('src', o.next[i]);
		}else{
			o.i.css('height', '0px');
		}
		if(o.nav[i]) {
			var $nextBtn=$("#nextButt"),$prevBtn=$("#prevButt"),nextHref = $nextBtn.attr('href'),prevHref = $prevBtn.attr('href');
			nextHref = nextHref.substr(0,nextHref.indexOf('i=')+2) + o.nav[i][0];
			prevHref = prevHref.substr(0,prevHref.indexOf('i=')+2) + o.nav[i][1];
			$nextBtn.attr('href',nextHref);
			$prevBtn.attr('href',prevHref);
		}
		return false;
	};
	o.loaded = function(){
		//TODO: do
		return;
		o.init();
		o.i.show();
		o.next[o.i.data('i')] = o.i.attr('src');
		Resize.force();
		
		if(!Fullscreen.isFullscreen)
			$("html, body").animate({ scrollTop: o.i.offset().top - (($(window).height() - o.i.height()) / 2) }, 600);
		
		Slideshow.next();
	};
	o.hud = function() {
		var $fb = $("#fullscreenBox");
		$fb.css('height','').css('width','');
		var w = $fb.width(), h = $fb.height();

		h = $(window).height();
		$(".gallery").width(w).height(h);
		//$("#photoToolbar").width(w).height(h);
		//$("#nextButt").height(h-48);
		//$("#prevButt").height(h-48);
		$(".gallery").flickity('resize').flickity('reposition');
		//
		History.log('ImgNext.hud',w+'x'+h);
	};
	o.xhrHand = function(currentId, nextId, prevId, currentUrl, nextUrl){
		o.init();
		o.nav[currentId]=[nextId,prevId];
		if(currentUrl && currentUrl != o.i.attr('src')) {
			o.i.attr('src', currentUrl).data('i', currentId);
		}
		if(nextUrl) {
			$("body").append('<img id="imgNextLoader'+nextId+'" class="noscreen" />');
			o.il[nextId] = $("#imgNextLoader"+nextId).load(o.preloaded);
			o.il[nextId].attr('src', nextUrl).data('i', nextId);
		}
	};
	o.preloaded = function(e){
		var $img = $(e.target);
		o.next[$img.data('i')] = $img.attr('src');
	}
};
/** SLIDESHOW */
var Slideshow = new function(){
	var o = this;
	o.on = false;
	o.t = 0;
	o.s = 5;
	o.f = function(){
		if(o.on)
			$("#prevButt").click();
	};
	o.toggle = function(){
		o.on = !o.on;
		o.next();
	};
	o.next = function(){
		clearTimeout(o.t);
		if(o.on)
			o.t = setTimeout(o.f, o.s * 1000);
	};
};
/** FULLSCREEN */
var Fullscreen = new function(){
	var o = this;
	o.el = null;
	o.tool = null;
	o.state = null;
	o.isFullscreen = false;
	o.d = $(document.documentElement);
	o.w = $(window);
	o.key = function(e){
		if(e.keyCode == 27)
			o.toggle();
		if(e.keyCode == 32)
			$("#nextButt").click();
	}
	o.init = function(){
		o.el = $('#fullscreenBox');
		if(o.el.length==0)return;
		listen('aFullscreen', 'click', o.toggle);
		$("#fullscreenLeave").click(o.toggle);
		$("#prevButt").click(function(){
			return false;
		});
		$("#nextButt").click(function(){
			return false;
		});
		o.tool = $("#photoToolbar");
		var fs = $("#aSlideshow img");
		fs.click(function(){
			fs.attr('src',fs.attr('src').replace(Slideshow.on?'stop':'play',Slideshow.on?'play':'stop'));
			Slideshow.toggle();
			return false;
		});
		Resize.init();
	};
	o.toggle = function(){
		if(!o.isFullscreen)
			o.onEnter();
		else
			o.onExit();
		return false;
	};
	o.onEnter = function() {
		o.isFullscreen = true;
		o.state = {
			y : o.w.scrollTop()
		};
		o.d.bind('keyup', o.key);
		o.el.addClass('fullscreen');
		o.w.scrollTop(0).scrollLeft(0);
		$('body').css('overflow', 'hidden');
		$("#fullscreenHint").removeClass('hidden').css('top', ((o.w.height() - $("#fullscreenHint").height()) / 2)).show().delay(1000).fadeOut('slow');
		Resize.force();
	};
	o.onExit = function(){
		o.isFullscreen = false;
		o.d.unbind('keyup', o.key);
		o.el.removeClass('fullscreen');
		$('body').css('overflow', 'auto');
		if(o.state.y>0) o.w.scrollLeft(o.state.y);
		o.state = null;
		Resize.force();
	};
};

/** IMAGE RESIZE TO FIT */
function imgResizeToFit(img, hw, hh, isFullscreen){
	img.removeAttr('height').removeAttr('width').css('width', 'auto').css('height', 'auto').css('top','').css('left','').css('position','').css('margin-top','');
	var iw = img.width(), ih = img.height(), th = hh;
	if(iw < hw) hw=iw;
	if(ih < hh) hh=ih;
	var r = Math.min(hw / iw, hh / ih), nh = Math.round(ih * r);
	img.css('height', nh).css('width', Math.round(iw * r));
	if(isFullscreen && th>ih) img.css('margin-top', (th - ih) / 2);
}