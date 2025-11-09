<?php

declare(strict_types=1);

namespace dev\xchillz\npc\entity;

use dev\xchillz\npc\utils\NBTHelper;
use dev\xchillz\npc\utils\NpcUUID;
use pocketmine\block\BlockIds;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\protocol\AddPlayerPacket;
use pocketmine\network\protocol\PlayerListPacket;
use pocketmine\Player;
use pocketmine\Server;

final class NPC extends Human {

    /** @var string */
    private $npcUniqueId = null;
    /** @var array<int, NPCLine> */
    private $npcLines = [];
    private $lastCachedNameTag = "";

    public function onUpdate($tick) {
        try {
            $rawName = $this->getRawName();
        } catch (\RuntimeException $exception) {
            $this->server->getLogger()->error($exception->getMessage());
            return false;
        }

        $rawName = str_replace('{server_count}', (string)count($this->server->getOnlinePlayers()), $rawName);
        $rawName = str_replace('{time}', date('H:i:s'), $rawName);
        $this->setNameTag($rawName);
        return true;
    }

    public function setNameTag($name) {
        if ($this->lastCachedNameTag === $name) {
            return;
        }
        $noPermissionPlayers = [];
        $permissionPlayers = [];
        foreach ($this->hasSpawned as $player) {
            if ($player->hasPermission('npc.see.id')) {
                $permissionPlayers[] = $player;
            } else {
                $noPermissionPlayers[] = $player;
            }
        }
        $this->sendData($noPermissionPlayers, [
            Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $name]
        ]);
        $this->sendData($permissionPlayers, [
            Entity::DATA_NAMETAG => [
                Entity::DATA_TYPE_STRING, 
                $name . " §7(Id: §f" . $this->getNpcUniqueId() . "§7)"
            ]
        ]);
        $this->lastCachedNameTag = $name;
    }

    public function getRawName(): string {
        if (!$this->namedtag->offsetExists('RawName')) {
            $this->close();
            throw new \RuntimeException("NPC is missing 'RawName' NBT tag and has been removed.");            
        }
        return strval($this->namedtag->offsetGet('RawName'));
    }

    public function attack($damage, EntityDamageEvent $source) {
        if (!($source instanceof EntityDamageByEntityEvent)) {
            return;
        }

        if ($source instanceof EntityDamageByChildEntityEvent) {
            return;
        }

        $damager = $source->getDamager();
        if (!($damager instanceof Player)) {
            return;
        }
        
        $playerCommands = $this->namedtag->offsetGet('PlayerCommands');
        if ($playerCommands instanceof ListTag) {
            foreach ($playerCommands as $playerCommand) {
                if (!($playerCommand instanceof StringTag)) {
                    continue;
                }

                Server::getInstance()
                    ->getCommandMap()
                    ->dispatch(
                        $damager, 
                        str_replace("{player}", $damager->getName(), strval($playerCommand)
                    ));
            }
        }

        $serverCommands = $this->namedtag->offsetGet('ServerCommands');
        if ($serverCommands instanceof ListTag) {
            foreach ($serverCommands as $serverCommand) {
                if (!($serverCommand instanceof StringTag)) {
                    continue;
                }

                Server::getInstance()
                    ->getCommandMap()
                    ->dispatch(
                        new ConsoleCommandSender(),
                        str_replace("{player}", $damager->getName(), strval($serverCommand)
                    ));
            }
        }
    }

    public function spawnTo(Player $player) {
        if (isset($this->hasSpawned[$player->getLoaderId()])) {
            return;
        }

        if (!isset($player->usedChunks[Level::chunkHash($this->chunk->getX(), $this->chunk->getZ())])) {
            return;
        }

        $this->hasSpawned[$player->getLoaderId()] = $player;

        $pk = new AddPlayerPacket();

        $pk->uuid = $this->getUniqueId();
        $pk->username = "";
        $pk->eid = $this->getId();
        $pk->x = $this->getX();
        $pk->y = $this->getY();
        $pk->z = $this->getZ();
        $pk->yaw = $this->getYaw();
        $pk->pitch = $this->getPitch();
        $pk->item = Item::get(BlockIds::AIR);
        $pk->metadata = [
            Entity::DATA_FLAGS => [
                Entity::DATA_TYPE_BYTE, 
                ($this->isInvisible() ? 1 : 0) << Entity::DATA_FLAG_INVISIBLE
            ],
            Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $this->getNameTag()],
            Entity::DATA_SHOW_NAMETAG => [
                Entity::DATA_TYPE_BYTE, 
                $this->getDataProperty(Entity::DATA_SHOW_NAMETAG)
            ],
            Entity::DATA_NO_AI => [Entity::DATA_TYPE_BYTE, 1],
            Entity::DATA_LEAD_HOLDER => [Entity::DATA_TYPE_LONG, -1],
            Entity::DATA_LEAD => [Entity::DATA_TYPE_BYTE, 0]
        ];

        if ($player->hasPermission('npc.see.id')) {
            $pk->metadata[Entity::DATA_NAMETAG] = [
                Entity::DATA_TYPE_STRING, 
                $this->getNameTag() . " §7(Id: §f" . $this->getNpcUniqueId() . ")"
            ];
        }

        $player->dataPacket($pk);

        if ($this->isInvisible()) {
            return;
        }

        $pk = new PlayerListPacket();
        $pk->type = PlayerListPacket::TYPE_ADD;
        $pk->entries[] = [$this->getUniqueId(), $this->getId(), "", $this->skinId, $this->skin];
        $packets[] = $pk;
        $player->dataPacket($pk);

        $pk = new PlayerListPacket();
        $pk->type = PlayerListPacket::TYPE_REMOVE;
        $pk->entries[] = [$this->getUniqueId()];
        $player->dataPacket($pk);
    }

    private function isInvisible(): bool {
        $invisibleTag = $this->namedtag->offsetGet('Invisible');
        return strval($invisibleTag) === 'true';
    }

    protected function initEntity() {
        parent::initEntity();

        if ($this->npcUniqueId === null) {
            $this->npcUniqueId = NpcUUID::generate();
        }
        
        if (!$this->namedtag->offsetExists('Lines')) {
            return;
        }

        $linesListTag = $this->namedtag->offsetGet('Lines');
        if (!($linesListTag instanceof ListTag)) {
            return;
        }

        $lastCachedLocation = Location::fromObject($this->add(0, 0.3), null, $this->yaw, $this->pitch);
        foreach ($linesListTag as $lineNameTag) {
            if (!($lineNameTag instanceof StringTag)) {
                continue;
            }
            $lineCompoundTag = NBTHelper::createBaseLineNBT(strval($lineNameTag), $lastCachedLocation);
            $npcLine = new NPCLine(
                $this->getLevel()->getChunk($this->x >> 4, $this->z >> 4, true),
                $lineCompoundTag
            );
            $npcLine->setNpcOwner($this);
            $npcLine->spawnToAll();
            $this->npcLines[] = $npcLine;

            $lastCachedLocation = $lastCachedLocation->add(0, 0.3);
        }
    }

    public function addTextLine(string $textLine) {
        $lastCachedLocation = Location::fromObject($this->add(0, 0.3 * (count($this->npcLines) + 1)), null, $this->yaw, $this->pitch);
        $lineCompoundTag = NBTHelper::createBaseLineNBT($textLine, $lastCachedLocation);
        $npcLine = new NPCLine(
            $this->getLevel()->getChunk($this->x >> 4, $this->z >> 4, true),
            $lineCompoundTag
        );
        $npcLine->setNpcOwner($this);
        $npcLine->spawnToAll();
        $this->npcLines[] = $npcLine;
        $this->namedtag->offsetSet('Lines', new ListTag(
            'Lines', 
            array_map(function (NPCLine $npcLine) : StringTag {
                return new StringTag('', $npcLine->getNameTag());
            }, $this->npcLines)
        ));
    }

    public function editTextLine(int $index, string $newText) {
        if ($index === 0) {
            if (count($this->npcLines) === 0) {
                return false;
            }
            $index = count($this->npcLines) - 1;
            $npcLine = $this->npcLines[$index];
            $this->namedtag->offsetSet('CustomName', new StringTag('CustomName', $newText));
            $this->namedtag->offsetSet('RawName', new StringTag('RawName', $newText));
            $this->setNameTag($newText);
            $this->despawnFromAll();
            $this->spawnToAll();

            $npcLine->setNameTag($newText);
            $npcLine->despawnFromAll();
            $npcLine->spawnToAll();

            $this->namedtag->offsetSet('Lines', new ListTag(
                'Lines', 
                array_map(function (NPCLine $npcLine): StringTag {
                    return new StringTag('', $npcLine->getNameTag());
                }, $this->npcLines)
            ));
            return true;
        }

        $index -= 1;
        if (!isset($this->npcLines[$index])) {
            return false;
        }

        $npcLine = $this->npcLines[$index];
        $npcLine->namedtag->offsetSet('CustomName', new StringTag('CustomName', $newText));
        $npcLine->namedtag->offsetSet('RawName', new StringTag('RawName', $newText));
        $npcLine->setNameTag($newText);
        $npcLine->despawnFromAll();
        $npcLine->spawnToAll();

        $this->namedtag->offsetSet('Lines', new ListTag(
            'Lines', 
            array_map(function (NPCLine $npcLine): StringTag {
                return new StringTag('', $npcLine->getNameTag());
            }, $this->npcLines)
        ));
        return true;
    }

    public function removeTextLine(int $index) {
        if ($index === 0) {
            if (count($this->npcLines) === 0) {
                return false;
            }
            $index = count($this->npcLines) - 1;
            $npcLine = $this->npcLines[$index];
            $this->namedtag->offsetSet('CustomName', new StringTag('CustomName', $npcLine->getNameTag()));
            $this->namedtag->offsetSet('RawName', new StringTag('RawName', $npcLine->getRawName()));
            $this->setNameTag($npcLine->getNameTag());
            $this->despawnFromAll();
            $this->spawnToAll();

            $npcLine->close();
            unset($this->npcLines[$index]);
            $this->namedtag->offsetSet('Lines', new ListTag(
                'Lines', 
                array_map(function (NPCLine $npcLine) : StringTag {
                    return new StringTag('', $npcLine->getNameTag());
                }, $this->npcLines)
            ));
            $this->adjustTextLines();
            return true;
        }

        $index -= 1;

        if (!isset($this->npcLines[$index])) {
            return false;
        }

        $npcLine = $this->npcLines[$index];
        $npcLine->close();
        unset($this->npcLines[$index]);
        $this->namedtag->offsetSet('Lines', new ListTag(
            'Lines', 
            array_map(function (NPCLine $npcLine) : StringTag {
                return new StringTag('', $npcLine->getNameTag());
            }, $this->npcLines)
        ));
        $this->adjustTextLines();
        return true;
    }

    public function updateSkin(string $skinId, string $skinData) {
        $this->namedtag->offsetSet('Skin', new CompoundTag('', [
            new StringTag('Name', $skinId),
            new StringTag('Data', $skinData)]));
        $this->skinId = $skinId;
        $this->skin = $skinData;
        $this->despawnFromAll();
        $this->spawnToAll();
    }

    public function setVisible(bool $visible) {
        $this->namedtag->offsetSet('Invisible', new StringTag('Invisible', $visible ? 'false' : 'true'));
        $this->despawnFromAll();
        $this->spawnToAll();
    }

    public function addPlayerCommand(string $command) {
        $playerCommands = $this->namedtag->offsetGet('PlayerCommands');
        if (!($playerCommands instanceof ListTag)) {
            $playerCommands = new ListTag('PlayerCommands', []);
        }

        $rawPlayerCommands = $playerCommands->getValue();
        $rawPlayerCommands[] = new StringTag('', $command);
        $this->namedtag->offsetSet('PlayerCommands', new ListTag('PlayerCommands', $rawPlayerCommands));
    }

    public function addServerCommand(string $command) {
        $serverCommands = $this->namedtag->offsetGet('ServerCommands');
        if (!($serverCommands instanceof ListTag)) {
            $serverCommands = new ListTag('ServerCommands', []);
        }

        $rawServerCommands = $serverCommands->getValue();
        $rawServerCommands[] = new StringTag('', $command);
        $this->namedtag->offsetSet('ServerCommands', new ListTag('ServerCommands', $rawServerCommands));
    }

    public function removePlayerCommand(): string {
        $playerCommands = $this->namedtag->offsetGet('PlayerCommands');
        if (!($playerCommands instanceof ListTag)) {
            return "";
        }

        foreach ($playerCommands as $i => $playerCommand) {
            if (!($playerCommand instanceof StringTag)) {
                continue;
            }

            unset($playerCommands[$i]);
            $this->namedtag->offsetSet('PlayerCommands', $playerCommands);
            return strval($playerCommand);
        }

        return "";
    }

    public function removeServerCommand(): string {
        $serverCommands = $this->namedtag->offsetGet('ServerCommands');
        if (!($serverCommands instanceof ListTag)) {
            return "";
        }

        foreach ($serverCommands as $i => $serverCommand) {
            if (!($serverCommand instanceof StringTag)) {
                continue;
            }

            unset($serverCommands[$i]);
            $this->namedtag->offsetSet('ServerCommands', $serverCommands);
            return strval($serverCommand);
        }

        return "";
    }

    private function adjustTextLines() {
        $this->npcLines = array_values($this->npcLines);
        $lastCachedLocation = Location::fromObject($this->add(0, 0.3), null, $this->yaw, $this->pitch);
        foreach ($this->npcLines as $npcLine) {
            $npcLine->teleport($lastCachedLocation);
            $npcLine->despawnFromAll();
            $npcLine->spawnToAll();
            $lastCachedLocation = $lastCachedLocation->add(0, 0.3);
        }
    }

    public function close() {
        parent::close();
        foreach ($this->npcLines as $npcLine) {
            $npcLine->close();
        }
    }

    public function getNpcUniqueId(): string {
        if ($this->npcUniqueId === null) {
            $this->npcUniqueId = NpcUUID::generate();
        }

        return $this->npcUniqueId;
    }
}