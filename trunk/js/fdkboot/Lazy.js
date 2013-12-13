/** LAZYLOADER */
var Lazy = new function(){
	var o = this;
	o.r = {};
	o.f = null;
	o.q = [];
	o.loading = false;
	o.load = function(l, f){
		var c = true;
		for( var i = 0; i < l.length; i++)
			if(!o.r[l[i]]){
				c = false;
				break
			}
		if(c)
			return c;
		o.q.push({
			l : l.concat(),
			f : f
		});
		if(!o.loading)
			return o.p();
	};
	o.p = function(){
		while(o.q[0].l.length > 0){
			var f = o.q[0].l.shift();
			if(!o.r[f]){
				o.loading = true;
				o.f = f;
				if(f.indexOf('.css') > -1){
					LazyLoad.css(f, function(){
						Lazy.c()
					});
				}else{
					LazyLoad.js(f, function(){
						Lazy.c()
					});
				}
				return;
			}
		}
		o.qc();
		return true;
	};
	o.c = function(){
		o.r[o.f] = true;
		if(o.q[0].l.length > 0)
			o.p();
		else
			o.qc();
	};
	o.qc = function(){
		if(o.q[0].f)
			o.q[0].f();
		o.q.shift();
		if(o.q.length > 0)
			o.p();
		else
			o.loading = false;
	}
}