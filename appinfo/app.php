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

// ToDo Use Container and Services
\OCP\App::addNavigationEntry([
	'id' => 'announcementcenter',
	'order' => 10,
	'href' => \OCP\Util::linkToRoute('announcementcenter.page.index'),
	'icon' => \OCP\Util::imagePath('announcementcenter', 'app.svg'),
	'name' => \OC_L10N::get('announcementcenter')->t('Announcement Center')
]);
