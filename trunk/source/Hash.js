var Hash={
old:'',
init:function(){$(window).hashchange(function(){var o=Hash,h=location.hash.replace('#','');if(h!=o.old){if(h=='' && o.old.length>0){window.location.reload();return;}h.old=h;Fajax.action(h);}});},
set:function(h){document.location.hash=h;},
reset:function(hash){document.location.hash=Hash.old=hash;},
data:function(){var h=document.location.hash.replace('#','').split('/'),d=h[1];if(d){var arr=d.split(';'),data={};while(arr.length>0){var v=arr.shift(),kv=v.split(':');data[kv[0]]=kv[1];} return data;}}
}