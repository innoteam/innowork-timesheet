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
    
    public function actionNewtsrow($eventData)
    {
    	$timesheet = new \Innowork\Timesheet\Timesheet();
    	$locale_country = new \Innomatic\Locale\LocaleCountry(InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getCountry());
    	$date_array = $locale_country->getDateArrayFromShortDatestamp($eventData['date']);
    
    	$timesheet->addTimesheetRow(
    			InnoworkProject::ITEM_TYPE,
    			$eventData['projectid'],
    			$eventData['user'],
    			$date_array,
    			$eventData['activitydesc'],
    			$eventData['timespent'],
    			$eventData['cost'],
    			$eventData['costtype'],
    			''
    	);
    }
    
    public function actionChangetsrow($eventData)
    {
    	$timesheet = new \Innowork\Timesheet\Timesheet();
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
    
    public function actionRemovetsrow($eventData)
    {
    	$timesheet = new \Innowork\Timesheet\Timesheet();
    	$timesheet->deleteTimesheetRow($eventData['rowid']);
    }
    
    public function actionConsolidate($eventData)
    {    
    	$timesheet = new \Innowork\Timesheet\Timesheet();
    	$timesheet->consolidateTimesheetRow($eventData['rowid']);
    }

    public function actionUnconsolidate($eventData)
    {
    	$timesheet = new \Innowork\Timesheet\Timesheet();
    	$timesheet->unconsolidateTimesheetRow( $eventData['rowid'] );
    }
}
