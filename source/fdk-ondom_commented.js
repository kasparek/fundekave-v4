//---global functions - get url query value
function gup( name , url ) {
  name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
  var regex = new RegExp( "[\\?&]"+name+"=([^&#]*)" );
  var results = regex.exec( url );
  if( results == null ) return 0;
  else return results[1];
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
  if(length > 0) { for(var z=0;z<length;z++) { arr[z].onclick = function() { xajax_forum_fotoDetail(gup("fid",this.href)); return false; }; } }
}
function onDomReady() {
  draftSetEventListeners();
  setTagEventListeners();
  setPopUpEventListeners();
  setPollEventListeners();
  setSwitchEventListeners();
  supernote = new SuperNote('supernote', {});
}
DOMReady(onDomReady);
