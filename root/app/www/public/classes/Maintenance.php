<?php

/*
----------------------------------
 ------  Created: 021024   ------
 ------  Austin Best	   ------
----------------------------------
*/

/**
 * Some of the code in these methods are duplicated and technically could be simplified
 * It is done this way so if things need to be done per host or per maintenance it is easy to split & log
 */

//-- BRING IN THE EXTRAS
loadClassExtras('Maintenance');

class Maintenance
{
    use Host;
    use Maint;

    public $docker;
    protected $maintenanceContainerName = 'dockwatch-maintenance';
    protected $maintenancePort;
    protected $maintenanceIP;
    protected $settingsTable;
    protected $hostContainer = [];
    protected $maintenanceContainer = [];
    protected $processList = [];

    public function __construct()
    {
        global $docker, $settingsTable;

        logger(MAINTENANCE_LOG, '$maintenance->__construct() ->');

        $this->docker           = $docker;
        $this->settingsTable    = $settingsTable;
        $this->maintenancePort  = $settingsTable['maintenancePort'];
        $this->maintenanceIP    = $settingsTable['maintenanceIP'];
        $getExpandedProcessList = getExpandedProcessList(true, true, true, true);
        $this->processList      = is_array($getExpandedProcessList['processList']) ? $getExpandedProcessList['processList'] : [];
        $imageMatch             = str_replace(':main', '', APP_IMAGE);
    
        logger(MAINTENANCE_LOG, 'Process list: ' . count($this->processList) . ' containers');

        foreach ($this->processList as $process) {
            logger(MAINTENANCE_LOG, 'Checking \'' . $process['inspect'][0]['Config']['Image'] . '\' contains \'' . $imageMatch . '\'');
    
            if (str_contains($process['inspect'][0]['Config']['Image'], $imageMatch) && $process['Names'] != $this->maintenanceContainerName) {
                $this->hostContainer = $process;
            }
    
            if ($process['Names'] == $this->maintenanceContainerName) {
                $this->maintenanceContainer = $process;
            }
    
            if ($this->hostContainer && $this->maintenanceContainer) {
                break;
            }
        }

        logger(MAINTENANCE_LOG, '$maintenance->__construct() <-');
    }

    public function __toString()
    {
        return 'Maintenance initialized';
    }

    public function startup()
    {
        logger(MAINTENANCE_LOG, '$maintenance->startup() ->');
    
        if (file_exists(TMP_PATH . 'restart.txt')) { //-- dockwatch-maintenance CHECKING ON dockwatch RESTART
            logger(MAINTENANCE_LOG, 'restart requested for \'' . $this->hostContainer['Names'] . '\'');
    
            unlink(TMP_PATH . 'restart.txt');
            logger(MAINTENANCE_LOG, 'removed ' . TMP_PATH . 'restart.txt');
    
            $this->stopHost();
            $this->startHost();
        } elseif (file_exists(TMP_PATH . 'update.txt')) { //-- dockwatch-maintenance CHECKING ON dockwatch UPDATE
            logger(MAINTENANCE_LOG, 'update requested for \'' . $this->hostContainer['Names'] . '\'');
    
            unlink(TMP_PATH . 'update.txt');
            logger(MAINTENANCE_LOG, 'removed ' . TMP_PATH . 'update.txt');

            $this->stopHost();
            $this->removeHost();
            $this->pullHost();
            $this->createHost();
        } else { //-- dockwatch CHECKING ON dockwatch-maintenance REMOVAL
            logger(MAINTENANCE_LOG, 'removing \'' . $this->maintenanceContainerName . '\'');
            $this->removeMaintenance();
        }
    
        logger(MAINTENANCE_LOG, '$maintenance->startup() <-');
    }

    function apply($action)
    {    
        file_put_contents(TMP_PATH . $action . '.txt', $action);
        $this->createMaintenance();
    }
}
