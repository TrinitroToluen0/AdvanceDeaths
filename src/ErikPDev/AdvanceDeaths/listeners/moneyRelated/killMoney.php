<?php

namespace ErikPDev\AdvanceDeaths\listeners\moneyRelated;

use ErikPDev\AdvanceDeaths\utils\currencyManager;
use ErikPDev\AdvanceDeaths\utils\translationContainer;
use ErrorException;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\player\Player;

class killMoney implements Listener {

	private currencyManager $currencyManager;

	private static array $wordTranslation = array(
		"gain" => "ganaste",
		"lose" => "perdiste"
	);

	public function __construct(private array $configuration) {

		$this->currencyManager = new currencyManager();

	}

    /**
     * @throws ErrorException
     */
    private function modifyMoney(Player $player, float $amount, string $type) {

		switch (strtolower($type)) {
			case "lose":
				$this->currencyManager->reduceMoney($player, $amount);
				break;

			case "gain":
				$this->currencyManager->addMoney($player, $amount);
				break;

			default:
				throw new ErrorException("Unknown value type at killMoney, check your configuration.");
		}

	}

	public function deathEvent(PlayerDeathEvent $event) {

		/** @var EntityDamageByEntityEvent|EntityDamageEvent $damageCause */
		$damageCause = $event->getEntity()->getLastDamageCause();

		if (!$damageCause instanceof EntityDamageByEntityEvent) return;
		if(!$damageCause->getDamager() instanceof Player) return;

		/** @var Player $player */
		$player = $damageCause->getDamager();
		$victim = $event->getPlayer();
		$this->currencyManager->getMoney($victim)->onCompletion(
        /**
         * @throws ErrorException
         */ function (int|float $balance) use ($player) {
				$amount = match ($this->configuration["type"]) {
					"playerMoney" => $balance,
					"amount" => $this->configuration["amount"],
					"percent" => ($this->configuration["amount"] / 100) * $balance,
					default => throw new ErrorException("Kill Type is invalid, check your configuration."),
				};

				if($amount > 0 ) {
					$this->modifyMoney($player, $amount, $this->configuration["valueType"]);
					$player->sendMessage(translationContainer::translate("killMoney", true, array("1" => self::$wordTranslation[$this->configuration["valueType"]], "2" => $amount)));
				}

			},
		function(){});
	}

}