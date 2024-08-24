#!/usr/bin/env php
<?php
/**
 * Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * Starts the background processing
 */

if (php_sapi_name() !== 'cli') {
	header($_SERVER["SERVER_PROTOCOL"] . ' 403 Forbidden');
	exit();
}

use Dice\Dice;
use Friendica\App;
use Friendica\App\Mode;
use Friendica\Core\Logger\Capability\LogChannel;
use Friendica\Core\Update;
use Friendica\Core\Worker;
use Friendica\DI;
use Psr\Log\LoggerInterface;

// Get options
$shortopts = 'sn';
$longopts = ['spawn', 'no_cron'];
$options = getopt($shortopts, $longopts);

// Ensure that worker.php is executed from the base path of the installation
if (!file_exists("index.php") && (sizeof($_SERVER["argv"]) != 0)) {
	$directory = dirname($_SERVER["argv"][0]);

	if (substr($directory, 0, 1) != '/') {
		$directory = $_SERVER["PWD"] . '/' . $directory;
	}
	$directory = realpath($directory . '/..');

	chdir($directory);
}

require dirname(__DIR__) . '/vendor/autoload.php';

$dice = (new Dice())->addRules(include __DIR__ . '/../static/dependencies.config.php');
/** @var \Friendica\Core\Addon\Capability\ICanLoadAddons $addonLoader */
$addonLoader = $dice->create(\Friendica\Core\Addon\Capability\ICanLoadAddons::class);
$dice = $dice->addRules($addonLoader->getActiveAddonConfig('dependencies'));
$dice = $dice->addRule(LoggerInterface::class, ['constructParams' => [LogChannel::WORKER]]);

DI::init($dice);
\Friendica\Core\Logger\Handler\ErrorHandler::register($dice->create(\Psr\Log\LoggerInterface::class));

DI::mode()->setExecutor(Mode::WORKER);

// Check the database structure and possibly fixes it
Update::check(DI::basePath(), true);

// Quit when in maintenance
if (!DI::mode()->has(App\Mode::MAINTENANCEDISABLED)) {
	return;
}

$spawn = array_key_exists('s', $options) || array_key_exists('spawn', $options);

if ($spawn) {
	Worker::spawnWorker();
	exit();
}

$run_cron = !array_key_exists('n', $options) && !array_key_exists('no_cron', $options);

$process = DI::process()->create(getmypid(), basename(__FILE__));

Worker::processQueue($run_cron, $process);

Worker::unclaimProcess($process);

DI::process()->delete($process);
