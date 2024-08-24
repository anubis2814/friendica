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

namespace Friendica\Module\Api\Mastodon\Timelines;

use Friendica\App;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Module\Api\ApiResponse;
use Friendica\Module\BaseApi;
use Friendica\Module\Conversation\Community;
use Friendica\Network\HTTPException;
use Friendica\Object\Api\Mastodon\TimelineOrderByTypes;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * @see https://docs.joinmastodon.org/methods/timelines/
 */
class PublicTimeline extends BaseApi
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
	 * @throws HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		$request = $this->getRequest([
			'max_id'          => null,  // Return results older than id
			'since_id'        => null,  // Return results newer than id
			'min_id'          => null,  // Return results immediately newer than id
			'limit'           => 20,    // Maximum number of results to return. Defaults to 20.
			'local'           => false, // Show only local statuses? Defaults to false.
			'remote'          => false, // Show only remote statuses? Defaults to false.
			'only_media'      => false, // Show only statuses with media attached? Defaults to false.
			'with_muted'      => false, // Pleroma extension: return activities by muted (not by blocked!) users.
			'exclude_replies' => false, // Don't show comments
			'friendica_order' => TimelineOrderByTypes::ID, // Sort order options (defaults to ID)
		], $request);

		if ($this->config->get('system', 'community_page_style') == Community::DISABLED) {
			$this->jsonExit([]);
		}

		if ($this->authRequired($request)) {
			$this->checkAllowedScope(BaseApi::SCOPE_READ);
		}

		$uid = self::getCurrentUserID();

		$condition = [
			'gravity' => [Item::GRAVITY_PARENT, Item::GRAVITY_COMMENT], 'private' => Item::PUBLIC,
			'network' => Protocol::FEDERATED, 'author-blocked' => false, 'author-hidden' => false
		];

		$condition = $this->addPagingConditions($request, $condition);
		$params = $this->buildOrderAndLimitParams($request);

		if ($request['local']) {
			$condition = DBA::mergeConditions($condition, ['origin' => true]);
		} else {
			$condition = DBA::mergeConditions($condition, ['uid' => 0]);
		}

		if ($request['remote']) {
			$condition = DBA::mergeConditions($condition, ["NOT `uri-id` IN (SELECT `uri-id` FROM `post-user` WHERE `origin` AND `post-user`.`uri-id` = `post-timeline-view`.`uri-id`)"]);
		}

		if ($request['only_media']) {
			$condition = DBA::mergeConditions($condition, [
				"`uri-id` IN (SELECT `uri-id` FROM `post-media` WHERE `type` IN (?, ?, ?))",
				Post\Media::AUDIO, Post\Media::IMAGE, Post\Media::VIDEO
			]);
		}

		if ($request['exclude_replies']) {
			$condition = DBA::mergeConditions($condition, ['gravity' => Item::GRAVITY_PARENT]);
		}

		if ($request['local']) {
			$items = Post::selectLocalTimelineForUser($uid, ['uri-id'], $condition, $params);
		} else {
			$items = Post::selectTimelineForUser($uid, ['uri-id'], $condition, $params);
		}

		$display_quotes = self::appSupportsQuotes();

		$statuses = [];
		while ($item = Post::fetch($items)) {
			try {
				$status =  DI::mstdnStatus()->createFromUriId($item['uri-id'], $uid, $display_quotes);
				$this->updateBoundaries($status, $item, $request['friendica_order']);
				$statuses[] = $status;
			} catch (\Throwable $th) {
				Logger::info('Post not fetchable', ['uri-id' => $item['uri-id'], 'uid' => $uid, 'error' => $th]);
			}
		}

		DBA::close($items);

		if (!empty($request['min_id'])) {
			$statuses = array_reverse($statuses);
		}

		self::setLinkHeader($request['friendica_order'] != TimelineOrderByTypes::ID);
		$this->jsonExit($statuses);
	}

	private function authRequired(array $request): bool
	{
		if ($this->config->get('system', 'block_public') || $this->config->get('system', 'community_page_style') == Community::DISABLED_VISITOR) {
			return true;
		}

		if ($request['local'] && $this->config->get('system', 'community_page_style') == Community::GLOBAL) {
			return true;
		}

		if ($request['remote'] && $this->config->get('system', 'community_page_style') == Community::LOCAL) {
			return true;
		}

		return false;
	}
}
