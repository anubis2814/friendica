#!/usr/bin/env php
<?php
/**
 * Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 */

if (php_sapi_name() !== 'cli') {
	header($_SERVER["SERVER_PROTOCOL"] . ' 403 Forbidden');
	exit();
}

use Dice\Dice;
use Friendica\Core\Logger\Capability\LogChannel;
use Friendica\DI;
use Psr\Log\LoggerInterface;

require dirname(__DIR__) . '/vendor/autoload.php';

$dice = (new Dice())->addRules(include __DIR__ . '/../static/dependencies.config.php');
/** @var \Friendica\Core\Addon\Capability\ICanLoadAddons $addonLoader */
$addonLoader = $dice->create(\Friendica\Core\Addon\Capability\ICanLoadAddons::class);
$dice = $dice->addRules($addonLoader->getActiveAddonConfig('dependencies'));
$dice = $dice->addRule(LoggerInterface::class, ['constructParams' => [LogChannel::CONSOLE]]);

/// @fixme Necessary until Hooks inside the Logger can get loaded without the DI-class
DI::init($dice);
\Friendica\Core\Logger\Handler\ErrorHandler::register($dice->create(\Psr\Log\LoggerInterface::class));

(new Friendica\Core\Console($dice, $argv))->execute();
