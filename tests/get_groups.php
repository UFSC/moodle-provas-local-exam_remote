<?php

include(dirname(__FILE__) . '/config.php');

$courseid = 322;

echo "\nGROUPINGS:\n";
$groupingids = array();
$groupings = call_ws('core_group_get_course_groupings', array('courseid'=>$courseid));
foreach($groupings AS $gr) {
    echo "- Grouping: $gr->id - $gr->name\n";
    $groupingids[] = $gr->id;
}

echo "\nGROUPS:\n";
$groups = call_ws('core_group_get_course_groups', array('courseid'=>$courseid));
foreach($groups AS $g) {
    echo "- Group: $g->id - $g->name\n";
    $gms = call_ws('core_group_get_group_members', array('groupids[0]'=>$g->id));
    foreach($gms AS $gm) {
        foreach($gm->userids AS $uid) {
            echo "     userid: $uid\n";
        }
    }
}

$params = array('returngroups'=>1);
foreach($groupingids AS $i=>$grid) {
    $params["groupingids[{$i}]"] = $grid;
}
$grs = call_ws('core_group_get_groupings', $params);
echo "\nGROUPINGS:\n";
foreach($grs AS $gr) {
    echo "- Grouping: $gr->id - $gr->name\n";
    foreach($gr->groups AS $g) {
        echo "     group: $g->id - $g->name\n";
    }
}
