<?php
namespace Innowork\Timesheet;

require_once('innowork/core/InnoworkItem.php');

class Timesheet extends InnoworkItem
{
	const ITEM_TYPE = 'timesheetrow';
	public $mNoAcl = true;
	public $mSearchable = false;
	public $mNoTrash = true;
	
	/**
	 * Symbol used to separate hours and minutes in a time entry.
	 * @var string
	 */
	const TIME_SEPARATOR = '.';
	
	public function __construct(
			$rrootDb,
			$rdomainDA,
			$rowId = 0
	) {
		parent::__construct(
				$rrootDb,
				$rdomainDA,
				self::ITEM_TYPE,
				$rowId
		);
	}
	
	// Innowork related methods
	
	public function getExternalItemWidgetXmlData($item)
	{
	    if (!$item->hasTypeTag('task')) {
	        return '';
	    }
	
	    if (!\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->hasPermission('add_hours')) {
	        return '';
	    }
	    
	    $item_data = $item->getItem(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId());
		
	    $xml = '<divframe><name>timesheettaskdata</name><args><id>timesheettaskdata</id></args><children>';
	    $xml .= $this->getExternalItemWidgetXmlDataContent(
	        'project',
	        $item_data['projectid'],
	        $item->getItemType(),
	        $item->getItemId()
	    );
	    $xml .= '</children></divframe>';
	
	    return $xml;
	}
	
	public function getExternalItemWidgetXmlDataContent($itemType, $itemId, $taskType, $taskId)
	{
	    $localeCatalog = new LocaleCatalog(
	        'innowork-timesheet::timesheet_main',
	        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getLanguage()
	    );
	    
	    // Get task timesheet totals by user
	    $users_ts = $this->getLoggedTaskTimesheetTotals(
	        $taskType,
	        $taskId
	    );

	    $xml = '<vertgroup><children>
  <horizgroup>
    <args>
      <align>middle</align>
      <width>0%</width>
    </args>
    <children>
      <button>
        <args>
          <themeimage>clock1</themeimage>
          <themeimagetype>mini</themeimagetype>
          <compact>true</compact>
        </args>
      </button>
          <label><name>convert</name>
        <args>
          <bold>true</bold>
          <label>'.WuiXml::cdata($localeCatalog->getStr('timesheet_acl_title.label')).'</label>
          <compact>true</compact>
        </args>
          </label>
    </children>
  </horizgroup>';
	    
	    // Show task timesheet totals by user, if there is logged time
        if (count($users_ts['users'])) {
            // Check if the current user can see other users time
            $seeAllHours = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')
                ->getCurrentUser()
                ->hasPermission('view_hours_all');
            $currentUserId = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')
                ->getCurrentUser()
                ->getUserId();
            
	        $xml .= '<horizbar/><grid><children>';
	    
	        $ts_row = 0;
            foreach ($users_ts['users'] as $userId => $user_ts) {
                if (!($seeAllHours or $userId == $currentUserId)) {
                    // User cannot see other users time, so skip user row
                    continue;
                }

	            $xml .= '<label row="'.$ts_row.'" col="0" halign="right"><args><label>'.WuiXml::cdata($user_ts['name']).'</label></args></label>
		            <label row="'.$ts_row++.'" col="1" halign="left"><args><label>'.WuiXml::cdata($user_ts['spenttime']).'</label></args></label>';
	        }
	    
	        $xml .= '<label row="'.$ts_row.'" col="0" halign="right"><args><bold>true</bold><label>'.WuiXml::cdata($localeCatalog->getStr('task_total_logged.label')).'</label></args></label>
		            <label row="'.$ts_row++.'" col="1" halign="left"><args><label>'.WuiXml::cdata($users_ts['totals']['logged']).'</label></args></label>
		        </children></grid><horizbar/>';
	    }
	    
	    $xml .= '<innoworktimesheetrapidlogger>
          		  <args>
          		    <userid></userid>
          		    <itemtype>'.$itemType.'</itemtype>
          		    <itemid>'.$itemId.'</itemid>
          		    <tasktype>'.$taskType.'</tasktype>
          		    <taskid>'.$taskId.'</taskid>
          		  </args>
          		</innoworktimesheetrapidlogger>
	    
          		</children></vertgroup>';
	    
	    return $xml;
	}
	
