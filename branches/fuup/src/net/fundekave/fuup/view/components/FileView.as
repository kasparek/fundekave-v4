package net.fundekave.fuup.view.components
{
	import com.bit101.components.Component;
	import com.bit101.components.Label;
	import com.bit101.components.VBox;
	import com.bit101.components.Window;
	import com.greensock.TweenLite;
	import com.greensock.easing.Quad;
	
	import flash.display.Bitmap;
	import flash.display.Loader;
	import flash.display.PixelSnapping;
	import flash.events.Event;
	import flash.events.MouseEvent;
	import flash.filters.DropShadowFilter;
	import flash.geom.Rectangle;
	import flash.net.FileReference;
	
	import net.fundekave.Application;
	import net.fundekave.Container;
	import net.fundekave.fuup.model.vo.FileVO;
	import net.fundekave.lib.ImageResize;
	
	public class FileView extends Container
	{
		
		public static const WIDTH:Number = 175;
		public static const HEIGHT:Number = 110;
		
		private var thumbMaxWidth:Number = 140;
		private var thumbMaxHeight:Number = 100;
		
		public function FileView()
		{
			this.addEventListener(Event.ADDED_TO_STAGE, onStage );
			this.setup();
			this.width = WIDTH;
			this.height = HEIGHT;
			this.backgroundColor = 0xdddeee;
		}
		private function onStage(e:Event):void {
			this.removeEventListener(Event.ADDED_TO_STAGE, onStage );
		}
		
			public static const FILE_CREATED:String = 'fileCreated';
			public static const FILE_UPDATED:String = 'fileUpdated';
			public static const FILE_REMOVE:String = 'fileRemove';
			
			public static const STATE_PROCESSING:String = 'processing';
			
			public var lang:Object;
			
			[Embed(source="/assets/X.png")]
			private var ClosePNG:Class;
			[Embed(source="/assets/CCW.png")]
			private var CCWPNG:Class;
			[Embed(source="/assets/CW.png")]
			private var CWPNG:Class;
			[Embed(source="/assets/show.png")]
			private var ShowPNG:Class;
									
			private var _fileVO:FileVO;
			
			public function get fileVO():FileVO {
				return _fileVO;
			}
			public function set fileVO(value:FileVO):void {
				_fileVO = value;
			}
			
			public function get file():FileReference {
				return _fileVO.file;
			}
			
			public function set file(value:FileReference):void {
				
				_fileVO.filename = value.name ;
				_fileVO.file = value;
				_fileVO.renderer = this;
											
				updateThumb();
				
				dispatchEvent( new Event( FILE_CREATED, true ));
			}
			
			public function setLocalState(stateName:String):void {
				switch( stateName ) {
					case STATE_PROCESSING:
						fileVO.renderer.updateStatus(String(lang.processing),false);
						if(previewWin) {
							previewWin.close();
						}
						break;
				}
			}
						
			/**
			 * CREATE SMALL THUMBNAIL
			*/
			private function drawLater(e:Event):void {
				var imageResize:ImageResize = e.target as ImageResize;
				fileVO.widthOriginal = imageResize.widthOriginal; 
				fileVO.heightOriginal = imageResize.heightOriginal;
				if(fileVO.showThumb===true) {
					var bmp:Bitmap = new Bitmap( imageResize.resultBmpData, PixelSnapping.NEVER, true );
					thumbUI.addChild( bmp );
					thumbUI.width = imageResize.resultBmpData.width;
					thumbUI.height = imageResize.resultBmpData.height;
					bmp.x = -imageResize.resultBmpData.width / 2
					bmp.y = -imageResize.resultBmpData.height / 2
					updateStatus('');
					TweenLite.to(this.thumbUI,0.3,{alpha:1});
				}
				imageResize.dispose();
				dispatchEvent( new Event( FILE_UPDATED, true ));
			}
			
			public function updateThumb():void {
				var imageResize:ImageResize;
				if( fileVO.showThumb === true ) { 
					if(this.thumbUI.numChildren > 0) {
						this.thumbUI.removeChildAt( 0 );
						//rotateTo = 0;
						thumbUI.rotation = 0;
					}
					this.thumbUI.alpha = 0;
					imageResize = new ImageResize(thumbMaxWidth,thumbMaxHeight);
					imageResize.addEventListener( ImageResize.RESIZED, drawLater ,false,0,true);
					if(fileVO.encodedJPG) {
						imageResize.loadBytes( fileVO.encodedJPG );
					} else {
						if(fileVO.file.data) {
							imageResize.loadBytes( fileVO.file.data );
						} else {
							imageResize.loadReference( fileVO.file );
						}
					}
					this.thumbUI.addChild( imageResize );
				} else {
					if(!fileVO.widthOriginal) {
						imageResize = new ImageResize(thumbMaxWidth,thumbMaxHeight);
						imageResize.loadInfoFromReference( fileVO.file );
						imageResize.addEventListener( ImageResize.INFO, drawLater ,false,0,true);
						this.thumbUI.addChild( imageResize );
					}
				}
			}
			
			public function updateStatus(status:String=null,showInfo:Boolean=true,type:int=0):void {
				switch(type) {
					case 1: //error
						//---set red background
						statusBar.backgroundColor = 0xffaa66;
						statusBar.backgroundAlpha = 0.9;
						statusBar.invalidate();
						break;
					default:
						//---set default background
						statusBar.backgroundColor = 0xffffff;
						statusBar.backgroundAlpha = 0.7;
						statusBar.invalidate();
				}
				if(status!==null) {
					this.statusLbl.text = status;
				}
				if(showInfo)
				if(fileVO.encodedJPG) {
					this.statusLbl.text += ' '+fileVO.widthNew+'x'+fileVO.heightNew+' '+String(Math.round(fileVO.encodedJPG.length/1024))+'kB';
				} else {
					this.statusLbl.text += ' '+fileVO.widthOriginal+'x'+fileVO.heightOriginal+' '+String(Math.round(fileVO.file.size/1024))+'kB';	
				}
			}
			
			private var rotateTo:Number = 0;
			private function rotate(dgDiff:Number):void {
				rotateTo += dgDiff;
				
				TweenLite.to( _fileVO, 0.5, {rotation:rotateTo, ease:Quad.easeInOut, onUpdate:onRotateUpdateTween , onComplete:onRotateTween} );
				
				if(rotateTo >= 360) rotateTo -= 360;
				if(rotateTo < 0) rotateTo += 360;  
				
			}
			private function onRotateUpdateTween():void {
				thumbUI.rotation = _fileVO.rotation;
			}
			private function onRotateTween():void {
				thumbUI.rotation = _fileVO.rotation = Number(rotateTo);
			}
						
			private var previewWin:Window;
			
			private function showResized():void {
				if(previewWin) {
					if(previewWin.minimized==true) {
						previewWin.minimized = false;
					}
					return;
				}
				
				var title:String = fileVO.filename
				+ ' ' 
				+ String((fileVO.widthNew)?(fileVO.widthNew):(fileVO.widthOriginal))
				+ 'x'
				+ String((fileVO.heightNew)?(fileVO.heightNew):(fileVO.heightOriginal))
				+ ' '
				+ String( Math.round(((fileVO.encodedJPG)?(fileVO.encodedJPG.length):(fileVO.file.size))/1024) ) + 'kB';
					
				
				previewWin = new Window(Application.application, 0, 0, title);
				previewWin.addEventListener( Event.RESIZE, onWinReady ,false,0,true);
				previewWin.draggable = true;
				previewWin.hasMinimizeButton = true;
				previewWin.hasCloseButton = true;
				
				if(fileVO.widthOriginal > fileVO.widthMax) {
					var resize:Rectangle = ImageResize.scaleCalc(fileVO.widthOriginal, fileVO.heightOriginal, fileVO.widthMax, fileVO.heightMax);
					previewWin.width = resize.width;
					previewWin.height = resize.height + 20;
				} else {
					previewWin.width =  fileVO.widthOriginal ;
					previewWin.height = fileVO.heightOriginal + 20;	
				}
				
				previewWin.addEventListener( Window.CLOSE, onClosePreview ,false,0,true);
				
				previewWin.x = -previewWin.width;
				previewWin.y = -previewWin.height;
				previewWin.closeTween = {alpha:0,ease:Quad.easeInOut};
				previewWin.closeTweenDuration = 0.2;
				
				if(previewWin.height > Application.application.height) {
					Application.application.height = previewWin.height + 10;
				}
			}
			
			private function onWinReady(e:Event):void {
				var win:Window = e.target as Window;
				win.removeEventListener( Event.RESIZE, onWinReady );
				TweenLite.to( win, 0.5, {x:0,y:0,ease:Quad.easeInOut,onComplete:onTweenPreviewWin,onCompleteParams:[win]} );
			}
			
			private function onTweenPreviewWin(win:Window):void {
				if(fileVO.encodedJPG) {
					var image:Loader = new Loader();
					image.alpha = 0;
					image.loadBytes( fileVO.encodedJPG );
					image.contentLoaderInfo.addEventListener( Event.COMPLETE, onLoaderPreview ,false,0,true);
					win.content.addChild( image );
					image.width = win.width;
					image.height = win.height-20;
				} else if(fileVO.file.data) {
					fileVO.file.addEventListener(Event.COMPLETE, onFileLoaded );
					fileVO.file.load();
				}
			}
			private function onFileLoaded(e:Event):void {
				fileVO.file.removeEventListener(Event.COMPLETE, onFileLoaded );
				var image:Loader = new Loader();
				image.alpha = 0;
				image.loadBytes( fileVO.file.data );
				image.contentLoaderInfo.addEventListener( Event.COMPLETE, onLoaderPreview ,false,0,true);
				previewWin.content.addChild( image );
			}
			
			private function onLoaderPreview(e:Event):void {
				var loader:Loader = e.target.loader as Loader;
				loader.contentLoaderInfo.removeEventListener(Event.COMPLETE, onLoaderPreview );
				
				loader.content.width = previewWin.width;
				loader.content.height = previewWin.height-20;
				
				TweenLite.to( loader, 0.5, {alpha:1,ease:Quad.easeInOut} );
			}
						
			private function onClosePreview( e:Event ):void {
				previewWin = null;
				fileVO.file.data.clear();
				Application.application.restoreHeight();
			}
			
			public function removeView():void {
				TweenLite.to( this, 0.2, {alpha:0, onComplete:onTween, overwrite:0});
				trace("TWEENREMOVE"+fileVO.filename);
				if(previewWin) {
					previewWin.close();
				}
			}
			
			private function onTween():void {
				this.fileVO.destroy();
				this.fileVO = null;
				this.parent.removeChild( this );	
			}
			
			/**
			 * BUTTON HANDLERS
			 * */
			private function onButtClose(e:Event):void {
				dispatchEvent(new Event(FILE_REMOVE,true));
			}
			private function onButtCCW(e:Event):void {
				rotate(-90)
			}
			private function onButtCW(e:Event):void {
				rotate(90);
			}
			private function onButtShow(e:Event):void {
				showResized();
			}
			
			private var thumbUI:Container;
			private var statusBar:Container;
			private var statusLbl:Label;
			private var buttonBar:VBox;
			private var s1:Component;
			private var s2:Component;
			private var s3:Component;
			private var s4:Component;
			private function setup():void {
				this.graphics.beginFill(0xbbbbbb);
				this.graphics.drawRoundRect(0,0,this.width,this.height,5,5);
				this.graphics.endFill();
				
				this.filters=[new DropShadowFilter(3,45,0,0.5,3,3)];
				
				//thumb holder
				var box:Container = new Container(this,5,5);
				box.width = thumbMaxWidth;
				box.height = thumbMaxHeight
				box.border = 0;
				box.backgroundColor = 0x000000;
				box.masked = true;
				thumbUI = new Container(box,thumbMaxWidth/2,thumbMaxHeight/2);
				//status bar
				statusBar = new Container(this,5,thumbMaxHeight-15);
				statusBar.width = thumbMaxWidth;
				statusBar.height = 15;
				statusLbl = new Label(statusBar,0,-2);
				//button bar
				buttonBar = new VBox(this,150,5);
				buttonBar.spacing = 6;
								
				var s1:Component = new Component(buttonBar);
				s1.useHandCursor = true;
				s1.buttonMode = true;
				s1.width = 20;
				s1.height = 20;
				s1.addChild( new ClosePNG() );
				s1.filters=[new DropShadowFilter(2,45,0,0.5,2,2)];
				
				
				var s2:Component = new Component(buttonBar);
				s2.useHandCursor = true;
				s2.buttonMode = true;
				s2.width = 20;
				s2.height = 20;
				s2.addChild( new CCWPNG() );
				s2.filters=[new DropShadowFilter(2,45,0,0.5,2,2)];
				
				var s3:Component = new Component(buttonBar);
				s3.useHandCursor = true;
				s3.buttonMode = true;
				s3.width = 20;
				s3.height = 20;
				s3.addChild( new CWPNG() );
				s3.filters=[new DropShadowFilter(2,45,0,0.5,2,2)];
				
				var s4:Component = new Component(buttonBar);
				s4.useHandCursor = true;
				s4.buttonMode = true;
				s4.width = 20;
				s4.height = 20;
				s4.addChild( new ShowPNG() );
				s4.filters=[new DropShadowFilter(2,45,0,0.5,2,2)];
				
				s1.addEventListener(MouseEvent.CLICK, onButtClose,false,0,true);
				s2.addEventListener(MouseEvent.CLICK, onButtCCW,false,0,true);
				s3.addEventListener(MouseEvent.CLICK, onButtCW,false,0,true);
				s4.addEventListener(MouseEvent.CLICK, onButtShow,false,0,true);
			}
	}
}