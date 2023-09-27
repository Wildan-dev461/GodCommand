<?php

namespace WildanDev\GodCommand;

use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;

class GodCommand extends PluginBase implements Listener {

    private $godEnabled = [];
    private $godDuration;
    private $godMessage;
    private $godDisableMessage;
    private $cooldownDuration;
    private $cooldownMessage;
    private $cooldowns = [];

    public function onEnable(): void {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        $this->saveDefaultConfig();
        $this->reloadConfig();

        $this->godDuration = $this->getConfig()->get("god-duration");
        $this->godMessage = $this->getConfig()->get("god-message");
        $this->godDisableMessage = $this->getConfig()->get("god-disable-message");
        $this->cooldownDuration = $this->getConfig()->get("cooldown-duration");
        $this->cooldownMessage = $this->getConfig()->get("cooldown-message");
    }

    public function onDisable(): void {
        // No logic implement
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage(TF::RED . "This command can only be used in-game.");
            return false;
        }

        if ($command->getName() === "god") {
            $this->handleGodCommand($sender);
            return true;
        }

        return false;
    }

    public function onEntityDamage(EntityDamageEvent $event): void {
        $entity = $event->getEntity();
        if (!$entity instanceof Player) {
            return;
        }

        if ($this->isGodEnabled($entity)) {
            $event->cancel();
        }
    }

    private function isGodEnabled(Player $player): bool {
        $playerName = $player->getName();

        if (isset($this->godEnabled[$playerName])) {
            $endTime = $this->godEnabled[$playerName];
            if ($endTime > time()) {
                return true;
            } else {
                unset($this->godEnabled[$playerName]);
            }
        }

        return false;
    }

    private function isCooldownActive(Player $player): bool {
        $playerName = $player->getName();

        if (isset($this->cooldowns[$playerName])) {
            $endTime = $this->cooldowns[$playerName];
            if ($endTime > time()) {
                return true;
            } else {
                unset($this->cooldowns[$playerName]);
            }
        }

        return false;
    }

    private function handleGodCommand(Player $player): void {
        $playerName = $player->getName();

        if ($this->isGodEnabled($player)) {
            $player->sendMessage(TF::YELLOW . "God mode is already enabled.");
            return;
        }

        if ($this->isCooldownActive($player)) {
            $remainingTime = $this->cooldowns[$playerName] - time();
            $player->sendMessage(TF::RED . str_replace("{remaining-time}", $remainingTime, $this->cooldownMessage));
            return;
        }

        $currentTimestamp = time();
        $endTime = $currentTimestamp + $this->godDuration;
        $cooldownEndTime = $currentTimestamp + $this->cooldownDuration;

        $this->godEnabled[$playerName] = $endTime;
        $this->cooldowns[$playerName] = $cooldownEndTime;

        $player->sendMessage(TF::GREEN . $this->godMessage);
    }
}
