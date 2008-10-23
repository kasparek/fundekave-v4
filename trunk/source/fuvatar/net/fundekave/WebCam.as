package net.fundekave {
	import flash.display.BitmapData;
	import flash.display.Sprite;
	import flash.events.ActivityEvent;
	import flash.events.Event;
	import flash.events.StatusEvent;
	import flash.media.Camera;
	import flash.media.Video;
	import flash.system.Security;
	import flash.system.SecurityPanel;

  public class WebCam extends Sprite {
    public static const DEFAULT_CAMERA_FPS : Number = 15;
    public static const VIDEO_ATTACHED : String = 'videoAttached';
    private var _video:Video;
    private var _camera:Camera;
    private var _camWidth:Number;
    private var _camHeight:Number;
    
    public function get activityLevel():int { return _camera.activityLevel; } 
   
    public function WebCam(paramWidth:Number, paramHeight:Number) {
    	_camWidth = paramWidth
    	_camHeight = paramHeight;
    	
    	for ( var name:String in Camera.names) _camera = Camera.getCamera( name );
		
      if (_camera != null) {
        _camera.setMode(_camWidth,_camHeight, DEFAULT_CAMERA_FPS)
        //---use best quality, dont care about bandwidth - as we are no transmitting video, just taking snapshots
        _camera.setQuality(0,100);
        _camera.setLoopback(false);
        //---add dummy listener to get activityLevel monitored
        _camera.addEventListener(ActivityEvent.ACTIVITY,onActivity);
        
        if(_video) removeChild(_video);
    	_video = new Video(_camera.width, _camera.height);
        _video.attachCamera(_camera);
        addChild(_video);

        _camera.addEventListener(StatusEvent.STATUS,cameraStatusHandler);
        
      } else {
        Security.showSettings(SecurityPanel.CAMERA)
      }
    }
    private function onActivity(e:Event):void {
    	
    }
    private function cameraStatusHandler(e:StatusEvent):void {
    	_camera.removeEventListener(StatusEvent.STATUS,cameraStatusHandler);
    	dispatchEvent(new Event(VIDEO_ATTACHED));
    }
    public function getSnapshotBitmapData(bmpdata:BitmapData):void {
      bmpdata.draw(_video);
    }
  }
}
