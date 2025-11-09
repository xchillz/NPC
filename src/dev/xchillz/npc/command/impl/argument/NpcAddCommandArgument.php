<?php

declare(strict_types=1);

namespace dev\xchillz\npc\command\impl\argument;

use dev\xchillz\npc\command\Argument;
use dev\xchillz\npc\entity\NPC;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\Player;

final class NpcAddCommandArgument extends Argument {

    public function onPlayerExecute(
        Player $player, 
        string $commandLabel, 
        string $argumentLabel, 
        array $args
    ) {
        if (!isset($args[0], $args[1], $args[2])) {
            $player->sendMessage($this->getMessagesConfig()->getMessage('npc.add.command.usage', []));
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

        if ($args[1] !== 'player' && $args[1] !== 'server') {
            $player->sendMessage($this->getMessagesConfig()->getMessage('npc.add.command.usage', []));
            return;
        }

        $command = implode(" ", array_slice($args, 2));
        if ($args[1] === 'player') {
            $npc->addPlayerCommand($command);
        } else {
            $npc->addServerCommand($command);
        }

        $player->sendMessage($this->getMessagesConfig()->getMessage('npc.add.command.success', ['id' => $npcId, 'command' => $command]));
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