	// Timesheet methods
	
	public function addTimesheetRow(
		$itemType,
		$itemId,
		$userId,
		$activityDate,
		$description,
		$spentTime,
		$cost,
		$costType,
		$reportingPeriod,
		$taskType = '',
		$taskId = '0'
	) {		 
		if (!strlen($costType)) $costType = 0;
		if (!strlen($userId)) $userId = InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId();
	
		$timestamp = $this->mrDomainDA->getTimestampFromDateArray( $activityDate );
		$domainDa = $this->mrDomainDA;
	
		// Reformat spent time string
		$spentTime = str_replace(array(':', ','), '.', $spentTime);
		if (strpos($spentTime, '.') === false) {
			$spentTime = '0.'.$spentTime;
		}
		
		$result = $domainDa->execute(
			'INSERT INTO innowork_timesheet VALUES('.
			$domainDa->getNextSequenceValue('innowork_timesheet_id_seq').','.
			$domainDa->formatText($itemType).','.
			$domainDa->formatText($itemId).','.
			$userId.','.
			$domainDa->formatText( $timestamp ).','.
			$domainDa->formatText( $description ).','.
			$domainDa->formatText( $spentTime ).','.
			$domainDa->formatText( $cost ).','.
			$costType.','.
			$domainDa->formatText( $reportingPeriod ).','.
			$domainDa->formatText( $domainDa->fmtfalse ).','.
			$domainDa->formatText($taskType).','.
			$domainDa->formatText($taskId).
			')'
		);
	
		return $result;
	}
	
	public function changeTimesheetRow(
		$rowId,
		$userId,
		$activityDate,
		$description,
		$spentTime,
		$cost,
		$costType
	) {
		$result = false;
		
		if (!strlen($costType)) $costType = 0;
		if (!strlen($userId)) $userId = InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId();
	
		$domainDa = $this->mrDomainDA;
		$timestamp = $domainDa->getTimestampFromDateArray( $activityDate );
	
		$result = $domainDa->execute(
				'UPDATE innowork_timesheet SET '.
				'userid = '.$userId.', '.
				'activitydate = '.$domainDa->formatText( $timestamp ).', '.
				'description = '.$domainDa->formatText( $description ).', '.
				'spenttime = '.$domainDa->formatText( $spentTime ).', '.
				'cost = '.$domainDa->formatText( $cost ).', '.
				'costtype = '.$costType.' '.
				'WHERE id='.$rowId
		);
	
		return $result;
	}
	
	public function deleteTimesheetRow($rowId)
	{
		$rowId = (int)$rowId;
	
		if (!$rowId) {
			return false;
		}
		
		return $this->mrDomainDA->execute(
			'DELETE FROM innowork_timesheet '.
			'WHERE id='.$rowId
		);
	}
	
	public function consolidateTimesheetRow($rowId)
	{
		$result = false;
		$rowId = (int)$rowId;
	
		if ($rowId) {
			$result = $this->mrDomainDA->execute(
				'UPDATE innowork_timesheet '.
				'SET consolidated = '.$this->mrDomainDA->formatText( $this->mrDomainDA->fmttrue ).' '.
				'WHERE id='.$rowId
			);
		}
	
		return $result;
	}
	
	public function unconsolidateTimesheetRow($rowId)
	{
		$rowId = (int)$rowId;
	
		if (!$rowId) {
			return false;
		}
		
		return $result = $this->mrDomainDA->execute(
			'UPDATE innowork_timesheet '.
			'SET consolidated = '.$this->mrDomainDA->formatText( $this->mrDomainDA->fmtfalse ).' '.
			'WHERE id='.$rowId
		);
	}
	
	// Generic timesheet extractions
	
