//dlite
var dLite=function(){var E=/*@cc_on!@*/false;var B=0;var H=false;var A=null;var G=[];var C={};var D=function(){for(var J=0,I=G.length;J<I;J++){try{G[J]();}catch(K){}}G=[];};var F=function(){if(H){return ;}H=true;D();};return{init:function(){window.elm=window.elm||this.elm;window.elmsByClass=window.elmsByClass||this.elmsByClass;window.addClass=window.addClass||this.addClass;window.removeClass=window.removeClass||this.removeClass;window.addEvent=window.addEvent||this.addEvent;window.removeEvent=window.removeEvent||this.removeEvent;window.stopDefault=window.stopDefault||this.stopDefault;window.cancelBubbling=window.cancelBubbling||this.cancelBubbling;window.DOMReady=window.DOMReady||this.DOMReady;if(document.addEventListener){document.addEventListener("DOMContentLoaded",F,false);}if(E){if(document.getElementById){document.write('<script id="ieScriptLoad" defer src="//:"><\/script>');document.getElementById("ieScriptLoad").onreadystatechange=function(){if(this.readyState==="complete"){F.call(this);}};}}if(/KHTML|WebKit|iCab/i.test(navigator.userAgent)){A=setInterval(function(){if(/loaded|complete/i.test(document.readyState)){F.call(this);clearInterval(A);}},10);}window.onload=F;},elm:function(I){return document.getElementById(I);},elmsByClass:function(J,I,K){if(document.getElementsByClassName){this.elmsByClass=function(Q,T,P){P=P||document;var L=P.getElementsByClassName(Q),S=(T)?new RegExp("\\b"+T+"\\b","i"):null,M=[],O;for(var N=0,R=L.length;N<R;N+=1){O=L[N];if(!S||S.test(O.nodeName)){M.push(O);}}return M;};}else{if(document.evaluate){this.elmsByClass=function(U,X,T){X=X||"*";T=T||document;var N=U.split(" "),V="",R="http://www.w3.org/1999/xhtml",W=(document.documentElement.namespaceURI===R)?R:null,O=[],L,M;for(var P=0,Q=N.length;P<Q;P+=1){V+="[contains(concat(' ', @class, ' '), ' "+N[P]+" ')]";}try{L=document.evaluate(".//"+X+V,T,W,0,null);}catch(S){L=document.evaluate(".//"+X+V,T,null,0,null);}while((M=L.iterateNext())){O.push(M);}return O;};}else{this.elmsByClass=function(W,Z,V){Z=Z||"*";V=V||document;var P=W.split(" "),Y=[],L=(Z==="*"&&V.all)?V.all:V.getElementsByTagName(Z),U,R=[],T;for(var Q=0,M=P.length;Q<M;Q+=1){Y.push(new RegExp("(^|\\s)"+P[Q]+"(\\s|$)"));}for(var O=0,X=L.length;O<X;O+=1){U=L[O];T=false;for(var N=0,S=Y.length;N<S;N+=1){T=Y[N].test(U.className);if(!T){break;}}if(T){R.push(U);}}return R;};}}return this.elmsByClass(J,I,K);},addClass:function(K,J){var I=K.className;if(!new RegExp(("(^|\\s)"+J+"(\\s|$)"),"i").test(I)){K.className=I+(I.length?" ":"")+J;}},removeClass:function(K,J){var I=new RegExp(("(^|\\s)"+J+"(\\s|$)"),"i");K.className=K.className.replace(I,function(L){var M="";if(new RegExp("^\\s+.*\\s+$").test(L)){M=L.replace(/(\s+).+/,"$1");}return M;}).replace(/^\s+|\s+$/g,"");},addEvent:function(K,I,J){if(document.addEventListener){this.addEvent=function(N,L,M){N.addEventListener(L,M,false);};}else{this.addEvent=function(P,L,N){if(!P.uniqueHandlerId){P.uniqueHandlerId=B++;}var O=false;if(N.attachedElements&&N.attachedElements[L+P.uniqueHandlerId]){O=true;}if(!O){if(!P.events){P.events={};}if(!P.events[L]){P.events[L]=[];var M=P["on"+L];if(M){P.events[L].push(M);}}P.events[L].push(N);P["on"+L]=dLite.handleEvent;if(!N.attachedElements){N.attachedElements={};}N.attachedElements[L+P.uniqueHandlerId]=true;}};}return this.addEvent(K,I,J);},handleEvent:function(I){var M=I||event;var N=M.target||M.srcElement||document;while(N.nodeType!==1&&N.parentNode){N=N.parentNode;}M.eventTarget=N;var J=this.events[M.type].slice(0);var L=J.length-1;if(L!==-1){for(var K=0;K<L;K++){J[K].call(this,M);}return J[K].call(this,M);}},removeEvent:function(K,I,J){if(document.addEventListener){this.removeEvent=function(N,L,M){N.removeEventListener(L,M,false);};}else{this.removeEvent=function(P,L,O){if(P.events){var M=P.events[L];if(M){for(var N=0;N<M.length;N++){if(M[N]===O){delete M[N];M.splice(N,1);}}}O.attachedElements[L+P.uniqueHandlerId]=null;}};}return this.removeEvent(K,I,J);},stopDefault:function(I){I.returnValue=false;if(I.preventDefault){I.preventDefault();}},cancelBubbling:function(I){I.cancelBubble=true;if(I.stopPropagation){I.stopPropagation();}},DOMReady:function(){for(var J=0,I=arguments.length,K;J<I;J++){K=arguments[J];if(!K.DOMReady&&!C[K]){if(typeof K==="string"){C[K]=true;K=new Function(K);}K.DOMReady=true;G.push(K);}}if(H){D();}}};}();dLite.init();
//supernote
function cancelEvent(e,c){e.returnValue=false;if(e.preventDefault)e.preventDefault();if(c){e.cancelBubble=true;if(e.stopPropagation)e.stopPropagation()}};function SuperNote(b,c){var d={myName:b,allowNesting:false,cssProp:'visibility',cssVis:'inherit',cssHid:'hidden',showDelay:200,hideDelay:0,hideOnMouseOut:true,animInSpeed:0.4,animOutSpeed:0.4,animations:[animFade],mouseX:0,mouseY:0,notes:{},rootElm:null,onshow:null,onhide:null};for(var p in d)this[p]=(typeof c[p]=='undefined')?d[p]:c[p];var e=this;addEvent(document,'mouseover',function(a){e.mouseHandler(a,1)});addEvent(document,'click',function(a){e.mouseHandler(a,2)});addEvent(document,'mousemove',function(a){e.mouseTrack(a)});addEvent(document,'mouseout',function(a){e.mouseHandler(a,0)});this.instance=SuperNote.instances.length;SuperNote.instances[this.instance]=this}SuperNote.instances=[];SuperNote.prototype.bTypes={};SuperNote.prototype.pTypes={};SuperNote.prototype.pTypes.mouseoffset=function(a,b,c,d){with(a){var e=notes[b];if(c&&!e.animating&&!e.visible){e.ref.style.left=checkWinX(mouseX,e)+'px';e.ref.style.top=checkWinY(mouseY,e)+'px'}}};SuperNote.prototype.pTypes.mousetrack=function(a,b,c,d){with(a){var e=notes[b];if(c&&!e.animating&&!e.visible){var f='with ('+myName+') {'+'var note = notes["'+b+'"];'+'note.ref.style.left = checkWinX(mouseX, note) + "px";'+'note.ref.style.top = checkWinY(mouseY, note) + "px" }';eval(f);if(!e.trackTimer)e.trackTimer=setInterval(f,50)}else if(!c&&!d){clearInterval(e.trackTimer);e.trackTimer=null}}};SuperNote.prototype.pTypes.triggeroffset=function(a,b,c,d){with(a){var e=notes[b];if(c&&!e.animating&&!e.visible){var x=0,y=0,elm=e.trigRef;while(elm){x+=elm.offsetLeft;y+=elm.offsetTop;elm=elm.offsetParent}e.ref.style.left=checkWinX(x,e)+'px';e.ref.style.top=checkWinY(y,e)+'px'}}};SuperNote.prototype.bTypes.pinned=function(a,b,c){with(a){return(!c)?false:true}};SuperNote.prototype.docBody=function(){return document[(document.compatMode&&document.compatMode.indexOf('CSS')>-1)?'documentElement':'body']};SuperNote.prototype.getWinW=function(){return this.docBody().clientWidth||window.innerWidth||0};SuperNote.prototype.getWinH=function(){return this.docBody().clientHeight||window.innerHeight||0};SuperNote.prototype.getScrX=function(){return this.docBody().scrollLeft||window.scrollX||0};SuperNote.prototype.getScrY=function(){return this.docBody().scrollTop||window.scrollY||0};SuperNote.prototype.checkWinX=function(a,b){with(this){return Math.max(getScrX(),Math.min(a,getScrX()+getWinW()-b.ref.offsetWidth-8))}};SuperNote.prototype.checkWinY=function(a,b){with(this){return Math.max(getScrY(),Math.min(a,getScrY()+getWinH()-b.ref.offsetHeight-8))}};SuperNote.prototype.mouseTrack=function(a){with(this){mouseX=a.pageX||a.clientX+getScrX()||0;mouseY=a.pageY||a.clientY+getScrY()||0}};SuperNote.prototype.mouseHandler=function(a,b){with(this){if(!document.documentElement)return true;var c=a.target||a.srcElement,trigRE=new RegExp(myName+'-(hover|click)-([a-z0-9]+)','i'),targRE=new RegExp(myName+'-(note)-([a-z0-9]+)','i'),trigFind=1,foundNotes={};if(c.nodeType!=1)c=c.parentNode;var d=c;while(d&&d!=rootElm){if(targRE.test(d.id)||(trigFind&&trigRE.test(d.className))){if(!allowNesting)trigFind=0;var e=RegExp.$1=='click'?1:0,noteID=RegExp.$2,ref=document.getElementById(myName+'-note-'+noteID),trigRef=trigRE.test(d.className)?d:null;if(ref){if(!notes[noteID]){notes[noteID]={click:e,ref:ref,trigRef:null,visible:0,animating:0,timer:null};ref._sn_obj=this;ref._sn_id=noteID}var f=notes[noteID];if(!f.click||(trigRef!=c))foundNotes[noteID]=true;if(!f.click||(b==2)||(f.visible&&this.hideOnMouseOut==true)){if(trigRef)notes[noteID].trigRef=notes[noteID].ref._sn_trig=d;display(noteID,b);if(f.click&&(c==trigRef))cancelEvent(a)}}}if(d._sn_trig){trigFind=1;d=d._sn_trig}else{d=d.parentNode}}if(b==2)for(var n in notes){if(notes[n].click&&notes[n].visible&&!foundNotes[n])display(n,0)}}};SuperNote.prototype.display=function(a,b){with(this){with(notes[a]){clearTimeout(timer);if(!animating||(b?!visible:visible)){var c=animating?1:(b?showDelay||1:hideDelay||1);timer=setTimeout('SuperNote.instances['+instance+'].setVis("'+a+'", '+b+', false)',c)}}}};SuperNote.prototype.checkType=function(a,b,c){with(this){var d=notes[a],bType,pType;if((/snp-([a-z]+)/).test(d.ref.className))pType=RegExp.$1;if((/snb-([a-z]+)/).test(d.ref.className))bType=RegExp.$1;if(c&&bType&&bTypes[bType]&&(bTypes[bType](this,a,b)==false))return false;if(pType&&pTypes[pType])pTypes[pType](this,a,b,c);return true}};SuperNote.prototype.setVis=function(a,b,c){with(this){var d=notes[a];if(d&&checkType(a,b,1)||c){d.visible=b;d.animating=1;animate(a,b,c)}}};SuperNote.prototype.animate=function(b,c,d){with(this){var e=notes[b];if(!e.animTimer)e.animTimer=0;if(!e.animC)e.animC=0;with(e){clearTimeout(animTimer);var f=(animations.length&&!d)?(c?animInSpeed:animOutSpeed):1;if(c&&!animC){if(onshow)this.onshow(b);ref.style[cssProp]=cssVis}animC=Math.max(0,Math.min(1,animC+f*(c?1:-1)));if(document.getElementById&&f<1)for(var a=0;a<animations.length;a++)animations[a](ref,animC);if(!c&&!animC){if(onhide)this.onhide(b);ref.style[cssProp]=cssHid}if(animC!=parseInt(animC)){animTimer=setTimeout(myName+'.animate("'+b+'", '+c+')',50)}else{checkType(b,animC?1:0,0);e.animating=0}}}};function animFade(a,b){var f=a.filters,done=(b==1);if(f){if(!done&&a.style.filter.indexOf("alpha")==-1)a.style.filter+=' alpha(opacity='+(b*100)+')';else if(f.length&&f.alpha)with(f.alpha){if(done)enabled=false;else{opacity=(b*100);enabled=true}}}else a.style.opacity=a.style.MozOpacity=b*0.999};
var supernote;
//ondom
//function gup(a,b){a=a.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");var c=new RegExp("[\\?&]"+a+"=([^&#]*)");var d=c.exec(b);if(d==null)return 0;else return d[1]}var arrDraft=new Array();var timeDiffToSaveDraft=10000;var idleDraftHandlerRunning=0;var timeDraftLastKeyPressed=0;function getTime(){var d=new Date();return d.getTime()}function setDraftElement(a){var x;var b=1;for(x in arrDraft){if(arrDraft[x][0]==a.id)b=0}if(b==1){arrDraft.push(new Array(a.id,0,getTime(),0))}}function draftEventHandler(){setDraftElement(this);handleDraft()}function idleDraftHandler(){var x;if(idleDraftHandlerRunning==1){if(((getTime()-timeDraftLastKeyPressed)>timeDiffToSaveDraft)){for(x in arrDraft){arrDraft[x][2]=timeDraftLastKeyPressed;arrDraft[x][1]=arrDraft[x][1]+1}handleDraft();idleDraftHandlerRunning=0}else setTimeout(idleDraftHandler,3000)}}function handleDraft(){var x;var t=getTime();for(x in arrDraft){var a=elm(arrDraft[x][0]);a.setAttribute("value",a.innerHTML);var b=a.value.length;if(b!=arrDraft[x][1]&&b>0){if(arrDraft[x][3]==0){addClass(a,'draftNotSave');arrDraft[x][3]=1}if((t-arrDraft[x][2])>timeDiffToSaveDraft){xajax_draft_save(a.id,a.value);arrDraft[x]=new Array(a.id,0,getTime(),0)}}}timeDraftLastKeyPressed=t;if(idleDraftHandlerRunning==0){idleDraftHandlerRunning=1;idleDraftHandler()}}function draftSetEventListeners(){var a=elmsByClass('draftable','textarea');var b=a.length;if(b>0)for(var z=0;z<b;z++)addEvent(a[z],'keyup',draftEventHandler)}function setTagEventListeners(){var a=elmsByClass('tagLink');var b=a.length;if(b>0){for(var z=0;z<b;z++){a[z].onclick=function(){xajax_user_tag(this.id);return false}}}}function openPopup(a){window.open(a,'fpopup','scrollbars='+gup("scrollbars",a)+',toolbar='+gup("toolbar",a)+',menubar='+gup("menubar",a)+',status='+gup("status",a)+',resizable='+gup("resizable",a)+',width='+gup("width",a)+',height='+gup("height",a)+'')}function setPopUpEventListeners(){var a=elmsByClass('popupLink');var b=a.length;if(b>0){for(var z=0;z<b;z++){a[z].onclick=function(){openPopup(this.href);return false}}}}function setPollEventListeners(){var a=elmsByClass('pollAnswerLink');var b=a.length;if(b>0){for(var z=0;z<b;z++){a[z].onclick=function(){xajax_poll_pollVote(gup('poll',this.href)); return false;}}}}function onDomReady(){draftSetEventListeners();setTagEventListeners();setPopUpEventListeners();setPollEventListeners();supernote = new SuperNote('supernote', {});}DOMReady(onDomReady);
//---global functions - get url query value
function gup( name , url ) {
  name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
  var regex = new RegExp( "[\\?&]"+name+"=([^&#]*)" );
  var results = regex.exec( url );
  if( results == null ) return 0;
  else return results[1];
}
function gul(url) {
  var arr = url.split('?');
  return arr[0];
}
//---textarea - text2cursor
var activeTextareaId = '';
function initInsertToTextarea() {
  var arr = elmsByClass('clickDaTag'); var length = arr.length;
  if(length > 0) {  for(var z=0;z<length;z++) { arr[z].onclick = function() { insert2Area(this.href); return false; }; } }
  var arr = elmsByClass('draftable'); var length = arr.length;
  if(length > 0) {  for(var z=0;z<length;z++) { arr[z].onclick = function() { activeTextareaId = this.id; }; } }
}
function insert2Area(what) {
  var activeTextareaIdFromHref = gup('textid',what);
  if(activeTextareaIdFromHref) activeTextareaId = elm(gup('textid',what)).id;
  if(activeTextareaId) insertAtCursor(elm(activeTextareaId), gup('tag',what));
}
function setCsr(elem, caretPos) {
  if(elem.createTextRange) {
    var range = elem.createTextRange();
    range.move('character', caretPos);
    range.select();
  } else {
    if(elem.selectionStart) {
      elem.focus();
      elem.setSelectionRange(caretPos, caretPos);
    } else
      elem.focus();
  }
}
function insertAtCursor(myField, myValue) {
  block = '';
  //IE support
  if (document.selection) {
    myField.focus();
    sel = document.selection.createRange();
    block = sel.text;
    sel.text = myValue;
  }
  //MOZILLA/NETSCAPE support
  else if (myField.selectionStart || myField.selectionStart == '0') {
    var startPos = myField.selectionStart;
    var endPos = myField.selectionEnd;
    block = myField.value.substring(startPos,endPos);
    myField.value = myField.value.substring(0, startPos)
      + myValue
      + myField.value.substring(endPos, myField.value.length);
  } else {
    myField.value += myValue;
  }
  caretPos = myField.value.indexOf('-cursor-')+block.length;
  myField.value = myField.value.replace('-cursor-', block);
  setCsr(myField,caretPos);
}

