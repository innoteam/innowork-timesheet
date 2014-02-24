<?php

namespace Shared\Dashboard;

use \Innomatic\Core\InnomaticContainer;
use \Shared\Wui;
use \Innomatic\Wui\Dispatch;

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
                
        $timesheet = new \Innowork\Timesheet\Timesheet(
        	\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
        	\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()
        );
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
        		 		
  <horizbar/>

  <horizgroup><args><width>0%</width></args><children>

  <button>
    <args>
      <horiz>true</horiz>
      <frame>false</frame>
      <themeimage>clock1</themeimage>
      <label>'.$locale_catalog->getStr('timesheet.button').'</label>
      <action>'.WuiXml::cdata(
    	      		\Innomatic\Wui\Dispatch\WuiEventsCall::buildEventsCallString(
    	      				'timesheet',
    	      				array(
    	      						array(
    	      								'view',
    	      								'default',
    	      								array(
    	      								)
    	      						)
    	      				)
    	      		)
    	      ).'</action>
    </args>
  </button>';
        
        if (\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->hasPermission('add_hours')) {
            $xml .= '	
  <button>
    <args>
      <horiz>true</horiz>
      <frame>false</frame>
      <themeimage>mathadd</themeimage>
      <label>'.$locale_catalog->getStr('log_work.button').'</label>
      <action>'.WuiXml::cdata(
    	      		\Innomatic\Wui\Dispatch\WuiEventsCall::buildEventsCallString(
    	      				'timesheet',
    	      				array(
    	      						array(
    	      								'view',
    	      								'logwork',
    	      								array(
    	      								)
    	      						)
    	      				)
    	      		)
    	      ).'</action>
    </args>
  </button>';
        }
        
        $xml .= '	
  </children></horizgroup>
    	      			
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
        return 90;
    }
    
    public function isVisible()
    {
        if (\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->hasPermission('add_hours')) {
            return true;
        } else {
            return false;
        }
    }
}
