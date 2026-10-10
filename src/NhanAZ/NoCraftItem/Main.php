<?php

declare(strict_types=1);

namespace NhanAZ\NoCraftItem;

use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\item\LegacyStringToItemParser;
use pocketmine\item\LegacyStringToItemParserException;
use pocketmine\item\StringToItemParser;
use pocketmine\plugin\DisablePluginException;
use pocketmine\plugin\PluginBase;

final class Main extends PluginBase implements Listener {

	/** @var list<Item> */
	private array $blockedItems = [];
	private bool $blockAll = false;
	private string $cancelMessage = '';

	protected function onEnable(): void {
		$this->saveDefaultConfig();
		$config = $this->getConfig();
		$blocked = $config->get('noCraftItem');
		$blockAll = $config->get('noCraftAllItem');
		$message = $config->get('cancelMessage');
		if (!is_array($blocked) || !array_is_list($blocked) || !is_bool($blockAll) || !is_string($message)) {
			throw new DisablePluginException('Invalid crafting configuration');
		}
		foreach ($blocked as $index => $name) {
			if (!is_string($name) || trim($name) === '') {
				throw new DisablePluginException("Invalid item at noCraftItem[$index]");
			}
			try {
				$item = StringToItemParser::getInstance()->parse($name) ?? LegacyStringToItemParser::getInstance()->parse($name);
			} catch (LegacyStringToItemParserException $e) {
				throw new DisablePluginException("Invalid item at noCraftItem[$index]: " . $e->getMessage());
			}
			$this->blockedItems[] = $item;
		}
		$this->blockAll = $blockAll;
		$this->cancelMessage = $message;
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onCraft(CraftItemEvent $event): void {
		if ($event->getPlayer()->hasPermission('nocraftitem.bypass')) {
			return;
		}
		if ($this->blockAll) {
			$this->cancelCraft($event);
			return;
		}
		foreach ($event->getOutputs() as $output) {
			foreach ($this->blockedItems as $blocked) {
				if ($output->equals($blocked)) {
					$this->cancelCraft($event);
					return;
				}
			}
		}
	}

	private function cancelCraft(CraftItemEvent $event): void {
		$event->cancel();
		if ($this->cancelMessage !== '') {
			$event->getPlayer()->sendMessage($this->cancelMessage);
		}
	}
}
