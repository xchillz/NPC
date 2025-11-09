<?php

declare(strict_types=1);

namespace dev\xchillz\npc\config;

use pocketmine\utils\Config;

final class MessagesConfig {

    /** @var Config */
    private $config;

    private function __construct(Config $config) {
        $this->config = $config;
    }

    public function getMessage(string $key, array $replaceables): string {
        $message = $this->config->get($key, 'No hay un mensaje configurado para esto. (' . $key . ')');
        if (empty($replaceables)) {
            return $message;
        }

        foreach ($replaceables as $key => $value) {
            $message = str_replace('{' . $key . '}', (string) $value, $message);
        }
        return $message;
    }

    public static function wrap(string $pathToConfig): MessagesConfig {
        return new self(new Config($pathToConfig));
    }
}