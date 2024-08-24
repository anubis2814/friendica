<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

use Dice\Dice;

$start_time = microtime(true);

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
	die('Vendor path not found. Please execute "bin/composer.phar --no-dev install" on the command line in the web root.');
}

require __DIR__ . '/vendor/autoload.php';

$dice = (new Dice())->addRules(include __DIR__ . '/static/dependencies.config.php');
/** @var \Friendica\Core\Addon\Capability\ICanLoadAddons $addonLoader */
$addonLoader = $dice->create(\Friendica\Core\Addon\Capability\ICanLoadAddons::class);
$dice = $dice->addRules($addonLoader->getActiveAddonConfig('dependencies'));
$dice = $dice->addRule(Friendica\App\Mode::class, ['call' => [['determineRunMode', [false, $_SERVER], Dice::CHAIN_CALL]]]);

\Friendica\DI::init($dice);

\Friendica\Core\Logger\Handler\ErrorHandler::register($dice->create(\Psr\Log\LoggerInterface::class));

$a = \Friendica\DI::app();

\Friendica\DI::mode()->setExecutor(\Friendica\App\Mode::INDEX);

$a->runFrontend(
	$dice->create(\Friendica\App\Router::class),
	$dice->create(\Friendica\Core\PConfig\Capability\IManagePersonalConfigValues::class),
	$dice->create(\Friendica\Security\Authentication::class),
	$dice->create(\Friendica\App\Page::class),
	$dice->create(\Friendica\Content\Nav::class),
	$dice->create(Friendica\Module\Special\HTTPException::class),
	new \Friendica\Util\HTTPInputData($_SERVER),
	$start_time,
	$_SERVER
);
