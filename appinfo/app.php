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
	return new \OCA\AnnouncementCenter\ActivityExtension(
		new \OCA\AnnouncementCenter\Manager(\OC::$server->getDatabaseConnection()),
		\OC::$server->getActivityManager(),
		\OC::$server->getL10NFactory()
	);
});
