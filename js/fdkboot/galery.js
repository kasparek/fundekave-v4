/** GALERY NEXT WITH PRELOADING */
//TODO: any image loaded - resize
/** RESIZE HANDLER */
(function( $ ) {
    $.fn.pop = function() {
        var top = this.get(-1);
        this.splice(this.length-1,1);
        return top;
    };

    $.fn.shift = function() {
        var bottom = this.get(0);
        this.splice(0,1);
        return bottom;
    };
})( jQuery );

;(function($) {

  $.fn.funveil = function(threshold, callback) {

    var $w = $(window),
        th = threshold || 0,
        retina = window.devicePixelRatio > 1,
        attrib = retina? "data-lazy-retina" : "data-lazy",
        images = this,
        loaded;

    $(this).height($w.height()).width('100%').addClass('funveil');

    this.one("unveil", function() {
      var source = this.getAttribute(attrib);
      source = source || this.getAttribute("data-src");
      if (source) {
        this.setAttribute("src", source);
        if (typeof callback === "function") callback.call(this);
      }
    });

    function unveil() {
      var inview = images.filter(function() {
        var $e = $(this);
        if ($e.is(":hidden")) return;

        var wt = $w.scrollTop(),
            wb = wt + $w.height(),
            et = $e.offset().top,
            eb = et + $e.height();

        return eb >= wt - th && et <= wb + th;
      });

      loaded = inview.trigger("unveil");
      images = images.not(loaded);
    }

    $w.on("scroll.unveil resize.unveil lookup.unveil", unveil);

    unveil();

    return this;

  };

})(window.jQuery || window.Zepto);

