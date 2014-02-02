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
            'innowork-timesheet::timesheet_main',
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
    
    public static function ajaxGetTimesheetCalendar($year, $month)
    {
        $country = new \Innomatic\Locale\LocaleCountry(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getCountry());
        
    	$dateCatalog = new LocaleCatalog(
    	    'innowork-timesheet::timesheet_widgets',
    	    \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getLanguage()
    	);
    	
    	$mainCatalog = new \Innomatic\Locale\LocaleCatalog(
    	    'innowork-timesheet::timesheet_main',
    	    InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getLanguage()
    	);
    	
        $ts_manager = new \Innowork\Timesheet\Timesheet(
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()
        );

        $tsdays = $ts_manager->getLoggedUserTimesheetMonthTotals(
            InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId(),
            array('year' => $year, 'mon' => (strlen($month) == 1 ? '0' : '').$month)
        );
         
        // Current period
        $curr_year = $year;
        $curr_month = $month;
        $current_period_label = $dateCatalog->getStr('month_'.$curr_month.'.label').' '.$curr_year;
        
        // Previous period
        if ($curr_month == 1) {
            $prev_year = $curr_year - 1;
            $prev_month = 12;
            $prev_period_label = '< '.$dateCatalog->getStr('month_'.$prev_month.'.label').' '.$prev_year;
        } else {
            $prev_year = $curr_year;
            $prev_month = $curr_month - 1;
            $prev_period_label = '< '.$dateCatalog->getStr('month_'.$prev_month.'.label');
        }
         
        // Next period
        if ($curr_month == 12) {
            $next_year = $curr_year + 1;
            $next_month = 1;
            $next_period_label = $dateCatalog->getStr('month_'.$next_month.'.label').' '.$next_year.' >';
        } else {
            $next_year = $curr_year;
            $next_month = $curr_month + 1;
            $next_period_label = $dateCatalog->getStr('month_'.$next_month.'.label').' >';
        }
        
        $xml = '<vertgroup><args><width>0%</width></args><children>
    	    <horizgroup><args><width>0%</width><groupalign>center</groupalign></args><children>
    	    <link><args><link>javascript:void(0)</link><label>'.WuiXml::cdata($prev_period_label).'</label></args>
    	        <events>
    	<click>'.WuiXml::cdata("xajax_GetTimesheetCalendar('".$prev_year."', '".$prev_month."');").'</click>
  	</events></link>
    	    <label><args><bold>true</bold><label>'.WuiXml::cdata($current_period_label).'</label></args></label>
    	    <link><args><link>javascript:void(0)</link><label>'.WuiXml::cdata($next_period_label).'</label></args>
    	        <events>
    	<click>'.WuiXml::cdata("xajax_GetTimesheetCalendar('".$next_year."', '".$next_month."');").'</click>
  	</events></link>
    	    </children></horizgroup>
  <innoworktimesheetcalendar>
    <name>calendar</name>
    <args>
      <timesheet type="array">'.WuiXml::encode($tsdays['days']).'</timesheet>
      <year>'.$curr_year.'</year>
      <month>'.$curr_month.'</month>
      <showdaybuilderfunction>\\TimesheetPanelViews::calendar_show_day_action_builder</showdaybuilderfunction>
      <showeventbuilderfunction>\\TimesheetPanelViews::calendar_show_event_action_builder</showeventbuilderfunction>
      <disp>view</disp>
      <newaction type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('timesheet', array(array('view', 'logwork')))).'</newaction>
    </args>
  </innoworktimesheetcalendar>
          <horizbar/>
          <grid><children>
            <label row="0" col="0"><args><label>'.WuiXml::cdata($mainCatalog->getStr('month_total_logged.label')).'</label></args></label>
            <label row="0" col="1"><args><label>'.WuiXml::cdata($tsdays['total']['logged']).'</label></args></label>
          </children></grid>
  <button>
    <args>
      <horiz>true</horiz>
      <frame>false</frame>
      <themeimage>printer</themeimage>
      <label>'.$mainCatalog->getStr('print_timesheet_report.button').'</label>
      <action>'.WuiXml::cdata(
    	      		WuiEventsCall::buildEventsCallString(
    	      				'',
    	      				array(
    	      						array(
    	      								'view',
    	      								'printuserreport',
    	      								array(
    	      								    'month' => $curr_month,
    	      								    'year' => $curr_year
    	      								)
    	      						)
    	      				)
    	      		)
    	      ).'</action>
    </args>
  </button>
            </children></vertgroup>';

        // Build AJAX response
        $objResponse = new XajaxResponse();
        $objResponse->addAssign("tscalendar", "innerHTML", WuiXml::getContentFromXml('', $xml));
        return $objResponse;
    }
}
