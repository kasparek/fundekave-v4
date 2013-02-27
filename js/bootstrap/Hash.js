/** HASH HANDLING */
var Hash = new function(){
	var o = this;
	o.old = '';
	o.init = function(){
		$(window).hashchange(function(){
			var h = location.hash.replace('#', '');
			if(h != o.old){
				if(h == '' && o.old.length > 0){
					window.location.reload();
					return;
				}
				h.old = h;
				Fajax.action(h);
			}
		});
	};
	o.set = function(h){
		document.location.hash = h;
	};
	o.reset = function(hash){
		document.location.hash = o.old = hash;
	};
	o.data = function(k){
		var h = document.location.hash.replace('#', '').split('/'), d = h[1];
		if(d){
			var arr = d.split(';'), data = {};
			while(arr.length > 0){
				var v = arr.shift(), kv = v.split(':');
				data[kv[0]] = kv[1];
			}
			if(data)
				if(data[k])
					return data[k];
		}
	}
}