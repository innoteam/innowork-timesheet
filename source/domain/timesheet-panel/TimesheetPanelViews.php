<?php

use \Innomatic\Core\InnomaticContainer;
use \Innomatic\Wui\Widgets;
use \Innomatic\Wui\Dispatch;
use \Innomatic\Locale\LocaleCatalog;
use \Shared\Wui;

class TimesheetPanelViews extends \Innomatic\Desktop\Panel\PanelViews
{
    protected $localeCatalog;
    protected $pageTitle;
    protected $icon;
    protected $pageXml;
    protected $status;
    protected $toolbars;

    public function update($observable, $arg = '')
    {
    }

    public function beginHelper()
    {
        $this->localeCatalog = new LocaleCatalog(
            'innowork-timesheet::timesheet_main',
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getLanguage()
        );
        
        $this->icon = 'clock1';
        
        /*
        $this->toolbars['view'] = array(
    'default' => array(
        'label' => $this->localeCatalog->getStr('applications_button'),
        'themeimage' => 'listdetailed2',
        'action' => \Innomatic\Wui\Dispatch\WuiEventsCall::buildEventsCallString('', array(array('view', 'default', ''))),
        'horiz' => 'true'
        ),
    'repository' => array(
        'label' => $this->localeCatalog->getStr('repository.toolbar'),
        'themeimage' => 'globe2',
        'action' => \Innomatic\Wui\Dispatch\WuiEventsCall::buildEventsCallString('', array(array('view', 'appcentral', ''))),
        'horiz' => 'true'
        ),
    'keyring' => array(
        'label' => $this->localeCatalog->getStr('keys.toolbar'),
        'themeimage' => 'keyhole',
        'horiz' => 'true',
        'action' => \Innomatic\Wui\Dispatch\WuiEventsCall::buildEventsCallString(
            '',
            array(
                array(
                    'view',
                    'keyring',
                    ''
                )
            )
        )
    )
);
        */
        $this->toolbars['view'] = array(
        	'default' => array(
        		'label' => $this->localeCatalog->getStr('timesheet.button'),
        		'themeimage' => 'calendar',
        		'action' => \Innomatic\Wui\Dispatch\WuiEventsCall::buildEventsCallString('', array(array('view', 'default', ''))),
        		'horiz' => 'true'
        	)
        );
        
        $this->toolbars['log'] = array(
			'default' => array(
				'label' => $this->localeCatalog->getStr('log_work.button'),
				'themeimage' => 'mathadd',
				'action' => WuiEventsCall::buildEventsCallString('', array(array('view', 'logwork', ''))),
				'horiz' => 'true'
			)
		);
    }

    public function endHelper()
    {
    	if (!strlen($this->pageTitle)) {
    		$this->pageTitle = $this->localeCatalog->getStr('timesheet.title');
    	}
    	
    	$this->_wuiContainer->addChild(
			new WuiInnomaticPage(
				'page',
				array(
					'pagetitle' => $this->pageTitle,
					'icon' => $this->icon,
					'maincontent' => new WuiXml('content', array('definition' => $this->pageXml)),
					'status' => $this->status,
					'toolbars' => array(
						new WuiInnomaticToolbar(
							'view',
							array(
								'toolbars' => $this->toolbars,
								'toolbar' => 'true'
							)
						)
					)
				)
			)
    	);
    }

    public function viewDefault($eventData)
    {
    	$country = new \Innomatic\Locale\LocaleCountry(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getCountry());
    	$ts_manager = new \Innowork\Timesheet\Timesheet();
    	
    	$tsdays = $ts_manager->getLoggedUserTimesheetMonthTotals(
    		InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId(),
    		$country->getDateArrayFromUnixTimestamp(time())
    	);
    	
    	$this->pageXml = '<vertgroup><children>
  <innoworktimesheetcalendar>
    <name>calendar</name>
    <args>
      <timesheet type="array">'.WuiXml::encode($tsdays).'</timesheet>
      <year>'.( isset($eventData['year'] ) ? $eventData['year'] : '' ).'</year>
      <month>'.( isset($eventData['month'] ) ? $eventData['month'] : '' ).'</month>
      <day>'.( isset($eventData['day'] ) ? $eventData['day'] : '' ).'</day>
      <showdaybuilderfunction>\\TimesheetPanelViews::calendar_show_day_action_builder</showdaybuilderfunction>
      <showeventbuilderfunction>\\TimesheetPanelViews::calendar_show_event_action_builder</showeventbuilderfunction>
      <disp>view</disp>
      <newaction type="encoded">'.urlencode(
        WuiEventsCall::buildEventsCallString(
            'timesheet',
            array(
                array(
                    'view',
                    'logwork'
                    )
                )
            )
        ).'</newaction>
    </args>
  </innoworktimesheetcalendar>
</children></vertgroup>';
    }
    
    public function viewLogwork($eventData)
    {
    	$this->pageTitle = $this->localeCatalog->getStr('log_work.title');

    	$innowork_dossier = new InnoworkProject(
    			\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
    			\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()
    	);
    	
    	// Users list
    	
    	$users_query = \Innowork\Timesheet\Timesheet::getTimesheetUsers();
    	$users = array();
    	
    	while (!$users_query->eof) {
    		$users[$users_query->getFields( 'id' )] = $users_query->getFields( 'lname' ).' '.$users_query->getFields( 'fname' );
    		$users_query->moveNext();
    	}
    	
    	// Projects list
    	$projects = array();

    	$this->pageXml =
    	'<vertgroup>
  <children>
    	
<form><name>tsrow</name>
      <args>
            <action>'.WuiXml::cdata( WuiEventsCall::buildEventsCallString( '', array(
    	            		array(
    	            				'view',
    	            				'default',
    	            				array(
    	            				)
    	            		)
    	            ) ) ).'</action>
      </args>
      <children>
