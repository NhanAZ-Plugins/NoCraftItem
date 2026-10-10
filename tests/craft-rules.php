<?php

declare(strict_types=1);

namespace pocketmine\event {
	interface Listener {}
}

namespace pocketmine\item {
	final class Item {
		public function __construct(private string $name) {}
		public function equals(self $other): bool { return $this->name === $other->name; }
	}
	final class LegacyStringToItemParserException extends \RuntimeException {}
	final class StringToItemParser {
		public static function getInstance(): self { return new self(); }
		public function parse(string $name): ?Item { return in_array($name, ['hopper', 'anvil'], true) ? new Item($name) : null; }
	}
	final class LegacyStringToItemParser {
		public static function getInstance(): self { return new self(); }
		public function parse(string $name): Item {
			if ($name === '1') { return new Item('stone'); }
			throw new LegacyStringToItemParserException('Unknown item');
		}
	}
}

namespace pocketmine\plugin {
	final class DisablePluginException extends \RuntimeException {}
	class PluginBase {
		/** @var array<string, mixed> */
		public static array $values = [];
		public function saveDefaultConfig(): void {}
		public function getConfig(): object { return new class {
			public function get(string $key): mixed { return PluginBase::$values[$key] ?? null; }
		}; }
		public function getServer(): object { return new class {
			public function getPluginManager(): object { return new class {
				public function registerEvents(object $listener, object $plugin): void {}
			}; }
		}; }
	}
}

namespace pocketmine\player {
	final class Player {
		/** @var list<string> */
		public array $messages = [];
		public function __construct(private bool $bypass = false) {}
		public function hasPermission(string $permission): bool { return $this->bypass; }
		public function sendMessage(string $message): void { $this->messages[] = $message; }
	}
}

namespace pocketmine\event\inventory {
	final class CraftItemEvent {
		private bool $cancelled = false;
		/** @param list<\pocketmine\item\Item> $outputs */
		public function __construct(private \pocketmine\player\Player $player, private array $outputs) {}
		public function getPlayer(): \pocketmine\player\Player { return $this->player; }
		/** @return list<\pocketmine\item\Item> */
		public function getOutputs(): array { return $this->outputs; }
		public function cancel(): void { $this->cancelled = true; }
		public function isCancelled(): bool { return $this->cancelled; }
	}
}

namespace {
	require __DIR__ . '/../src/NhanAZ/NoCraftItem/Main.php';

	use NhanAZ\NoCraftItem\Main;
	use pocketmine\event\inventory\CraftItemEvent;
	use pocketmine\item\Item;
	use pocketmine\player\Player;
	use pocketmine\plugin\DisablePluginException;
	use pocketmine\plugin\PluginBase;
	function expect(bool $condition): void {
		if (!$condition) { throw new RuntimeException('Craft rule check failed'); }
	}

	$start = new ReflectionMethod(Main::class, 'onEnable');
	PluginBase::$values = ['noCraftItem' => ['hopper', '1'], 'noCraftAllItem' => false, 'cancelMessage' => 'Blocked'];
	$plugin = new Main();
	$start->invoke($plugin);
	$player = new Player();
	$event = new CraftItemEvent($player, [new Item('anvil'), new Item('hopper')]);
	$plugin->onCraft($event);
	expect($event->isCancelled() && $player->messages === ['Blocked']);
	$event = new CraftItemEvent(new Player(), [new Item('stone')]);
	$plugin->onCraft($event);
	expect($event->isCancelled());
	$event = new CraftItemEvent(new Player(true), [new Item('hopper')]);
	$plugin->onCraft($event);
	expect(!$event->isCancelled());
	$event = new CraftItemEvent(new Player(), [new Item('dirt')]);
	$plugin->onCraft($event);
	expect(!$event->isCancelled());

	PluginBase::$values['noCraftAllItem'] = true;
	PluginBase::$values['cancelMessage'] = '';
	$plugin = new Main();
	$start->invoke($plugin);
	$player = new Player();
	$event = new CraftItemEvent($player, [new Item('dirt')]);
	$plugin->onCraft($event);
	expect($event->isCancelled() && $player->messages === []);

	foreach ([['noCraftItem' => ['missing']], ['noCraftItem' => ['']], ['noCraftItem' => 'hopper'], ['noCraftAllItem' => 'true'], ['cancelMessage' => []]] as $invalid) {
		PluginBase::$values = array_replace(['noCraftItem' => [], 'noCraftAllItem' => false, 'cancelMessage' => 'Blocked'], $invalid);
		try {
			$start->invoke(new Main());
			throw new RuntimeException('Invalid configuration was accepted');
		} catch (DisablePluginException) {}
	}
	echo "Craft cancellation, bypass, parser fallback, and invalid configuration checks passed\n";
}
