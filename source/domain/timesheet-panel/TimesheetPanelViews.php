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

        $this->toolbars['view'] = array(
        	'default' => array(
        		'label' => $this->localeCatalog->getStr('timesheet.button'),
        		'themeimage' => 'calendar',
        		'action' => \Innomatic\Wui\Dispatch\WuiEventsCall::buildEventsCallString('', array(array('view', 'default', ''))),
        		'horiz' => 'true'
        	)
        );

        if (\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->hasPermission('add_hours') ||
        	\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->hasPermission('add_hours_all')) {
            $this->toolbars['log'] = array(
    			'default' => array(
    				'label' => $this->localeCatalog->getStr('log_work.button'),
    				'themeimage' => 'mathadd',
    				'action' => WuiEventsCall::buildEventsCallString('', array(array('view', 'logwork', ''))),
    				'horiz' => 'true'
    			)
    		);
        }
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

    	$dateCatalog = new LocaleCatalog(
    	    'innowork-timesheet::timesheet_widgets',
    	    \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getLanguage()
    	);

    	$ts_manager = new \Innowork\Timesheet\Timesheet(
    		\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
    		\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()
    	);

    	$tsdays = $ts_manager->getLoggedUserTimesheetMonthTotals(
    		InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId(),
    		$country->getDateArrayFromUnixTimestamp(time())
    	);

    	// Current period
    	if (isset($eventData['year'])) {
    	    $curr_year = $eventData['year'];
    	} else {
    	    $curr_year = date('Y');
    	}
    	if (isset($eventData['month'])) {
    	    $curr_month = $eventData['month'];
    	} else {
    	    $curr_month = date('n');
    	}
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

    	$this->pageXml = '<divframe><args><id>tscalendar</id></args><children>
    	    <vertgroup><args><width>0%</width></args><children>
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
      <year>'.( isset($eventData['year'] ) ? $eventData['year'] : '' ).'</year>
      <month>'.( isset($eventData['month'] ) ? $eventData['month'] : '' ).'</month>
      <day>'.( isset($eventData['day'] ) ? $eventData['day'] : '' ).'</day>
      <showdaybuilderfunction>\\TimesheetPanelViews::calendar_show_day_action_builder</showdaybuilderfunction>
      <showeventbuilderfunction>\\TimesheetPanelViews::calendar_show_event_action_builder</showeventbuilderfunction>
      <disp>view</disp>
      <newaction type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('timesheet', array(array('view', 'logwork')))).'</newaction>
    </args>
  </innoworktimesheetcalendar>
          <horizbar/>
          <grid><children>
            <label row="0" col="0"><args><label>'.WuiXml::cdata($this->localeCatalog->getStr('month_total_logged.label')).'</label></args></label>
            <label row="0" col="1"><args><label>'.WuiXml::cdata($tsdays['total']['logged']).'</label></args></label>
          </children></grid>
                <horizgroup><children>
  <button>
    <args>
      <horiz>true</horiz>
      <frame>false</frame>
      <themeimage>printer</themeimage>
      <label>'.$this->localeCatalog->getStr('print_timesheet_report.button').'</label>
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
  </button>';
    	if (\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->hasPermission('view_hours_all')) {
    	    $this->pageXml .='
  <button>
    <args>
      <horiz>true</horiz>
      <frame>false</frame>
      <themeimage>printer</themeimage>
      <label>'.$this->localeCatalog->getStr('print_team_timesheet_report.button').'</label>
      <action>'.WuiXml::cdata(
    	      		WuiEventsCall::buildEventsCallString(
    	      				'',
    	      				array(
    	      						array(
    	      								'view',
    	      								'printteamreport',
    	      								array(
    	      								    'month' => $curr_month,
    	      								    'year' => $curr_year
    	      								)
    	      						)
    	      				)
    	      		)
    	      ).'</action>
    </args>
  </button>';
    	}

    	$this->pageXml .= '
    	          </children></horizgroup>
            </children></vertgroup>
          </children></divframe>';
    }

    public function viewLogwork($eventData)
    {
        if (!\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->hasPermission('add_hours')) {
            $this->viewDefault($eventData);
            return;
        }

    	$this->pageTitle = $this->localeCatalog->getStr('log_work.title');

    	$innowork_dossier = new InnoworkProject(
    		\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
    		\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()
    	);

    	$supported_item_types = Timesheet::getSupportedItemTypes();
    	$task_types = array();
    	foreach ($supported_item_types as $item_type) {
    		$task_types[$item_type['type']] = $item_type['label'];
    	}

    	// Projects list
    	$projects = array();

    	$row = 0;

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
    	'<label row="'.$row.'" col="0" halign="right"><args><label>'.WuiXml::cdata($this->localeCatalog->getStr('task.label')).'</label></args></label>
    			<string row="'.$row++.'" col="1"><name>taskid</name>
              <args>
            		<id>taskid</id>
            		<autocomplete>true</autocomplete>
            		<autocompleteminlength>2</autocompleteminlength>
            		<autocompletesearchurl>'.WuiXml::cdata(WuiEventsCall::buildEventsCallString(
                    '',
                    array(array('view', 'searchtasks'))
                )).'</autocompletesearchurl>
                <disp>action</disp>
                <size>30</size>
              </args>
            		</string>';

    	if (false and count($supported_item_types)) {
    		$this->pageXml .= '<label row="'.$row.'" col="0"><args><label>'.WuiXml::cdata($this->localeCatalog->getStr('task_type.label')).'</label></args></label>
    <combobox row="'.$row++.'" col="1"><name>tasktype</name>
      <args>
        <disp>action</disp>
        <elements type="array">'.WuiXml::encode($task_types).'</elements>
      </args>
    </combobox>
    		<label row="'.$row.'" col="0"><args><label>'.WuiXml::cdata($this->localeCatalog->getStr('task.label')).'</label></args></label>
    		<string row="'.$row++.'" col="1"><name>taskid</name>
              <args>
            		<id>itemid</id>
            		<autocomplete>true</autocomplete>
            		<autocompleteminlength>2</autocompleteminlength>
            		<autocompletesearchurl>'.WuiXml::cdata(WuiEventsCall::buildEventsCallString(
                    '',
                    array(array('view', 'searchproject'))
                )).'</autocompletesearchurl>
                <disp>action</disp>
                <size>40</size>
              </args>
            		</string>';
    	}

    	$this->pageXml .= '
    <label row="'.$row.'" col="0" halign="right"><args><label>'.WuiXml::cdata($this->localeCatalog->getStr('activitydate.label')).'</label></args></label>
    <date row="'.$row++.'" col="1"><name>date</name>
              <args>
                <disp>action</disp>
                <value type="array">'.WuiXml::encode($start_date_array).'</value>
              </args>
            </date>
    <label row="'.$row.'" col="0" halign="right"><args><label>'.WuiXml::cdata($this->localeCatalog->getStr('spenttime.label')).'</label></args></label>
    <string row="'.$row++.'" col="1"><name>timespent</name>
		<args>
          <disp>action</disp>
	      <size>6</size>
		</args>
	</string>
    <label row="'.$row.'" col="0" halign="right"><args><label>'.WuiXml::cdata($this->localeCatalog->getStr('description.label')).'</label></args></label>
    <text row="'.$row++.'" col="1"><name>activitydesc</name>
		<args>
          <disp>action</disp>
	      <cols>60</cols>
          <rows>8</rows>
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
      <mainaction>true</mainaction>
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

    	$query = $domain_da->execute('SELECT id, name FROM innowork_projects WHERE name LIKE "%'.$_GET['term'].'%" AND done <> '.$domain_da->formatText($domain_da->fmttrue));
    	$k = 0;

    	while (!$query->eof) {
    		$content[$k]['id'] = $query->getFields('id');
    		$content[$k++]['value'] = $query->getFields('name');
    		$query->moveNext();
    	}
    	echo json_encode($content);
    	InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->halt();
    }

    public function viewSearchtasks($eventData)
    {
    	$domain_da = InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess();

    	$core = \Innowork\Core\InnoworkCore::instance(
    		'\Innowork\Core\InnoworkCore',
    		\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
    		$domain_da
    	);

    	$items = $core->getSummaries('', false, array('task'));

    	$k = 0;
    	foreach ($items as $type => $item) {
        	$query = $domain_da->execute('SELECT id, title FROM '.$item['table'].' WHERE title LIKE "%'.$_GET['term'].'%"');

        	while (!$query->eof) {
        		$content[$k]['id'] = $type.'-'.$query->getFields('id');
        		$content[$k++]['value'] = $item['label'].' '.$query->getFields('id').(strlen($query->getFields('title')) ? ': '.$query->getFields('title') : '');
        		$query->moveNext();
        	}
    	}
    	echo json_encode($content);
    	InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->halt();
    }

    public function viewPrintuserreport($eventData)
    {
        $domain_da = InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess();

        $innowork_core = \Innowork\Core\InnoworkCore::instance('\Innowork\Core\InnoworkCore', \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
        $summaries = $innowork_core->getSummaries();

        $timesheet_manager = new \Innowork\Timesheet\Timesheet(
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()
        );
        $timesheet = $timesheet_manager->getUserTimesheet(
            InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId(),
            array('mday' => sprintf('%02d', 1), 'mon' => sprintf('%02d', $eventData['month']), 'year' => $eventData['year']),
            array('mday' => sprintf('%02d', date('t', mktime(0, 0, 0, $eventData['month'], 1, $eventData['year']))), 'mon' => sprintf('%02d', $eventData['month']), 'year' => $eventData['year'])
        );

        // Users list
        /*
        $users_query = \Innowork\Timesheet\Timesheet::getTimesheetUsers();

        $users = array();

        while ( !$users_query->eof )
        {
            $users[$users_query->getFields( 'id' )] = $users_query->getFields( 'lname' ).
            ' '.$users_query->getFields( 'fname' );

            $users_query->moveNext();
        }
        */

        $headers[0]['label'] = $this->localeCatalog->getStr( 'project.header' );
        $headers[1]['label'] = $this->localeCatalog->getStr( 'activity.header' );
        $headers[2]['label'] = $this->localeCatalog->getStr( 'date.header' );
        $headers[3]['label'] = $this->localeCatalog->getStr( 'activitydesc.header' );
        $headers[4]['label'] = $this->localeCatalog->getStr( 'timespent.header' );

        $row = 1;

        $country = new LocaleCountry(
            InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getCountry()
        );

        echo "<html><head><title>Timesheet Report</title><meta charset=\"UTF-8\"></head><body onload=\"window.print()\">\n";

        echo '<table style="width: 100%"><tr><td>User</td><td>Month</td><td>Year</td></tr>
			<tr><td><strong>'.InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId().'</strong></td><td><strong>'.$eventData['month'].'</strong></td><td><strong>'.$eventData['year'].'</strong></td></tr></table>';

        echo "<br><table border=\"0\" cellspacing=\"0\" cellpadding=\"4\" style=\"border: solid 0px; width: 100%;\">\n";
        echo "<tr>\n";
        echo "<th valign=\"top\">".$headers[0]['label']."</th>\n";
        echo "<th valign=\"top\">".$headers[1]['label']."</th>\n";
        echo "<th valign=\"top\">".$headers[2]['label']."</th>\n";
        echo "<th valign=\"top\">".$headers[3]['label']."</th>\n";
        echo "<th valign=\"top\">".$headers[4]['label']."</th>\n";
        echo "</tr>\n";

        $prev_project = '';
        $prev_task = '';

        $sum = 0;

        foreach ($timesheet as $ts_row) {
            // Check if this is a different project. If so, print the project header.
            if ($prev_project != $ts_row['itemtype'].$ts_row['itemid']) {
                $item_title = '';

                if (isset($summaries[$ts_row['itemtype']]) and strlen($ts_row['itemid'])) {
                    $task_query = $domain_da->execute(
                        'SELECT name '.
                        'FROM '.$summaries[$ts_row['itemtype']]['table'].' '.
                        'WHERE id='.$ts_row['itemid']
                    );

                    if ($task_query->getNumberRows() > 0) {
                        $item_title = $task_query->getFields('name');
                    }

                    if (strlen($item_title)) {
                        $item_title = ': '.$item_title;
                    }
                }

                // $summaries[$ts_row['itemtype']]['label']
                echo '<tr><td colspan="5"><strong>'.$this->localeCatalog->getStr( 'project.header' ).' '.$ts_row['itemid'].$item_title.'</strong></td></tr>';
            }

            // Check if this is a different task. If so, print the task header.
            if ($prev_task != $ts_row['tasktype'].$ts_row['taskid']) {
                $task_title = '';

                if (isset($summaries[$ts_row['tasktype']]) and strlen($ts_row['taskid'])) {
                    $task_query = $domain_da->execute(
                        'SELECT title '.
                        'FROM '.$summaries[$ts_row['tasktype']]['table'].' '.
                        'WHERE id='.$ts_row['taskid']
                    );

                    if ($task_query->getNumberRows() > 0) {
                        $task_title = $task_query->getFields('title');
                    }

                    if (strlen($task_title)) {
                        $task_title = ': '.$task_title;
                    }
                }

                echo '<tr><td></td><td colspan="4"><strong>'.$summaries[$ts_row['tasktype']]['label'].' '.$ts_row['taskid'].$task_title.'</strong></td></tr>';
            }

            echo "<tr>\n";
            echo "<td></td><td></td>\n";
            echo "<td valign=\"top\">".$country->FormatShortArrayDate( $ts_row['activitydate'] )."</td>\n";
            echo "<td valign=\"top\">".nl2br( $ts_row['description'] )."</td>\n";
            echo "<td align=\"right\" valign=\"top\">".$ts_row['spenttime']."</td>\n";
            echo "</tr>\n";

            // Keep track of previous task type/id
            $prev_task = $ts_row['tasktype'].$ts_row['taskid'];
            $prev_project = $ts_row['itemtype'].$ts_row['itemid'];

            $sum = \Innowork\Timesheet\Timesheet::sumTime($sum, $ts_row['spenttime']);
        }
        echo "</table>\n";

        echo "<p>".$this->localeCatalog->getStr('month_total_logged.label')." <strong>".$sum."</strong></p>\n";
        echo "</body></html>\n";

        InnomaticContainer::instance('innomaticcontainer')->halt();
    }

    public function viewPrintteamreport($eventData)
    {
        if (!\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->hasPermission('view_hours_all')) {
            $this->viewDefault($eventData);
            return;
        }

        $domain_da = InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess();

        $innowork_core = \Innowork\Core\InnoworkCore::instance('\Innowork\Core\InnoworkCore', \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
        $summaries = $innowork_core->getSummaries();

        $timesheet_manager = new \Innowork\Timesheet\Timesheet(
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()
        );
        $timesheet = $timesheet_manager->getLoggedTeamTimesheetMonthTotals(
            array('mday' => sprintf('%02d', 1), 'mon' => sprintf('%02d', $eventData['month']), 'year' => $eventData['year'])
        );

        // Users list
        /*
        $users_query = \Innowork\Timesheet\Timesheet::getTimesheetUsers();

        $users = array();

        while ( !$users_query->eof )
        {
        $users[$users_query->getFields( 'id' )] = $users_query->getFields( 'lname' ).
        ' '.$users_query->getFields( 'fname' );

        $users_query->moveNext();
        }
        */

            $row = 1;

                $country = new LocaleCountry(
            InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getCountry()
        );

                $month_days = date('t', mktime(0, 0, 0, $eventData['month'], 1, $eventData['year']));
                $month = sprintf('%02d', $eventData['month']);

        echo "<html><head><title>Team Timesheet Report</title><meta charset=\"UTF-8\">
<style type=\"text/css\">
<!--
   @media print{
      @page {size: landscape;}
    }

* { font-family: arial, helvetica, sans-serif; font-size: 8pt; }
-->
</style>
            </head><body onload=\"window.print()\">\n";

        echo '<table style="width: 100%"><tr><td>Month</td><td>Year</td></tr>
			<tr><td><strong>'.$eventData['month'].'</strong></td><td><strong>'.$eventData['year'].'</strong></td></tr></table>';

        echo "<br><table border=\"1\" cellspacing=\"0\" cellpadding=\"4\" style=\"border: solid 1px; width: 100%;\">\n";
        echo "<tr>\n";
        echo "<th valign=\"top\">".$this->localeCatalog->getStr('user.header')."</th>\n";

        for ($i = 1; $i <= $month_days; $i++) {
            echo "<th valign=\"top\">".$i."</th>\n";
        }
        echo "<th valign=\"top\">Totale</th>\n";
        echo "</tr>\n";

        $sum = 0;

        $sum_days = array();

        // Users list
        $users_query = \Innowork\Timesheet\Timesheet::getTimesheetUsers();

        while (!$users_query->eof) {
            $userid = $users_query->getFields('id');
            $sum_user = 0;
            echo "<tr>\n";
            echo '<td align="right">'.$users_query->getFields('fname').' '.$users_query->getFields('lname')."</td>";
            for ($i = 1; $i <= $month_days; $i++) {
                if (isset($timesheet['days'][$userid][$eventData['year']][$month][sprintf('%02d', $i)])) {
                    echo '<td align="center">'.$timesheet['days'][$userid][$eventData['year']][$month][sprintf('%02d', $i)]['sum'].'</td>';
                    $sum_days[sprintf('%02d', $i)] = \Innowork\Timesheet\Timesheet::sumTime($sum_days[sprintf('%02d', $i)], $timesheet['days'][$userid][$eventData['year']][$month][sprintf('%02d', $i)]['sum']);
                    $sum_user = \Innowork\Timesheet\Timesheet::sumTime($sum_user, $timesheet['days'][$userid][$eventData['year']][$month][sprintf('%02d', $i)]['sum']);
                    $sum = \Innowork\Timesheet\Timesheet::sumTime($sum, $timesheet['days'][$userid][$eventData['year']][$month][sprintf('%02d', $i)]['sum']);
                } else {
                    echo '<td align="center">0</td>';
                }
            }
                    echo '<td align="center">'.$sum_user.'</td>';
            echo "</tr>\n";
            $users_query->moveNext();
        }
        echo "<tr>\n";
        echo '<td align="right">Totali</td>';
        for ($i = 1; $i <= $month_days; $i++) {
            if (isset($sum_days[sprintf('%02d', $i)])) {
                $sum_day = $sum_days[sprintf('%02d', $i)];
            } else {
                $sum_day = 0;
            }
            echo '<td align="center">'.$sum_day.'</td>';
        }
        echo '<td align="center">'.$sum.'</td>';
        echo "</tr>\n";

        echo "</table>\n";

        echo "<p>".$this->localeCatalog->getStr('month_total_logged.label')." <strong>".$sum."</strong></p>\n";
        echo "</body></html>\n";

        InnomaticContainer::instance('innomaticcontainer')->halt();
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
