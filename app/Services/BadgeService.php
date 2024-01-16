<?php

namespace App\Services;

class BadgeService
{
    public static function getBadges()
    {
        return [
            'Beginner' => 0,
            'Intermediate' => 4,
            'Advanced' => 8,
            'Master' => 10
        ];
    }
}
