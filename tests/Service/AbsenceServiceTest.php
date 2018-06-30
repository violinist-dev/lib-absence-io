<?php

namespace Dpeuscher\AbsenceIo\Tests\Service;

use Dpeuscher\AbsenceIo\Service\AbsenceService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @category  lib-absence-io
 * @copyright Copyright (c) 2018 Dominik Peuscher
 * @covers \Dpeuscher\AbsenceIo\Service\AbsenceService
 */
class AbsenceServiceTest extends TestCase
{
    /**
     * @var AbsenceService
     */
    protected $sut;

    /**
     * @var AbsenceService|MockObject
     */
    protected $mock;

    /**
     * @var array
     */
    protected $activeMock = ['post_locations.json'];

    /**
     * @var string
     */
    protected $fixtureFolder;

    public function setup()
    {
        $this->fixtureFolder = realpath(dirname(__DIR__) . '/fixtures/');
        $this->sut = new AbsenceService('', '', '');

        $this->mock = $this->getMockBuilder(AbsenceService::class)->setConstructorArgs(['', '', ''])
            ->setMethods(['executeCall'])->getMock();
        $this->mock->expects($this->any())
            ->method('executeCall')
            ->withAnyParameters()
            ->will($this->returnCallback(
                function (string $uri, array $fields, string $method) {
                    $mockFile = array_shift($this->activeMock);
                    $data = json_decode(file_get_contents($this->fixtureFolder . '/' . $mockFile),
                        JSON_OBJECT_AS_ARRAY);
                    if ($data['uri'] === $uri && $data['fields'] === $fields && $data['method'] === $method) {
                        return [$data['response'], $data['headerSize']];
                    }
                    return [null, null];
                }
            ));
    }

    /**
     * @throws \Exception
     * @expectedException \Exception
     */
    public function testThrowExceptionIfNoResponse()
    {
        $this->activeMock = [];
        $this->mock->getPublicHolidays('2018-01-01', '2018-12-31', 'München');
    }

    public function testGetPublicHolidays()
    {
        $expected = [
            '2018-01-01T00:00:00.000Z' => 1,
            '2018-01-06T00:00:00.000Z' => 1,
            '2018-03-30T00:00:00.000Z' => 1,
            '2018-04-02T00:00:00.000Z' => 1,
            '2018-05-01T00:00:00.000Z' => 1,
            '2018-05-10T00:00:00.000Z' => 1,
            '2018-05-21T00:00:00.000Z' => 1,
            '2018-05-31T00:00:00.000Z' => 1,
            '2018-08-15T00:00:00.000Z' => 1,
            '2018-10-03T00:00:00.000Z' => 1,
            '2018-11-01T00:00:00.000Z' => 1,
            '2018-12-24T00:00:00.000Z' => 0.5,
            '2018-12-25T00:00:00.000Z' => 1,
            '2018-12-26T00:00:00.000Z' => 1,
        ];
        $publicHolidays = null;
        try {
            $this->activeMock = ['post_locations.json'];
            $publicHolidays = $this->mock->getPublicHolidays('2018-01-01', '2018-12-31', 'München');
        } catch (\Exception $e) {
            $this->fail($e);
        }
        $this->assertEquals($expected, $publicHolidays);
    }

    public function testGetWorkingDays()
    {
        $workingDays = null;
        try {
            $workingDays = $this->sut->getWorkingDays('2018-01-01', '2018-12-31');
        } catch (\Exception $e) {
            $this->fail($e);
        }
        $this->assertEquals(260, $workingDays);
    }

    public function testGetAbsentDays()
    {
        $expected = [
            'John Doe'   => 4,
            'Johny Doey' => 16,
        ];
        $absentDays = null;
        try {
            $this->activeMock = ['post_absences.json'];
            $absentDays = $this->mock->getAbsentDays('2018-04-01', '2018-07-01', ['Johny Doey', 'John Doe'], [
                '2018-04-07' => 1,
                '2018-04-10' => 1,
                '2018-05-23' => 0.5,
                '2018-05-31' => 1,
            ]);
        } catch (\Exception $e) {
            $this->fail($e);
        }
        $this->assertEquals($expected, $absentDays);
    }

    public function testCalculateWorkdays()
    {
        $expected = [
            'John Doe'   => 55,
            'Johny Doey' => 46,
        ];
        $absentDays = null;
        try {
            $this->activeMock = ['post_locations.json', 'post_absences.json'];
            $absentDays = $this->mock->calculateWorkdays(new \DateTime('2018-04-01 00:00:00'),
                new \DateTime('2018-07-01'), 'München',
                //['Dominik Peuscher', 'Pawel Oleksy']);
                ['Johny Doey', 'John Doe']);
        } catch (\Exception $e) {
            $this->fail($e);
        }
        $this->assertEquals($expected, $absentDays);
    }

}
