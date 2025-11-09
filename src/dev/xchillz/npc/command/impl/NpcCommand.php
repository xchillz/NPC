<?php

declare(strict_types=1);

namespace dev\xchillz\npc\command\impl;

use dev\xchillz\npc\command\Command;
use dev\xchillz\npc\command\impl\argument\NpcAddArgument;
use dev\xchillz\npc\command\impl\argument\NpcAddCommandArgument;
use dev\xchillz\npc\command\impl\argument\NpcAddLineArgument;
use dev\xchillz\npc\command\impl\argument\NpcEditLineArgument;
use dev\xchillz\npc\command\impl\argument\NpcHelpArgument;
use dev\xchillz\npc\command\impl\argument\NpcInvisibleArgument;
use dev\xchillz\npc\command\impl\argument\NpcRemoveArgument;
use dev\xchillz\npc\command\impl\argument\NpcRemoveCommandArgument;
use dev\xchillz\npc\command\impl\argument\NpcRemoveLineArgument;
use dev\xchillz\npc\command\impl\argument\NpcSkinArgument;
use dev\xchillz\npc\command\impl\argument\NpcVisibleArgument;
use dev\xchillz\npc\config\MessagesConfig;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\Player;

final class NpcCommand extends Command {

    public function __construct(MessagesConfig $messagesConfig) {
        parent::__construct(
            'npc',
            'Maneja los NPCs del servidor.',
            '/npc ayuda',
            ['slapper'],
            $messagesConfig
        );
        $this->setPermission('npc.command');
        $this->registerArgument(new NpcAddArgument('add', [
            'añadir', 
            'adicionar'
        ], $messagesConfig));
        $this->registerArgument(new NpcRemoveArgument('remove', [
            'delete', 
            'eliminar'
        ], $messagesConfig));
        $this->registerArgument(new NpcAddLineArgument('addline', [
            'añadirlinea', 
            'newline'
        ], $messagesConfig));
        $this->registerArgument(new NpcRemoveLineArgument('removeline', [
            'eliminarlinea', 
            'delline', 
            'sacarlinea',
            'deline'
        ], $messagesConfig));
        $this->registerArgument(new NpcEditLineArgument('editline', [
            'editarlinea'
        ], $messagesConfig));
        $this->registerArgument(new NpcSkinArgument('editskin', [
            'skin', 
            'cambiarskin', 
            'changeskin', 
            'mudarskin'
        ], $messagesConfig));
        $this->registerArgument(new NpcVisibleArgument('visible', [], $messagesConfig));
        $this->registerArgument(new NpcInvisibleArgument('invisible', [], $messagesConfig));
        $this->registerArgument(new NpcAddCommandArgument('addcommand', [
            'añadircomando', 
            'addcmd', 
            'adicionarcomando',
            'addcommand'
        ], $messagesConfig));
        $this->registerArgument(new NpcRemoveCommandArgument('removecommand', [
            'eliminarcomando', 
            'delcmd', 
            'removercomando', 
            'removecommand',
            'deletecmd',
            'deletecommand'
        ], $messagesConfig));
        $this->registerArgument(new NpcHelpArgument('help', [
            'ayuda', 
            'help', 
            'ajuda',
            '?'
        ], $messagesConfig));
    }

    public function onPlayerExecute(
        Player $player, 
        string $commandLabel, 
        array $args
    ) {
        if (!isset($args[0])) {
            $player->sendMessage($this->getMessagesConfig()->getMessage('command.usage', ['usage' => $this->getUsage()]));
            return;
        }

        $argument = $this->getArgument($args[0]);
        if ($argument === null) {
            $player->sendMessage($this->getMessagesConfig()->getMessage('command.usage', ['usage' => $this->getUsage()]));
            return;
        }
        $argument->onPlayerExecute($player, $commandLabel, $args[0], array_slice($args, 1));
    }

    public function onConsoleExecute(
        ConsoleCommandSender $sender, 
        string $commandLabel, 
        array $args
    ) {
        if (!isset($args[0])) {
            $sender->sendMessage($this->getMessagesConfig()->getMessage('command.usage', ['usage' => $this->getUsage()]));
            return;
        }

        $argument = $this->getArgument($args[0]);
        if ($argument === null) {
            $sender->sendMessage($this->getMessagesConfig()->getMessage('command.usage', ['usage' => $this->getUsage()]));
            return;
        }
        $argument->onConsoleExecute($sender, $commandLabel, $args[0], array_slice($args, 1));
    }
}