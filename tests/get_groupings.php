<?php

include(dirname(__FILE__) . '/config.php');

$courseid = 322;

$groupings = call_ws('core_group_get_course_groupings', array('courseid'=>$courseid));
foreach($groupings AS $gr) {
    echo "Grouping: $gr->id - $gr->name\n";
    $groupingid = $gr->id;
}
