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

namespace Friendica\Module\Api\Mastodon;

use Friendica\App;
use Friendica\Core\L10n;
use Friendica\DI;
use Friendica\Content\Conversation\Factory\Channel as ChannelFactory;
use Friendica\Content\Conversation\Repository;
use Friendica\Content\GroupManager;
use Friendica\Module\BaseApi;
use Friendica\Model\Circle;
use Friendica\Module\Api\ApiResponse;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * @see https://docs.joinmastodon.org/methods/timelines/lists/
 */
class Lists extends BaseApi
{
	/** @var ChannelFactory */
	protected $channel;
	/** @var Repository\UserDefinedChannel */
	protected $userDefinedChannel;

	public function __construct(Repository\UserDefinedChannel $userDefinedChannel, ChannelFactory $channel, \Friendica\Factory\Api\Mastodon\Error $errorFactory, App $app, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, array $server, array $parameters = [])
	{
		parent::__construct($errorFactory, $app, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->channel            = $channel;
		$this->userDefinedChannel = $userDefinedChannel;
	}

	protected function delete(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		if (empty($this->parameters['id'])) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		if (!Circle::exists($this->parameters['id'], $uid)) {
			$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
		}

		if (!Circle::remove($this->parameters['id'])) {
			$this->logAndJsonError(500, $this->errorFactory->InternalError());
		}

		$this->jsonExit([]);
	}

	protected function post(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		$request = $this->getRequest([
			'title' => '',
		], $request);

		if (empty($request['title'])) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		Circle::create($uid, $request['title']);

		$id = Circle::getIdByName($uid, $request['title']);
		if (!$id) {
			$this->logAndJsonError(500, $this->errorFactory->InternalError());
		}

		$this->jsonExit(DI::mstdnList()->createFromCircleId($id));
	}

	public function put(array $request = [])
	{
		$request = $this->getRequest([
			'title'          => '', // The title of the list to be updated.
			'replies_policy' => '', // One of: "followed", "list", or "none".
		], $request);

		if (empty($request['title']) || empty($this->parameters['id'])) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		Circle::update($this->parameters['id'], $request['title']);
	}

	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		if (empty($this->parameters['id'])) {
			$lists = [];

			foreach (Circle::getByUserId($uid) as $circle) {
				$lists[] = DI::mstdnList()->createFromCircleId($circle['id']);
			}

			foreach ($this->channel->getTimelines($uid) as $channel) {
				$lists[] = DI::mstdnList()->createFromChannel($channel);
			}

			foreach ($this->userDefinedChannel->selectByUid($uid) as $channel) {
				$lists[] = DI::mstdnList()->createFromChannel($channel);
			}

			foreach (GroupManager::getList($uid, true, true, true) as $group) {
				$lists[] = DI::mstdnList()->createFromGroup($group);
			}
		} else {
			$id = $this->parameters['id'];

			if (!Circle::exists($id, $uid)) {
				$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
			}
			$lists = DI::mstdnList()->createFromCircleId($id);
		}

		$this->jsonExit($lists);
	}
}
