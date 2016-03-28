<?php
/**
 * Created by PhpStorm.
 * User: khaled
 * Date: 3/28/16
 * Time: 12:46 PM
 */

include "states.php";
require "vendor/autoload.php";
error_reporting(E_ERROR);

$db = new MysqliDb('localhost', "a", "a", 'countthebern');

$fhandle = fopen('countthebernstates-3-28-16.csv', 'r');
$counter = 0;
while($line = fgetcsv($fhandle)) {
    if($counter > 0) {
        $db->where('state_abbr', $line[0]);
        $db->update('states', array('Region' => $line[1]));
        echo "Updated: ".$line[0]." with Region: ".$line[1]."\n";
    }
    $counter++;
}
exit();