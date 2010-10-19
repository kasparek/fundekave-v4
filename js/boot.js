LazyLoad=function(){function r(c,b){c=i.createElement(c);var a;for(a in b)b.hasOwnProperty(a)&&c.setAttribute(a,b[a]);return c}function k(c){var b=h[c],a,e;if(b){a=b.callback;e=b.urls;e.shift();l=0;if(!e.length){if(a)a.call(b.context||window,b.obj);h[c]=null;j[c].length&&m(c)}}}function w(){if(!d){var c=navigator.userAgent,b=parseFloat,a;d={gecko:0,ie:0,opera:0,webkit:0};if((a=c.match(/AppleWebKit\/(\S*)/))&&a[1])d.webkit=b(a[1]);else if((a=c.match(/MSIE\s([^;]*)/))&&a[1])d.ie=b(a[1]);else if(/Gecko\/(\S*)/.test(c)){d.gecko=1;if((a=c.match(/rv:([^\s\)]*)/))&&a[1])d.gecko=b(a[1])}else if(a=c.match(/Opera\/(\S*)/))d.opera=b(a[1])}}function m(c,b,a,e,s){var n=function(){k(c)},o=c==="css",f,g,p;w();if(b){b=b.constructor===Array?b:[b];if(o||d.gecko&&d.gecko<2||d.opera)j[c].push({urls:[].concat(b),callback:a,obj:e,context:s});else{f=0;for(g=b.length;f<g;++f)j[c].push({urls:[b[f]],callback:f===g-1?a:null,obj:e,context:s})}}if(!(h[c]||!(p=h[c]=j[c].shift()))){q=q||i.head||i.getElementsByTagName("head")[0];b=p.urls;f=0;for(g=b.length;f<g;++f){a=b[f];a=o?r("link",{charset:"utf-8","class":"lazyload",href:a,rel:"stylesheet",type:"text/css"}):r("script",{charset:"utf-8","class":"lazyload",src:a});if(d.ie)a.onreadystatechange=function(){var t=this.readyState;if(t==="loaded"||t==="complete"){this.onreadystatechange=null;n()}};else if(o&&(d.gecko||d.webkit))if(d.webkit){p.urls[f]=a.href;u()}else setTimeout(n,50*g);else a.onload=a.onerror=n;q.appendChild(a)}}}function u(){var c=h.css,b;if(c){for(b=v.length;--b;)if(v[b].href===c.urls[0]){k("css");break}l+=1;if(c)l<200?setTimeout(u,50):k("css")}}var i=document,q,h={},l=0,j={css:[],js:[]},v=i.styleSheets,d;return{css:function(c,b,a,e){m("css",c,b,a,e)},js:function(c,b,a,e){m("js",c,b,a,e)}}}();
LazyLoad.js(LAZYLOAD['boot'], function () { boot(); });