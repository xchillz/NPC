<?php

declare(strict_types=1);

namespace dev\xchillz\npc\command;

use dev\xchillz\npc\config\MessagesConfig;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\Player;

abstract class Command extends \pocketmine\command\Command {

    /** @var MessagesConfig */
    private $messagesConfig;
    /** @var array<string, Argument> */
    private $arguments;

	public function __construct(
        string $name,
        string $description, 
        string $usageMessage, 
        array $aliases,
        MessagesConfig $messagesConfig
    ) {
        parent::__construct($name, $description, $usageMessage, $aliases);
        $this->messagesConfig = $messagesConfig;
    }

    public final function registerArgument(Argument $argument) {
        $this->arguments[$argument->getId()] = $argument;
        foreach ($argument->getAliases() as $alias) {
            $this->arguments[$alias] = $argument;
        }
    }

    public final function getArgument(string $id) {
        return $this->arguments[$id] ?? null;
    }

    public function execute(CommandSender $sender, $commandLabel, array $args) {
        if ($sender instanceof Player) {
            if (!$this->testPermission($sender)) {
                return;
            }
            $this->onPlayerExecute($sender, $commandLabel, $args);
            return;
        }

        if ($sender instanceof ConsoleCommandSender) {
            $this->onConsoleExecute($sender, $commandLabel, $args);
            return;
        }

        $sender->sendMessage($this->getMessagesConfig()->getMessage('unknown.sender', []));
    }

    public abstract function onPlayerExecute(
        Player $player,
        string $commandLabel, 
        array $args
    );

    public abstract function onConsoleExecute(ConsoleCommandSender $sender, 
        string $commandLabel, 
        array $args
    );

    public final function getMessagesConfig(): MessagesConfig {
        return $this->messagesConfig;
    }
}