<?php
declare(strict_types=1);
namespace DeathChest;
use pocketmine\block\Chest as ChestBlock;
use pocketmine\block\tile\Chest;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\math\Facing;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Config;
use pocketmine\world\particle\SmokeParticle;
use pocketmine\world\Position;
use pocketmine\world\World;

class Main extends PluginBase implements Listener
{
    private array $cooldowns = [];
    private Config $config;
    private array $floatingTexts = [];

    public function onEnable(): void
    {
        $this->saveResource("config.yml");
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, [
            "chest-duration" => 300,
            "enable-particles" => true,
            "particle-count" => 20,
            "warnings" => [120, 60, 30, 10],
            "messages" => [
                "death-message" => "§eLoot at X: %x §eY: %y §eZ: %z",
                "time-remaining" => "§cExpires in: §e%02d:%02d",
                "warning" => "§cChest vanishes in %s seconds!",
                "removed" => "§cYour chest disappeared.",
                "loot-click" => "§aYou have looted the chest!"
            ]
        ]);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onDeath(PlayerDeathEvent $event): void
    {
        $player = $event->getPlayer();
        $world = $player->getWorld();
        $items = $event->getDrops();
        $event->setDrops([]);

        $pos = $this->findSafePosition($player->getPosition());
        if ($pos === null) {
            $event->setDrops($items);
            return;
        }

        $slots = $this->calculateSlots($items);
        $isDouble = ($slots > 27);
        $chests = $this->placeChests($world, $pos, $isDouble);
        if (empty($chests)) {
            $event->setDrops($items);
            return;
        }

        $this->distributeItems($chests, $items);
        $this->startRemovalTimer($player, $pos, $isDouble);
        $this->sendDeathMessage($player, $pos);
        $this->spawnEffects($pos, $world);
    }

    public function onPlayerInteract(PlayerInteractEvent $event): void
    {
        $block = $event->getBlock();
        if ($block instanceof ChestBlock) {
            $pos = $this->getMainChestPosition($block->getPosition());
            $posHash = $pos->__toString();
            if (isset($this->cooldowns[$posHash])) {
                $event->getPlayer()->sendMessage($this->config->get("messages")["loot-click"]);
                $this->removeFloatingText($pos);
                unset($this->cooldowns[$posHash]);
            }
        }
    }

    public function onBlockBreak(BlockBreakEvent $event): void
    {
        $block = $event->getBlock();
        if ($block instanceof ChestBlock) {
            $pos = $this->getMainChestPosition($block->getPosition());
            $posHash = $pos->__toString();
            $this->removeFloatingText($pos);
            unset($this->cooldowns[$posHash]);
        }
    }

    public function onExplosion(EntityExplodeEvent $event): void
    {
        foreach ($event->getBlockList() as $block) {
            $posHash = $block->getPosition()->__toString();
            if (isset($this->cooldowns[$posHash])) {
                $event->cancel();
            }
        }
    }

    private function calculateSlots(array $items): int
    {
        $slots = 0;
        foreach ($items as $item) {
            $slots += ceil($item->getCount() / $item->getMaxStackSize());
        }
        return (int)($slots);
    }

    private function placeChests(World $world, Position $pos, bool $isDouble): array
    {
        $world->setBlock($pos, VanillaBlocks::CHEST());
        $mainChest = $world->getTile($pos);
        if (!$mainChest instanceof Chest) {
            return [];
        }

        $this->cooldowns[$pos->__toString()] = true;
        $chests = [$mainChest];

        if ($isDouble) {
            $sidePos = $pos->getSide(Facing::EAST);
            $world->setBlock($sidePos, VanillaBlocks::CHEST());
            $sideChest = $world->getTile($sidePos);
            if ($sideChest instanceof Chest) {
                $mainChest->pairWith($sideChest);
                $this->cooldowns[$sidePos->__toString()] = true;
                $chests[] = $sideChest;
            }
        }

        return $chests;
    }

    private function distributeItems(array $chests, array $items): void
    {
        $currentIndex = 0;
        foreach ($items as $item) {
            $chest = $chests[$currentIndex] ?? null;
            if ($chest instanceof Chest) {
                if (!$chest->getInventory()->canAddItem($item)) {
                    $currentIndex++;
                    $chests[$currentIndex]?->getInventory()->addItem($item);
                } else {
                    $chest->getInventory()->addItem($item);
                }
            }
        }
    }

