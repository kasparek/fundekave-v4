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