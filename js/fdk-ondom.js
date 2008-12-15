//dlite
var dLite=function(){var E=/*@cc_on!@*/false;var B=0;var H=false;var A=null;var G=[];var C={};var D=function(){for(var J=0,I=G.length;J<I;J++){try{G[J]();}catch(K){}}G=[];};var F=function(){if(H){return ;}H=true;D();};return{init:function(){window.elm=window.elm||this.elm;window.elmsByClass=window.elmsByClass||this.elmsByClass;window.addClass=window.addClass||this.addClass;window.removeClass=window.removeClass||this.removeClass;window.addEvent=window.addEvent||this.addEvent;window.removeEvent=window.removeEvent||this.removeEvent;window.stopDefault=window.stopDefault||this.stopDefault;window.cancelBubbling=window.cancelBubbling||this.cancelBubbling;window.DOMReady=window.DOMReady||this.DOMReady;if(document.addEventListener){document.addEventListener("DOMContentLoaded",F,false);}if(E){if(document.getElementById){document.write('<script id="ieScriptLoad" defer src="//:"><\/script>');document.getElementById("ieScriptLoad").onreadystatechange=function(){if(this.readyState==="complete"){F.call(this);}};}}if(/KHTML|WebKit|iCab/i.test(navigator.userAgent)){A=setInterval(function(){if(/loaded|complete/i.test(document.readyState)){F.call(this);clearInterval(A);}},10);}window.onload=F;},elm:function(I){return document.getElementById(I);},elmsByClass:function(J,I,K){if(document.getElementsByClassName){this.elmsByClass=function(Q,T,P){P=P||document;var L=P.getElementsByClassName(Q),S=(T)?new RegExp("\\b"+T+"\\b","i"):null,M=[],O;for(var N=0,R=L.length;N<R;N+=1){O=L[N];if(!S||S.test(O.nodeName)){M.push(O);}}return M;};}else{if(document.evaluate){this.elmsByClass=function(U,X,T){X=X||"*";T=T||document;var N=U.split(" "),V="",R="http://www.w3.org/1999/xhtml",W=(document.documentElement.namespaceURI===R)?R:null,O=[],L,M;for(var P=0,Q=N.length;P<Q;P+=1){V+="[contains(concat(' ', @class, ' '), ' "+N[P]+" ')]";}try{L=document.evaluate(".//"+X+V,T,W,0,null);}catch(S){L=document.evaluate(".//"+X+V,T,null,0,null);}while((M=L.iterateNext())){O.push(M);}return O;};}else{this.elmsByClass=function(W,Z,V){Z=Z||"*";V=V||document;var P=W.split(" "),Y=[],L=(Z==="*"&&V.all)?V.all:V.getElementsByTagName(Z),U,R=[],T;for(var Q=0,M=P.length;Q<M;Q+=1){Y.push(new RegExp("(^|\\s)"+P[Q]+"(\\s|$)"));}for(var O=0,X=L.length;O<X;O+=1){U=L[O];T=false;for(var N=0,S=Y.length;N<S;N+=1){T=Y[N].test(U.className);if(!T){break;}}if(T){R.push(U);}}return R;};}}return this.elmsByClass(J,I,K);},addClass:function(K,J){var I=K.className;if(!new RegExp(("(^|\\s)"+J+"(\\s|$)"),"i").test(I)){K.className=I+(I.length?" ":"")+J;}},removeClass:function(K,J){var I=new RegExp(("(^|\\s)"+J+"(\\s|$)"),"i");K.className=K.className.replace(I,function(L){var M="";if(new RegExp("^\\s+.*\\s+$").test(L)){M=L.replace(/(\s+).+/,"$1");}return M;}).replace(/^\s+|\s+$/g,"");},addEvent:function(K,I,J){if(document.addEventListener){this.addEvent=function(N,L,M){N.addEventListener(L,M,false);};}else{this.addEvent=function(P,L,N){if(!P.uniqueHandlerId){P.uniqueHandlerId=B++;}var O=false;if(N.attachedElements&&N.attachedElements[L+P.uniqueHandlerId]){O=true;}if(!O){if(!P.events){P.events={};}if(!P.events[L]){P.events[L]=[];var M=P["on"+L];if(M){P.events[L].push(M);}}P.events[L].push(N);P["on"+L]=dLite.handleEvent;if(!N.attachedElements){N.attachedElements={};}N.attachedElements[L+P.uniqueHandlerId]=true;}};}return this.addEvent(K,I,J);},handleEvent:function(I){var M=I||event;var N=M.target||M.srcElement||document;while(N.nodeType!==1&&N.parentNode){N=N.parentNode;}M.eventTarget=N;var J=this.events[M.type].slice(0);var L=J.length-1;if(L!==-1){for(var K=0;K<L;K++){J[K].call(this,M);}return J[K].call(this,M);}},removeEvent:function(K,I,J){if(document.addEventListener){this.removeEvent=function(N,L,M){N.removeEventListener(L,M,false);};}else{this.removeEvent=function(P,L,O){if(P.events){var M=P.events[L];if(M){for(var N=0;N<M.length;N++){if(M[N]===O){delete M[N];M.splice(N,1);}}}O.attachedElements[L+P.uniqueHandlerId]=null;}};}return this.removeEvent(K,I,J);},stopDefault:function(I){I.returnValue=false;if(I.preventDefault){I.preventDefault();}},cancelBubbling:function(I){I.cancelBubble=true;if(I.stopPropagation){I.stopPropagation();}},DOMReady:function(){for(var J=0,I=arguments.length,K;J<I;J++){K=arguments[J];if(!K.DOMReady&&!C[K]){if(typeof K==="string"){C[K]=true;K=new Function(K);}K.DOMReady=true;G.push(K);}}if(H){D();}}};}();dLite.init();
//supernote
if(typeof addEvent!='function'){var addEvent=function(o,t,f,l){var d='addEventListener',n='on'+t,rO=o,rT=t,rF=f,rL=l;if(o[d]&&!l)return o[d](t,f,false);if(!o._evts)o._evts={};if(!o._evts[t]){o._evts[t]=o[n]?{b:o[n]}:{};o[n]=new Function('e','var r = true, o = this, a = o._evts["'+t+'"], i; for (i in a) {'+'o._f = a[i]; r = o._f(e||window.event) != false && r; o._f = null;'+'} return r');if(t!='unload')addEvent(window,'unload',function(){removeEvent(rO,rT,rF,rL)})}if(!f._i)f._i=addEvent._i++;o._evts[t][f._i]=f};addEvent._i=1;var removeEvent=function(o,t,f,l){var d='removeEventListener';if(o[d]&&!l)return o[d](t,f,false);if(o._evts&&o._evts[t]&&f._i)delete o._evts[t][f._i]}}function cancelEvent(e,c){e.returnValue=false;if(e.preventDefault)e.preventDefault();if(c){e.cancelBubble=true;if(e.stopPropagation)e.stopPropagation()}};function SuperNote(myName,config){var defaults={myName:myName,allowNesting:false,cssProp:'visibility',cssVis:'inherit',cssHid:'hidden',showDelay:200,hideDelay:0,hideOnMouseOut:true,animInSpeed:0.4,animOutSpeed:0.4,animations:[animFade],mouseX:0,mouseY:0,notes:{},rootElm:null,onshow:null,onhide:null};for(var p in defaults)this[p]=(typeof config[p]=='undefined')?defaults[p]:config[p];var obj=this;addEvent(document,'mouseover',function(evt){obj.mouseHandler(evt,1)});addEvent(document,'click',function(evt){obj.mouseHandler(evt,2)});addEvent(document,'mousemove',function(evt){obj.mouseTrack(evt)});addEvent(document,'mouseout',function(evt){obj.mouseHandler(evt,0)});this.instance=SuperNote.instances.length;SuperNote.instances[this.instance]=this}SuperNote.instances=[];SuperNote.prototype.bTypes={};SuperNote.prototype.pTypes={};SuperNote.prototype.pTypes.mouseoffset=function(obj,noteID,nextVis,nextAnim){with(obj){var note=notes[noteID];if(nextVis&&!note.animating&&!note.visible){note.ref.style.left=checkWinX(mouseX,note)+'px';note.ref.style.top=checkWinY(mouseY,note)+'px'}}};SuperNote.prototype.pTypes.mousetrack=function(obj,noteID,nextVis,nextAnim){with(obj){var note=notes[noteID];if(nextVis&&!note.animating&&!note.visible){var posString='with ('+myName+') {'+'var note = notes["'+noteID+'"];'+'note.ref.style.left = checkWinX(mouseX, note) + "px";'+'note.ref.style.top = checkWinY(mouseY, note) + "px" }';eval(posString);if(!note.trackTimer)note.trackTimer=setInterval(posString,50)}else if(!nextVis&&!nextAnim){clearInterval(note.trackTimer);note.trackTimer=null}}};SuperNote.prototype.pTypes.triggeroffset=function(obj,noteID,nextVis,nextAnim){with(obj){var note=notes[noteID];if(nextVis&&!note.animating&&!note.visible){var x=0,y=0,elm=note.trigRef;while(elm){x+=elm.offsetLeft;y+=elm.offsetTop;elm=elm.offsetParent}note.ref.style.left=checkWinX(x,note)+'px';note.ref.style.top=checkWinY(y,note)+'px'}}};SuperNote.prototype.bTypes.pinned=function(obj,noteID,nextVis){with(obj){return(!nextVis)?false:true}};SuperNote.prototype.docBody=function(){return document[(document.compatMode&&document.compatMode.indexOf('CSS')>-1)?'documentElement':'body']};SuperNote.prototype.getWinW=function(){return this.docBody().clientWidth||window.innerWidth||0};SuperNote.prototype.getWinH=function(){return this.docBody().clientHeight||window.innerHeight||0};SuperNote.prototype.getScrX=function(){return this.docBody().scrollLeft||window.scrollX||0};SuperNote.prototype.getScrY=function(){return this.docBody().scrollTop||window.scrollY||0};SuperNote.prototype.checkWinX=function(newX,note){with(this){return Math.max(getScrX(),Math.min(newX,getScrX()+getWinW()-note.ref.offsetWidth-8))}};SuperNote.prototype.checkWinY=function(newY,note){with(this){return Math.max(getScrY(),Math.min(newY,getScrY()+getWinH()-note.ref.offsetHeight-8))}};SuperNote.prototype.mouseTrack=function(evt){with(this){mouseX=evt.pageX||evt.clientX+getScrX()||0;mouseY=evt.pageY||evt.clientY+getScrY()||0}};SuperNote.prototype.mouseHandler=function(evt,show){with(this){if(!document.documentElement)return true;var srcElm=evt.target||evt.srcElement,trigRE=new RegExp(myName+'-(hover|click)-([a-z0-9]+)','i'),targRE=new RegExp(myName+'-(note)-([a-z0-9]+)','i'),trigFind=1,foundNotes={};if(srcElm.nodeType!=1)srcElm=srcElm.parentNode;var elm=srcElm;while(elm&&elm!=rootElm){if(targRE.test(elm.id)||(trigFind&&trigRE.test(elm.className))){if(!allowNesting)trigFind=0;var click=RegExp.$1=='click'?1:0,noteID=RegExp.$2,ref=document.getElementById(myName+'-note-'+noteID),trigRef=trigRE.test(elm.className)?elm:null;if(ref){if(!notes[noteID]){notes[noteID]={click:click,ref:ref,trigRef:null,visible:0,animating:0,timer:null};ref._sn_obj=this;ref._sn_id=noteID}var note=notes[noteID];if(!note.click||(trigRef!=srcElm))foundNotes[noteID]=true;if(!note.click||(show==2)||(note.visible&&this.hideOnMouseOut==true)){if(trigRef)notes[noteID].trigRef=notes[noteID].ref._sn_trig=elm;display(noteID,show);if(note.click&&(srcElm==trigRef))cancelEvent(evt)}}}if(elm._sn_trig){trigFind=1;elm=elm._sn_trig}else{elm=elm.parentNode}}if(show==2)for(var n in notes){if(notes[n].click&&notes[n].visible&&!foundNotes[n])display(n,0)}}};SuperNote.prototype.display=function(noteID,show){with(this){with(notes[noteID]){clearTimeout(timer);if(!animating||(show?!visible:visible)){var tmt=animating?1:(show?showDelay||1:hideDelay||1);timer=setTimeout('SuperNote.instances['+instance+'].setVis("'+noteID+'", '+show+', false)',tmt)}}}};SuperNote.prototype.checkType=function(noteID,nextVis,nextAnim){with(this){var note=notes[noteID],bType,pType;if((/snp-([a-z]+)/).test(note.ref.className))pType=RegExp.$1;if((/snb-([a-z]+)/).test(note.ref.className))bType=RegExp.$1;if(nextAnim&&bType&&bTypes[bType]&&(bTypes[bType](this,noteID,nextVis)==false))return false;if(pType&&pTypes[pType])pTypes[pType](this,noteID,nextVis,nextAnim);return true}};SuperNote.prototype.setVis=function(noteID,show,now){with(this){var note=notes[noteID];if(note&&checkType(noteID,show,1)||now){note.visible=show;note.animating=1;animate(noteID,show,now)}}};SuperNote.prototype.animate=function(noteID,show,now){with(this){var note=notes[noteID];if(!note.animTimer)note.animTimer=0;if(!note.animC)note.animC=0;with(note){clearTimeout(animTimer);var speed=(animations.length&&!now)?(show?animInSpeed:animOutSpeed):1;if(show&&!animC){if(onshow)this.onshow(noteID);ref.style[cssProp]=cssVis}animC=Math.max(0,Math.min(1,animC+speed*(show?1:-1)));if(document.getElementById&&speed<1)for(var a=0;a<animations.length;a++)animations[a](ref,animC);if(!show&&!animC){if(onhide)this.onhide(noteID);ref.style[cssProp]=cssHid}if(animC!=parseInt(animC)){animTimer=setTimeout(myName+'.animate("'+noteID+'", '+show+')',50)}else{checkType(noteID,animC?1:0,0);note.animating=0}}}};function animFade(ref,counter){var f=ref.filters,done=(counter==1);if(f){if(!done&&ref.style.filter.indexOf("alpha")==-1)ref.style.filter+=' alpha(opacity='+(counter*100)+')';else if(f.length&&f.alpha)with(f.alpha){if(done)enabled=false;else{opacity=(counter*100);enabled=true}}}else ref.style.opacity=ref.style.MozOpacity=counter*0.999};
var supernote;
//---global functions - get url query value
function gup(name, url) {
  name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
  var regex = new RegExp( "[\\?&]"+name+"=([^&#]*)" ), results = regex.exec( url );
  return (results === null)?(0):(results[1]);
}
function hasClass(elm, className) {
  return new RegExp(("(^|\\s)" + className + "(\\s|$)"), "i").test(elm.className);
}
function setListeners(className,eventName,functionDefinition) {
  var arr = elmsByClass(className), length = arr.length, z;
  if(length > 0) { 
    for(z=0;z<length;z++) { 
      removeEvent(arr[z],eventName,functionDefinition);
      addEvent(arr[z],eventName,functionDefinition);
    }; 
  };
};

