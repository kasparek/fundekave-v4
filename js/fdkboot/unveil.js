;(function($) {
	$.fn.unveil = function(data, threshold, defaultHeight, callback) {
		var $w = $(window), attr = "data-"+(data || "src"),th = threshold || 0,images = this,loaded;
    	if(defaultHeight) {
    		$(this).height($w.height());
    	}
		this.one("unveil", function() {
			var source = this.getAttribute(attr);
			if (source) {
				$(this).data(attr.replace('data-',''),'');
				this.setAttribute("src", source);
				console.log("unveil::one: "+source);
				if (typeof callback === "function") callback.call(this);
			}
		});
		function unveil() {
			var inview = images.filter(function() {
				var $e = $(this);
				if(!$e.attr(attr)) return;
				if($e.is(":hidden")) return;
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
})(window.jQuery);