<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 *
 * @copyright Copyright (c) 2016, Joas Schilling <nickvergessen@owncloud.com>
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\AnnouncementCenter\AppInfo;

use OCA\AnnouncementCenter\ActivityExtension;
use OCA\AnnouncementCenter\Manager;
use OCA\AnnouncementCenter\NotificationsNotifier;

$app = new Application();

\OC::$server->getNavigationManager()->add(function() use ($app) {
	$urlGenerator = $app->getContainer()->getServer()->getURLGenerator();
	$l = $app->getContainer()->getServer()->getL10NFactory()->get('announcementcenter');
	return [
		'id' => 'announcementcenter',
		'order' => 10,
		'href' => $urlGenerator->linkToRoute('announcementcenter.page.index'),
		'icon' => $urlGenerator->imagePath('announcementcenter', 'announcementcenter.svg'),
		'name' => $l->t('Announcements'),
	];
});

\OC::$server->getActivityManager()->registerExtension(function() use ($app) {
	return $app->getContainer()->query('OCA\AnnouncementCenter\ActivityExtension');
});

\OC::$server->getNotificationManager()->registerNotifier(function() use ($app) {
	return $app->getContainer()->query('OCA\AnnouncementCenter\NotificationsNotifier');
}, function() use ($app) {
	$l = $app->getContainer()->getServer()->getL10NFactory()->get('announcementcenter');
	return [
		'id' => 'announcementcenter',
		'name' => $l->t('Announcements'),
	];
});
