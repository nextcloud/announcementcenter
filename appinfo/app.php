<?php
/**
 * ownCloud - announcementcenter
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Joas Schilling <nickvergessen@gmx.de>
 * @copyright Joas Schilling 2015
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
		'icon' => $urlGenerator->imagePath('announcementcenter', 'app.svg'),
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
