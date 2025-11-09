<?php

declare(strict_types=1);

namespace dev\xchillz\npc\command\impl\argument;

use dev\xchillz\npc\command\Argument;
use dev\xchillz\npc\entity\NPC;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\Player;

final class NpcRemoveArgument extends Argument {

    public function onPlayerExecute(
        Player $player, 
        string $commandLabel, 
        string $argumentLabel, 
        array $args
    ) {
        if (!isset($args[0])) {
            $player->sendMessage($this->getMessagesConfig()->getMessage('npc.remove.usage', []));
            return;
        }

        $npcId = $args[0];
        foreach ($player->getLevel()->getEntities() as $entity) {
            if (!($entity instanceof NPC)) {
                continue;
            }

            if ($entity->getNpcUniqueId() === $npcId) {
                $entity->close();
                $player->sendMessage($this->getMessagesConfig()->getMessage('npc.remove.success', ['id' => $npcId]));
                return;
            }
        }

        $player->sendMessage($this->getMessagesConfig()->getMessage('npc.remove.notfound', ['id' => $npcId]));
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