<?php

use \Innomatic\Core\InnomaticContainer;
use \Innomatic\Wui\Widgets;
use \Innomatic\Wui\Dispatch;
use \Innomatic\Domain\User;
use \Shared\Wui;

class TimesheetPanelActions extends \Innomatic\Desktop\Panel\PanelActions
{
    protected $localeCatalog;

    public function __construct(\Innomatic\Desktop\Panel\PanelController $controller)
    {
        parent::__construct($controller);
    }

    public function beginHelper()
    {
        $this->localeCatalog = new \Innomatic\Locale\LocaleCatalog(
            'innowork-timesheet::timesheet_domain',
            InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getLanguage()
        );
    }

    public function endHelper()
    {
    }
    
    public function executeNewtsrow($eventData)
    {
    	$timesheet = new \Innowork\Timesheet\Timesheet(
    		\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
    		\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()
    	);
    	$locale_country = new \Innomatic\Locale\LocaleCountry(InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getCountry());
    	$date_array = $locale_country->getDateArrayFromShortDatestamp($eventData['date']);
    
    	// Old style require, waiting for Innowork Project upgrade
    	require_once('innowork/projects/InnoworkProject.php');
    	
    	$timesheet->addTimesheetRow(
    			InnoworkProject::ITEM_TYPE,
    			$eventData['projectid_id'],
    			$eventData['user'],
    			$date_array,
    			$eventData['activitydesc'],
    			$eventData['timespent'],
    			'',
    			'',
    			'',
    			$eventData['tasktype'],
    			$eventData['taskid']
    	);
    }
    
    public function executeChangetsrow($eventData)
    {
    	$timesheet = new \Innowork\Timesheet\Timesheet(
    		\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
    		\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()
    	);
    	$locale_country = new \Innomatic\Locale\LocaleCountry(InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getCountry());
    	$date_array = $locale_country->getDateArrayFromShortDatestamp($eventData['date']);
    
    	$timesheet->changeTimesheetRow(
    			$eventData['rowid'],
    			$eventData['user'],
    			$date_array,
    			$eventData['activitydesc'],
    			$eventData['timespent'],
    			$eventData['cost'],
    			$eventData['costtype']
    	);
    }
    
    public function executeRemovetsrow($eventData)
    {
    	$timesheet = new \Innowork\Timesheet\Timesheet(
    		\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
    		\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()
    	);
    	$timesheet->deleteTimesheetRow($eventData['rowid']);
    }
    
    public function executeConsolidate($eventData)
    {    
    	$timesheet = new \Innowork\Timesheet\Timesheet(
    		\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
    		\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()
    	);
    	$timesheet->consolidateTimesheetRow($eventData['rowid']);
    }

    public function executeUnconsolidate($eventData)
    {
    	$timesheet = new \Innowork\Timesheet\Timesheet(
    		\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
    		\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()
    	);
    	$timesheet->unconsolidateTimesheetRow( $eventData['rowid'] );
    }
}