//---textarea - text2cursor
var activeTextareaId = '';

function setCsr(elem, caretPos) {
  if(elem.createTextRange) {
    var range = elem.createTextRange();
    range.move('character', caretPos);
    range.select();
  } else {
    if(elem.selectionStart) {
      elem.focus();
      elem.setSelectionRange(caretPos, caretPos);
    } else {
      elem.focus();
    };
  };
};
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
    var startPos = myField.selectionStart, endPos = myField.selectionEnd;
    block = myField.value.substring(startPos,endPos);
    myField.value = myField.value.substring(0, startPos) + myValue + myField.value.substring(endPos, myField.value.length);
  } else {
    myField.value += myValue;
  }
  caretPos = myField.value.indexOf('-cursor-')+block.length;
  myField.value = myField.value.replace('-cursor-', block);
  setCsr(myField,caretPos);
};
function insert2Area(what) {
  var activeTextareaIdFromHref = gup('textid',what);
  if(activeTextareaIdFromHref) { activeTextareaId = elm(gup('textid',what)).id; };
  if(activeTextareaId) { insertAtCursor(elm(activeTextareaId), gup('tag',what)); };
}
//---textarea - text2curosr init function
function initInsertToTextarea() {
  setListeners('clickDaTag','click',function(event) { insert2Area(this.href); stopDefault(event); });
  setListeners('draftable','click',function(event) { activeTextareaId = this.id; stopDefault(event); });
  setListeners('toggleToolSize','click',function(event) { 
    var className = gup("class",this.href), elmId = gup("textid",this.href), elmInstance = elm(elmId), currentClass = elmInstance.className, toolInst = elm(gup('toolid',this.href));
    if(!hasClass(elmInstance,className)) { addClass(elmInstance,className); } else { removeClass(elmInstance,className); };
    if (!new RegExp(("(^|\\s)" + gup('toolclass',this.href) + "(\\s|$)"), "i").test(toolInst.className)) { addClass(toolInst,'textareaToolboxLarge'); } else { removeClass(toolInst,'textareaToolboxLarge'); };
    stopDefault(event); });
}

