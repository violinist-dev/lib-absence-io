[![Build Status](https://travis-ci.org/dpeuscher/lib-absence-io.svg?branch=master)](https://travis-ci.org/dpeuscher/lib-absence-io) [![codecov](https://codecov.io/gh/dpeuscher/lib-absence-io/branch/master/graph/badge.svg)](https://codecov.io/gh/dpeuscher/lib-absence-io)
[![FOSSA Status](https://app.fossa.io/api/projects/git%2Bgithub.com%2Fdpeuscher%2Flib-absence-io.svg?type=shield)](https://app.fossa.io/projects/git%2Bgithub.com%2Fdpeuscher%2Flib-absence-io?ref=badge_shield)
# lib-absence-io

A library to identify the team capacity for a certain team size by member

```php
$absenceEndPoint = 'https://app.absence.io/api/v2/';
$absenceKey = '012345678901234567890123';
$absenceId = '0123456789012345678901234567890123456789012345678901234567890123';

$absenceService = new AbsenceService($absenceEndPoint, $absenceKey, $absenceId);
$team = ["John Doe", "Johny Doey"];
$from = \DateTime::createFromFormat('Y-m-d', '2018-07-01');
$to = \DateTime::createFromFormat('Y-m-d', '2018-10-01');
var_dump($absenceService->calculateWorkdays($from, $to, 'München', $team));
```
Result looks like this:
> array(2) {
    'Johny Doey' =>
    int(46)
    'John Doe' =>
    int(55)
  }


## License
[![FOSSA Status](https://app.fossa.io/api/projects/git%2Bgithub.com%2Fdpeuscher%2Flib-absence-io.svg?type=large)](https://app.fossa.io/projects/git%2Bgithub.com%2Fdpeuscher%2Flib-absence-io?ref=badge_large)