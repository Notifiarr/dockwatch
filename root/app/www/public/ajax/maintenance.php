<?php

/*
----------------------------------
 ------  Created: 020724   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'shared.php';

if ($_POST['m'] == 'dockwatchMaintenance') {
    initiaiteMaintenance($_POST['action']);
}