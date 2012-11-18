package net.fundekave.fuup.view.components
{
	import com.bit101.components.*;
	import com.greensock.TweenLite;
	import flash.filters.GlowFilter;
	import flash.filters.GradientGlowFilter;
	
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
	import flash.filters.DropShadowFilter;
	import flash.events.MouseEvent;
	
	import net.fundekave.Application;
	import net.fundekave.Container;
	import net.fundekave.fuup.model.vo.FileVO;
	
	public class FilesView extends Container
	{
		public function FilesView(parent:DisplayObjectContainer = null, xpos:Number = 0, ypos:Number = 0)
		{
			super(parent, xpos, ypos);
		}
		
		[Embed(source="/assets/browse.png")]
		private var BROWSEIMG:Class;
		[Embed(source="/assets/upload.png")]
		private var UPLOADIMG:Class;
		[Embed(source="/assets/cancel.png")]
		private var CANCELIMG:Class;
		
		public static const RESIZE:String = 'resize';
		
		public static const PROGRESS:String = 'progress';
		
		public static const ACTION_LOAD:String = 'actionLoad';
		public static const ACTION_UPLOAD:String = 'actionUpload';
		public static const ACTION_CANCEL:String = 'actionCancel';
		public static const FILE_CHECK_EXITS:String = 'fileCheckExits';
		public static const FILE_ERROR_NUMLIMIT:String = 'fileErrorNumLimit';
		
		public static const GAP_HORIZONTAL:int = 5;
		public static const GAP_VERTICAL:int = 5;
		
		public var filesNumMax:int;
		public var fileTypes:String = 'jpg,gif,png';
		public var multiFiles:Boolean = true;
		public var embedWidth:Number;
		
		private var _autoUpload:Boolean = false;
		
		public function set autoUpload(v:Boolean):void
		{
			_autoUpload = v;
			if (showControls)
				if (uploadButt)
					uploadButt.visible = !v;
		}
		
		public function get autoUpload():Boolean
		{
			return _autoUpload;
		}
		
		private var _showControls:Boolean = true;
		
		public function set showControls(v:Boolean):void
		{
			if (uploadButt)
				uploadButt.visible = false;
			if (cancelButt)
				cancelButt.visible = false;
			_showControls = v;
		}
		
		public function get showControls():Boolean
		{
			return _showControls;
		}
		
		private var _showImages:Boolean = true;
		
		public function set showImages(v:Boolean):void
		{
			_showImages = v;
			if (filesBox)
				filesBox.visible = v;
		}
		
		public function get showImages():Boolean
		{
			return _showImages;
		}
		
		private var filesLoadingNum:int = 0;
		private var fileRefList:FileReferenceList
		
		private function browseFiles(e:Event):void
		{
			var fileFilter:String = "*." + fileTypes.replace(/,/gi, ";*.");
			if (multiFiles === true)
			{
				fileRefList = new FileReferenceList();
				fileRefList.addEventListener(Event.SELECT, onFilesSelect);
				fileRefList.browse([new FileFilter(fileTypes.toUpperCase(), fileFilter)]);
			}
			else
			{
				var fileRef:FileReference = new FileReference();
				fileRef.addEventListener(Event.SELECT, onFileSelect);
				fileRef.browse([new FileFilter(fileTypes.toUpperCase(), fileFilter)]);
			}
		}
		
		public var progress:Number = 0;
		
		public function initProgress():void
		{
			//---init global progress bar
			selectFilesButt.visible = false;
			uploadButt.visible = false;
			globalProgressBar.visible = true;
			globalProgresslabel.visible = true;
			if (showControls)
				cancelButt.visible = true;
			progress = globalProgressBar.value = 0;
			globalProgressBar.maximum = 100;
			TweenLite.to(globalProgressBar, 0.3, {value: globalProgressBar.maximum * 0.1});
			globalProgresslabel.text = '';
		}
		
		public function toProgress(value:Number):void
		{
			progress = value;
			TweenLite.to(globalProgressBar, 0.1, {value: value});
		}
		
		public function closeProgress():void
		{
			selectFilesButt.visible = true;
			if (showControls)
				if (!autoUpload)
					uploadButt.visible = true;
			
			globalProgressBar.visible = false;
			globalProgresslabel.visible = false;
			cancelButt.visible = false;
		}
		
		private var filesArr:Array;
		private var populateFilesTimeout:uint;
		
		private function onFilesSelect(e:Event):void
		{
			filesArr = (e.target as FileReferenceList).fileList;
			if (filesArr.length > 0)
			{
				doAction(ACTION_LOAD);
				filesLoadingNum = filesBox.numChildren + filesArr.length;
				initProgress();
				setTimeout(populateFiles, 1);
			}
		}
		
		public function cancel():void
		{
			if (populateFilesTimeout)
				clearTimeout(populateFilesTimeout);
			closeProgress();
			currFile = null;
			filesArr = [];
		}
		
		private function onFileSelect(e:Event):void
		{
			
			var fileRef:FileReference = e.target as FileReference;
			if (fileRef.name)
			{
				filesArr = [];
				currFile = fileRef;
				initProgress();
				setTimeout(fileCheckLater, 300);
			}
		}
		
		private function fileCheckLater():void
		{
			dispatchEvent(new Event(FILE_CHECK_EXITS));
		}
		
		private var currFileView:FileView;
		public var currFile:FileReference;
		
		private function populateFiles():void
		{
			if (populateFilesTimeout)
				populateFilesTimeout = 0;
			if (filesArr.length > 0)
			{
				currFile = filesArr.shift();
				//---check if file is not already in list
				dispatchEvent(new Event(FILE_CHECK_EXITS));
				return;
			}
			else if (autoUpload === true)
			{
				doAction(ACTION_UPLOAD);
			}
		}
		
		public function failFile():void
		{
			progressInc();
			populateFiles();
		}
		
		public function addFile():void
		{
			//---check for limit
			if (filesBox.numChildren >= filesNumMax)
			{
				//---show error
				dispatchEvent(new Event(FILE_ERROR_NUMLIMIT));
				closeProgress();
				currFile = null;
				filesArr = null;
				return;
			}
			
			currFileView = new FileView();
			currFileView.fileVO = new FileVO();
			currFileView.fileVO.showThumb = this.showImages;
			//---set filesbox size and child position
			var pos:Object = this.getNextPos();
			currFileView.x = pos.x;
			currFileView.y = pos.y;
			//---wait till filesBox is resized with new element
			currFileView.addEventListener(Event.ADDED_TO_STAGE, onFileViewFrame);
			filesBox.addChild(currFileView);
		}
		
		private function getNextPos(index:int = -1):Object
		{
			var total:Number = index > -1 ? index : filesBox.numChildren;
			var cols:Number = Math.floor(filesBox.width / (FileView.WIDTH + GAP_HORIZONTAL));
			var rowsDone:Number = Math.floor(total / cols);
			var rest:Number = total - (cols * rowsDone);
			return {x: rest * (FileView.WIDTH + GAP_HORIZONTAL), y: rowsDone * (FileView.HEIGHT + GAP_VERTICAL)};
		}
		
		private function resetLayout():void
		{
			if (filesBox.numChildren > 0)
			{
				var delay:Number = 0;
				for (var i:int = 0; i < filesBox.numChildren; i++)
				{
					var child:DisplayObject = filesBox.getChildAt(i) as DisplayObject;
					var pos:Object = this.getNextPos(i);
					if (child.x != pos.x || child.y != pos.y)
					{
						child.x = pos.x;
						child.y = pos.y;
						trace('LAYOUT::SETTINGNEWPOSITION');
					}
				}
				var cols:Number = Math.floor(filesBox.width / (FileView.WIDTH + GAP_HORIZONTAL));
				var rows:Number = Math.ceil(filesBox.numChildren / cols);
				filesBox.height = rows * currFileView.height;
			}
		}
		
		private function onFileViewFrame(e:Event):void
		{
			(e.target as FileView).removeEventListener(Event.ADDED_TO_STAGE, onFileViewFrame);
			fileLater(currFileView, currFile);
		}
		
		private function fileLater(fileView:FileView, file:FileReference):void
		{
			fileView.addEventListener(FileView.FILE_UPDATED, onFileCreated);
			fileView.file = file;
		}
		
		private function onFileCreated(e:Event):void
		{
			var fileView:FileView = e.target as FileView;
			fileView.removeEventListener(FileView.FILE_UPDATED, onFileCreated);
			//---set progress
			progressInc();
			setTimeout(populateFiles, 100);
		}
		
		private function progressInc():void
		{
			var p:Number = Math.round((filesBox.numChildren / filesLoadingNum) * 100);
			toProgress(p);
			dispatchEvent(new Event(PROGRESS));
			if (filesArr.length == 0)
			{
				doAction(ACTION_CANCEL);
			}
		}
		
		private var fileBoxHeight:int = 0;
		private var fileBoxNumChildred:int = 0;
		private var oldStageWidth:int = 0;
		
		private function onResize(e:Event):void
		{
			if (showImages === true)
			{
				if (Application.application.stage.stageWidth != oldStageWidth)
				{
					var stage:Stage = Application.application.stage;
					oldStageWidth = stage.stageWidth;
					filesBox.width = stage.stageWidth - 20;
					trace("STAGERESIZE::NEWFILESBOXSIZE::" + filesBox.width);
					//---set new positions for children
					resetLayout();
				}
				if (filesBox.height != fileBoxHeight || fileBoxNumChildred != filesBox.numChildren)
				{
					
					if (fileBoxNumChildred != filesBox.numChildren)
					{
						resetLayout();
						fileBoxNumChildred = filesBox.numChildren;
					}
					
					if (filesBox.numChildren > 0)
					{
						
						if (filesBox.height != fileBoxHeight)
						{
							fileBoxHeight = filesBox.height;
							Application.application.height = filesBox.height + Fuup.HEIGHT + 300;
						}
						
					}
					else if (Application.application.height != Fuup.HEIGHT)
					{
						fileBoxHeight = 0;
						Application.application.height = Fuup.HEIGHT;
					}
					dispatchEvent(new Event(RESIZE));
				}
			}
		}
		
		private function doAction(actionStr:String):void
		{
			dispatchEvent(new Event(actionStr));
		}
		
		public var selectFilesButt:Component;
		private var uploadButt:Component;
		public var cancelButt:Component;
		public var globalProgressBar:ProgressBar;
		public var globalProgresslabel:Label;
		private var filesBox:Container;
		
		public function setup():void
		{
			selectFilesButt = new Component(this);
			selectFilesButt.x = 5;
			selectFilesButt.y = 4;
			selectFilesButt.useHandCursor = true;
			selectFilesButt.buttonMode = true;
			selectFilesButt.width = 32;
			selectFilesButt.height = 32;
			selectFilesButt.addChild(new BROWSEIMG());
			selectFilesButt.filters = [new DropShadowFilter(2, 45, 0, 0.5, 2, 2)];
			selectFilesButt.addEventListener(MouseEvent.CLICK, browseFiles, false, 0, true);
			selectFilesButt.addEventListener(MouseEvent.ROLL_OVER, onButtOver, false, 0, true);
			selectFilesButt.addEventListener(MouseEvent.ROLL_OUT, onButtOut, false, 0, true);
			
			uploadButt = new Component(this);
			uploadButt.useHandCursor = true;
			uploadButt.buttonMode = true;
			uploadButt.x = 47;
			uploadButt.y = 4;
			uploadButt.width = 32;
			uploadButt.height = 32;
			uploadButt.addChild(new UPLOADIMG());
			uploadButt.filters = [new DropShadowFilter(2, 45, 0, 0.5, 2, 2)];
			uploadButt.addEventListener(MouseEvent.CLICK, onUploadClick, false, 0, true);
			uploadButt.addEventListener(MouseEvent.ROLL_OVER, onButtOver, false, 0, true);
			uploadButt.addEventListener(MouseEvent.ROLL_OUT, onButtOut, false, 0, true);
			uploadButt.visible = showControls ? !_autoUpload : false;
			
			cancelButt = new Component(this);
			cancelButt.useHandCursor = true;
			cancelButt.buttonMode = true;
			cancelButt.x = 200;
			cancelButt.y = 4;
			cancelButt.width = 32;
			cancelButt.height = 32;
			cancelButt.addChild(new CANCELIMG());
			cancelButt.filters = [new DropShadowFilter(2, 45, 0, 0.5, 2, 2)];
			cancelButt.addEventListener(MouseEvent.CLICK, onCancelClick, false, 0, true);
			cancelButt.addEventListener(MouseEvent.ROLL_OVER, onButtOver, false, 0, true);
			cancelButt.addEventListener(MouseEvent.ROLL_OUT, onButtOut, false, 0, true);
			cancelButt.visible = false;
			
			if (embedWidth == -1)
				embedWidth = 200;
			globalProgressBar = new ProgressBar(this, 5, 6);
			globalProgressBar.width = embedWidth < 190 ? embedWidth - 10 : 190;
			globalProgressBar.height = 20;
			globalProgressBar.maximum = 100;
			globalProgressBar.visible = false;
			
			globalProgresslabel = new Label(this, 10, 6);
			globalProgresslabel.visible = false;
			
			filesBox = new Container(this, 5, 37);
			filesBox.mouseChildren = true;
			filesBox.visible = showImages;
			
			filesBox.addEventListener(Event.ENTER_FRAME, onResize);
		}
		
		private function onButtOver(e:Event):void
		{
			(e.target as Component).filters = [new GlowFilter(0)];
		}
		
		private function onButtOut(e:Event):void
		{
			(e.target as Component).filters = [];
		}
		
		private function onUploadClick(e:Event):void
		{
			doAction(ACTION_UPLOAD);
		}
		
		private function onCancelClick(e:Event):void
		{
			doAction(ACTION_CANCEL);
		}
	}
}