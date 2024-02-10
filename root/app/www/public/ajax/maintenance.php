<?php

/*
----------------------------------
 ------  Created: 020724   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'shared.php';

//-- INITIALIZE THE MAINTENANCE CLASS
$maintenance = new Maintenance();

if ($_POST['m'] == 'dockwatchMaintenance') {
    $maintenance->apply($_POST['action']);
}