	public function getTimesheet($itemType = '', $itemId = '', $taskType = '', $taskId = '')
	{
	    $result = array();
	
	    $timesheet_query = $this->mrDomainDA->execute(
	        'SELECT * '.
	        'FROM innowork_timesheet '.
	        ((strlen($itemType) and (int)$itemId > 0 ) ?
	            'WHERE itemtype='.$this->mrDomainDA->formatText($itemType).' AND itemid='.$itemId.' ' : '').
	        ((strlen($taskType) and (int)$taskId > 0 ) ?
	            'AND tasktype='.$this->mrDomainDA->formatText($taskType).' AND taskid='.$itemId.' ' : '').
	        'ORDER BY activitydate DESC'
	    );
	
	    while (!$timesheet_query->eof) {
	        $result[] = array(
	            'id' => $timesheet_query->getFields('id'),
	            'userid' => $timesheet_query->getFields('userid'),
	            'description' => $timesheet_query->getFields('description'),
	            'activitydate' => $this->mrDomainDA->getDateArrayFromTimestamp(
	                $timesheet_query->getFields('activitydate')
	            ),
	            'spenttime' => $timesheet_query->getFields('spenttime'),
	            'cost' => $timesheet_query->getFields('cost'),
	            'costtype' => $timesheet_query->getFields('costtype'),
	            'reportingperiod' => $timesheet_query->getFields('reportingperiod'),
	            'consolidated' => $timesheet_query->getFields('consolidated'),
	            'tasktype' => $timesheet_query->getFields('tasktype'),
	            'taskid' => $timesheet_query->getFields('taskid')
	        );
	
	        $timesheet_query->moveNext();
	    }
	
	    return $result;
	}
	
	public function getUserTimesheet($userId, $fromDate, $toDate)
	{
	    $result = array();
	    
	    $from = $fromDate['year'].'-'.$fromDate['mon'].'-'.$fromDate['mday'].' 00:00:00';
	    $to = $toDate['year'].'-'.$toDate['mon'].'-'.$toDate['mday'].' 23:59:59';
	
	    $timesheet_query = $this->mrDomainDA->execute(
	        'SELECT * '.
	        'FROM innowork_timesheet '.
	        'WHERE userid='.$userId.' '.
	        'AND activitydate >= '.$this->mrDomainDA->formatText($from).' '.
			'AND activitydate <= '.$this->mrDomainDA->formatText($to).' '.
	        'ORDER BY itemid, tasktype, taskid, activitydate DESC'
	    );
	
	    while (!$timesheet_query->eof) {
	        $result[] = array(
	            'id' => $timesheet_query->getFields('id'),
	            'description' => $timesheet_query->getFields('description'),
	            'activitydate' => $this->mrDomainDA->getDateArrayFromTimestamp(
	                $timesheet_query->getFields('activitydate')
	            ),
	            'spenttime' => $timesheet_query->getFields('spenttime'),
	            'itemtype' => $timesheet_query->getFields('itemtype'),
	            'itemid' => $timesheet_query->getFields('itemid'),
	            'tasktype' => $timesheet_query->getFields('tasktype'),
	            'taskid' => $timesheet_query->getFields('taskid')
	        );
	
	        $timesheet_query->moveNext();
	    }
	
	    return $result;
	}
	
	// User related timesheet extractions
	
	public function getLoggedUserTimesheetDayTotal($userId, $day)
	{
		$from = $day['year'].'-'.$day['mon'].'-'.$day['mday'].' 00:00:00';
		$to = $day['year'].'-'.$day['mon'].'-'.$day['mday'].' 23:59:59';
			
		$query = $this->mrDomainDA->execute(
			'SELECT spenttime
			FROM innowork_timesheet
			WHERE userid='.$userId.'
			AND activitydate >= '.$this->mrDomainDA->formatText($from).'
			AND activitydate <= '.$this->mrDomainDA->formatText($to)
		);
		
		$sum = 0;
		
		while (!$query->eof) {
			$sum = self::sumTime($sum, $query->getFields('spenttime'));
			$query->moveNext();
		}
		
		return $sum;
	}

