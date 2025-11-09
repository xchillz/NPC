<?php

declare(strict_types=1);

namespace dev\xchillz\npc\command\impl\argument;

use dev\xchillz\npc\command\Argument;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\Player;

final class NpcHelpArgument extends Argument {

    public function onPlayerExecute(
        Player $player, 
        string $commandLabel, 
        string $argumentLabel, 
        array $args
    ) {
        $player->sendMessage($this->getMessagesConfig()->getMessage('command.npc.help', []));
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