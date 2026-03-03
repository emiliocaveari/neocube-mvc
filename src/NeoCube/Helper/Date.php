<?php

namespace NeoCube\Helper;

use DateInterval;
use DateTime;

class Date {

    static public function dateFormat(string &$date): bool {
        if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $date)) {
            return true;
        } else if (preg_match('/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$/', $date)) {
            $date = implode('-', array_reverse(explode('/', $date)));
            return true;
        } else if (preg_match('/^[0-9]{2}-[0-9]{2}-[0-9]{4}$/', $date)) {
            $date = implode('-', array_reverse(explode('-', $date)));
            return true;
        } else {
            $date = null;
            return false;
        }
    }

    static public function timeFormat(string &$time): bool {
        if (strlen($time) == 5) $time = $time .= ':00';
        if (preg_match('/^[012]{1}[0-9]{1}:[012345]{1}[0-9]{1}:[012345]{1}[0-9]{1}$/', $time)) {
            return true;
        } else {
            $time = null;
            return false;
        }
    }

    static public function dateTimeFormat(string &$datetime): bool {
        $date = substr($datetime, 0, 10);
        $time = substr($datetime, 11);
        $datetime = null;

        if (empty($time)) $time = '00:00:00';
        else if (!static::timeFormat($time)) return false;

        if (!static::dateFormat($date)) return false;

        $datetime = $date . ' ' . $time;
        return true;
    }

    static public function dateTimeDiff(string|DateTime $dateBegin, string|DateTime $dateEnd = 'now', string $return = DateDiffReturn::INTERVAL): int|DateInterval|false {

        try {
            if (! $dateBegin instanceof DateTime)
                $dateBegin = new DateTime($dateBegin);
            if (! $dateEnd instanceof DateTime)
                $dateEnd = new DateTime($dateEnd);
        } catch (\Throwable $e) {
            return false;
        }

        $interval = $dateBegin->diff($dateEnd);

        return match ($return) {
            DateDiffReturn::INTERVAL => $interval,
            DateDiffReturn::YEAR     => $interval->y,
            DateDiffReturn::MONTH    => ($interval->y * 12) + $interval->m,
            DateDiffReturn::DAY      => $interval->days,
            DateDiffReturn::HOUR     => ($interval->days * 24) + $interval->h,
            DateDiffReturn::MINUTE   => ((($interval->days * 24) + $interval->h) * 60) + $interval->i,
            DateDiffReturn::SECOND   => ((((($interval->days * 24) + $interval->h) * 60) + $interval->i) * 60) + $interval->s,
            default => false
        };
    }

}
