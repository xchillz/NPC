<?php

declare(strict_types=1);

namespace dev\xchillz\npc\entity;

use pocketmine\block\BlockIds;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\network\protocol\AddPlayerPacket;
use pocketmine\Player;

final class NPCLine extends Human {

    /** @var NPC */
    private $npcOwner;

    public function onUpdate($tick) {
        if ($this->npcOwner === null) {
            $this->close();
            return false;
        }
        
        try {
            $rawName = $this->getRawName();
        } catch (\RuntimeException $exception) {
            $this->server->getLogger()->logException($exception, $exception->getTrace());
            return false;
        }

        static $search = ['{server_count}', '{world_count}', '{time}'];
        $replace = [
            count($this->server->getOnlinePlayers()),
            $this->npcOwner->getWorldPlayersCount(),
            date('H:i:s'),
        ];

        $this->setNameTag(str_replace($search, $replace, $rawName));
        return true;
    }

    public function getRawName(): string {
        if (!$this->namedtag->offsetExists('RawName')) {
            $this->close();
            throw new \RuntimeException("NPC is missing 'RawName' NBT tag and has been removed.");            
        }
        return strval($this->namedtag->offsetGet('RawName'));
    }

    public function saveNBT() {}

    protected function initEntity() {
        parent::initEntity();
    }

    public function attack($damage, EntityDamageEvent $source) {
        if ($this->npcOwner === null) {
            $this->close();
            return;
        }
        
        $this->npcOwner->attack($damage, $source);
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
            Entity::DATA_FLAGS => [Entity::DATA_TYPE_BYTE, 1 << Entity::DATA_FLAG_INVISIBLE],
            Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $this->getNameTag()],
            Entity::DATA_SHOW_NAMETAG => [Entity::DATA_TYPE_BYTE, 1],
            Entity::DATA_NO_AI => [Entity::DATA_TYPE_BYTE, 1],
            Entity::DATA_LEAD_HOLDER => [Entity::DATA_TYPE_LONG, -1],
            Entity::DATA_LEAD => [Entity::DATA_TYPE_BYTE, 0]
        ];

        $player->dataPacket($pk);
    }

    public function setNpcOwner(NPC $npcOwner) {
        $this->npcOwner = $npcOwner;
    }
}