	public function getLoggedUserTimesheetWeekTotal($userId, $day)
	{
		$dateTime = new \DateTime($day['year'].'-'.$day['mon'].'-'.$day['mday']);
		$week_start = clone $dateTime->modify(('Sunday' == $dateTime->format('l')) ? 'Monday last week' : 'Monday this week');
		$week_end = clone $dateTime->modify('Sunday this week');
		
		$from = $week_start->format('Y-m-d 00:00:00');
		$to = $week_end->format('Y-m-d 23:59:59');
			
		$query = $this->mrDomainDA->execute(
				'SELECT spenttime
			FROM innowork_timesheet
			WHERE userid='.$userId.'
			AND activitydate >= '.$this->mrDomainDA->formatText($from).'
			AND activitydate <= '.$this->mrDomainDA->formatText($to)
		);
	
		$sum = 0;
	
		while (!$query->eof) {
			$sum = self::sumTime($sum, $query->getFields('spenttime'));
			$query->moveNext();
		}
	
		return $sum;
	}
	
	public function getLoggedUserTimesheetMonthTotal($userId, $date)
	{
		$from = date('Y-m-d 00:00:00', mktime(0, 0, 0, $date['mon'], 1, $date['year']));
		$to = date('Y-m-t 23:59:59', mktime(0, 0, 0, $date['mon'], 1, $date['year']));
			
		$query = $this->mrDomainDA->execute(
				'SELECT spenttime
			FROM innowork_timesheet
			WHERE userid='.$userId.'
			AND activitydate >= '.$this->mrDomainDA->formatText($from).'
			AND activitydate <= '.$this->mrDomainDA->formatText($to)
		);
		
		$sum = 0;
		
		while (!$query->eof) {
			$sum = self::sumTime($sum, $query->getFields('spenttime'));
			$query->moveNext();
		}
		
		return $sum;
	}
	
	public function getLoggedUserTimesheetMonthTotals($userId, $date)
	{
		$from = date('Y-m-d 00:00:00', mktime(0, 0, 0, $date['mon'], 1, $date['year']));
		$to = date('Y-m-t 23:59:59', mktime(0, 0, 0, $date['mon'], 1, $date['year']));
			
		$query = $this->mrDomainDA->execute(
				'SELECT spenttime, activitydate
			FROM innowork_timesheet
			WHERE userid='.$userId.'
			AND activitydate >= '.$this->mrDomainDA->formatText($from).'
			AND activitydate <= '.$this->mrDomainDA->formatText($to)
		);
	
		$tsdays['days'] = array();
		$tsdays['total']['logged'] = 0;
	
		while (!$query->eof) {
			$activity_date = $this->mrDomainDA->getDateArrayFromTimestamp($query->getFields('activitydate'));
			
			if (isset($tsdays['days'][$date['year']][$date['mon']][$activity_date['mday']]['sum'])) {
				$tsdays['days'][$date['year']][$date['mon']][$activity_date['mday']]['sum'] = self::sumTime(
					$tsdays['days'][$date['year']][$date['mon']][$activity_date['mday']]['sum'],
					$query->getFields('spenttime')
				);
			} else {
				$tsdays['days'][$date['year']][$date['mon']][$activity_date['mday']]['sum'] = $query->getFields('spenttime');
			}
			
			// Advance month total
			$tsdays['total']['logged'] = self::sumTime($tsdays['total']['logged'], $query->getFields('spenttime'));
				
			$query->moveNext();
		}
	
		return $tsdays;
	}
	
	public function getLoggedTeamTimesheetMonthTotals($date)
	{
	    $from = date('Y-m-d 00:00:00', mktime(0, 0, 0, $date['mon'], 1, $date['year']));
	    $to = date('Y-m-t 23:59:59', mktime(0, 0, 0, $date['mon'], 1, $date['year']));
	    	
	    $query = $this->mrDomainDA->execute(
	        'SELECT ts.userid, ts.spenttime, ts.activitydate
			FROM innowork_timesheet AS ts
	        JOIN domain_users AS us ON ts.userid = us.id
			WHERE
			activitydate >= '.$this->mrDomainDA->formatText($from).'
			AND activitydate <= '.$this->mrDomainDA->formatText($to)
	    );
	
	    $tsdays['days'] = array();
	    $tsdays['total']['logged'] = 0;
	
	    while (!$query->eof) {
	        $activity_date = $this->mrDomainDA->getDateArrayFromTimestamp($query->getFields('activitydate'));
	        	
	        if (isset($tsdays['days'][$query->getFields('userid')][$date['year']][$date['mon']][$activity_date['mday']]['sum'])) {
	            $tsdays['days'][$query->getFields('userid')][$date['year']][$date['mon']][$activity_date['mday']]['sum'] = self::sumTime(
	                $tsdays['days'][$query->getFields('userid')][$date['year']][$date['mon']][$activity_date['mday']]['sum'],
	                $query->getFields('spenttime')
	            );
	        } else {
	            $tsdays['days'][$query->getFields('userid')][$date['year']][$date['mon']][$activity_date['mday']]['sum'] = $query->getFields('spenttime');
	        }
	        
	        // Advance month total
	        $tsdays['total']['logged'] = self::sumTime($tsdays['total']['logged'], $query->getFields('spenttime'));

	        $query->moveNext();
	    }
        
	    return $tsdays;
	}
	
