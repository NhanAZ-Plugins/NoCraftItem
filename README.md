# NoCraftItem

NoCraftItem prevents players from crafting configured items on an Axolotl-PM server.

## Features

- Block specific craft outputs or all crafting.
- Let players with `nocraftitem.bypass` craft normally.
- Send a configurable message when a craft is blocked.
- Validate the item list when the plugin starts.

## Requirements and compatibility

NoCraftItem declares Axolotl-PM API `5.0.0` and requires PHP 8.1 or newer. It has no separate runtime library dependency. PHPStan at maximum level passes against Axolotl-PM source revisions 5.0.0 and the revision pinned in the [build workflow](.github/workflows/build.yml). The release PHAR is tested on the latter revision. Other 5.x server releases and connected Bedrock clients have not been verified.

## Installation

Download `NoCraftItem.phar` from [GitHub releases](https://github.com/NhanAZ-Plugins/NoCraftItem/releases), place it in the server's `plugins/` directory, and restart the server. Edit `plugin_data/NoCraftItem/config.yml` after the first start.

## Configuration

```yaml
cancelMessage: "§cYou cannot craft this item!"
noCraftAllItem: false
noCraftItem:
  - hopper
  - anvil
```

Set `noCraftAllItem` to `true` to block every craft. Set `cancelMessage` to an empty string to disable the message. Item names use Axolotl-PM's current and legacy string item parsers. Invalid names or incorrectly typed values stop the plugin from enabling so the intended rules cannot silently fail.

The `nocraftitem.bypass` permission defaults to operators. The plugin compares each craft output with the configured items. Item count does not change the match.

## Building and testing

The [build workflow](.github/workflows/build.yml) runs the crafting fixture, PHPStan at maximum level, and [DevTools](https://github.com/NhanAZ/DevTools). DevTools builds and validates the PHAR, including its checksum and MIT license. Run `php tests/craft-rules.php` for the local behavior fixture. It covers matching outputs, legacy item names, bypass, messaging, and malformed configuration. A fixture does not establish client gameplay behavior.

## License and support

The source retains its [MIT license](LICENSE). For support, use [NhanAZ Discord](https://discord.gg/j2X83ujT6c).
