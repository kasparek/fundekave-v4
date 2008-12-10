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
var arrDraft = []; //0-id, 1-lastlength, 2-lasttime, 3-classNOTsave (0-not set,1-set)
var timeDiffToSaveDraft = 10000;
var idleDraftHandlerRunning = 0;
var timeDraftLastKeyPressed = 0;
function getTime() { var d = new Date(); return d.getTime(); };

function idleDraftHandler() { 
  if(idleDraftHandlerRunning==1) {
    if(((getTime() - timeDraftLastKeyPressed) > timeDiffToSaveDraft)) {
      var x, arrDraftLength = arrDraft.length; 
      for (x=0;x<arrDraftLength;x++) { 
        arrDraft[x][2] = timeDraftLastKeyPressed; 
        arrDraft[x][1] = arrDraft[x][1] + 1; 
      }; //---set all changed
      handleDraft(); 
      idleDraftHandlerRunning = 0;
    } else {
      setTimeout(idleDraftHandler, 3000);
    };
  };
};

function handleDraft() { 
  var x, t = getTime(), arrDraftLength = arrDraft.length; 
  for (x=0;x<arrDraftLength;x++) {
    var currentTextarea = elm(arrDraft[x][0]), 
    taText = '', taValueText = currentTextarea.value, taHTMLText = currentTextarea.innerHTML;
    if(taValueText.length > taText.length) { taText = taValueText; };
    if(taHTMLText.length > taText.length) { taText = taHTMLText; };
    if(taText.length != arrDraft[x][1] && taText.length > 0) {
      if(arrDraft[x][3] === 0) {
        if(hasClass(currentTextarea,'draftSave')) {
          removeClass(currentTextarea,'draftSave');
        }; 
        addClass(currentTextarea,'draftNotSave');
        arrDraft[x][3]=1; 
      };
      if((t - arrDraft[x][2]) > timeDiffToSaveDraft) {
        xajax_draft_save(currentTextarea.id,taText);
        arrDraft[x] = [currentTextarea.id,0,getTime(),0];
      };
    };
  };
  timeDraftLastKeyPressed = t;
  if(idleDraftHandlerRunning === 0) { idleDraftHandlerRunning = 1; idleDraftHandler(); };
};

function setDraftElement(textareaParam) { 
  var x, add=1, arrDraftLength = arrDraft.length; 
  for (x=0;x<arrDraftLength;x++) { 
    if(arrDraft[x][0] == textareaParam.id) {
      add=0;
    }; 
  }; 
  if(add==1) { 
    arrDraft.push([textareaParam.id,0,getTime(),0]); 
  }; 
};

function draftEventHandler() { setDraftElement(this); handleDraft(); };

function draftSaved(textareaId) {
  textareaInst = elm(textareaId);
  if(hasClass(textareaInst,'draftNotSave')) {
    removeClass(textareaInst,'draftNotSave');
  }; 
  addClass(textareaInst,'draftSave');
}

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
  setSwitchEventListeners();
  //---pocket
  setPocketAddEventListeners();
  //---textarea toolbox
  initInsertToTextarea();
  //---super note init
  supernote = new SuperNote('supernote', {});
  
};
DOMReady(onDomReady);