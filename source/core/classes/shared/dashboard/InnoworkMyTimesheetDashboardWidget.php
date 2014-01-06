<?php

namespace Shared\Dashboard;

use \Innomatic\Core\InnomaticContainer;
use \Shared\Wui;

class InnoworkMyTimesheetDashboardWidget extends \Innomatic\Desktop\Dashboard\DashboardWidget
{
    public function getWidgetXml()
    {
        $locale_catalog = new \Innomatic\Locale\LocaleCatalog(
            'innowork-timesheet::timesheet_dashboard',
            InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getLanguage()
        );
    	
    	$locale_country = new \Innomatic\Locale\LocaleCountry(
			InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getCountry()
        );
                
        $timesheet = new \Innowork\Timesheet\Timesheet();
        $day_sum = $timesheet->getLoggedUserTimesheetDayTotal(
        	InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId(),
        	$locale_country->getDateArrayFromUnixTimestamp(time())
        );
        $week_sum = $timesheet->getLoggedUserTimesheetWeekTotal(
        		InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId(),
        		$locale_country->getDateArrayFromUnixTimestamp(time())
        );
        
        $xml =
        '<vertgroup>
           <children>
             <label>
               <args>
        		 <label>'.WuiXml::cdata(sprintf($locale_catalog->getStr('logged_today.label'), $day_sum)).'</label>
        	   </args>
        	 </label>
             <label>
               <args>
        		 <label>'.WuiXml::cdata(sprintf($locale_catalog->getStr('logged_week.label'), $week_sum)).'</label>
        	   </args>
        	 </label>
           </children>
         </vertgroup>';

        return $xml;
    }

    public function getWidth()
    {
        return 1;
    }

    public function getHeight()
    {
        return 60;
    }
}
