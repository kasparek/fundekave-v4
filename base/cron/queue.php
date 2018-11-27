<?php
$q = FQueue::getInstance();
$q->process();
echo $q->to_log;