_fdk.load = {'boot':['//code.jquery.com/jquery-2.1.4.min.js',//_fdk.cfg.jsUrl+'jquery-1.10.2.min.js',
_fdk.cfg.jsUrl+'jquery.history.js',
'//unpkg.com/packery@2.1.1/dist/packery.pkgd.min.js',
_fdk.cfg.jsUrl+'bootstrap.min.js'
,_fdk.cfg.jsUrl+'i18n/_fdk.lng.cs.js'
,_fdk.cfg.jsUrl+'fdkboot.js']
,'goomapi':[_fdk.cfg.jsUrl+'goomapi.min.js']
,'colorbox':[_fdk.cfg.jsUrl+'colorbox.min.js',_fdk.cfg.jsUrl+'i18n/jquery.colorbox-cs.js']
,'swf':['//cdnjs.cloudflare.com/ajax/libs/json3/3.2.4/json3.min.js','//ajax.googleapis.com/ajax/libs/swfobject/2.2/swfobject.js']
,'ui':[_fdk.cfg.jsUrl+'boot-ui.min.js',_fdk.cfg.jsUrl+'i18n/bootstrap-datepicker.cs.js',_fdk.cfg.cssUrl+'datepicker.css']
,'richta':['//tinymce.cachefly.net/4.2/tinymce.min.js']};

LazyLoad=function(){function r(c,b){var a=h.createElement(c),e;for(e in b)b.hasOwnProperty(e)&&a.setAttribute(e,b[e]);return a}function k(c){var b=i[c],a,e;if(b){a=b.callback;e=b.urls;e.shift();l=0;if(!e.length){a&&a.call(b.context,b.obj);i[c]=null;j[c].length&&m(c)}}}function w(){if(!d){var c=navigator.userAgent,b=parseFloat,a;d={async:h.createElement("script").async===true,gecko:0,ie:0,opera:0,webkit:0};if((a=c.match(/AppleWebKit\/(\S*)/))&&a[1])d.webkit=b(a[1]);else if((a=c.match(/MSIE\s([^;]*)/))&&a[1])d.ie=b(a[1]);else if(/Gecko\/(\S*)/.test(c)){d.gecko=1;if((a=c.match(/rv:([^\s\)]*)/))&&a[1])d.gecko=b(a[1])}else if(a=c.match(/Opera\/(\S*)/))d.opera=b(a[1])}}function m(c,b,a,e,s){var n=function(){k(c)},o=c==="css",f,g,p;w();if(b){b=Object.prototype.toString.call(b)==="[object Array]"?b:[b];if(o||d.gecko&&(d.async||d.gecko<2)||d.opera)j[c].push({urls:[].concat(b),callback:a,obj:e,context:s});else{f=0;for(g=b.length;f<g;++f)j[c].push({urls:[b[f]],callback:f===g-1?a:null,obj:e,context:s})}}if(!(i[c]||!(p=i[c]=j[c].shift()))){q=q||h.head||h.getElementsByTagName("head")[0];b=p.urls;f=0;for(g=b.length;f<g;++f){a=b[f];if(o)a=r("link",{charset:"utf-8","class":"lazyload",href:a,rel:"stylesheet",type:"text/css"});else{a=r("script",{charset:"utf-8","class":"lazyload",src:a});if(d.async)a.async=false}if(d.ie)a.onreadystatechange=function(){var t=this.readyState;if(t==="loaded"||t==="complete"){this.onreadystatechange=null;n()}};else if(o&&(d.gecko||d.webkit))if(d.webkit){p.urls[f]=a.href;u()}else setTimeout(n,50*g);else a.onload=a.onerror=n;q.appendChild(a)}}}function u(){var c=i.css,b;if(c){for(b=v.length;b&&--b;)if(v[b].href===c.urls[0]){k("css");break}l+=1;if(c)l<200?setTimeout(u,50):k("css")}}var h=document,d,q,i={},l=0,j={css:[],js:[]},v=h.styleSheets;return{css:function(c,b,a,e){m("css",c,b,a,e)},js:function(c,b,a,e){m("js",c,b,a,e)}}}();

LazyLoad.js(_fdk.load.boot,function(){boot();});