<?php
if(!isset($_GET['g'])) exit;

session_write_close();

FError::write_log("cron::invalidate - begin - ".$_GET['g']);
FSystem::superInvalidateHandle(explode(";",$_GET['g']));
FError::write_log("cron::invalidate - COMPLETE - ".$_GET['g']);

