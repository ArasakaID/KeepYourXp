<?php

namespace ArasakaID\KeepYourXp;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;

class Main extends PluginBase implements Listener
{

    private $playerXp = [];
    private const DROPPED_XP = "droppedXp";
    private const REAL_XP = "realXp";

    public function onEnable()
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->updateConfig();
    }

    private function updateConfig(): void
    {
        if (!$this->getConfig()->exists("config-version") or $this->getConfig()->get("config-version") !== 1.1) {
            rename($this->getDataFolder() . "config.yml", $this->getDataFolder() . "config.old.yml");
            $this->reloadConfig();
        }
    }

    /**
     * @param PlayerDeathEvent $event
     */
    public function onPlayerDeath(PlayerDeathEvent $event)
    {
        if($this->getConfig()->get("keepPlayerXp")) {
            $player = $event->getPlayer();
            $type = $this->getConfig()->get("typeXp");
            if ($type === self::DROPPED_XP) {
                $this->playerXp[$player->getName()] = $event->getXpDropAmount();
            } elseif ($type === self::REAL_XP) {
                $this->playerXp[$player->getName()] = $player->getCurrentTotalXp();
            }
            $event->setXpDropAmount(0);
        }
    }

    /**
     * @param PlayerRespawnEvent $event
     */
    public function onPlayerRespawn(PlayerRespawnEvent $event)
    {
        if($this->getConfig()->get("keepPlayerXp")) {
            $player = $event->getPlayer();
            $this->getScheduler()->scheduleDelayedTask(new ClosureTask(
                function (int $currentTick) use ($player): void {
                    $player->getXpLevel();
                    if (isset($this->playerXp[$player->getName()])) {
                        if ($player->isOnline()) {
                            $player->addXp($this->playerXp[$player->getName()]);
                            $player->sendMessage(TextFormat::colorize($this->getConfig()->get("playerRespawnMessage")));
                        }
                        unset($this->playerXp[$player->getName()]);
                    }
                }
            ), 20);
        }
    }

}
