<?php

declare(strict_types=1);

namespace dev\xchillz\npc\command;

use dev\xchillz\npc\config\MessagesConfig;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\Player;

abstract class Argument {

    /** @var string */
    private $id;
    /** @var array<int, string> */
    private $aliases;
    /** @var MessagesConfig */
    private $messagesConfig;

    public function __construct(
        string $id, 
        array $aliases, 
        MessagesConfig $messagesConfig
    ) {
        $this->id = $id;
        $this->aliases = $aliases;
        $this->messagesConfig = $messagesConfig;
    }

    public final function getId(): string {
        return $this->id;
    }

    public final function getAliases(): array {
        return $this->aliases;
    }

    public final function getMessagesConfig(): MessagesConfig {
        return $this->messagesConfig;
    }

    public abstract function onPlayerExecute(
        Player $player,
        string $commandLabel,
        string $argumentLabel,
        array $args
    );

    public abstract function onConsoleExecute(
        ConsoleCommandSender $sender,
        string $commandLabel,
        string $argumentLabel,
        array $args
    );
}