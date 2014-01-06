<?php

/*!
 @class WuiInnoworkCalendar

 @abstract Calendar widget.
 */
class WuiInnoworktimesheetcalendar extends WuiWidget
{
    var $mNotes;
    var $mNewAction;
    var $mDisp;
    var $mToday;
    var $mDay;
    var $mMonth;
    var $mYear;
    var $mTimesheet = array();
    var $mShift;
    var $mParentShift;
    var $mShowEventBuilderFunction;
    var $mShowDayBuilderFunction;

    public function __construct(
        $elemName,
        $elemArgs = '',
        $elemTheme = '',
        $dispEvents = ''
       )
    {
        parent::__construct($elemName, $elemArgs, $elemTheme, $dispEvents);

        $tmp_sess = $this->retrieveSession();

        if (isset($this->mArgs['day']) and strlen($this->mArgs['day'])) $this->mDay = $this->mArgs['day'];
        else if (isset($tmp_sess['day']) and strlen($tmp_sess['day'])) $this->mDay = $tmp_sess['day'];
        else $this->mDay = date('d');

        if (isset($this->mArgs['month']) and strlen($this->mArgs['month'])) $this->mMonth = $this->mArgs['month'];
        else if (isset($tmp_sess['month']) and strlen($tmp_sess['month'])) $this->mMonth = $tmp_sess['month'];
        else $this->mMonth = date('n');

        if (isset($this->mArgs['year']) and strlen($this->mArgs['year'])) $this->mYear = $this->mArgs['year'];
        else if (isset($tmp_sess['year']) and strlen($tmp_sess['year'])) $this->mYear = $tmp_sess['year'];
        else $this->mYear = date('Y');

        if (is_array($this->mArgs['timesheet'])) $this->mTimesheet = &$this->mArgs['timesheet'];
        if (
            isset($this->mArgs['showdaybuilderfunction'])
            and strlen($this->mArgs['showdaybuilderfunction'])
           )
            $this->mShowDayBuilderFunction = $this->mArgs['showdaybuilderfunction'];

        if (
            isset($this->mArgs['showeventbuilderfunction'])
            and strlen($this->mArgs['showeventbuilderfunction'])
           )
            $this->mShowEventBuilderFunction = $this->mArgs['showeventbuilderfunction'];

        if (
            isset($this->mArgs['shift'])
            and strlen($this->mArgs['shift'])
           )
        {
            switch($this->mArgs['shift'])
            {
            case 'previous':
                $this->mShift = $this->mArgs['shift'];
                break;

            case 'next':
                $this->mShift = $this->mArgs['shift'];
                break;
            }
        }
        if (
            isset($this->mArgs['parentshift'])
            and strlen($this->mArgs['parentshift'])
           )
        {
            switch($this->mArgs['parentshift'])
            {
            case 'parentprevious':
                $this->mParentShift = $this->mArgs['parentshift'];
                break;

            case 'parentnext':
                $this->mParentShift = $this->mArgs['parentshift'];
                break;
            }
        }


            switch ($this->mShift)
            {
            case 'previous':
                $this->mMonth--;
                if ($this->mMonth == 0)
                {
                    $this->mMonth = 12;
                    $this->mYear--;
                }
                break;
            case 'next':
                $this->mMonth++;
                if ($this->mMonth == 13)
                {
                    $this->mMonth = 1;
                    $this->mYear++;
                }
                break;
            }

            switch ($this->mParentShift)
            {
             case 'parentprevious':
                $this->mYear--;
                break;
             case 'parentnext':
                $this->mYear++;
                break;
            }


        $this->storeSession(
            array(
                'day' => $this->mDay,
                'month' => $this->mMonth,
                'year' => $this->mYear
               )
           );

        if (isset($this->mArgs['newaction'])) $this->mNewAction = $this->mArgs['newaction'];
        if (isset($this->mArgs['disp'])) $this->mDisp = $this->mArgs['disp'];
    }

