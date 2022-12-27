<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Core\Config\Model;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Config\Util\ConfigFileManager;
use Friendica\Core\Config\ValueObject\Cache;

/**
 * Configuration model, which manages the whole system configuration
 */
class Config implements IManageConfigValues
{
	/**
	 * @var Cache
	 */
	protected $configCache;

	/** @var ConfigFileManager */
	protected $configFileManager;

	/**
	 * @param ConfigFileManager $configFileManager The configuration file manager to save back configs
	 * @param Cache  $configCache The configuration cache (based on the config-files)
	 */
	public function __construct(ConfigFileManager $configFileManager, Cache $configCache)
	{
		$this->configFileManager = $configFileManager;
		$this->configCache       = $configCache;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getCache(): Cache
	{
		return $this->configCache;
	}

	public function save()
	{
		$this->configFileManager->saveData($this->configCache);
	}

	public function load(string $cat = 'config')
	{
		$configCache = new Cache();

		$this->configFileManager->setupCache($configCache, $_SERVER);
		$this->configCache = $configCache;
	}

	public function get(string $cat, string $key, $default_value = null, bool $refresh = false)
	{
		return $this->configCache->get($cat, $key) ?? $default_value;
	}

	public function set(string $cat, string $key, $value, bool $autosave = true): bool
	{
		$stored = $this->configCache->set($cat, $key, $value, Cache::SOURCE_DATA);

		if ($stored && $autosave) {
			$this->save();
		}

		return $stored;
	}

	public function delete(string $cat, string $key, bool $autosave = true): bool
	{
		$removed = $this->configCache->delete($cat, $key);

		if ($removed && $autosave) {
			$this->save();
		}

		return $removed;
	}
}
