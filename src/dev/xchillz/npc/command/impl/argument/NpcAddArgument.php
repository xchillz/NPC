<?php

declare(strict_types=1);

namespace dev\xchillz\npc\command\impl\argument;

use dev\xchillz\npc\command\Argument;
use dev\xchillz\npc\entity\NPC;
use dev\xchillz\npc\utils\NBTHelper;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\level\Location;
use pocketmine\math\Vector3;
use pocketmine\Player;

final class NpcAddArgument extends Argument {

    public function onPlayerExecute(
        Player $player, 
        string $commandLabel, 
        string $argumentLabel, 
        array $args
    ) {
        if (!isset($args[0], $args[1])) {
            $player->sendMessage($this->getMessagesConfig()->getMessage('npc.add.usage', []));
            return;
        }

        $type = strtolower($args[0]);
        if ($type !== 'invisible' && $type !== 'visible') {
            $player->sendMessage($this->getMessagesConfig()->getMessage('npc.add.usage', []));
            return;
        }

        $nameTag = implode(' ', array_slice($args, 1));
        $npc = new NPC(
            $player->getLevel()->getChunk($player->getFloorX() >> 4, $player->getFloorZ() >> 4),
            NBTHelper::createBaseNpcNBT(
                $nameTag,
                Location::fromObject(
                    new Vector3(
                        $player->getX(),
                        $player->getY(),
                        $player->getZ()
                    ),
                    null,
                    $player->getYaw(),
                    $player->getPitch()
                ),
                $player->getSkinId(),
                $player->getSkinData(),
                [],
                [],
                $type === 'invisible'
            )
        );
        $npc->spawnToAll();

        $player->sendMessage($this->getMessagesConfig()->getMessage('npc.add.success', ['id' => $npc->getNpcUniqueId()]));
    }

    public function onConsoleExecute(
        ConsoleCommandSender $sender, 
        string $commandLabel, 
        string $argumentLabel, 
        array $args
    ) {
        $sender->sendMessage($this->getMessagesConfig()->getMessage('run.only.player', []));
    }
}