//---textarea drafting
var arrDraft = [], //0-id, 1-lastlength
timerRunning = false,
draftTimer = 5000,
draftTimeout, //---with this timer could be stopped
draftTimeoutCounter = 0; //--if bigger than 3 stop timer - reset every time save function is called

function draftDoSave() { 
  var x, arrDraftLength = arrDraft.length; 
  for (x=0;x<arrDraftLength;x++) {
    var currentTextarea = elm(arrDraft[x][0]);
    taRefreshValue(currentTextarea);
    taText = currentTextarea.value;
    if(taText.length != arrDraft[x][1] && taText.length > 0) {
        xajax_draft_save(currentTextarea.id,taText);
        arrDraft[x][1] = taText.length;
        draftTimeoutCounter = 0;
    };
  };
};
//---new
function taRefreshValue(textareaInst) {
  if(document.all) {
    //---firefox
    textareaInst.setAttribute("value",currentTextarea.innerHTML);
  }
}
function draftGetLength(textareaInst) {
  taRefreshValue(textareaInst);
  return textareaInst.value.length;
}
//set class - is saved - green - callback function from xajax
function draftSaved(textareaId) {
  textareaInst = elm(textareaId);
  removeClass(textareaInst,'draftNotSave');
  addClass(textareaInst,'draftSave');
}
//check all ta and set not saved class if there is a difference between string length
function draftIsSavedSetClass() {
  var x, ta, arrDraftLength = arrDraft.length;
  for (x=0;x<arrDraftLength;x++) {
    ta = elm(arrDraft[x][0]);
    if(arrDraft[x][1] != draftGetLength(ta)) {
      removeClass(ta,'draftSave');
      addClass(ta,'draftNotSave');
    }
  } 
}
//register textarea
function setDraftElement(textareaParam) { 
  var x, add=1, arrDraftLength = arrDraft.length; 
  for (x=0;x<arrDraftLength;x++) { 
    if(arrDraft[x][0] == textareaParam.id) {
      add=0;
    }; 
  }; 
  if(add==1) { 
    arrDraft.push([textareaParam.id,0]); 
  }; 
};
//initiate timer, check not saved
function startDraftTimer() {
  if(timerRunning === false) {
    draftTimeout = setTimeout(draftTimerHandler, draftTimer);
    timerRunning = true;
  }
  draftIsSavedSetClass();
}
function draftTimerHandler() {
  draftDoSave();
  draftTimeout = setTimeout(draftTimerHandler, draftTimer);
  if(draftTimeoutCounter > 3) {
    draftTimeoutCounter = 0;
    timerRunning = false;
    clearTimeout(draftTimeout);
  }
}
//register ib keyup
function draftEventHandler() { setDraftElement(this); startDraftTimer(); };

