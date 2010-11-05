package net.fundekave.fuup.view.components
{
	import com.bit101.components.*;
	import com.greensock.TweenLite;
	
	import flash.display.DisplayObject;
	import flash.display.DisplayObjectContainer;
	import flash.display.Stage;
	import flash.events.Event;
	import flash.net.FileFilter;
	import flash.net.FileReference;
	import flash.net.FileReferenceList;
	import flash.text.TextFormat;
	import flash.utils.clearTimeout;
	import flash.utils.setTimeout;
	
	import net.fundekave.Application;
	import net.fundekave.Container;
	
	public class FilesView extends Container
	{
		public function FilesView(parent:DisplayObjectContainer=null, xpos:Number=0, ypos:Number=0,lang:Object=null)
		{
			super(parent, xpos, ypos);
			this.lang = lang;
		}
		
		import net.fundekave.fuup.model.vo.FileVO;
		
		public static const RESIZE:String = 'resize';
		
		public static const ACTION_PROCESS:String = 'actionProcess';
		public static const ACTION_UPLOAD:String = 'actionUpload';
		public static const ACTION_CANCEL:String = 'actionCancel';
		public static const FILE_CHECK_EXITS:String = 'fileCheckExits';
		
		public static const GAP_HORIZONTAL:int = 5;
		public static const GAP_VERTICAL:int = 5;
		
		public var lang:Object;
		
		public var filesNumMax:int;
		public var multiFiles:Boolean = true;
				
		private var _settingsVisible:Boolean = true;
		public function get settingsVisible():Boolean {
			return _settingsVisible;
		}
		public function set settingsVisible(v:Boolean):void {
			_settingsVisible = v;
			if(correctionsCheckboxHolder) {
				correctionsCheckboxHolder.visible = _settingsVisible;
			}
		}
		
		public var settingsOn:Boolean = false;
		public var _processVisible:Boolean = true;
		public function set processVisible(v:Boolean):void {
			_processVisible = v;
			if(processButt)processButt.visible = v; 
		}
		public function get processVisible():Boolean { return _autoUpload; }
		
		public var _processOn:Boolean = true;
		public function set processOn(v:Boolean):void {
			_processOn = v;
			if(processButt)processButt.selected = v; 
		}
		public function get processOn():Boolean { return _autoUpload; }
		
		private var _autoProcess:Boolean = false;
		public function set autoProcess(v:Boolean):void { 
			_autoProcess = v;
			if(processButt)processButt.visible = !v && processVisible; 
		}
		public function get autoProcess():Boolean { return _autoProcess; }
		
		private var _autoUpload:Boolean = false;
		public function set autoUpload(v:Boolean):void {
			_autoUpload = v; 
			if(processButt)processButt.visible = !v && processVisible;
			if(uploadButt)uploadButt.visible = !v; 
		}
		public function get autoUpload():Boolean { return _autoUpload; }
		
		private var _displayContent:Boolean = true;
		public function set displayContent(v:Boolean):void { 
			_displayContent = v; 
			if(filesBox)filesBox.visible = v;
		}
		public function get displayContent():Boolean { return _displayContent; }
				
		private var fileRefList:FileReferenceList
		private function browseFiles(e:Event):void {
			globalMessagesBox.visible = false;
			
			if(multiFiles===true) {
				fileRefList = new FileReferenceList();
				fileRefList.addEventListener(Event.SELECT, onFilesSelect );
				fileRefList.browse([new FileFilter(String(lang.filetypes), "*.jpg;*.gif;*.png")]);
			} else {
				var fileRef:FileReference = new FileReference();
				fileRef.addEventListener(Event.SELECT, onFileSelect );
				fileRef.browse([new FileFilter(String(lang.filetypes), "*.jpg;*.gif;*.png")]);
			}
		}
		
		public function initProgress(state:String,max:uint):void {
			//---init global progress bar
			globalProgressBar.visible = true;
			globalProgresslabel.visible = true;
			cancelButt.visible = true;
			globalProgressBar.value = 0;
			globalProgressBar.maximum = max*100;
			TweenLite.to(globalProgressBar,0.3,{value:globalProgressBar.maximum*0.1});
			globalProgresslabel.text = lang[state];
			this.addEventListener(ACTION_CANCEL, onCancel);
		}
		public function toProgress(value:Number):void {
			TweenLite.to(globalProgressBar,0.1,{value:value*100});
		}
		public function closeProgress():void {
			globalProgressBar.visible = false;
			globalProgresslabel.visible = false;
			cancelButt.visible = false;
		}
		
		private var filesArr:Array;
		private var populateFilesTimeout:uint;
		private function onFilesSelect(e:Event):void {
			filesArr = (e.target as FileReferenceList).fileList;
			if(filesArr.length>0) {
				initProgress('loading',filesArr.length+filesBox.numChildren);
				setTimeout(populateFiles,300);
			}
		}

		private function onCancel(e:Event):void
		{
			this.removeEventListener(ACTION_CANCEL, onCancel);
			if(populateFilesTimeout) clearTimeout(populateFilesTimeout);
			closeProgress();
			currFile = null;
			filesArr = [];
		}
		
		private function onFileSelect(e:Event):void {
			
			var fileRef:FileReference = e.target as FileReference;
			if( fileRef.name ) {
				filesArr = [];
				currFile = fileRef;
				initProgress('loading',1);
				setTimeout(fileCheckLater,300);
			}
		}
		private function fileCheckLater():void {
			dispatchEvent( new Event( FILE_CHECK_EXITS ));
		}
		
		private var currFileView:FileView;
		public var currFile:FileReference;
		
		private function populateFiles():void {
			if(populateFilesTimeout) populateFilesTimeout=0;
			if(filesArr.length > 0) {
				currFile = filesArr.shift();
				//---check if file is not already in list
				dispatchEvent( new Event( FILE_CHECK_EXITS ));
				return;
			} else if(autoUpload === true) {
				doAction(ACTION_UPLOAD);
			}
		}
		
		public function failFile():void {
			//---set progress
			
			//---show message
			progressInc();
			//---populate next file
			populateFiles();
		}
		
		public function addFile():void {
			//---check for limit
			if(filesBox.numChildren >= filesNumMax) {
				//---show error
				var errWin:CloseWindow = new CloseWindow(this,200,5,"ERROR");
				errWin.color = 0xff8888;
				errWin.closeTween = {alpha:0,delay:5};
				errWin.height = 50;
				errWin.width = 200;
				errWin.close();
				errWin.content.addChild( new Label(null,0,5,lang.filelimiterror) );
				
				closeProgress();
				currFile = null;
				filesArr = null;
				return;
			}
			
			currFileView = new FileView();
			currFileView.fileVO = new FileVO();
			currFileView.fileVO.showThumb = this.displayContent;
			currFileView.lang = this.lang;
			//---set filesbox size and child position
			var pos:Object = this.getNextPos();
			currFileView.x = pos.x;
			currFileView.y = pos.y; 
			//---wait till filesBox is resized with new element
			currFileView.addEventListener(Event.ADDED_TO_STAGE, onFileViewFrame );
			filesBox.addChild( currFileView );
			//setTimeout( onFileViewFrame, 100, null);
		}
				
		private function getNextPos(index:int=-1):Object {
			var total:Number = index>-1 ? index : filesBox.numChildren;
			var cols:Number = Math.floor(filesBox.width / (FileView.WIDTH+GAP_HORIZONTAL));
			var rowsDone:Number = Math.floor(total / cols);
			var rest:Number = total - (cols*rowsDone);
			return {x:rest * (FileView.WIDTH+GAP_HORIZONTAL),y:rowsDone * (FileView.HEIGHT+GAP_VERTICAL)};
		}
		
		private function resetLayout():void {
			if(filesBox.numChildren>0) {
				var delay:Number = 0;
				for(var i:int=0; i<filesBox.numChildren; i++) {
					var child:DisplayObject = filesBox.getChildAt(i) as DisplayObject; 
					var pos:Object = this.getNextPos(i);
					if(child.x != pos.x || child.y != pos.y) {
						child.x = pos.x;
						child.y = pos.y;
						trace('LAYOUT::SETTINGNEWPOSITION');  
					}
				}
				var cols:Number = Math.floor(filesBox.width / (FileView.WIDTH+GAP_HORIZONTAL));
				var rows:Number = Math.ceil( filesBox.numChildren / cols );
				filesBox.height = rows * currFileView.height;
			}
		}
		
		private function onFileViewFrame(e:Event):void {
			(e.target as FileView).removeEventListener(Event.ADDED_TO_STAGE, onFileViewFrame );
			fileLater( currFileView, currFile );
		}
		
		private function fileLater(fileView:FileView, file:FileReference):void {
			fileView.addEventListener( FileView.FILE_UPDATED, onFileCreated );
			fileView.file = file;
		}
		
		private function onFileCreated(e:Event):void {
			var fileView:FileView = e.target as FileView;
			fileView.removeEventListener( FileView.FILE_UPDATED, onFileCreated );
			//---set progress
			progressInc();
			setTimeout(populateFiles,100);
		}
		
		private function progressInc():void {
			toProgress(filesBox.numChildren);
			if(filesArr.length == 0) {
				//---hide progress bar
				closeProgress();
			}
		}
		
		private var fileBoxHeight:int = 0;
		private var fileBoxNumChildred:int = 0;
		private var oldStageWidth:int=0;
		private function onResize(e:Event):void {
			if(displayContent===true) {
				if(Application.application.stage.stageWidth != oldStageWidth) {
					var stage:Stage = Application.application.stage;
					oldStageWidth = stage.stageWidth;
					filesBox.width = stage.stageWidth-20;
					trace("STAGERESIZE::NEWFILESBOXSIZE::"+filesBox.width);
					//---set new positions for children
					resetLayout();
				}
				if( filesBox.height != fileBoxHeight || fileBoxNumChildred != filesBox.numChildren) {
					
					if( fileBoxNumChildred != filesBox.numChildren ) {
						resetLayout();
						fileBoxNumChildred = filesBox.numChildren;	
					}
					
					if(filesBox.numChildren > 0) {
						
						if( filesBox.height != fileBoxHeight ) {
							fileBoxHeight = filesBox.height;
							Application.application.height = filesBox.height + Fuup.HEIGHT + 300;
						}
						
					} else if(Application.application.height != Fuup.HEIGHT) {
						fileBoxHeight = 0;
						Application.application.height = Fuup.HEIGHT;
					}
					dispatchEvent( new Event(RESIZE) );
				}
			}
		}
		
		private function doAction(actionStr:String):void {
			dispatchEvent( new Event( actionStr ));
		}
				
		private var selectFilesButt:PushButton;
		private var correctionsCheckboxHolder:Container;
		public var correctionsCheckbox:CheckBox;
		public var processButt:CheckBox;
		private var uploadButt:PushButton;
		public var cancelButt:PushButton;
		public var globalProgressBar:ProgressBar;
		public var globalProgresslabel:Label;
		public var globalMessagesBox:Container;
		public var globalMessages:Label;
		private var filesBox:Container;
		public function setup():void {
			selectFilesButt = new PushButton(this,5,5,lang.selectfiles,browseFiles);
			selectFilesButt.width = 60;
			
			correctionsCheckboxHolder = new Container(this,70,5);
			correctionsCheckboxHolder.width = 60;
			correctionsCheckboxHolder.height = 20;
			correctionsCheckboxHolder.visible = _settingsVisible;
			correctionsCheckbox = new CheckBox(correctionsCheckboxHolder,5,5,lang.corections);
			correctionsCheckbox.selected = settingsOn;
			
			processButt = new CheckBox(this,135-(_settingsVisible?65:0)+5,10,lang.process,onProcessClick);
			processButt.selected=_processOn;
			processButt.width = 60;
			processButt.visible = !_autoUpload && _processVisible;
			
			uploadButt = new PushButton(this,200-(!_settingsVisible?65:0)-(!processButt.visible?65:0),5,lang.upload,onUploadClick);
			uploadButt.width = 60;
			uploadButt.visible = !_autoUpload;
			
			cancelButt = new PushButton(this,200,5,lang.cancel,onCancelClick);
			cancelButt.width = 60;
			cancelButt.visible = false;
			
			globalProgressBar = new ProgressBar(this,5,5);
			globalProgressBar.width = 190;
			globalProgressBar.height = 20;
			globalProgressBar.maximum = 100;
			globalProgressBar.visible = false;
			
			globalProgresslabel = new Label(this,10,5);
			globalProgresslabel.visible = false;
			
			globalMessagesBox = new Container(this,270,5);
			globalMessagesBox.visible = false;
			globalMessagesBox.width = 200;
			globalMessagesBox.height = 20;
			globalMessagesBox.backgroundColor = 0xaa3333;
			
			globalMessages = new Label(globalMessagesBox,5,0);
			var format:TextFormat = globalMessages.textField.defaultTextFormat;
			format.color = 0xffffff;
			globalMessages.textField.defaultTextFormat = format;
			
			filesBox = new Container(this,5,30);
			filesBox.mouseChildren = true;
			filesBox.visible = displayContent;
			
			filesBox.addEventListener(Event.ENTER_FRAME, onResize);
		}
		
		private function onProcessClick(e:Event):void {
			doAction(ACTION_PROCESS);
		}
		private function onUploadClick(e:Event):void {
			doAction(ACTION_UPLOAD);
		}
		private function onCancelClick(e:Event):void {
			doAction(ACTION_CANCEL);
		}
	}
}