	// Task related timesheet extractions
	
	/**
	 * Returns the total time logged in a task, grouped by timesheet users.
	 * 
	 * @param string $taskType
	 * @param string $taskId
	 */
	public function getLoggedTaskTimesheetTotals($taskType, $taskId)
	{
		$query = $this->mrDomainDA->execute(
			"SELECT ts.spenttime, us.id, us.lname, us.fname
			 FROM innowork_timesheet AS ts
			 JOIN domain_users AS us ON ts.userid = us.id
			 WHERE tasktype=".$this->mrDomainDA->formatText($taskType)." AND taskid='".$taskId."'"
		);
		
		$users = array();
		$users['users'] = array();
		$users['totals']['logged'] = '';
		
		while (!$query->eof) {
			$logged_time = $query->getFields('spenttime');
			
			// Increase the total logged time
			$users['totals']['logged'] = self::sumTime($logged_time, $users['totals']['logged']);
			
			// Check if the current user has been already populated in the array
			if (isset($users['users'][$query->getFields('id')])) {
				$logged_time = self::sumTime($logged_time, $users['users'][$query->getFields('id')]['spenttime']);
			}
			
			// Populate the result query
			$users['users'][$query->getFields('id')] = array(
				'spenttime' => $logged_time,
				'name' => $query->getFields('fname')
					.((strlen($query->getFields('fname')) and strlen($query->getFields('lname'))) ? ' ' : '')
					.$query->getFields('lname')
			);
			
			$query->moveNext();
		}
		
		return $users;
	}
	
	// Utility methods
	
	/**
	 * Sums two timesheet time entries.
	 * 
	 * @param string $time1
	 * @param string $time2
	 */
	public static function sumTime($time1, $time2)
	{
		$times = array($time1, $time2);
		$seconds = 0;
		foreach ($times as $time) {
			list($hour,$minute,$second) = explode(self::TIME_SEPARATOR, $time.'.00');
			$seconds += $hour*3600;
			$seconds += $minute*60;
			$seconds += $second;
		}
		$hours = floor($seconds/3600);
		$seconds -= $hours*3600;
		$minutes  = floor($seconds/60);
		$seconds -= $minutes*60;
		return sprintf('%d'.self::TIME_SEPARATOR.'%02d', $hours, $minutes);
	}
	
	public static function getTimesheetUsers()
	{
		return InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->execute(
			'SELECT domain_users.id AS id,fname,lname,username '.
			'FROM domain_users '.
			'WHERE username<>'.InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText(User::getAdminUsername(InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDomainId())).' '.
			'ORDER BY lname,fname');
	}
	
	public static function getElencoCodiciImponibili()
	{
		// @todo remove this sort of method when possible
		return array(
			0 => '',
			1 => 'Imponibile',
			2 => 'Non imponibile ex art. 7',
			3 => 'Non imponibile ex art. 15'
		);
	}

	/**
	 * Returns a list of the items that are eligible for recording timesheet rows.
	 * 
	 * @return array
	 */
	public static function getSupportedItemTypes()
	{
		$core = \Innowork\Core\InnoworkCore::instance(
			'\Innowork\Core\InnoworkCore',
			\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
			\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()
		);
		
		$items = $core->getSummaries('', false, array('task'));
		
		return $items;
	}
}
