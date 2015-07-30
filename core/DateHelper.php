<?php
namespace Bro\core;


class DateHelper
{
    public static function changeTimeZone($date, $timeZone)
    {
        $date = new \DateTime($date);
        $date->setTimezone(new \DateTimeZone($timeZone));
        return $date->format("Y-m-d H:i:sP'");
    }
}