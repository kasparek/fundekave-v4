<?php
$q = array(
"update sys_pages_items set enclosure=concat('page/event/',enclosure) where typeId='event' and enclosure is not null"
);