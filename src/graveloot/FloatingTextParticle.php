<?php
declare(strict_types=1);
namespace DeathChest;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use pocketmine\world\World;

class FloatingTextParticle extends \pocketmine\world\particle\FloatingTextParticle
{
    private string $identifier;
    private string $message;
    private World $world;
    private Position $position;

    public function __construct(Position $pos, string $identifier, string $message)
    {
        parent::__construct("", "");
        $this->world = $pos->getWorld();
        $this->identifier = $identifier;
        $this->message = $message;
        $this->position = $pos;
    }

    public function getWorld(): World
    {
        return $this->world;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function update(?string $message = null): void
    {
        $this->message = $message ?? $this->message;
        $this->setTitle($this->message);
        $this->sendChangesToAll();
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function sendChangesToAll(): void
    {
        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            $this->sendChangesTo($player);
        }
    }

    public function sendChangesTo(Player $player): void
    {
        $playerWorld = $player->getWorld();
        if ($playerWorld === null || $this->world->getDisplayName() !== $playerWorld->getDisplayName()) {
            return;
        }
        $this->world->addParticle($this->position, $this, [$player]);
    }

    public function spawn(Player $player): void
    {
        $this->setInvisible(false);
        $this->setTitle($this->message);
        $playerWorld = $player->getWorld();
        if ($playerWorld === null || $this->world->getDisplayName() !== $playerWorld->getDisplayName()) {
            return;
        }
        $this->world->addParticle($this->position, $this, [$player]);
    }

    public function despawn(Player $player): void
    {
        $this->setInvisible(true);
        $playerWorld = $player->getWorld();
        if ($playerWorld === null || $this->world->getDisplayName() !== $playerWorld->getDisplayName()) {
            return;
        }
        $this->world->addParticle($this->position, $this, [$player]);
    }

    public function spawnToAll(): void
    {
        $this->setInvisible(false);
        $this->setTitle($this->message);
        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            $this->spawn($player);
        }
    }

    public function despawnToAll(): void
    {
        $this->setInvisible(true);
        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            $this->despawn($player);
        }
    }
}
