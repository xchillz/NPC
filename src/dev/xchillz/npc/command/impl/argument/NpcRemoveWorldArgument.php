<?php

namespace dev\xchillz\npc\command\impl\argument;

use dev\xchillz\npc\command\Argument;
use dev\xchillz\npc\entity\NPC;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\Player;
use pocketmine\Server;

final class NpcRemoveWorldArgument extends Argument {

    public function onPlayerExecute(
        Player $player, 
        string $commandLabel, 
        string $argumentLabel, 
        array $args
    ) {
        if (!isset($args[0], $args[1])) {
            $player->sendMessage($this->getMessagesConfig()->getMessage('npc.remove.world.usage', []));
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

        $worldId = implode(" ", array_slice($args, 1));
        if (trim($worldId) === "") {
            $player->sendMessage($this->getMessagesConfig()->getMessage('npc.world.notfound', ['world' => $worldId]));
            return;
        }

        Server::getInstance()->loadLevel($worldId);
        $world = Server::getInstance()->getLevelByName($worldId);
        if ($world === null) {
            $player->sendMessage($this->getMessagesConfig()->getMessage('npc.world.notfound', ['world' => $worldId]));
            return;
        }
        $npc->removeWorld($worldId);
        
        $player->sendMessage($this->getMessagesConfig()->getMessage('npc.remove.world.success', ['id' => $npcId, 'world' => $worldId]));
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