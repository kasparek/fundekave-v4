var Lazy = {
	r:{},f:null,q:[],
	load:function(l,f) {
		var o=Lazy,c=true;for(var i=0;i<l.length;i++)if(!o.r[l[i]]){c=false;break}if(c)return c;
		o.q.push({l:l.concat(),f:f});
		if(o.q.length==1)return o.p();
	},
	p:function() {
		var o=Lazy;
		while(o.q[0].l.length>0) {
			var f=o.q[0].l.shift();
			if(!o.r[f]) {
				o.f=f;
				if(f.indexOf('.css')>-1){LazyLoad.css(f,o.c);}else{LazyLoad.js(f,o.c);}
	    	return;
	    }
		}
		return true;
	},
	c:function(){
		var o=Lazy;
		o.r[o.f]=true;
		if(o.q[0].l.length>0){o.p();return;}
		if(o.q[0].f)o.q[0].f();
		o.q.shift();
		if(o.q.length>0)o.p();
	}
};