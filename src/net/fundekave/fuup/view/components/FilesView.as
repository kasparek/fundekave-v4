package net.fundekave.fuup.view.components
{
	import com.bit101.components.*;
	
	import flash.display.DisplayObject;
	import flash.display.DisplayObjectContainer;
	import flash.display.Stage;
	import flash.events.Event;
	import flash.net.FileFilter;
	import flash.net.FileReference;
	import flash.net.FileReferenceList;
	import flash.text.TextFormat;
	import flash.utils.setTimeout;
	
	import net.fundekave.Application;
	import net.fundekave.Container;
	
	public class FilesView extends Container
	{
		public function FilesView(parent:DisplayObjectContainer=null, xpos:Number=0, ypos:Number=0,lang:Object=null)
		{
			super(parent, xpos, ypos);
			this.lang = lang;
			this.setup();
		}
		
		import net.fundekave.fuup.model.vo.FileVO;
		
		public static const RESIZE:String = 'resize';
		
		public static const ACTION_PROCESS:String = 'actionProcess';
		public static const ACTION_UPLOAD:String = 'actionUpload';
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
		
		private var _autoProcess:Boolean = false;
		public function set autoProcess(v:Boolean):void { _autoProcess = v; processButt.visible = !v }
		public function get autoProcess():Boolean { return _autoProcess; }
		
		private var _autoUpload:Boolean = false;
		public function set autoUpload(v:Boolean):void { _autoUpload = v; processButt.visible = !v; uploadButt.visible = !v; }
		public function get autoUpload():Boolean { return _autoUpload; }
		
		private var _displayContent:Boolean = true;
		public function set displayContent(v:Boolean):void { _displayContent = v; filesBox.visible = v }
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
		
		private var filesArr:Array;
		private function onFilesSelect(e:Event):void {
			filesArr = (e.target as FileReferenceList).fileList;
			if(filesArr.length>0) {
				//---init global progress bar
				globalProgressBar.visible = true;
				globalProgressBar.maximum = filesArr.length;
				globalProgressBar.value = 0;
				
				populateFiles();
			}
		}
		
		private function onFileSelect(e:Event):void {
			
			var fileRef:FileReference = e.target as FileReference;
			if( fileRef.name ) {
				filesArr = [];
				currFile = fileRef;
				dispatchEvent( new Event( FILE_CHECK_EXITS ));
			}
		}
		
		private var currFileView:FileView;
		public var currFile:FileReference;
		
		private function populateFiles():void {
			if(filesArr.length > 0) {
				currFile = filesArr.shift();
				//---check if file is not already in list
				dispatchEvent( new Event( FILE_CHECK_EXITS ));
				return;
			} else {
				if(autoProcess===true) {
					doAction(ACTION_PROCESS);
				} else if(autoUpload === true) {
					doAction(ACTION_UPLOAD);
				}
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
				
				globalProgressBar.visible = false;
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
			globalProgressBar.value = Number(globalProgressBar.value + 1);
			if(globalProgressBar.value == globalProgressBar.maximum) {
				//---hide progress bar
				globalProgressBar.visible = false;
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
						
					} else {
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
		private var processButt:PushButton;
		private var uploadButt:PushButton;
		public var globalProgressBar:ProgressBar;
		public var globalMessagesBox:HBox;
		public var globalMessages:Label;
		private var filesBox:Container;
		private function setup():void {
			var box:HBox = new HBox(this,5,5);
			box.height = 25;
			
			selectFilesButt = new PushButton(box,0,0,lang.selectfiles,browseFiles);
			selectFilesButt.width = 60;
			
			correctionsCheckboxHolder = new Container(box);
			correctionsCheckboxHolder.width = 60;
			correctionsCheckboxHolder.height = 20;
			correctionsCheckboxHolder.visible = _settingsVisible;
			correctionsCheckbox = new CheckBox(correctionsCheckboxHolder,5,5,lang.corections);
			correctionsCheckbox.selected = settingsOn;
			
			processButt = new PushButton(box,0,0,lang.process,onProcessClick);
			processButt.width = 60;
			processButt.visible = !_autoUpload;
			
			uploadButt = new PushButton(box,0,0,lang.upload,onUploadClick);
			uploadButt.width = 60;
			uploadButt.visible = !autoUpload;
			
			globalProgressBar = new ProgressBar(box);
			globalProgressBar.visible = false;
			globalProgressBar.width = 275;
			globalProgressBar.height = 20;
			globalProgressBar.maximum = 100; 
			
			globalMessagesBox = new HBox(box);
			globalMessagesBox.visible = false;
			globalMessagesBox.width = 275;
			globalMessagesBox.height = 20;
			//globalMessagesBox.backgroundColor = 0xaa3333;
			
			globalMessages = new Label(globalMessagesBox);
			globalMessages.textField.defaultTextFormat.color = 0xffffff;
			
			filesBox = new Container(this,5,30);
			filesBox.mouseChildren = true;
			filesBox.visible = displayContent;
			
			filesBox.addEventListener(Event.ENTER_FRAME, onResize);
		}
		
		private function onProcessClick(e:Event):void {
			doAction(ACTION_PROCESS)
		}
		private function onUploadClick(e:Event):void {
			doAction(ACTION_UPLOAD)
		}
		
	}
}