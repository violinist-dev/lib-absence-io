<?php

namespace Dpeuscher\AbsenceIo\Service;

use Dpeuscher\AbsenceIo\Alfred\AlfredTemplate;

/**
 * @category  lib-absence-io
 * @copyright Copyright (c) 2018 Dominik Peuscher
 */
class TeamMapperService
{
    /**
     * @var string[]
     */
    protected $dev;

    /**
     * @var string[]
     */
    protected $pm;

    /**
     * @var string[]
     */
    protected $tl;

    /**
     * @var string
     */
    protected $location;

    /**
     * @var AbsenceService
     */
    protected $absenceService;

    /**
     * @var AlfredTemplate
     */
    protected $alfredTemplate;

    /**
     * AbsenceCheck constructor.
     *
     * @param string[] $dev
     * @param string[] $pm
     * @param string[] $tl
     * @param string $location
     * @param AbsenceService $absenceService
     * @param AlfredTemplate $alfredTemplate
     */
    public function __construct(
        array $dev,
        array $pm,
        array $tl,
        string $location,
        AbsenceService $absenceService,
        AlfredTemplate $alfredTemplate
    ) {
        $this->dev = $dev;
        $this->pm = $pm;
        $this->tl = $tl;
        $this->location = $location;
        $this->absenceService = $absenceService;
        $this->alfredTemplate = $alfredTemplate;
    }

    /**
     * @param \DateTime $begin
     * @param \DateTime $end
     * @param \DateTime|null $compareTo
     * @return string
     * @throws \Exception
     */
    public function checkTeamAvailability(\DateTime $begin, \DateTime $end, ?\DateTime $compareTo = null): string
    {
        $now = $compareTo ?? new \DateTime();

        $workDaysLeft = [];
        if ($begin < $now && $end > $now) {
            $workDaysLeft = $this->absenceService->calculateWorkdays($now, $end, $this->location,
                array_merge($this->dev,
                    $this->pm, $this->tl));
        }
        $workDaysLeftDevs = $this->removeKeys($workDaysLeft, $this->pm);
        $workDaysLeftDevsWithoutTL = $this->removeKeys($workDaysLeft, array_merge($this->pm, $this->tl));
        $workDaysLeftPM = $this->removeKeys($workDaysLeft, array_merge($this->tl, $this->dev));

        $workDays = $this->absenceService->calculateWorkdays($begin, $end, $this->location,
            array_merge($this->dev, $this->pm, $this->tl));
        $workDaysDevs = $this->removeKeys($workDays, $this->pm);
        $workDaysDevsWithoutTL = $this->removeKeys($workDays, array_merge($this->pm, $this->tl));
        $workDaysPM = $this->removeKeys($workDays, array_merge($this->tl, $this->dev));

        $workflow = $this->alfredTemplate->buildTemplate($begin, $end, $workDaysDevs, $workDaysDevsWithoutTL,
            $workDaysPM, $workDays,
            $workDaysLeftDevs, $workDaysLeftDevsWithoutTL, $workDaysLeftPM, $workDaysLeft);
        return $workflow;
    }

    protected function removeKeys(array $assocArray, array $removeKeys): array
    {
        $workDaysLeftDevs = array_filter($assocArray, function ($name) use ($removeKeys) {
            return !in_array($name, $removeKeys);
        }, ARRAY_FILTER_USE_KEY);
        return $workDaysLeftDevs;
    }
}
