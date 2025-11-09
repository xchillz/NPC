<?php

declare(strict_types=1);

namespace dev\xchillz\npc\utils;

use pocketmine\level\Location;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;

final class NBTHelper {

    public static function createBaseNpcNBT(
        string $nameTag, 
        Location $location, 
        string $skinName, 
        string $skinData, 
        array $playerCommands,
        array $serverCommands,
        bool $invisible
    ): CompoundTag {
        $nameTagLines = [];
        $rawNameTagLines = array_reverse(explode("{n}", $nameTag));
        $actualNameTag = array_shift($rawNameTagLines);
        foreach ($rawNameTagLines as $rawNameTagLine) {
            $nameTagLines[] = new StringTag('', $rawNameTagLine);
        }

        $compoundTag = self::createBaseNBT(
            $actualNameTag,
            $location, 
            $skinName, 
            $skinData, 
            $playerCommands, 
            $serverCommands, 
            $invisible);
        $compoundTag->offsetSet('Lines', new ListTag('Lines', $nameTagLines));
        return $compoundTag;
    }

    public static function createBaseLineNBT(string $nameTag, Location $location): CompoundTag {
        return self::createBaseNBT(
            $nameTag,
            $location,
            '',
            '',
            [],
            [],
            true
        );
    }

    private static function createBaseNBT(
        string $name, 
        Location $location, 
        string $skinName, 
        string $skinData, 
        array $playerCommands,
        array $serverCommands,
        bool $invisible
    ): CompoundTag {
        $compoundTag = new CompoundTag();
        $compoundTag->offsetSet('CustomName', new StringTag('CustomName', $name));
        $compoundTag->offsetSet('RawName', new StringTag('RawName', $name));
        $compoundTag->offsetSet('Health', new FloatTag('Health', 1.0));
        $compoundTag->offsetSet('Inventory', new ListTag('Inventory', []));
        $compoundTag->offsetSet('Pos', new ListTag('Pos', [
            new DoubleTag('', $location->getFloorX() + 0.5),
            new DoubleTag('', $location->getY()),
            new DoubleTag('', $location->getFloorZ() + 0.5)]));
        $compoundTag->offsetSet('Rotation', new ListTag('Rotation', [
            new FloatTag('', $location->getYaw()),
            new FloatTag('', $location->getPitch())]));
        $compoundTag->offsetSet('Motion', new ListTag('Motion', [
            new DoubleTag('', 0),
            new DoubleTag('', 0),
            new DoubleTag('', 0)]));
        $compoundTag->offsetSet('Skin', new CompoundTag('', [
            new StringTag('Name', $skinName),
            new StringTag('Data', $skinData)]));
        $compoundTag->offsetSet(
            'PlayerCommands',
            self::getArrayAsListStringTag('PlayerCommands', $playerCommands));
        $compoundTag->offsetSet(
            'ServerCommands',
            self::getArrayAsListStringTag('ServerCommands', $serverCommands));
        $compoundTag->offsetSet('Worlds', new ListTag('Worlds', []));
        $compoundTag->offsetSet('Invisible', new StringTag('Invisible', $invisible ? 'true' : 'false'));
        return $compoundTag;
    }
    
    private static function getArrayAsListStringTag(string $name, array $array): array {
        $data = [];
        foreach ($array as $value) {
            $data[] = new StringTag('', $value);
        }

        return $data;
    }
}