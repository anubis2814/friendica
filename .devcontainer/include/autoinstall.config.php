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

return [
	'database' => [
		'hostname' => '${MYSQL_HOST}',
		'username' => '${MYSQL_USER}',
		'password' => '${MYSQL_PASSWORD}',
		'database' => '${MYSQL_DATABASE}',
		'charset' => 'utf8mb4',
	],

	// ****************************************************************
	// The configuration below will be overruled by the admin panel.
	// Changes made below will only have an effect if the database does
	// not contain any configuration for the friendica system.
	// ****************************************************************

	'config' => [
		'admin_email' => 'admin@${ServerAlias}',
		'sitename' => 'Friendica Social Network',
		'register_policy' => \Friendica\Module\Register::OPEN,
		'register_text' => '',
		'php' => '${FRIENDICA_PHP_PATH}',
	],
	'system' => [
		'default_timezone' => 'UTC',
		'language' => 'en',
		'basepath' => '${workspaceFolder}',
		'url' => 'http://${ServerName}:${ServerPort}',
	],
];
