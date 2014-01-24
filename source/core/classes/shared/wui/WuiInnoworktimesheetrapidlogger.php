<?php

/*!
 @class WuiInnoworkCalendar

 @abstract Calendar widget.
 */
class WuiInnoworktimesheetrapidlogger extends \Shared\Wui\WuiXml
{
    var $mItemType;
    var $mItemId;
    var $mTaskType;
    var $mTaskId;
    var $mUserId;

    public function __construct(
        $elemName,
        $elemArgs = '',
        $elemTheme = '',
        $dispEvents = ''
       )
    {
        parent::__construct($elemName, $elemArgs, $elemTheme, $dispEvents);

        if (isset($this->mArgs['itemtype']) and strlen($this->mArgs['itemtype'])) $this->mItemType = $this->mArgs['itemtype'];
        if (isset($this->mArgs['itemid']) and strlen($this->mArgs['itemid'])) $this->mItemId = $this->mArgs['itemid'];
        if (isset($this->mArgs['tasktype']) and strlen($this->mArgs['tasktype'])) $this->mTaskType = $this->mArgs['tasktype'];
        if (isset($this->mArgs['taskid']) and strlen($this->mArgs['taskid'])) $this->mTaskId = $this->mArgs['taskid'];
        if (isset($this->mArgs['userid']) and strlen($this->mArgs['userid'])) $this->mUserId = $this->mArgs['userid'];
        
        $this->fillDefinition();
    }

    protected function fillDefinition()
    {
        $this->mDefinition = $this->getWidgetXml(
            $this->mItemType,
            $this->mItemId,
            $this->mTaskType,
            $this->mTaskId,
            $this->mUserId
        );

        return true;
    }
   
    public function getWidgetXml($itemType, $itemId, $taskType, $taskId, $userId)
    {
        $localeCatalog = new LocaleCatalog(
            'innowork-timesheet::timesheet_main',
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getLanguage()
        );
        
        $year = isset($eventData['year']) ? $eventData['year'] : date('Y');
        $mon = isset($eventData['mon']) ? $eventData['mon'] : date('m');
        $mday = isset($eventData['mday']) ? $eventData['mday'] : date('d');
         
        $start_date_array = array(
            'year' => $year,
            'mon' => $mon,
            'mday' => $mday,
            'hours' => date( 'H' ),
            'minutes' => '00',
            'seconds' => '00'
        );
        
        $xml = '<vertgroup><children>    <grid><children>
            <label row="0" col="0" halign="right"><args><label>'.WuiXml::cdata($localeCatalog->getStr('activitydate.label')).'</label></args></label>
    <date row="0" col="1"><name>date</name>
              <args>
             <id>timesheet_add_date</id>
             <disp>action</disp>
             <value type="array">'.WuiXml::encode($start_date_array).'</value>
              </args>
            </date>
    <label row="1" col="0" halign="right"><args><label>'.WuiXml::cdata($localeCatalog->getStr('spenttime.label')).'</label></args></label>
    <string row="1" col="1"><name>timespent</name>
        <args>
          <id>timesheet_add_timespent</id>
          <disp>action</disp>
          <size>6</size>
        </args>
    </string>
    <label row="2" col="0" halign="right"><args><label>'.WuiXml::cdata($localeCatalog->getStr('description.label')).'</label></args></label>
    <text row="2" col="1"><name>activitydesc</name>
        <args>
          <id>timesheet_add_activitydesc</id>
          <disp>action</disp>
          <cols>30</cols>
          <rows>4</rows>
        </args>
    </text>
    </children></grid>
        
  <button>
    <args>
      <horiz>true</horiz>
      <frame>false</frame>
      <themeimage>mathadd</themeimage>
      <themeimagetype>mini</themeimagetype>
      <formsubmit>tsrow</formsubmit>
      <label>'.$localeCatalog->getStr('add_ts_row.button').'</label>
      <action>javascript:void(0)</action>
    </args>
    			  <events>
    			    <click>xajax_WuiInnoworktimesheetrapidloggerAddTimesheetRow(\''.$itemType.'\',\''.$itemId.'\',\''.$taskType.'\',\''.$taskId.'\',\''.$userId.'\',document.getElementById(\'timesheet_add_date\').value,document.getElementById(\'timesheet_add_activitydesc\').value,document.getElementById(\'timesheet_add_timespent\').value)</click>
    			  </events>
  </button>
              </children></vertgroup>';
        
        return $xml;
    }
    
    public static function ajaxAddTimesheetRow($itemType, $itemId, $taskType, $taskId, $userId, $date, $activityDesc, $timeSpent)
    {
		$timesheet = new \Innowork\Timesheet\Timesheet(
		    \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
		    \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()
		);
		$locale_country = new \Innomatic\Locale\LocaleCountry(InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getCountry());
		$date_array = $locale_country->getDateArrayFromShortDatestamp($date);
    	
        $timesheet->addTimesheetRow(
            $itemType,
			$itemId,
            $userId,
            $date_array,
            $activityDesc,
            $timeSpent,
            '',
            '',
            '',
            $taskType,
            $taskId
        );
        
        $objResponse = new XajaxResponse();
        $xml = self::getTimesheetBoxContent($itemType, $itemId, $taskType, $taskId);
        $html = WuiXml::getContentFromXml('', $xml);
        
        $objResponse->addAssign("timesheettaskdata", "innerHTML", $html);
        
        return $objResponse;
    }
    
    public static function getTimesheetBoxContent($itemType, $itemId, $taskType, $taskId)
    {
        $summary = \Innowork\Core\InnoworkCore::instance(
            '\Innowork\Core\InnoworkCore',
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()
        )->getSummaries();

        if (!isset($summary[$taskType])) {
            return false;
        }
        
        $class_name = $summary[$taskType]['classname'];
        if (!class_exists($class_name)) {
            return false;
        }
        
        $item = new $class_name(
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(),
            $taskId
        );
        
        if (!is_object($item)) {
            return false;
        }

        $timesheet = new \Innowork\Timesheet\Timesheet(
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()
        );
        
        // Get the xml content
        $xml = $timesheet->getExternalItemWidgetXmlDataContent(
        	$itemType,
            $itemId,
            $taskType,
            $taskId
        );

        return $xml;
    }
}

?>