<grid><name>timesheet</name>
  <children>';
    	
    	$country = new \Innomatic\Locale\LocaleCountry(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getCountry());
    	
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
    	
    	$this->pageXml .=
    	'<label row="0" col="0"><args><label>'.WuiXml::cdata($this->localeCatalog->getStr('user.label')).'</label></args></label>
    <combobox row="0" col="1"><name>user</name>
      <args>
        <disp>action</disp>
        <elements type="array">'.WuiXml::encode($users).'</elements>
        <default>'.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId().'</default>
      </args>
    </combobox>
    <label row="1" col="0"><args><label>'.WuiXml::cdata($this->localeCatalog->getStr('project.label')).'</label></args></label>
    			<string row="1" col="1"><name>projectid</name>
              <args>
            		<id>projectid</id>
            		<autocomplete>true</autocomplete>
            		<autocompleteminlength>2</autocompleteminlength>
            		<autocompletesearchurl>'.WuiXml::cdata(WuiEventsCall::buildEventsCallString(
                    '',
                    array(array('view', 'searchproject'))
                )).'</autocompletesearchurl>
                <disp>action</disp>
                <size>30</size>
              </args>
            		</string>
    <label row="2" col="0"><args><label>'.WuiXml::cdata($this->localeCatalog->getStr('activitydate.label')).'</label></args></label>
    <date row="2" col="1"><name>date</name>
              <args>
                <disp>action</disp>
                <value type="array">'.WuiXml::encode($start_date_array).'</value>
              </args>
            </date>
    <label row="3" col="0"><args><label>'.WuiXml::cdata($this->localeCatalog->getStr('spenttime.label')).'</label></args></label>
    <string row="3" col="1"><name>timespent</name>
		<args>
          <disp>action</disp>
	      <size>6</size>
		</args>
	</string>
    <label row="4" col="0"><args><label>'.WuiXml::cdata($this->localeCatalog->getStr('description.label')).'</label></args></label>
    <text row="4" col="1"><name>activitydesc</name>
		<args>
          <disp>action</disp>
	      <cols>30</cols>
          <rows>4</rows>
		</args>
	</text>
  </children>
</grid>

  <horizbar/>
    		
  <button>
    <args>
      <horiz>true</horiz>
      <frame>false</frame>
      <themeimage>mathadd</themeimage>
      <themeimagetype>mini</themeimagetype>
      <formsubmit>tsrow</formsubmit>
      <label>'.$this->localeCatalog->getStr('add_ts_row.button').'</label>
      <action>'.WuiXml::cdata(
    	      		WuiEventsCall::buildEventsCallString(
    	      				'',
    	      				array(
    	      						array(
    	      								'view',
    	      								'default',
    	      								array(
    	      								)
    	      						),
    	      						array(
    	      								'action',
    	      								'newtsrow',
    	      								array(
    	      								)
    	      						)
    	      				)
    	      		)
    	      ).'</action>
    </args>
  </button>
    		</children>
		</form>
    	
      </children>
	</vertgroup>';
    }
    
    public function viewSearchproject($eventData)
    {
    	$domain_da = InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess();
    	
    	$query = $domain_da->execute('SELECT id, name FROM innowork_projects WHERE name LIKE "%'.$_GET['term'].'%"');
    	$k = 0;
    	
    	while (!$query->eof) {
    		$content[$k]['id'] = $query->getFields('id');
    		$content[$k++]['value'] = $query->getFields('name');
    		$query->moveNext();
    	}
    	echo json_encode($content);
    	InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->halt();
    }
    
    public static function calendar_show_event_action_builder( $id )
    {
    	return WuiEventsCall::buildEventsCallString( '', array( array(
    			'view',
    			'showevent',
    			array( 'id' => $id )
    	) ) );
    }
    
    public static function calendar_show_day_action_builder(
    		$year,
    		$month,
    		$day
    )
    {
    	return WuiEventsCall::buildEventsCallString( '', array( array(
    			'view',
    			'default',
    			array(
    					'year' => $year,
    					'month' => $month,
    					'day' => $day,
    					'viewby' => 'day'
    			)
    	) ) );
    }
}
