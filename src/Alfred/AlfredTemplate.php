<?php

namespace Dpeuscher\AbsenceIo\Alfred;

use Dpeuscher\AlfredSymfonyTools\Alfred\WorkflowHelper;
use Dpeuscher\AlfredSymfonyTools\Alfred\WorkflowResult;

/**
 * @category  lib-absence-io
 * @copyright Copyright (c) 2018 Dominik Peuscher
 */
class AlfredTemplate
{
    /**
     * @var WorkflowHelper
     */
    protected $workflowHelper;

    /**
     * AlfredTemplate constructor.
     *
     * @param WorkflowHelper $workflowHelper
     */
    public function __construct(WorkflowHelper $workflowHelper)
    {
        $this->workflowHelper = $workflowHelper;
    }

    /**
     * @param \DateTime $begin
     * @param \DateTime $end
     * @param $workDaysDevs
     * @param $workDaysDevsWithoutTL
     * @param $workDaysPM
     * @param $workDays
     * @param $workDaysLeftDevs
     * @param $workDaysLeftDevsWithoutTL
     * @param $workDaysLeftPM
     * @param $workDaysLeft
     * @return string
     */
    public function buildTemplate(
        \DateTime $begin,
        \DateTime $end,
        $workDaysDevs,
        $workDaysDevsWithoutTL,
        $workDaysPM,
        $workDays,
        $workDaysLeftDevs,
        $workDaysLeftDevsWithoutTL,
        $workDaysLeftPM,
        $workDaysLeft
    ): string {
        $result = new WorkflowResult();

        $result->setTitle($begin->format('d.m.') . '-' . $end->format('d.m.') . ' ' .
            $this->buildWorkflowText('', $workDaysDevsWithoutTL, $workDaysLeftDevsWithoutTL, true));
        $result->setSubtitle(
            $this->buildWorkflowText('Devs+TL', $workDaysDevs, $workDaysLeftDevs, true) . ' | ' .
            $this->buildWorkflowText('PM', $workDaysPM, $workDaysLeftPM, true));
        $details = [];
        foreach (array_keys($workDays) as $name) {
            $details[$name] = $workDays[$name];
            $left = $workDaysLeft[$name] ?? 0;
            if ($workDays[$name] != $left && $left !== 0) {
                $details[$name] .= ' (' . $left . ' left ' . round($left / $workDays[$name] * 100) . '%)';
            }
        }
        $result->setLargetype(json_encode($details, WorkflowResult::JSON_OPTIONS));
        $result->setCopy(json_encode($details, WorkflowResult::JSON_OPTIONS));

        $this->workflowHelper->applyResult($result);
        return $this->workflowHelper->__toString();
    }

    /**
     * @param $title
     * @param $workDaysDevs
     * @param $workDaysLeftDevs
     * @param bool $includePercentage
     * @return string
     */
    protected function buildWorkflowText($title, $workDaysDevs, $workDaysLeftDevs, $includePercentage = false): string
    {
        return (!empty($title) ? $title . ': ' : '') . array_sum($workDaysDevs) . 'd (' . round(array_sum($workDaysDevs) / 5,
                1) . 'w)' . (!empty($workDaysLeftDevs) ? ' left: ' . array_sum($workDaysLeftDevs) . 'd (' . round(array_sum($workDaysLeftDevs) / 5,
                    1) . 'w)' . ($includePercentage ? ' ' . round(array_sum($workDaysLeftDevs) / array_sum($workDaysDevs) * 100) . '%' : '') : '');
    }
}