    protected function generateSource()
    {
        $result = true;

        $this->mLayout .=
'<script language="JavaScript">
<!--
linkopened = false
sendevent = \'\'

function innoworktscalendarnewevent(event)
{
    sendevent = event
    setTimeout("sendinnoworktscalendarevent()", 100)
}

function sendinnoworktscalendarevent()
{
if (linkopened == false) window.location = sendevent
}

function innoworktscalendarlinkopened()
{
    linkopened = true
}
-->
</script>'."\n";

        $locale = new \Innomatic\Locale\LocaleCatalog(
            'innowork-timesheet::timesheet_widgets',
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getLanguage()
           );

        $locale_country = new \Innomatic\Locale\LocaleCountry(
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getCountry()
           );

        //Assign timestamps to dates

        $today_ts = mktime(0, 0, 0, $this->mMonth, $this->mDay, $this->mYear);

        $firstday_month_ts = mktime(0, 0, 0, $this->mMonth, 1, $this->mYear); // first day of the month
        $lastday_month_ts = mktime(0, 0, 0, $this->mMonth + 1, 0, $this->mYear);    // last day of the month

        $month_days = date('t', $firstday_month_ts);

        // raplace day 0 for day 7, week starts on monday
        // This referers to day of the week
        $month_start_day = date('w', $firstday_month_ts);
        if ($month_start_day == 0) $month_start_day = 7;

        $month_end_day = date('w', $lastday_month_ts);
        if ($month_end_day == 0) $month_end_day = 7;

        $colspan = 2;
        $width = 300;
        $days = 1;

        $current_day[0]['y'] = $this->mYear;
        $current_day[0]['m'] = $this->mMonth;
        $current_day[0]['d'] = $this->mDay;
        $current_day[0]['wd'] = date('w', $today_ts) == 0 ? 6 : date('w', $today_ts) - 1;

        $total_width = '525';
        $width = '75';
        $height = '75';

        $this->mLayout .=
'<table border="0" cellspacing="2" cellpadding="1" width="'.$total_width.'">
<tr><td width="'.$total_width.'" bgcolor="'.$this->mThemeHandler->mColorsSet['tables']['gridcolor'].'">
<table border="0" width="'.$total_width.'" cellspacing="1" cellpadding="3" bgcolor="'.$this->mThemeHandler->mColorsSet['tables']['bgcolor'].'">
<tr>
<td colspan="7" width="'.$total_width.'" bgcolor="'.$this->mThemeHandler->mColorsSet['tables']['headerbgcolor'].'" class="bold" align="center">'.$locale->getStr('month_'.$this->mMonth.'.label').' '.$this->mYear.'</td>
</tr>
<tr>
<td bgcolor="white" align="center" width="'.$width.'">'.$locale->getStr('weekday_1.label').'</td>
<td bgcolor="white" align="center" width="'.$width.'">'.$locale->getStr('weekday_2.label').'</td>
<td bgcolor="white" align="center" width="'.$width.'">'.$locale->getStr('weekday_3.label').'</td>
<td bgcolor="white" align="center" width="'.$width.'">'.$locale->getStr('weekday_4.label').'</td>
<td bgcolor="white" align="center" width="'.$width.'">'.$locale->getStr('weekday_5.label').'</td>
<td bgcolor="white" align="center" width="'.$width.'">'.$locale->getStr('weekday_6.label').'</td>
<td bgcolor="white" align="center" width="'.$width.'">'.$locale->getStr('weekday_7.label').'</td>
</tr>
<tr>';
        // Fill with white spaces until the first day
        for ($k=1; $k < $month_start_day; $k++) {
            $this->mLayout .= '<td width="'.$width.'" height="'.$height.'" bgcolor="'.$this->mThemeHandler->mColorsSet['tables']['headerbgcolor'].'">&nbsp;</td>';
        }

            for ($i = 1; $i <= $month_days; $i++) {
                // Assigns a timestamp to day i
                $day_i_ts = mktime(0, 0, 0, date('n', $firstday_month_ts), $i, date('y', $firstday_month_ts));
                $day_i = date('w', $day_i_ts);

                if ($day_i == 0) $day_i = 7;

                $content = $pre = $post = '';
                if (isset($this->mTimesheet[$this->mYear][str_pad($this->mMonth, 2, '0', STR_PAD_LEFT)][str_pad($i, 2, '0', STR_PAD_LEFT)])) {
                    $pre = '<u>';
                    $post = '</u>';

                    $content .= '<div class="normalmini" align="center">';
                    /*
                    $content .= '<br>'.
                                '<a onClick="innoworktscalendarlinkopened()" href="'.call_user_func($this->mShowEventBuilderFunction, $id).'">'.
                                sprintf(
                                	$locale->getStr('hours.label'),
                                    $this->mTimesheet[$this->mYear][str_pad($this->mMonth, 2, '0', STR_PAD_LEFT)][str_pad($i, 2, '0', STR_PAD_LEFT)]['sum']
                                ).'</a><br>';
                    */
                    $content .= '<br>'.
                                sprintf(
                                	$locale->getStr('hours.label'),
                                    $this->mTimesheet[$this->mYear][str_pad($this->mMonth, 2, '0', STR_PAD_LEFT)][str_pad($i, 2, '0', STR_PAD_LEFT)]['sum']
                                ).'<br>';
                    $content .= '</div>';
                }

                // Target link
                if (
                    $i == date('d')
                    and $this->mMonth == date('n')
                    and $this->mYear == date('Y')
        		) {
                	$link_label = '<b>'.$pre.$i.$post.'</b>';
                } else {
                	$link_label = $pre.$i.$post;
                }
                
                $this->mLayout .= '<td width="'.$width.
                    '" height="'.$height.
                    '" align="center" valign="top" bgcolor="white"'.
                    ' onMouseOver="this.style.backgroundColor=\''.$this->mThemeHandler->mColorsSet['buttons']['selected'].'\'" onMouseOut="this.style.backgroundColor=\'white\'"'.
                    ' onClick="innoworktscalendarnewevent(\''.$this->mNewAction.
                    '&wui['.$this->mDisp.'][evd][year]='.$this->mYear.
                    '&wui['.$this->mDisp.'][evd][mon]='.$this->mMonth.
                    '&wui['.$this->mDisp.'][evd][mday]='.$i.
                    '\')">';
                /*
                    '<a onClick="innoworktscalendarlinkopened()" href="'.
                    call_user_func($this->mShowDayBuilderFunction, $this->mYear, $this->mMonth, $i).'">'.
                    $link_label.'</a>'.$content.'</td>';
                    */
                
                $this->mLayout .= $link_label.'</a>'.$content.'</td>';

                if ($day_i == 7 and $i < $month_days){
                    $this->mLayout .= "</tr>\n<tr>";
                } elseif ($day_i == 7 and $i == $month_days) {
                    $this->mLayout .= "</tr>\n";
                } elseif ($i == $month_days) {
                    for ($h = $month_end_day; $h < 7; $h++) {
                        $this->mLayout .= '<td width="'.$width.'" height="'.$height.'" bgcolor="'.$this->mThemeHandler->mColorsSet['tables']['headerbgcolor'].'">&nbsp;</td>';
                    }
                    $this->mLayout .= "</tr>\n";
                }
            }

            $this->mLayout .=
'</table>
</td></tr></table>';

        return $result;
    }
}

?>