//---popup opening
function openPopup(href) { window.open(href,'fpopup','scrollbars='+gup("scrollbars",href)+',toolbar='+gup("toolbar",href)+',menubar='+gup("menubar",href)+',status='+gup("status",href)+',resizable='+gup("resizable",href)+',width='+gup("width",href)+',height='+gup("height",href)+''); };

function setSwitchEventListeners(event) { setListeners('xajaxSwitch','click',function(event) { xajax_forum_fotoDetail(gup("fid",this.href)); stopDefault(event); }); };

function setTagEventListeners(event) { setListeners('tagLink','click',function(event) { xajax_user_tag(this.id); stopDefault(event); }); };

function setPocketAddEventListeners(event) { setListeners('pocketAdd','click',function(event) { var item = gup("i",this.href), page = 1; if(item > 0) { page = 0; } else { item = gup("k",this.href); }; xajax_pocket_add(item,page); stopDefault(event); }); };

function draftSetEventListeners(event) { setListeners('draftable','keyup',draftEventHandler); };

function setPollListeners(event) { setListeners('pollAnswerLink','click',function(event) { xajax_poll_pollVote(gup('poll',this.href)); stopDefault(event); }); };

function onDomReady() {
  //---draft set listeners
  draftSetEventListeners();
  //---set tag listeners
  setTagEventListeners();
  //---set popups
  setListeners('popupLink','click',function(event) { openPopup(this.href); stopDefault(event); });
  //---set poll
  setPollListeners();
  //---switch
  //setSwitchEventListeners();
  //---pocket
  setPocketAddEventListeners();
  //---textarea toolbox
  initInsertToTextarea();
  //---super note init
  supernote = new SuperNote('supernote', {});
  
  var arr = elmsByClass('fuvatarswf'), length = arr.length, z;
  if(length > 0) { 
    for(z=0;z<length;z++) { 
      var elmInst = arr[z];
      var elmImgInst = elm(elmInst.id.replace('fuplay','fuimg'));
      var width = gup('w',elmImgInst.src);
      var height =  gup('h',elmImgInst.src);
      swfobject.embedSWF("/fuvatar/fuplay.swf", elmInst.id, width, height, "9.0.115", "expressInstall.swf",{u:elmInst.id.replace('fuplay',''),time:gup('t',elmImgInst.src)},{allowFullScreen:"true"});    
    }; 
  };
};
function datePickerInit() {
  datePickerController.create();
}
DOMReady(onDomReady);