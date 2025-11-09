<?php

declare(strict_types=1);

namespace dev\xchillz\npc\command\impl\argument;

use dev\xchillz\npc\command\Argument;
use dev\xchillz\npc\entity\NPC;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\Player;

final class NpcSkinArgument extends Argument {

    public function onPlayerExecute(
        Player $player, 
        string $commandLabel, 
        string $argumentLabel, 
        array $args
    ) {
        if (!isset($args[0])) {
            $player->sendMessage($this->getMessagesConfig()->getMessage('npc.edit.skin.usage', []));
            return;
        }

        $npc = null;
        $npcId = $args[0];
        foreach ($player->getLevel()->getEntities() as $entity) {
            if (!($entity instanceof NPC)) {
                continue;
            }

            if ($entity->getNpcUniqueId() === $npcId) {
                $npc = $entity;
                break;
            }
        }

        if ($npc === null) {
            $player->sendMessage($this->getMessagesConfig()->getMessage('npc.remove.notfound', ['id' => $npcId]));
            return;
        }
        
        $npc->updateSkin($player->getSkinId(), $player->getSkinData());

        $player->sendMessage($this->getMessagesConfig()->getMessage('npc.edit.skin.success', ['id' => $npcId]));
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