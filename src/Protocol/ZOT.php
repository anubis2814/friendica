<?php
/**
 * @copyright Copyright (C) 2010-2024, the Friendica project
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

namespace Friendica\Protocol;

use Friendica\App;
use Friendica\Core\Addon;
use Friendica\Core\Logger;
use Friendica\DI;
use Friendica\Module;
use Friendica\Module\Register;

/**
 * ZOT Protocol class
 *
 * This class contains functionality that is needed for OpenWebAuth, which is part of ZOT.
 * Friendica doesn't support the ZOT protocol itself.
 */
class ZOT
{
	/**
	 * Checks if the web request is done for the AP protocol
	 *
	 * @return bool is it ZOT?
	 */
	public static function isRequest(): bool
	{
		if (stristr($_SERVER['HTTP_ACCEPT'] ?? '', 'application/x-zot+json')) {
			Logger::debug('Is ZOT request', ['accept' => $_SERVER['HTTP_ACCEPT'], 'agent' => $_SERVER['HTTP_USER_AGENT'] ?? '']);
			return true;
		}

		return false;
	}

	/**
	 * Get information about this site
	 *
	 * @return array
	 */
	public static function getSiteInfo(): array
	{
		$policies = [
			Module\Register::OPEN    => 'open',
			Module\Register::APPROVE => 'approve',
			Module\Register::CLOSED  => 'closed',
		];

		return [
			'url'             => (string)DI::baseUrl(),
			'openWebAuth'     => (string)DI::baseUrl() . '/owa',
			'authRedirect'    => (string)DI::baseUrl() . '/magic',
			'register_policy' => $policies[Register::getPolicy()],
			'accounts'        => DI::keyValue()->get('nodeinfo_total_users'),
			'plugins'         => Addon::getVisibleList(),
			'sitename'        => DI::config()->get('config', 'sitename'),
			'about'           => DI::config()->get('config', 'info'),
			'project'         => App::PLATFORM,
			'version'         => App::VERSION,
		];
	}
}
