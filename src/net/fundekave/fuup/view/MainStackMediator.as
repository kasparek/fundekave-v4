package net.fundekave.fuup.view
{
	import assets.shell.SoundBank;
	
	import caurina.transitions.Tweener;
	
	import co.uk._4t2.disneyhannahoke.SoundManager;
	import co.uk._4t2.disneyhannahoke.shell.ApplicationFacade;
	import co.uk._4t2.disneyhannahoke.shell.model.AssetsDataProxy;
	import co.uk._4t2.disneyhannahoke.shell.model.BoardProxy;
	import co.uk._4t2.disneyhannahoke.shell.model.GamesDataProxy;
	import co.uk._4t2.disneyhannahoke.shell.model.HighscoreProxy;
	import co.uk._4t2.disneyhannahoke.shell.model.LangDataProxy;
	import co.uk._4t2.disneyhannahoke.shell.model.SettingsProxy;
	import co.uk._4t2.disneyhannahoke.shell.model.TeamProxy;
	import co.uk._4t2.disneyhannahoke.shell.model.vo.BoardEffectVO;
	import co.uk._4t2.disneyhannahoke.shell.model.vo.GameResultsVO;
	import co.uk._4t2.disneyhannahoke.shell.model.vo.GameVO;
	import co.uk._4t2.disneyhannahoke.shell.model.vo.SettingsVO;
	import co.uk._4t2.disneyhannahoke.shell.model.vo.TeamMemberVO;
	import co.uk._4t2.disneyhannahoke.shell.model.vo.TeamVO;
	import co.uk._4t2.disneyhannahoke.shell.view.components.MainStack;
	import co.uk._4t2.disneyhannahoke.shell.view.components.TeamMemberSetup;
	
	import flash.events.Event;
	import flash.utils.setTimeout;
	
	import org.puremvc.as3.multicore.interfaces.INotification;
	import org.puremvc.as3.multicore.patterns.mediator.Mediator;
	import org.puremvc.as3.multicore.utilities.statemachine.State;
	import org.puremvc.as3.multicore.utilities.statemachine.StateMachine;

	public class MainStackMediator extends Mediator
	{
		
		public static const NAME:String = 'MainStackViewMediator';
		
		private var finalChallange:Boolean = false;
		private var gameOver:Boolean = false;
		private var practiceModeSkipIntructions:Boolean = true;
		private var skipIntro:Boolean = false;
		
		private var boardProxy:BoardProxy;
		
		public function MainStackMediator( viewComponent:Object=null )
		{
			 super( NAME, viewComponent );
		}
		//---rather stupid variable to fix the bug
		private var congratStr:String;
		override public function onRegister():void
		{
			
			var langProxy:LangDataProxy = facade.retrieveProxy( LangDataProxy.NAME ) as LangDataProxy;
			mainStackView.lang = langProxy.lang;
			congratStr = langProxy.lang.Texts.Item.(@name=="finalCongrats");
			
			mainStackView.addEventListener(MainStack.USER_ACTION, onUserAction );
			
			facade.registerMediator( new GameShellMediator( mainStackView.gameShellCan ) );
			
			//---enable highscore butt
			var highscoreProxy:HighscoreProxy = facade.retrieveProxy( HighscoreProxy.NAME ) as HighscoreProxy;
			if(highscoreProxy.teamBest) {
				mainStackView.titleCan.highscoreButt.visible = true;
			}
			
			boardProxy = facade.retrieveProxy( BoardProxy.NAME ) as BoardProxy;
		}
		
		private function loadGame():void {
			//---show loading label rolling
			sendNotification( ApplicationFacade.ROLL_LOADING );
			//---load game
			mainStackView.gameShellCan.loadGame( boardProxy.gameActive );
		}
		
		private function onPlayButtAlphaTween():void {
			mainStackView.playButt.visible = false;
		}
		
		protected function onUserAction(e:Event):void {
			var teamVO:TeamVO;
			var settingsProxy:SettingsProxy;
			
			switch(mainStackView.userAction) {
				case MainStack.MAIN_MENU:
					sendNotification ( StateMachine.ACTION, null, ApplicationFacade.ACTION_TITLE );
				break;
				case MainStack.TITLE_PLAY:
					sendNotification ( StateMachine.ACTION, null, ApplicationFacade.ACTION_WELCOME );
				break;
				case MainStack.GAME_TEAM_SCORE:
					//---show scores from current game
					sendNotification( ApplicationFacade.BOARD_CLEAN );
				case MainStack.TITLE_HIGHSCORES:
					sendNotification ( StateMachine.ACTION, null, ApplicationFacade.ACTION_HIGHSCORE );
				break;
				case MainStack.WELCOME_SKIP:
					sendNotification ( StateMachine.ACTION, null, ApplicationFacade.ACTION_INPUT_SELECT );
				break;
				case MainStack.INPUT_SELECT_NEXT:
					var inputsSelected:String = String( (mainStackView.inputSetupCan.inputSelectCombo.selectedItem as XML).@name );
					settingsProxy = facade.retrieveProxy( SettingsProxy.NAME ) as SettingsProxy;
					settingsProxy.settingsVO.inputsSelected = inputsSelected;
					if(mainStackView.inputSetupCan.inputSelectCombo.selectedIndex==0 || settingsProxy.settingsVO.inputsAccessAllowed==true) {
						sendNotification ( StateMachine.ACTION, null, ApplicationFacade.ACTION_TEAM );
					} else {
						settingsProxy.settingsVO.inputsPermissionsChecked = true;
						sendNotification ( StateMachine.ACTION, null, ApplicationFacade.ACTION_INPUT_PERMISSIONS );
					}
				break;
				case MainStack.INPUTS_PERMISSION_CHECK_SHOWN:
					//---launch cam mic check
					sendNotification( ApplicationFacade.INPUTS_PERMISSIONS );
				break;
				case MainStack.INPUT_PERMISSIONS_NEXT:
					settingsProxy = facade.retrieveProxy( SettingsProxy.NAME ) as SettingsProxy;
					if(settingsProxy.settingsVO.inputsAccessAllowed==true) {
						sendNotification ( StateMachine.ACTION, null, ApplicationFacade.ACTION_TEAM );	
					} else {
						sendNotification ( StateMachine.ACTION, null, ApplicationFacade.ACTION_INPUT_SELECT );
					}
				break;
				case MainStack.TEAM_CREATE_BACK:
					sendNotification ( StateMachine.ACTION, null, ApplicationFacade.ACTION_TEAM );
				break;
				case MainStack.TEAM_CREATE:
					//---create team
					teamVO = new TeamVO(mainStackView.teamNameLbl.text, Number(XML(mainStackView.teamSizeCombo.selectedItem).@name));
					sendNotification( ApplicationFacade.TEAM_CREATE, teamVO);
					mainStackView.teamMemberCan.setCurrentState( TeamMemberSetup.STATE_CAPTAIN );
					sendNotification( StateMachine.ACTION, null, ApplicationFacade.ACTION_TEAM_MEMBER );
				break;
				case MainStack.TEAM_CHANGE:
					settingsProxy = facade.retrieveProxy( SettingsProxy.NAME ) as SettingsProxy;
					var oldValue:Boolean = settingsProxy.settingsVO.cheatPractice;
					var teamName:String = mainStackView.teamNameLbl.text;
					teamName = teamName.toLowerCase();
					teamName = teamName.replace(' ','');			
					if(settingsProxy.settingsVO.cheatPraticeCode == teamName) {
						settingsProxy.settingsVO.cheatPractice = true;
					} else {
						settingsProxy.settingsVO.cheatPractice = false;
					}
					if(oldValue != settingsProxy.settingsVO.cheatPractice) {
						sendNotification( ApplicationFacade.CHEAT_PRACTICE );
					}
				break;
				case MainStack.TEST_MODE:
					sendNotification( StateMachine.ACTION, null, ApplicationFacade.ACTION_TESTMODE );
				break;
				case MainStack.GAME_PRACTICE:
					//---set play button disabled
					mainStackView.playButtonEnabled = false;
					//---set practice mode				
					boardProxy.practiceMode = true;
					//---load game and play
					var gameVO:GameVO = mainStackView.testgameCombo.selectedItem as GameVO;
					mainStackView.gameVOActive = gameVO;
					boardProxy.gameActive = gameVO;
					
					mainStackView.practiceButt.enabled = false;
					
					loadGame();
					
					if(practiceModeSkipIntructions==false) {
						sendNotification ( StateMachine.ACTION, null, ApplicationFacade.ACTION_GAME_INSTRUCTIONS );
					}
				break;
				case MainStack.TEAM_SELECT:
					//---set active team
					var proxy:TeamProxy = facade.retrieveProxy( TeamProxy.NAME ) as TeamProxy;
					teamVO = mainStackView.teamPreviousCombo.selectedItem as TeamVO;
					if( teamVO ) {
						proxy.teamVO = teamVO; 
						proxy.teamVO.scoreReset();
						sendNotification ( StateMachine.ACTION, null, ApplicationFacade.ACTION_TEAM_CAPTAIN );
					}
				break;
				case MainStack.TEAM_MEMBER_SAVE:
					var teamMemberVO:TeamMemberVO = new TeamMemberVO();
					teamMemberVO.name = mainStackView.teamMemberCan.teamMemberNameLbl.text;
					teamMemberVO.age = XML(mainStackView.teamMemberCan.teamMemberAgeCombo.selectedItem).@name;
					teamMemberVO.role = XML(mainStackView.teamMemberCan.teamMemberRoleCombo.selectedItem).@name;
					sendNotification(ApplicationFacade.TEAM_MEMBER_CREATE, teamMemberVO);
				break;
				case MainStack.CAPTAIN_SELECT:
					//---set the captain
					sendNotification( ApplicationFacade.TEAM_SET_CAPTAIN, mainStackView.captainCombo.selectedItem);
					sendNotification ( StateMachine.ACTION, null, ApplicationFacade.ACTION_GAME_WELCOME);
				break;
				case MainStack.CAPTAIN_CANCEL:
					sendNotification ( StateMachine.ACTION, null, ApplicationFacade.ACTION_TEAM );
				break;
				case MainStack.GAME_WELCOME_SKIP:
					boardProxy.practiceMode = false; 
					sendNotification ( StateMachine.ACTION, null, ApplicationFacade.ACTION_GAME_SELECT );
				break;
				case MainStack.GAME_SELECT:
					//---draw a game and show user turn or captain select
					boardProxy.boardTileActive = boardProxy.boardTilesList.getAt( boardProxy.highlightedIndex );
					mainStackView.playButt.enabled = false;
					Tweener.addTween( mainStackView.playButt, {alpha:0, time:0.3, transition:'linear', onComplete:onPlayButtAlphaTween}); 
					
					settingsProxy = facade.retrieveProxy( SettingsProxy.NAME ) as SettingsProxy;
					sendNotification( ApplicationFacade.BOARD_EFFECT, BoardEffectVO.rouletteSelect(settingsProxy.settingsVO.boardSelectTime, settingsProxy.settingsVO.boardSelectTransition) );
				break;
				case MainStack.GAME_INSTRUCTIONS:
					sendNotification ( StateMachine.ACTION, null, ApplicationFacade.ACTION_GAME_INSTRUCTIONS );
				break;
				case MainStack.GAME_PLAY:
					sendNotification ( StateMachine.ACTION, null, ApplicationFacade.ACTION_GAME_PLAY );
					mainStackView.judgeCan.setCurrentState('');
				break;
				case MainStack.GAME_NEXT:
					sendNotification ( StateMachine.ACTION, null, ApplicationFacade.ACTION_GAME_SELECT );
				break;
				case MainStack.GAME_INSTRUCTIONS_BACK:
					if(boardProxy.practiceMode==true) {
						sendNotification ( StateMachine.ACTION, null, ApplicationFacade.ACTION_TESTMODE);
						break;
					}
				case MainStack.GAME_FINAL_CHALLANGE:
					loadGame();
					if(boardProxy.boardTileActive.type=='gameSecret') {
						//---captain select
						sendNotification ( StateMachine.ACTION, null, ApplicationFacade.ACTION_GAME_PLAYER_CAPTAIN);
					} else {
						//---next player
						sendNotification ( StateMachine.ACTION, null, ApplicationFacade.ACTION_GAME_PLAYER_TURN);
					}
				break;
				case MainStack.GAME_JUDGEME:
					sendNotification( ApplicationFacade.BOARD_SHOW_TIMEBONUS );
				break;
			}
		}
		
		override public function listNotificationInterests():Array
		{
			return [ 
					StateMachine.CHANGED,
					ApplicationFacade.INPUTS_AVAILABLE,
					ApplicationFacade.INPUTS_PERMISSIONS_CHECKED_FIRST,
					ApplicationFacade.INPUTS_PERMISSIONS_CHECKED,
					ApplicationFacade.TEAM_MEMBERS_CREATE_FINISHED,
					ApplicationFacade.TEAM_MEMBERS_CREATE_NEXT,
					ApplicationFacade.BOARD_EFFECT_FINISHED,
					ApplicationFacade.GAME_SELECTED,
					ApplicationFacade.TEAM_MEMBER_TURN_FINISHED,
					ApplicationFacade.GAMESHELL_LOADED,
					ApplicationFacade.GAMESHELL_SCORE,
					ApplicationFacade.GAME_FINAL,
					ApplicationFacade.GAME_OVER,
					ApplicationFacade.GAME_ALL_SCORE,
					ApplicationFacade.TEAM_SCORE,
					ApplicationFacade.BOARD_FOR_GAME_FINISHED,
					];
		}
		
		override public function handleNotification(note:INotification):void
		{
			var settingsVO:SettingsVO;
			var teamProxy:TeamProxy;
			var boardProxy:BoardProxy = facade.retrieveProxy( BoardProxy.NAME ) as BoardProxy;
			var assetsProxy:AssetsDataProxy;
			var gamesProxy:GamesDataProxy;
			var settingsProxy:SettingsProxy;
			var langProxy:LangDataProxy;
			switch ( note.getName() )
			{
				case ApplicationFacade.GAME_ALL_SCORE:
					mainStackView.highscoreCan.gamesScore = note.getBody() as Array;
				break;
				case ApplicationFacade.TEAM_SCORE:
					mainStackView.highscoreCan.teamScoreVO = note.getBody() as TeamVO;
				break;
				case ApplicationFacade.GAME_FINAL:
					//---next game is last
					finalChallange = true;
				break;
				case ApplicationFacade.GAME_OVER:
					//---after judge screen show score
					finalChallange = false;
					gameOver = true;
				break;
				case ApplicationFacade.GAMESHELL_SCORE:
					var score:Number = note.getBody() as Number;
					var gameResults:GameResultsVO = new GameResultsVO(boardProxy.gameActive.type ,score );
					gameResults.gameName = boardProxy.gameActive.name;
					sendNotification( ApplicationFacade.GAME_RESULTS, gameResults );
					if(gameOver == true) {
						gameOver = false;
						sendNotification ( StateMachine.ACTION, null, ApplicationFacade.ACTION_GAME_POSTFINAL );	
					} else {
						sendNotification ( StateMachine.ACTION, null, ApplicationFacade.ACTION_GAME_JUDGE );
					}
				break;
				case ApplicationFacade.GAMESHELL_LOADED:
					if(boardProxy.practiceMode==true) {
						mainStackView.practiceButt.enabled = true;
						if(practiceModeSkipIntructions==true) {
							sendNotification ( StateMachine.ACTION, null, ApplicationFacade.ACTION_GAME_PLAY );
						}
					} else {
						if(skipIntro == true) {
							skipIntro = false;
							sendNotification ( StateMachine.ACTION, null, ApplicationFacade.ACTION_GAME_PLAY );
						}
					}
					//---enable play button
					mainStackView.playButtonEnabled = true;
				break;
				case ApplicationFacade.TEAM_MEMBER_TURN_FINISHED:
					var teamMemberVO:TeamMemberVO = note.getBody() as TeamMemberVO;
					mainStackView.teamMemberActive = teamMemberVO;
				break;
				case ApplicationFacade.GAME_SELECTED:
					//---set play button disabled
					mainStackView.playButtonEnabled = false;
				
					var gameVO:GameVO = note.getBody() as GameVO;
					//---set active game
					mainStackView.gameVOActive = gameVO;
					
					//---set next player on turn
					sendNotification( ApplicationFacade.TEAM_MEMBER_TURN );
					sendNotification( ApplicationFacade.BOARD_FOR_IN_GAME );
					
					if(gameVO.mandatory==true) {
						//---next state
						if(finalChallange === true) {
							//---prefinal video
							sendNotification ( StateMachine.ACTION, null, ApplicationFacade.ACTION_GAME_PREFINAL);
						} else {
							if(boardProxy.boardTileActive.type=='gameSecret') {
								//---captain select
								sendNotification ( StateMachine.ACTION, null, ApplicationFacade.ACTION_GAME_PLAYER_CAPTAIN);
							} else {
								//---next player
								sendNotification ( StateMachine.ACTION, null, ApplicationFacade.ACTION_GAME_PLAYER_TURN);
							}
							//---load game
							loadGame();
						}
					} else {
						if(gameVO.skipIntro==true) {
							//---next step directly into the game
							skipIntro = true;
						} else {
							//---next player on turn
							sendNotification ( StateMachine.ACTION, null, ApplicationFacade.ACTION_GAME_PLAYER_TURN);
						}
						//---load game
						loadGame();
					}
					
				break;
				case ApplicationFacade.BOARD_EFFECT_FINISHED:
					var eff:BoardEffectVO = note.getBody() as BoardEffectVO;
					switch ( eff.type ) {
						case BoardEffectVO.ROULETTE_SELECT:
							sendNotification( ApplicationFacade.BOARD_GAME_SELECTED );
						break;
					}
				break;
				case ApplicationFacade.TEAM_MEMBERS_CREATE_NEXT:
					//---reset and get ready for next one or go for next step if it was last member
					mainStackView.teamMemberCan.reset();
					break;
				case ApplicationFacade.TEAM_MEMBERS_CREATE_FINISHED:
					//---team created next step
					sendNotification ( StateMachine.ACTION, null, ApplicationFacade.ACTION_GAME_WELCOME);
					break;
				case ApplicationFacade.INPUTS_PERMISSIONS_CHECKED_FIRST:
					settingsVO = note.getBody() as SettingsVO;
					if(settingsVO.inputsAccessAllowed==true) {
						sendNotification ( StateMachine.ACTION, null, ApplicationFacade.ACTION_TEAM );
					} else {
						sendNotification ( StateMachine.ACTION, null, ApplicationFacade.ACTION_INPUT_SELECT );
					}
				break;
				case ApplicationFacade.INPUTS_AVAILABLE:
					settingsVO = note.getBody() as SettingsVO;
					mainStackView.inputSetupCan.micAvailable = settingsVO.micAvailable; 
					mainStackView.inputSetupCan.camAvailable = settingsVO.camAvailable;
				break;
				case ApplicationFacade.BOARD_FOR_GAME_FINISHED:
					mainStackView.playButt.enabled = true;
					//---go flash around and wait for play
					settingsProxy = facade.retrieveProxy( SettingsProxy.NAME ) as SettingsProxy;
					boardProxy.highlightedIndex = 0;
					if(settingsProxy.settingsVO.cheatSlowDown==true) {
						sendNotification( ApplicationFacade.BOARD_EFFECT, BoardEffectVO.rouletteDraw(settingsProxy.settingsVO.cheatSlowDownTime, settingsProxy.settingsVO.boardDrawTransition) );
					} else {
						sendNotification( ApplicationFacade.BOARD_EFFECT, BoardEffectVO.rouletteDraw(settingsProxy.settingsVO.boardDrawTime, settingsProxy.settingsVO.boardDrawTransition) );
					}
				break;
				case StateMachine.CHANGED:
					var state:State = note.getBody() as State;
					switch(state.name) {
						case ApplicationFacade.STATE_TITLE:
							//---highscore
							var highscoreProxy:HighscoreProxy = facade.retrieveProxy( HighscoreProxy.NAME ) as HighscoreProxy;
							highscoreProxy.getHighScore();
							mainStackView.showView(1);
						break;
						case ApplicationFacade.STATE_WELCOME:
							assetsProxy = facade.retrieveProxy( AssetsDataProxy.NAME ) as AssetsDataProxy;
							mainStackView.welcomeVideoCan.videoSourceUri = null;
							mainStackView.welcomeVideoCan.videoSourceUri = XML( assetsProxy.assetsXML..Item.(@name=='videoWelcome') );
							mainStackView.showView(2);
						break;
						case ApplicationFacade.STATE_INPUT_SELECT:
							sendNotification( ApplicationFacade.INPUTS_CHECK );
							mainStackView.showView(3);
						break;
						case ApplicationFacade.STATE_INPUT_PERMISSIONS:
							mainStackView.showView(4);
						break;
						case ApplicationFacade.STATE_TEAM:
							//---set screen
							mainStackView.showView(5);
							//---team list
							teamProxy = facade.retrieveProxy( TeamProxy.NAME ) as TeamProxy;
							mainStackView.teamsList = teamProxy.teamsList;
							///---games list
							settingsProxy = facade.retrieveProxy( SettingsProxy.NAME ) as SettingsProxy;
							gamesProxy = facade.retrieveProxy( GamesDataProxy.NAME ) as GamesDataProxy;
							mainStackView.availableGamesList = gamesProxy.getGamesUsable( settingsProxy.isMicUsable(), settingsProxy.isCamUsable(), false, true );
						break;
						case ApplicationFacade.STATE_TESTMODE:
							mainStackView.showView(8);
						break;
						case ApplicationFacade.STATE_TEAM_MEMBER:
							mainStackView.showView(6);
						break;
						case ApplicationFacade.STATE_TEAM_CAPTAIN:
							teamProxy = facade.retrieveProxy( TeamProxy.NAME ) as TeamProxy;
							mainStackView.teamActive = teamProxy.teamVO;
							mainStackView.showView(7);
						break;
						case ApplicationFacade.STATE_TESTMODE:
							mainStackView.showView(8);
						break;
						case ApplicationFacade.STATE_HIGHSCORE:
							mainStackView.highscoreCan.setDefault();
							mainStackView.showView(9);
						break;
						case ApplicationFacade.STATE_GAME_WELCOME:
							teamProxy = facade.retrieveProxy( TeamProxy.NAME ) as TeamProxy;
							mainStackView.teamActive = teamProxy.teamVO;
							//---check for chat name
							sendNotification( ApplicationFacade.CHEAT_BOARD_SLOW, teamProxy.teamVO);
							assetsProxy = facade.retrieveProxy( AssetsDataProxy.NAME ) as AssetsDataProxy;
							mainStackView.welcomeGameVideoCan.videoSourceUri = null;
							mainStackView.welcomeGameVideoCan.videoSourceUri = XML( assetsProxy.assetsXML..Item.(@name=='videoGameStart') );
							mainStackView.showView(10);
							//---set game intputs available
							settingsProxy = facade.retrieveProxy( SettingsProxy.NAME ) as SettingsProxy;
							mainStackView.gameShellCan.micUseAllowed = settingsProxy.isMicUsable();
							mainStackView.gameShellCan.camUseAllowed = settingsProxy.isCamUsable();
							//---set time settings
							gamesProxy = facade.retrieveProxy( GamesDataProxy.NAME ) as GamesDataProxy;
							mainStackView.gameShellCan.timeBonusToSeconds = gamesProxy.timeBonusToSeconds;
						break
						case ApplicationFacade.STATE_GAME_SELECT:
							//---reset play button
							mainStackView.playButt.visible = true;
							if(mainStackView.playButt.alpha!=1) {
								Tweener.addTween( mainStackView.playButt, {alpha:1, time:0.5, transition:'linear'});
							}
							mainStackView.showView(11);
							//---track team/game info to Tracker
							sendNotification( ApplicationFacade.TRACKER_CALL );
							mainStackView.hannahokeBigLogo.show();
							
							//---set board tiles for game
							setTimeout( sendNotification, 1000, ApplicationFacade.BOARD_FOR_GAME );
						break;
						case ApplicationFacade.STATE_GAME_PLAYER_TURN:
							mainStackView.showView(12);
						break;
						case ApplicationFacade.STATE_GAME_PLAYER_CAPTAIN:
							mainStackView.showView(13);
						break;
						case ApplicationFacade.STATE_GAME_INSTRUCTIONS:
							//---set progress text
							var text:String;
							if(mainStackView.gameVOActive.mandatory==true) {
								teamProxy = facade.retrieveProxy( TeamProxy.NAME ) as TeamProxy;
								gamesProxy = facade.retrieveProxy( GamesDataProxy.NAME ) as GamesDataProxy;
								langProxy = facade.retrieveProxy( LangDataProxy.NAME ) as LangDataProxy;
								if(finalChallange == true) {
									text = langProxy.lang.Texts.Item.(@name=='instructionsProgress2')
									text = text.replace('{SECONDSWON}',teamProxy.teamVO.timeBonus * gamesProxy.timeBonusToSeconds);
								} else {
									text = langProxy.lang.Texts.Item.(@name=='instructionsProgress1')
									text = text.replace('{GAMESLEFT}', teamProxy.teamVO.gamesTotal-teamProxy.teamVO.gamesDone);
								}
								mainStackView.instructionsProgressText.text = text;
								mainStackView.instructionsProgressText.visible = true;
							} else {
								mainStackView.instructionsProgressText.visible = false;
							}
							mainStackView.showView(14);
						break;
						case ApplicationFacade.STATE_GAME_PLAYING:
							//---track played game
							sendNotification( ApplicationFacade.TRACKER_GAME );
							mainStackView.showView(15);
							//---disable play button
							mainStackView.playButtonEnabled = false;
						break;
						case ApplicationFacade.STATE_GAME_JUDGE:
							boardProxy.timerReset();
							if(mainStackView.gameShellCan.gameVO.skipOutro == true) {
								sendNotification ( StateMachine.ACTION, null, ApplicationFacade.ACTION_GAME_SELECT );
								mainStackView.judgeCan.setCurrentState('');
							} else {
								gamesProxy = facade.retrieveProxy( GamesDataProxy.NAME ) as GamesDataProxy;
								assetsProxy = facade.retrieveProxy( AssetsDataProxy.NAME ) as AssetsDataProxy;
								teamProxy = facade.retrieveProxy( TeamProxy.NAME ) as TeamProxy;
								//---get username if he sets new highscore, score
								if( teamProxy.teamVO.lastGameResults.newBest === true ) {
									mainStackView.judgeCan.setHighscore( teamProxy.teamVO.getTeamMemberCurrent().name, teamProxy.teamVO.lastGameResults.score, teamProxy.teamVO.lastGameResults.gameName );
								} else {
									mainStackView.judgeCan.setScore( teamProxy.teamVO.getTeamMemberCurrent().name, teamProxy.teamVO.lastGameResults.score, teamProxy.teamVO.lastGameResults.gameName );
								}
								//---get timebonus
								var newTimeBonus:Number = teamProxy.teamVO.lastGameResults.timeBonus;
								if(!newTimeBonus) newTimeBonus = 0;
								var timeSecondsBonus:Number = newTimeBonus * gamesProxy.timeBonusToSeconds;
								mainStackView.judgeCan.setSeconds(timeSecondsBonus);
								//---based on timebonus show video
								var video:XML;
								switch(newTimeBonus) {
									case 0:
									case 1:
										video = XML( assetsProxy.assetsXML..Item.(@name=='videoJudgePoor') );
									break;
									case 2:
										video = XML( assetsProxy.assetsXML..Item.(@name=='videoJudgeGood') );
									break;
									default:
										video = XML( assetsProxy.assetsXML..Item.(@name=='videoJudgeGreat') );
									break;	
								}
								mainStackView.judgeCan.judgeBG.playIntro();
								mainStackView.judgeCan.judgeVideoUri = null;
								mainStackView.judgeCan.judgeVideoUri = video;
								mainStackView.showView(16);
							}
						break;
						case ApplicationFacade.STATE_GAME_PREFINAL:
							assetsProxy = facade.retrieveProxy( AssetsDataProxy.NAME ) as AssetsDataProxy;
							mainStackView.prefinalVideoCan.videoSourceUri = null;
							mainStackView.prefinalVideoCan.videoSourceUri = XML( assetsProxy.assetsXML..Item.(@name=='videoPreFinal') );
							sendNotification( ApplicationFacade.BOARD_SHOW_TIMEBONUS, true );
							mainStackView.showView(17);
						break;
						case ApplicationFacade.STATE_GAME_POSTFINAL:
							teamProxy = facade.retrieveProxy( TeamProxy.NAME ) as TeamProxy;
							gamesProxy = facade.retrieveProxy( GamesDataProxy.NAME ) as GamesDataProxy;
							assetsProxy = facade.retrieveProxy( AssetsDataProxy.NAME ) as AssetsDataProxy;
							
							mainStackView.postfinalVideoCan.visible = true;
							mainStackView.postfinalVideoCan.videoSourceUri = null;
							
							mainStackView.postfinalVideoCan.videoSourceUri = XML( assetsProxy.assetsXML..Item.(@name=='videoCongratulations') );
							
							SoundManager.getInstance().playSound( SoundBank.Audience_05, 0.7 );
							
							//---set text
							var congratText:String =  String(congratStr);
							if(teamProxy.teamVO) {
								congratText = congratText.replace('{FINALGAMETIME}', teamProxy.teamVO.timeBonus * gamesProxy.timeBonusToSeconds);
								congratText = congratText.replace('{FINALGAMESCORE}', teamProxy.teamVO.lastGameResults.score);
								//---set highscore and reset active team
								mainStackView.highscoreCan.teamScoreVO = teamProxy.teamVO;
								mainStackView.highscoreCan.gamesScore = teamProxy.teamVO.gamesResults;
							}
							mainStackView.finalCongratText.htmlText = congratText;
							
							//---reset board .. set all enabled
							sendNotification( ApplicationFacade.BOARD_RESET );
							sendNotification( ApplicationFacade.BOARD_FOR_POSTFINAL );
							
							mainStackView.showView(18);
						break;
					}
				break;
			}
		}
		
		protected function get mainStackView():MainStack
        {
            return viewComponent as MainStack;
        }
		
	}
}