    private function startRemovalTimer(Player $player, Position $pos, bool $isDouble): void
    {
        $duration = $this->config->get("chest-duration");
        $ftPos = new Position($pos->getX() + 0.5, $pos->getY() + 1.5, $pos->getZ() + 0.5, $pos->getWorld());
        $id = "Death_Chest_" . $pos->__toString() . "_" . $ftPos->__toString() . "_" . $player->getName();
        $playerName = $player->getName();

        $floatingText = new FloatingTextParticle($ftPos, $id, "§e{$playerName}'s Loot\n" . $this->formatTime($duration));
        $floatingText->spawnToAll();
        $this->floatingTexts[$pos->__toString()] = $floatingText;

        $startTime = time();
        $this->getScheduler()->scheduleRepeatingTask(
            new ClosureTask(function () use ($floatingText, $playerName, $startTime, $duration): void {
                $remaining = max(0, $duration - (time() - $startTime));
                $floatingText->update("§e{$playerName}'s Loot\n" . $this->formatTime($remaining));
            }),
            20
        );

        $this->getScheduler()->scheduleDelayedTask(
            new ClosureTask(function () use ($pos, $isDouble, $floatingText): void {
                $this->removeChest($pos, $isDouble);
                $floatingText->despawnToAll();
                unset($this->floatingTexts[$pos->__toString()]);
                unset($this->cooldowns[$pos->__toString()]);
                if ($isDouble) {
                    unset($this->cooldowns[$pos->getSide(Facing::EAST)->__toString()]);
                }
            }),
            $duration * 20
        );
    }

    private function removeFloatingText(Position $pos): void
    {
        $posHash = $pos->__toString();
        if (isset($this->floatingTexts[$posHash])) {
            $this->floatingTexts[$posHash]->despawnToAll();
            unset($this->floatingTexts[$posHash]);
        }
    }

    private function getMainChestPosition(Position $pos): Position
    {
        $chest = $pos->getWorld()->getTile($pos);
        if ($chest instanceof Chest && ($pair = $chest->getPair()) !== null) {
            $chestPos = $chest->getBlock()->getPosition();
            $pairPos = $pair->getBlock()->getPosition();
            return ($chestPos->x < $pairPos->x) ? $chestPos : $pairPos;
        }
        return $pos;
    }

    private function sendDeathMessage(Player $player, Position $pos): void
    {
        $msg = str_replace(
            ["%x", "%y", "%z"],
            [$pos->x, $pos->y, $pos->z],
            $this->config->get("messages")["death-message"]
        );
        $player->sendMessage($msg);
    }

    private function spawnEffects(Position $pos, World $world): void
    {
        if ($this->config->get("enable-particles")) {
            $center = new Position($pos->x + 0.5, $pos->y + 0.5, $pos->z + 0.5, $world);
            for ($i = 0; $i < $this->config->get("particle-count"); $i++) {
                $world->addParticle($center, new SmokeParticle());
            }
        }

        $pk = new PlaySoundPacket();
        $pk->soundName = "random.pop";
        $pk->x = $pos->x + 0.5;
        $pk->y = $pos->y + 0.5;
        $pk->z = $pos->z + 0.5;
        $pk->volume = 1.0;
        $pk->pitch = 1.0;
        $world->broadcastPacketToViewers($pos, $pk);
    }

    private function removeChest(Position $pos, bool $isDouble): void
    {
        $world = $pos->getWorld();
        $world->setBlock($pos, VanillaBlocks::AIR());
        if ($isDouble) {
            $world->setBlock($pos->getSide(Facing::EAST), VanillaBlocks::AIR());
        }
    }

    private function findSafePosition(Position $pos): ?Position
    {
        $world = $pos->getWorld();
        $x = (int)$pos->x;
        $y = (int)$pos->y;
        $z = (int)$pos->z;

        if ($world->getBlockAt($x, $y, $z)->canBeReplaced() && $world->getBlockAt($x + 1, $y, $z)->canBeReplaced()) {
            return new Position($x, $y, $z, $world);
        }

        for ($iy = $y; $iy >= 0; $iy--) {
            if ($world->getBlockAt($x, $iy, $z)->isSolid() && $world->getBlockAt($x + 1, $iy, $z)->isSolid()) {
                return new Position($x, $iy + 1, $z, $world);
            }
        }

        return null;
    }

    private function formatTime(int $seconds): string
    {
        $min = floor($seconds / 60);
        $sec = $seconds % 60;
        return sprintf($this->config->get("messages")["time-remaining"], $min, $sec);
    }
}
