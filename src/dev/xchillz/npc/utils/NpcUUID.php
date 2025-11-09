<?php

declare(strict_types=1);

namespace dev\xchillz\npc\utils;

final class NpcUUID {

    private static $npcCounter = 0;

    public static function generate(): string {
        self::$npcCounter++;

        $counter = strtoupper(base_convert(strval(self::$npcCounter), 10, 36));

        $rand1 = strtoupper(base_convert(strval(mt_rand(0, 35)), 10, 36));
        $rand2 = strtoupper(base_convert(strval(mt_rand(0, 35)), 10, 36));

        return $counter . $rand1 . $rand2;
    }
}