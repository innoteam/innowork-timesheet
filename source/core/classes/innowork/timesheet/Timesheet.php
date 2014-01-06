<?php
namespace Innowork\Timesheet;

class Timesheet
{
	/**
	 * Symbol used to separate hours and minutes in a time entry.
	 * @var string
	 */
	const TIME_SEPARATOR = '.';
	
	public function getTimesheet($itemType = '', $itemId = '') {
		$result = array();

		$timesheet_query = InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->execute(
			'SELECT * '.
			'FROM innowork_timesheet '.((strlen($itemType) and (int)$itemId > 0 ) ?
			'WHERE itemid='.$itemId.' ' : '').
			'ORDER BY activitydate DESC'
		);
			 
		while (!$timesheet_query->eof) {
			$result[] = array(
				'id' => $timesheet_query->getFields('id'),
				'userid' => $timesheet_query->getFields('userid'),
				'description' => $timesheet_query->getFields('description'),
				'activitydate' => InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->getDateArrayFromTimestamp(
					$timesheet_query->getFields('activitydate')
				),
				'spenttime' => $timesheet_query->getFields('spenttime'),
				'cost' => $timesheet_query->getFields('cost'),
				'costtype' => $timesheet_query->getFields('costtype'),
				'reportingperiod' => $timesheet_query->getFields('reportingperiod'),
				'consolidated' => $timesheet_query->getFields('consolidated')
			);

			$timesheet_query->moveNext();
		}
	
		return $result;
	}
	
	public function addTimesheetRow(
		$itemType,
		$itemId,
		$userId,
		$activityDate,
		$description,
		$spentTime,
		$cost,
		$costType,
		$reportingPeriod
	) {		 
		if (!strlen($costType)) $costType = 0;
		if (!strlen($userId)) $userId = InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId();
	
		$timestamp = InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->getTimestampFromDateArray( $activityDate );
		$domainDa = InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess();
	
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
				$domainDa->formatText( $domainDa->fmtfalse ).
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
	
		$domainDa = InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess();
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
	
	public function consolidateTimesheetRow($rowId) {
		$result = false;
		$rowId = (int)$rowId;
	
		if ($rowId) {
			$domain_da = InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess();
			
			$result = $domain_da->execute(
				'UPDATE innowork_timesheet '.
				'SET consolidated = '.$domain_da->formatText( $domain_da->fmttrue ).' '.
				'WHERE id='.$rowId
			);
		}
	
		return $result;
	}
	
	public function unconsolidateTimesheetRow($rowId) {
		$rowId = (int)$rowId;
	
		if (!$rowId) {
			return false;
		}
		
		$domain_da = InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess();
		
		return $result = $domain_da->execute(
			'UPDATE innowork_timesheet '.
			'SET consolidated = '.$domain_da->formatText( $domain_da->fmtfalse ).' '.
			'WHERE id='.$rowId
		);
	}
	
	public function deleteTimesheetRow($rowId) {
		$rowId = (int)$rowId;
	
		if (!$rowId) {
			return false;
		}
		
		$domain_da = InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess();
		
		return $domain_da->execute(
			'DELETE FROM innowork_timesheet '.
			'WHERE id='.$rowId
		);
	}
	
	/**
	 * Sums two timesheet time entries.
	 * 
	 * @param string $time1
	 * @param string $time2
	 */
	public static function sumTime($time1, $time2) {
		$times = array($time1, $time2);
		$seconds = 0;
		foreach ($times as $time)
		{
			list($hour,$minute,$second) = explode(self::TIME_SEPARATOR, $time.'.00');
			$seconds += $hour*3600;
			$seconds += $minute*60;
			$seconds += $second;
		}
		$hours = floor($seconds/3600);
		$seconds -= $hours*3600;
		$minutes  = floor($seconds/60);
		$seconds -= $minutes*60;
		return sprintf('%02d'.self::TIME_SEPARATOR.'%02d', $hours, $minutes);
	}
	
	public static function getTimesheetUsers() {
		return InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->Execute(
			'SELECT domain_users.id AS id,fname,lname,username '.
			'FROM domain_users '.
			'WHERE username<>'.InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText(User::getAdminUsername(InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDomainId())).' '.
			'ORDER BY lname,fname');
	}
	
	public static function getElencoCodiciImponibili() {
		return array(
			0 => '',
			1 => 'Imponibile',
			2 => 'Non imponibile ex art. 7',
			3 => 'Non imponibile ex art. 15'
		);
	}
}