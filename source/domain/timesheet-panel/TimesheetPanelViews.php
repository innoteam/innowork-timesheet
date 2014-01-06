<?php

use \Innomatic\Core\InnomaticContainer;
use \Innomatic\Wui\Widgets;
use \Innomatic\Wui\Dispatch;
use \Innomatic\Locale\LocaleCatalog;
use \Shared\Wui;

class TimesheetPanelViews extends \Innomatic\Desktop\Panel\PanelViews
{
    protected $localeCatalog;

    public function update($observable, $arg = '')
    {
    }

    public function beginHelper()
    {
        $this->localeCatalog = new LocaleCatalog(
            'innowork-timesheet::domain_timesheet',
            InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getLanguage()
        );
    }

    public function endHelper()
    {
    }

    public function viewDefault($eventData)
    {
    }
}