//---textarea drafting
var arrDraft = new Array(); //0-id, 1-lastlength, 2-lasttime, 3-classNOTsave (0-not set,1-set)
var timeDiffToSaveDraft = 10000;
var idleDraftHandlerRunning = 0;
var timeDraftLastKeyPressed = 0;
function getTime() { var d = new Date(); return d.getTime(); }
function setDraftElement(textareaParam) { var x; var add=1; for (x in arrDraft) { if(arrDraft[x][0]==textareaParam.id) add=0; } if(add==1) { arrDraft.push(new Array(textareaParam.id,0,getTime(),0)); } }
function draftEventHandler() { setDraftElement(this); handleDraft(); }
function idleDraftHandler() { var x;
  if(idleDraftHandlerRunning==1) {
    if(((getTime() - timeDraftLastKeyPressed) > timeDiffToSaveDraft)) {
      for (x in arrDraft) { arrDraft[x][2] = timeDraftLastKeyPressed; arrDraft[x][1] = arrDraft[x][1] + 1; } //---set all changed
      handleDraft(); idleDraftHandlerRunning = 0;
    } else setTimeout( idleDraftHandler, 3000 );
  } 
}
function handleDraft() { var x; var t = getTime();
  for (x in arrDraft) {
    var currentTextarea = elm(arrDraft[x][0]);
    currentTextarea.setAttribute("value",currentTextarea.innerHTML);
    var textLength = currentTextarea.value.length;
    if(textLength != arrDraft[x][1] && textLength > 0) {
      if(arrDraft[x][3]==0) { addClass (currentTextarea,'draftNotSave'); arrDraft[x][3]=1; }
      if((t - arrDraft[x][2]) > timeDiffToSaveDraft) {
        xajax_draft_save(currentTextarea.id,currentTextarea.value);
        arrDraft[x] = new Array(currentTextarea.id,0,getTime(),0);
      }
    }
  }
  timeDraftLastKeyPressed = t;
  if(idleDraftHandlerRunning==0) { idleDraftHandlerRunning = 1; idleDraftHandler(); }
}
function draftSetEventListeners() { var arr = elmsByClass('draftable', 'textarea'); var length = arr.length;
  if(length > 0)  for(var z=0; z < length; z++)  addEvent(arr[z],'keyup',draftEventHandler);
}
//---set tagging
function setTagEventListeners() { var arr = elmsByClass('tagLink'); var length = arr.length;
  if(length > 0) { for(var z=0;z<length;z++) { arr[z].onclick = function() { xajax_user_tag(this.id); return false; }; } }
}
//---popup opening
function openPopup(href) { window.open(href,'fpopup','scrollbars='+gup("scrollbars",href)+',toolbar='+gup("toolbar",href)+',menubar='+gup("menubar",href)+',status='+gup("status",href)+',resizable='+gup("resizable",href)+',width='+gup("width",href)+',height='+gup("height",href)+''); }
function setPopUpEventListeners() { var arr = elmsByClass('popupLink'); var length = arr.length;
  if(length > 0) {  for(var z=0;z<length;z++) { arr[z].onclick = function() { openPopup(this.href); return false; }; } }
}
//---set poll answer links
function setPollEventListeners() { var arr = elmsByClass('pollAnswerLink'); var length = arr.length;
  if(length > 0) { for(var z=0;z<length;z++) { arr[z].onclick = function() { xajax_poll_pollVote(gup('poll',this.href)); return false; }; } }
}
function setSwitchEventListeners() { var arr = elmsByClass('xajaxSwitch'); var length = arr.length;
  if(length > 0) { for(var z=0;z<length;z++) { arr[z].onclick = function() { xajax_forum_fotoDetail(gup("i",this.href)); return false; }; } }
}
function setPocketAddEventListeners() { var arr = elmsByClass('pocketAdd'); var length = arr.length;
  if(length > 0) { for(var z=0;z<length;z++) { arr[z].onclick = function() { var item = gup("i",this.href); var page = 1; if(item > 0) page = 0; else item = gup("k",this.href); xajax_pocket_add(item,page); return false; }; } }
}
function onDomReady() {
  draftSetEventListeners();
  setTagEventListeners();
  setPopUpEventListeners();
  setPollEventListeners();
  setSwitchEventListeners();
  setPocketAddEventListeners();
  initInsertToTextarea();
  supernote = new SuperNote('supernote', {});
}
DOMReady(onDomReady);