var Resize = new function(){
	var o = this;
	o.t = 0;
	o.cw = 0;
	o.ch = 0;

	o.flktySelected = null;
	o.flktyTimeout = 0;

	o.queue = null;

	o.lazyLoad = function(img) {
		$img = $(img);
		if(!$(img).data('lazy')) {o.nextQueue();return;}
		$(img).attr('src',$(img).data('lazy'));
		$(img).data('lazy',null);
	};
	o.isQueue = false;
	o.doQueue = function() {
		o.queue = $('img.gallery-cell-image');
		o.queue.shift();
		o.lazyLoad(o.queue.pop());
		if(o.queue.length>0) o.isQueue = true;
	};
	o.nextQueue = function(){
		if(!o.isQueue) return;
		if(o.queue.length===0) {o.isQueue=false;return;}
		o.lazyLoad(o.queue.shift());
	};

	o.unveilInit = function() {
		$(".gallery-cell-image").funveil(500);
	}
	o.currentIndex = 0;
	o.numCell = 0;
	o.init = function(){
		if(isMobile) {
			$(window).on('scroll',function(){
				clearTimeout($.data(this, 'scrollTimer'));
			    $.data(this, 'scrollTimer', setTimeout(function() {
			    	console.log('scroll stopped');
			    	var index=0;
			        $(".gallery-cell").each(function(){
			        	$i = $(this), $w = $(window);

						var docViewTop = $w.scrollTop();
						var docViewBottom = docViewTop + $w.height();

						var elemTop = $i.offset().top;
						var elemBottom = elemTop + $i.height();

						//return ((elemBottom <= docViewBottom) && (elemTop >= docViewTop));
						if(elemBottom >= docViewTop) {
							o.currentIndex = index;
							//if(o.currentIndex<0) o.currentIndex=0;
							return false;
						}
						index++;
			        });
			    }, 250));
			});
			$("#galleryNext").on("click",function(){
				if(o.numCell===0) o.numCell = $("div.gallery-cell").length;
				o.currentIndex++;
				if(o.currentIndex>=o.numCell) o.currentIndex = 0;
				var i = $($('div.gallery-cell')[o.currentIndex]);
				if(Fullscreen.isFullscreen) {
					$('.gallery').prepend(i);
					//check if current is loaded
					o.lazyLoad($('img',i));
					//load next
					var ni = $($('div.gallery-cell')[o.currentIndex+1]);
					if(ni) o.lazyLoad($('img',ni));
				} else {
					$("html, body").animate({ scrollTop: i.offset().top - (($(window).height() - i.height()) / 2) }, 600);
				}
				var itemId = i.data('itemid');
				History.pushState({action:'item-show',eid:itemId,i:itemId}, "Loading ...", "?i="+itemId);
			});
			$("#galleryPrev").on("click",function(){
				if(o.numCell===0) o.numCell = $("div.gallery-cell").length;
				o.currentIndex--;
				if(o.currentIndex<0) o.currentIndex = 0;
				var i = $($('div.gallery-cell')[o.currentIndex]);
				if(Fullscreen.isFullscreen) {
					$('.gallery').prepend(i);
					o.lazyLoad($('img',i));
				} else {
					$("html, body").animate({ scrollTop: i.offset().top - (($(window).height() - i.height()) / 2) }, 600);
				}
				var itemId = i.data('itemid');
				History.pushState({action:'item-show',eid:itemId,i:itemId}, "Loading ...", "?i="+itemId);
			});
		}
		$(window).resize(o.on).resize();
		//flickity init
		var firstImg = $('img.gallery-cell-image:first')[0];
		if(firstImg.complete) {
			if(isMobile) o.unveilInit(); else setTimeout(o.doQueue,500);
		} else {
			$(firstImg).on('load',function(){
				if(isMobile) o.unveilInit(); else setTimeout(o.doQueue,500);
			});
		}
		$('.gallery img').load(function(){
			var w = $(window), ww = w.width(), wh = w.height();
			imgResizeToFit($(this), Fullscreen.isFullscreen ? ww : $('#fullscreenBox').width(), wh-(Fullscreen.isFullscreen ? 0 : 0), Fullscreen.isFullscreen);
			ImgNext.hud();
			if(o.isQueue && o.queue.length>0) {
				o.nextQueue();
			}
		}).error(function(){
			var stamp = new Date().getTime();
			var url = $(this).attr('src')+'?'+stamp;
			$(this).attr('src',url);
			console.log('Error Loading Image '+url);
		});
		//init flickity
		$(".js-flickity").on('cellSelect',function(){
			var flkty = $(".gallery").data('flickity');
			if(!flkty) return;
			var cell = flkty.selectedElement,changeState = true;
			if(!o.flktySelected) changeState = false;
			if(o.flktySelected == cell) changeState = false;
			o.flktySelected = cell;
			if(!changeState) return;
			var img = $("img",cell);
			if($(img).data('lazy')) {
				$(img).attr('src',$(img).data('lazy'));
				$(img).data('lazy',null);
			}
			if(o.flktyTimeout) clearTimeout(o.flktyTimeout);
			o.flktyTimeout = setTimeout(function(){
				var itemId = $(cell).data('itemid');
				History.pushState({action:'item-show',eid:itemId,i:itemId}, "Loading ...", "?i="+itemId);
			},1000);
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
			imgResizeToFit($(this), Fullscreen.isFullscreen ? ww : $('#fullscreenBox').width(), wh-(Fullscreen.isFullscreen ? 0 : 0), Fullscreen.isFullscreen, Fullscreen.isFullscreen);
		});
	};
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
	o.thumbsInit = function() {
		$(".thumbnail-xs a").each(function(){$(this).on('click',function(e){
			var i = gup("i", e.currentTarget.href);
			History.pushState({action:'item-show',eid:$(e.target).attr('id'),i:i}, "Loading ...", "?i="+i);
			var i = $("#fullscreenBox");
			$("html, body").animate({ scrollTop: i.offset().top - (($(window).height() - i.height()) / 2) }, 600);
			return false;
		});});
	};
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
		//keet the original in case we reverse funcionality
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

		//resizing images for flickty
		h = $(window).height();
		var maxHeight = 0;
		$(".gallery-cell img").each(function(){
			var h = $(this).height();
			if(h > maxHeight) maxHeight = h;
		});
		if(!isMobile) {
			$(".gallery").width(w).height(maxHeight);
		}
		//$(".gallery").flickity('resize').flickity('reposition');
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
		if(o.on) {
			if(!isMobile) {
				$(".gallery").flickity('next');
			} else {
				$("#galleryNext").click();
			}
			//$("#prevButt").click();
			o.next();
		}
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
	};
	o.init = function(){
		o.el = $('#fullscreenBox');
		if(o.el.length===0)return;
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
function imgResizeToFit(img, hw, hh, isFullscreen,upScale){
	img.removeAttr('height').removeAttr('width').css('width', 'auto').css('height', 'auto').css('top','').css('left','').css('position','').css('margin-top','');
	var iw = img.width(), ih = img.height(), r = Math.min(hw / iw, hh / ih),tw = Math.round(r*iw),th = Math.round(r*ih);
	if(!upScale && (tw>iw || th>ih)) {tw=iw;th=ih;}
	if(!upScale && isFullscreen && hh>ih) img.css('margin-top', (hh - ih) / 2);
	img.css('height', Math.round(th)).css('width', Math.round(tw));
}