<?php

namespace App\Enums;

enum SeverityEnum: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function rank(): int
    {
        return match ($this) {
            self::Low => 1,
            self::Medium => 2,
            self::High => 3,
        };
    }

    public static function fromFarmerCount(int $count): self
    {
        return match (true) {
            $count >= 8 => self::High,
            $count >= 5 => self::Medium,
            default => self::Low,
        };
    }
}
