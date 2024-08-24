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

namespace Friendica\Module\Api\Mastodon\Trends;

use Friendica\App;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Post;
use Friendica\Module\Api\ApiResponse;
use Friendica\Module\BaseApi;
use Friendica\Module\Conversation\Community;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * @see https://docs.joinmastodon.org/methods/trends/#statuses
 */
class Statuses extends BaseApi
{
	/**
	 * @var IManageConfigValues
	 */
	private $config;

	public function __construct(IManageConfigValues $config, \Friendica\Factory\Api\Mastodon\Error $errorFactory, App $app, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, array $server, array $parameters = [])
	{
		parent::__construct($errorFactory, $app, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
		$this->config = $config;
	}

	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		if ($this->config->get('system', 'block_public') || $this->config->get('system', 'community_page_style') == Community::DISABLED_VISITOR) {
			$this->checkAllowedScope(BaseApi::SCOPE_READ);
		}

		$uid = self::getCurrentUserID();

		$request = $this->getRequest([
			'limit' => 10, // Maximum number of results to return. Defaults to 10.
			'offset' => 0, // Offset in set, Defaults to 0.
		], $request);

		$condition = ["NOT `private` AND `commented` > ? AND `created` > ?", DateTimeFormat::utc('now -1 day'), DateTimeFormat::utc('now -1 week')];
		$condition = DBA::mergeConditions($condition, ['network' => Protocol::FEDERATED]);

		$display_quotes = self::appSupportsQuotes();

		$trending = [];
		$statuses = Post::selectPostThread(['uri-id'], $condition, ['limit' => [$request['offset'], $request['limit']],  'order' => ['total-actors' => true]]);
		while ($status = Post::fetch($statuses)) {
			try {
				$trending[] = DI::mstdnStatus()->createFromUriId($status['uri-id'], $uid, $display_quotes);
			} catch (\Exception $exception) {
				Logger::info('Post not fetchable', ['uri-id' => $status['uri-id'], 'uid' => $uid, 'exception' => $exception]);
			}
		}
		DBA::close($statuses);

		if (!empty($trending)) {
			self::setLinkHeaderByOffsetLimit($request['offset'], $request['limit']);
		}

		$this->jsonExit($trending);
	}
}
