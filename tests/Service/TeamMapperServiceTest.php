<?php

namespace Dpeuscher\AbsenceIo\Tests\Service;

use Alfred\Workflows\Workflow;
use DateTime;
use Dpeuscher\AbsenceIo\Alfred\AlfredTemplate;
use Dpeuscher\AbsenceIo\Service\AbsenceService;
use Dpeuscher\AbsenceIo\Service\TeamMapperService;
use Dpeuscher\AlfredSymfonyTools\Alfred\WorkflowHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @category  lib-absence-io
 * @copyright Copyright (c) 2018 Dominik Peuscher
 * @covers \Dpeuscher\AbsenceIo\Service\TeamMapperService
 * @covers \Dpeuscher\AbsenceIo\Alfred\AlfredTemplate
 * @covers \Dpeuscher\AbsenceIo\Service\AbsenceService
 */
class TeamMapperServiceTest extends TestCase
{
    /**
     * @var TeamMapperService
     */
    protected $sut;

    /**
     * @var array
     */
    protected $activeMock = ['post_locations.json'];

    /**
     * @var string
     */
    protected $fixtureFolder;

    /**
     * @var \DateTime
     */
    protected $from;

    /**
     * @var \DateTime
     */
    protected $to;

    /**
     * @var \DateTime
     */
    protected $currentBefore;

    /**
     * @var \DateTime
     */
    protected $currentInBetween;

    /**
     * @var \DateTime
     */
    protected $currentAfter;

    public function setup()
    {
        $this->fixtureFolder = realpath(dirname(__DIR__) . '/fixtures/');

        $this->from = DateTime::createFromFormat('Y-m-d', '2018-04-30');
        $this->to = DateTime::createFromFormat('Y-m-d', '2018-05-10');
        $this->currentBefore = DateTime::createFromFormat('Y-m-d', '2018-04-15');
        $this->currentAfter = DateTime::createFromFormat('Y-m-d', '2018-05-15');
        $this->currentInBetween = DateTime::createFromFormat('Y-m-d', '2018-05-04');

        /** @var MockObject|AbsenceService $absenceService */
        $absenceService = $this->getMockBuilder(AbsenceService::class)->setConstructorArgs([
            '',
            '',
            '',
        ])->setMethods(['__construct', 'calculateWorkdays'])->getMock();

        $alfredTemplate = new AlfredTemplate(new WorkflowHelper('/tmp/', new Workflow()));

        $this->sut = new TeamMapperService(['John Doe', 'Johny Doey'], ['TLJohn TLDoe'], ['PMJohn PMDoe'], 'München',
            $absenceService, $alfredTemplate);

        $absenceService->expects($this->any())
            ->method('calculateWorkdays')
            ->willReturnMap([
                [$this->from, $this->to, 'München', ['John Doe', 'Johny Doey'], ['John Doe' => 5, 'Johny Doey' => 4]],
                [$this->from, $this->to, 'München', ['TLJohn TLDoe'], ['TLJohn TLDoe' => 2]],
                [$this->from, $this->to, 'München', ['PMJohn PMDoe'], ['PMJohn PMDoe' => 3]],
                [
                    $this->from,
                    $this->to,
                    'München',
                    ['John Doe', 'Johny Doey', 'TLJohn TLDoe', 'PMJohn PMDoe'],
                    ['John Doe' => 5, 'Johny Doey' => 4, 'TLJohn TLDoe' => 2, 'PMJohn PMDoe' => 3,],
                ],
                [
                    $this->currentBefore,
                    $this->to,
                    'München',
                    ['John Doe', 'Johny Doey'],
                    ['John Doe' => 5, 'Johny Doey' => 4],
                ],
                [$this->currentBefore, $this->to, 'München', ['TLJohn TLDoe'], ['TLJohn TLDoe' => 2]],
                [$this->currentBefore, $this->to, 'München', ['PMJohn PMDoe'], ['PMJohn PMDoe' => 3]],
                [
                    $this->currentBefore,
                    $this->to,
                    'München',
                    ['John Doe', 'Johny Doey', 'TLJohn TLDoe', 'PMJohn PMDoe'],
                    ['John Doe' => 5, 'Johny Doey' => 4, 'TLJohn TLDoe' => 2, 'PMJohn PMDoe' => 3,],
                ],
                [
                    $this->currentAfter,
                    $this->to,
                    'München',
                    ['John Doe', 'Johny Doey'],
                    ['John Doe' => 0, 'Johny Doey' => 0],
                ],
                [$this->currentAfter, $this->to, 'München', ['TLJohn TLDoe'], ['TLJohn TLDoe' => 0]],
                [$this->currentAfter, $this->to, 'München', ['PMJohn PMDoe'], ['PMJohn PMDoe' => 0]],
                [
                    $this->currentAfter,
                    $this->to,
                    'München',
                    ['John Doe', 'Johny Doey', 'TLJohn TLDoe', 'PMJohn PMDoe'],
                    ['John Doe' => 0, 'Johny Doey' => 0, 'TLJohn TLDoe' => 0, 'PMJohn PMDoe' => 0,],
                ],
                [
                    $this->currentInBetween,
                    $this->to,
                    'München',
                    ['John Doe', 'Johny Doey'],
                    ['John Doe' => 2, 'Johny Doey' => 2],
                ],
                [$this->currentInBetween, $this->to, 'München', ['TLJohn TLDoe'], ['TLJohn TLDoe' => 2]],
                [$this->currentInBetween, $this->to, 'München', ['PMJohn PMDoe'], ['PMJohn PMDoe' => 2]],
                [
                    $this->currentInBetween,
                    $this->to,
                    'München',
                    ['John Doe', 'Johny Doey', 'TLJohn TLDoe', 'PMJohn PMDoe'],
                    ['John Doe' => 2, 'Johny Doey' => 2, 'TLJohn TLDoe' => 2, 'PMJohn PMDoe' => 2,],
                ],
            ]);
    }

    /**
     * @throws \Exception
     */
    public function testShowCorrectDateTimeWithCurrentBefore()
    {
        $expectedJson = 'check_team_availability_with_current_before.json';
        $json = $this->sut->checkTeamAvailability($this->from, $this->to, $this->currentBefore);
        $this->assertJsonStringEqualsJsonFile($this->fixtureFolder . '/' . $expectedJson, $json);
    }

    /**
     * @throws \Exception
     */
    public function testShowCorrectDateTimeCurrentAfter()
    {
        $expectedJson = 'check_team_availability_with_current_after.json';
        $json = $this->sut->checkTeamAvailability($this->from, $this->to, $this->currentAfter);
        $this->assertJsonStringEqualsJsonFile($this->fixtureFolder . '/' . $expectedJson, $json);
    }

    /**
     * @throws \Exception
     */
    public function testShowCorrectDateTimeCurrentInBetween()
    {
        $expectedJson = 'check_team_availability_with_current_in_between.json';
        $json = $this->sut->checkTeamAvailability($this->from, $this->to, $this->currentInBetween);
        $this->assertJsonStringEqualsJsonFile($this->fixtureFolder . '/' . $expectedJson, $json);
    }
}
