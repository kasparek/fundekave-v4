package net.fundekave.fuup.view.components
{
	import com.bit101.components.*;
	import com.greensock.TweenLite;
	import flash.display.Bitmap;
	import flash.display.Loader;
	import flash.display.LoaderInfo;
	import flash.events.IOErrorEvent;
	import flash.filters.GlowFilter;
	import flash.filters.GradientGlowFilter;
	import flash.net.URLRequest;
	
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
		
		public var browseImgUrl:String;
		private var browseImg:Bitmap;
		
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
		public static const BROWSEIMGERROR:String = 'browseImgError';
		
		public static const GAP_HORIZONTAL:int = 5;
		public static const GAP_VERTICAL:int = 5;
		
		public var filesNumMax:int;
		public var fileTypes:String = 'jpg,gif,png';
		public var multiFiles:Boolean = true;
		public var embedWidth:Number;
		public var embedHeight:Number;
		
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
			if (showControls)
				cancelButt.visible = true;
			progress = globalProgressBar.value = 0;
			globalProgressBar.maximum = 100;
			TweenLite.to(globalProgressBar, 0.3, {value: globalProgressBar.maximum * 0.1});
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
		
		private var oldStageWidth:int = 0;
		private var oldFilesBoxNum:int = 0;
		
		private function onFrame(e:Event):void
		{
			var needResetLayout:Boolean = false;
			if (!showImages) return;
			
			if (stage.stageWidth != oldStageWidth)
			{
				oldStageWidth = stage.stageWidth;
				filesBox.width = stage.stageWidth - 20;
				needResetLayout = true;
			}
			
			if (oldFilesBoxNum != filesBox.numChildren) {
				oldFilesBoxNum = filesBox.numChildren;
				needResetLayout = true;
			}

			var newH:Number = filesBox.numChildren > 0 ? filesBox.height + filesBox.y + (FileView.HEIGHT*2) : controlBox.height + controlBox.y;
			if (newH >= embedHeight) 
				Application.application.height = newH;
			
			if(needResetLayout) resetLayout();
		}
		
		private function doAction(actionStr:String):void
		{
			dispatchEvent(new Event(actionStr));
		}
		
		private var controlBox:Container;
		public var selectFilesButt:Component;
		private var uploadButt:Component;
		public var cancelButt:Component;
		public var globalProgressBar:ProgressBar;
		private var filesBox:Container;
		
		private function onBrowseImgLoaderError(e:Event):void {
			var browseImgLoader:Loader = (e.currentTarget as LoaderInfo).loader;
			browseImgLoader.contentLoaderInfo.removeEventListener(Event.COMPLETE, onBrowseImgLoader);
			browseImgLoader.contentLoaderInfo.removeEventListener(IOErrorEvent.IO_ERROR, onBrowseImgLoaderError);
			doAction(BROWSEIMGERROR);
			updateControlsLayout();
			controlBox.visible = true;
		}
		
		private function onBrowseImgLoader(e:Event):void {
			var browseImgLoader:Loader = (e.currentTarget as LoaderInfo).loader;
			browseImgLoader.contentLoaderInfo.removeEventListener(Event.COMPLETE, onBrowseImgLoader);
			browseImgLoader.contentLoaderInfo.removeEventListener(IOErrorEvent.IO_ERROR, onBrowseImgLoaderError);
			browseImg = browseImgLoader.content as Bitmap;
			selectFilesButt.removeChildAt(0);
			selectFilesButt.addChild(browseImg);
			selectFilesButt.width = browseImg.width;
			selectFilesButt.height = browseImg.height;
			updateControlsLayout();
			controlBox.visible = true;
		}
		
		private function updateControlsLayout():void {
			uploadButt.x = selectFilesButt.width + 5;
			uploadButt.y = (selectFilesButt.height - uploadButt.height) / 2;
			globalProgressBar.y = (selectFilesButt.height - globalProgressBar.height) / 2;
			cancelButt.x = globalProgressBar.x + globalProgressBar.width + 5;
			controlBox.height = selectFilesButt.y + selectFilesButt.height + 5;
			filesBox.y = controlBox.height;
		}
		
		public function setup():void
		{
			controlBox = new Container(this, 5, 2);
						
			selectFilesButt = new Component(controlBox);
			selectFilesButt.useHandCursor = true;
			selectFilesButt.buttonMode = true;
			selectFilesButt.width = 24;
			selectFilesButt.height = 24;
			selectFilesButt.addChild(new BROWSEIMG());
			selectFilesButt.filters = [new DropShadowFilter(2, 45, 0, 0.5, 2, 2)];
			selectFilesButt.addEventListener(MouseEvent.CLICK, browseFiles, false, 0, true);
			selectFilesButt.addEventListener(MouseEvent.ROLL_OVER, onButtOver, false, 0, true);
			selectFilesButt.addEventListener(MouseEvent.ROLL_OUT, onButtOut, false, 0, true);
			
			uploadButt = new Component(controlBox);
			uploadButt.useHandCursor = true;
			uploadButt.buttonMode = true;
			uploadButt.x = selectFilesButt.width+5;
			uploadButt.width = 24;
			uploadButt.height = 24;
			uploadButt.addChild(new UPLOADIMG());
			uploadButt.filters = [new DropShadowFilter(2, 45, 0, 0.5, 2, 2)];
			uploadButt.addEventListener(MouseEvent.CLICK, onUploadClick, false, 0, true);
			uploadButt.addEventListener(MouseEvent.ROLL_OVER, onButtOver, false, 0, true);
			uploadButt.addEventListener(MouseEvent.ROLL_OUT, onButtOut, false, 0, true);
			uploadButt.visible = showControls ? !_autoUpload : false;
			
			if (embedWidth == -1)
				embedWidth = 200;
			globalProgressBar = new ProgressBar(controlBox);
			globalProgressBar.width = embedWidth < 190 ? embedWidth - 10 : 190;
			globalProgressBar.height = 20;
			globalProgressBar.maximum = 100;
			globalProgressBar.visible = false;
			
			cancelButt = new Component(controlBox);
			cancelButt.useHandCursor = true;
			cancelButt.buttonMode = true;
			cancelButt.width = 24;
			cancelButt.height = 24;
			cancelButt.addChild(new CANCELIMG());
			cancelButt.filters = [new DropShadowFilter(2, 45, 0, 0.5, 2, 2)];
			cancelButt.addEventListener(MouseEvent.CLICK, onCancelClick, false, 0, true);
			cancelButt.addEventListener(MouseEvent.ROLL_OVER, onButtOver, false, 0, true);
			cancelButt.addEventListener(MouseEvent.ROLL_OUT, onButtOut, false, 0, true);
			cancelButt.visible = false;
						
			filesBox = new Container(this, 5);
			filesBox.mouseChildren = true;
			filesBox.visible = showImages;
			if(showImages) filesBox.addEventListener(Event.ENTER_FRAME, onFrame);
			
			if(browseImgUrl) {
				var browseImgLoader:Loader = new Loader();
				browseImgLoader.contentLoaderInfo.addEventListener(Event.COMPLETE, onBrowseImgLoader);
				browseImgLoader.contentLoaderInfo.addEventListener(IOErrorEvent.IO_ERROR, onBrowseImgLoaderError);
				controlBox.visible = false;
				browseImgLoader.load(new URLRequest(browseImgUrl));
			} else {
				updateControlsLayout();
			}
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