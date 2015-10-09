<?php
/**
 * ownCloud - AnnouncementCenter App
 *
 * @author Joas Schilling
 * @copyright 2015 Joas Schilling nickvergessen@owncloud.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\AnnouncementCenter\AppInfo;

use OCA\AnnouncementCenter\ActivityExtension;
use OCA\AnnouncementCenter\Manager;
use OCA\AnnouncementCenter\NotificationsNotifier;

\OC::$server->getNavigationManager()->add(function () {
	$urlGenerator = \OC::$server->getURLGenerator();
	$l = \OC::$server->getL10NFactory()->get('announcementcenter');
	return [
		'id' => 'announcementcenter',
		'order' => 10,
		'href' => $urlGenerator->linkToRoute('announcementcenter.page.index'),
		'icon' => $urlGenerator->imagePath('announcementcenter', 'announcementcenter.svg'),
		'name' => $l->t('Announcements'),
	];
});

\OC::$server->getActivityManager()->registerExtension(function() {
	return new ActivityExtension(
		new Manager(\OC::$server->getDatabaseConnection()),
		\OC::$server->getActivityManager(),
		\OC::$server->getL10NFactory()
	);
});

\OC::$server->getNotificationManager()->registerNotifier(function() {
	return new NotificationsNotifier(
		new Manager(\OC::$server->getDatabaseConnection()),
		\OC::$server->getL10NFactory()
	);
});
