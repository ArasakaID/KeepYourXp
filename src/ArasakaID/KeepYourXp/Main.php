<?php

namespace ArasakaID\KeepYourXp;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;

class Main extends PluginBase implements Listener{

    private array $playerXp = [];

    public function onEnable()
    {
        $this->saveResource("config.yml");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    /**
     * @return bool
     */
    private function keepXpEnable(): bool
    {
        if($this->getConfig()->get("keepPlayerXp")){
            return true;
        }
        return false;
    }

    /**
     * @param PlayerDeathEvent $event
     */
    public function onPlayerDeath(PlayerDeathEvent $event){
        $player = $event->getPlayer();
        if($this->keepXpEnable()){
            $this->playerXp[$player->getName()] = $event->getXpDropAmount();
            $event->setXpDropAmount(0);
        }
    }

    /**
     * @param PlayerRespawnEvent $event
     */
    public function onPlayerRespawn(PlayerRespawnEvent $event){
        $player = $event->getPlayer();
        if($this->keepXpEnable()){
            $this->getScheduler()->scheduleDelayedTask(new ClosureTask(
            function(int $currentTick) use ($player) : void{
                    if(isset($this->playerXp[$player->getName()])){
                        $player->addXp($this->playerXp[$player->getName()]);
                        $player->sendMessage(TextFormat::colorize($this->getConfig()->get("playerRespawnMessage")));
                        unset($this->playerXp[$player->getName()]);
                    }
                }
            ), 20);
        }
    }

}