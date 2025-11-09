<?php

declare(strict_types=1);

namespace dev\xchillz\npc;

use dev\xchillz\npc\command\impl\NpcCommand;
use dev\xchillz\npc\config\MessagesConfig;
use dev\xchillz\npc\entity\NPC;
use dev\xchillz\npc\entity\NPCLine;
use pocketmine\entity\Entity;
use pocketmine\plugin\PluginBase;

final class NPCPlugin extends PluginBase {

    public function onLoad() {
        Entity::registerEntity(NPC::class, true);
        Entity::registerEntity(NPCLine::class, true);

        $this->saveResource('messages.yml', !$this->isPhar());
    }

    public function onEnable() {
        $messagesConfig = MessagesConfig::wrap($this->getDataFolder() . 'messages.yml');
        $this->getServer()->getCommandMap()->registerAll('npc', [
            new NpcCommand($messagesConfig)
        ]);
    }
}