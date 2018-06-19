<?php

namespace Dpeuscher\AbsenceIo\Service;

use Dflydev\Hawk\Client\Client;
use Dflydev\Hawk\Client\ClientBuilder;
use Dflydev\Hawk\Credentials\Credentials;

/**
 * @category  lib-absence-io
 * @copyright Copyright (c) 2018 Dominik Peuscher
 */
class AbsenceService
{
    /**
     * @var Client
     */
    protected $hawkClient;

    /**
     * @var Credentials
     */
    protected $credentials;

    /**
     * @var string
     */
    protected $endPoint;

    /**
     * AbsenceService constructor.
     *
     * @param string $absenceEndPoint
     * @param string $absenceKey
     * @param string $absenceId
     */
    public function __construct(string $absenceEndPoint, string $absenceKey, string $absenceId)
    {
        $this->endPoint = $absenceEndPoint;
        $this->credentials = new Credentials($absenceKey, 'sha256', $absenceId);
        $this->hawkClient = ClientBuilder::create()->build();
    }

    /**
     * @param \DateTime $date
     * @param \DateTime $dateEnd
     * @param $location
     * @param $users
     * @return array
     * @throws \Exception
     */
    public function calculateWorkdays($date, $dateEnd, $location, $users): array
    {
        $workingDays = $this->getWorkingDays($date->format('Y-m-d'), $dateEnd->format('Y-m-d'));
        $holidays = $this->getPublicHolidays($date->format('Y-m-d'), $dateEnd->format('Y-m-d'),
            $location);
        $absentDays = $this->getAbsentDays($date->format('Y-m-d'), $dateEnd->format('Y-m-d'), $users,
            $holidays);
        $workDays = [];

        foreach ($users as $name) {
            $workDays[$name] = max(0, $workingDays - array_sum($holidays) - ($absentDays[$name] ?? 0));
        }
        return $workDays;
    }

    /**
     * @param $dateStart
     * @param $dateEnd
     * @param $names
     * @param array $holidays
     * @return array
     * @throws \Exception
     */
    public function getAbsentDays($dateStart, $dateEnd, $names, $holidays): array
    {
        $begin = new \DateTime($dateStart . "T00:00:00.000Z");
        $end = new \DateTime($dateEnd . "T00:00:00.000Z");
        $method = 'POST';
        $uri = 'absences';
        $namesFormatted = [];
        foreach ($names as $name) {
            $namesFormatted[] = ['firstName' => strtok($name, ' '), 'lastName' => strtok(' ')];
        }
        $fields = [
            'skip'      => '0',
            'limit'     => '50',
            'filter'    => [
                "assignedTo:user._id" => [
                    '$or' => $namesFormatted,
                ],
                "start"               => ['$lte' => $end->format('Y-m-d\TH:i:s') . ".000Z"],
                "end"                 => ['$gte' => $begin->format('Y-m-d\TH:i:s') . ".000Z",],
            ],
            'relations' => ['assignedToId',],
        ];

        $body = $this->call($method, $uri, $fields);
        $absentDays = [];
        foreach ($body['data'] as $entry) {
            $absenceEnd = min($end, new \DateTime($entry['end']));
            $absenceStart = max($begin, new \DateTime($entry['start']));
            $days = $this->getWorkingDays($absenceStart->format('Y-m-d'), $absenceEnd->format('Y-m-d'));
            foreach ($holidays as $dayString => $num) {
                $day = new \DateTime($dayString);
                if ($day >= $absenceStart && $day < $absenceEnd) {
                    $days -= $num;
                }
            }
            $absentDays[$entry['assignedTo']['firstName'] . ' ' . $entry['assignedTo']['lastName']] = $days;
        }
        return $absentDays;
    }

    /**
     * @param $dateStart
     * @param $dateEnd
     * @param $location
     * @return array
     * @throws \Exception
     */
    public function getPublicHolidays($dateStart, $dateEnd, $location): array
    {
        $begin = new \DateTime($dateStart . "T00:00:00.000Z");
        $end = new \DateTime($dateEnd . "T00:00:00.000Z");
        $method = 'POST';
        $uri = 'locations';
        $fields = [
            'skip'      => '0',
            'limit'     => '50',
            'filter'    => [
                "name" => $location,
            ],
            'relations' => ['holidayIds',],
        ];
        $body = $this->call($method, $uri, $fields);
        $days = [];
        foreach ($body['data'][0]['holidays'] as $entry) {
            foreach ($entry['dates'] as $dateString) {
                $date = new \DateTime($dateString);
                if ($date >= $begin && $date < $end) {
                    $days[$dateString] = isset($entry['halfDay']) && $entry['halfDay'] ? .5 : 1;
                }
            }
        }
        return $days;
    }

    /**
     * @param $dateStart
     * @param $dateEnd
     * @return int
     * @throws \Exception
     */
    public function getWorkingDays($dateStart, $dateEnd): int
    {
        $begin = new \DateTime($dateStart . "T00:00:00.000Z");
        $counter = new \DateTime($dateStart . "T00:00:00.000Z");
        $end = new \DateTime($dateEnd . "T00:00:00.000Z");
        $workingDays = 0;
        do {
            if ($counter >= $begin && $counter->format('w') >= 1 && $counter->format('w') <= 5) {
                $workingDays++;
            }
            $counter->add(new \DateInterval('P1D'));
        } while ($counter < $end);
        return $workingDays;
    }

    /**
     * @param $method
     * @param $uri
     * @param $fields
     * @return mixed
     * @throws \Exception
     */
    protected function call($method, $uri, $fields)
    {
        list($response, $header_size) = $this->executeCall($uri, $fields, $method);

        $header = explode("\n", substr($response, 0, $header_size));
        $head = [];
        foreach ($header as $row) {
            if (!strpos($row, ': ')) {
                continue;
            }
            $head[substr($row, 0, strpos($row, ': '))] = trim(substr($row, strpos($row, ': ')));
        }
        $body = json_decode(substr($response, $header_size), JSON_OBJECT_AS_ARRAY);
        if (is_null($body)) {
            throw new \Exception("Error when calling absence.io: Header: " . json_encode($head) . ' - Body: ' . $response);
        }
        return $body;
    }

    /**
     * @param string $uri
     * @param array $fields
     * @param string $method
     * @return array
     */
    protected function executeCall($uri, $fields = [], $method = 'GET'): array
    {
        //@codeCoverageIgnoreStart
        $request = $this->hawkClient->createRequest($this->credentials, $this->endPoint . $uri, $method, $fields);
        $ch = curl_init($this->endPoint . $uri);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            $request->header()->fieldName() . ': ' . $request->header()->fieldValue(),
            'Content-type:application/json',
        ]);
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        }
        $response = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        return [$response, $headerSize];
        //@codeCoverageIgnoreEnd
    }
}
