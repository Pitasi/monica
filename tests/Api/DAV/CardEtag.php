<?php

namespace Tests\Api\DAV;

use App\Models\Contact\Task;
use App\Models\Contact\Contact;
use App\Models\Instance\SpecialDate;

trait CardEtag
{
    protected function getEtag($obj)
    {
        $data = '';
        if ($obj instanceof Contact) {
            $data = $this->getCard($obj, true);
        } elseif ($obj instanceof SpecialDate) {
            $data = $this->getCal($obj, true);
        } elseif ($obj instanceof Task) {
            $data = $this->getVTodo($obj, true);
        }

        return md5($data);
    }

    protected function getCard(Contact $contact, bool $realFormat = false): string
    {
        $url = route('people.show', $contact);
        $sabreversion = \Sabre\VObject\Version::VERSION;

        $data = "BEGIN:VCARD
VERSION:4.0
PRODID:-//Sabre//Sabre VObject {$sabreversion}//EN
UID:{$contact->uuid}
SOURCE:{$url}
FN:{$contact->name}
N:{$contact->last_name};{$contact->first_name};{$contact->middle_name};;
GENDER:O;
END:VCARD
";

        if ($realFormat) {
            $data = mb_ereg_replace("\n", "\r\n", $data);
        }

        return $data;
    }

    protected function getCal(SpecialDate $specialDate, bool $realFormat = false): string
    {
        $contact = $specialDate->contact;
        $url = route('people.show', $contact);
        $description = "See {$contact->name}’s profile: {$url}";
        $description1 = mb_substr($description, 0, 61);
        $description2 = mb_substr($description, 61);

        $sabreversion = \Sabre\VObject\Version::VERSION;
        $timestamp = $specialDate->created_at->format('Ymd\THis\Z');

        $start = $specialDate->date->format('Ymd');
        $end = $specialDate->date->addDays(1)->format('Ymd');

        $data = "BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject {$sabreversion}//EN
CALSCALE:GREGORIAN
UID:{$specialDate->uuid}
BEGIN:VTIMEZONE
TZID:UTC
END:VTIMEZONE
BEGIN:VEVENT
UID:{$specialDate->uuid}
DTSTAMP:{$timestamp}
SUMMARY:Birthday of {$contact->name}
DTSTART:{$start}
DTEND:{$end}
RRULE:FREQ=YEARLY
CREATED:{$timestamp}
DESCRIPTION:{$description1}
 {$description2}
END:VEVENT
END:VCALENDAR
";

        if ($realFormat) {
            $data = mb_ereg_replace("\n", "\r\n", $data);
        }

        return $data;
    }

    protected function getVTodo(Task $task, bool $realFormat = false): string
    {
        $sabreversion = \Sabre\VObject\Version::VERSION;
        $timestamp = $task->created_at->format('Ymd\THis\Z');
        $contact = $task->contact;

        $data = "BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject {$sabreversion}//EN
CALSCALE:GREGORIAN
BEGIN:VTIMEZONE
TZID:UTC
END:VTIMEZONE
BEGIN:VTODO
UID:{$task->uuid}
DTSTAMP:{$timestamp}
SUMMARY:{$task->title}
CREATED:{$timestamp}
DESCRIPTION:{$task->description}
";
        if ($contact) {
            $url = route('people.show', $contact);
            $data .= "ATTACH:{$url}
";
        }
        $data .= 'END:VTODO
END:VCALENDAR
';

        if ($realFormat) {
            $data = mb_ereg_replace("\n", "\r\n", $data);
        }

        return $data;
    }
}
