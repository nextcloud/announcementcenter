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

namespace OCA\AnnouncementCenter\Activity;

use OCA\AnnouncementCenter\Manager;
use OCP\Activity\IExtension;
use OCP\Activity\IManager;
use OCP\L10N\IFactory;

class Extension implements IExtension {
	/** @var Manager */
	protected $manager;
	/** @var IManager */
	protected $activityManager;
	/** @var IFactory */
	protected $languageFactory;

	/**
	 * @param Manager $manager
	 * @param IManager $activityManager
	 * @param IFactory $languageFactory
	 */
	public function __construct(Manager $manager, IManager $activityManager, IFactory $languageFactory) {
		$this->manager = $manager;
		$this->activityManager = $activityManager;
		$this->languageFactory = $languageFactory;
	}

	/**
	 * The extension can return an array of additional notification types.
	 * If no additional types are to be added false is to be returned
	 *
	 * @param string $languageCode
	 * @return array|false
	 */
	public function getNotificationTypes($languageCode) {
		return false;
	}

	/**
	 * For a given method additional types to be displayed in the settings can be returned.
	 * In case no additional types are to be added false is to be returned.
	 *
	 * @param string $method
	 * @return array|false
	 */
	public function getDefaultTypes($method) {
		return ['announcementcenter'];
	}

	/**
	 * A string naming the css class for the icon to be used can be returned.
	 * If no icon is known for the given type false is to be returned.
	 *
	 * @param string $type
	 * @return string|false
	 */
	public function getTypeIcon($type) {
		return $type === 'announcementcenter' ? 'icon-info' : false;
	}

	/**
	 * The extension can translate a given message to the requested languages.
	 * If no translation is available false is to be returned.
	 *
	 * @param string $app
	 * @param string $text
	 * @param array $params
	 * @param boolean $stripPath
	 * @param boolean $highlightParams
	 * @param string $languageCode
	 * @return string|false
	 */
	public function translate($app, $text, $params, $stripPath, $highlightParams, $languageCode) {
		if ($app === 'announcementcenter') {
			$l = $this->languageFactory->get('announcementcenter', $languageCode);

			list(, $id) = explode('#', $text);

			try {
				$announcement = $this->manager->getAnnouncement($id, true);
			} catch (\InvalidArgumentException $e) {
				return (string) $l->t('Announcement does not exist anymore', $params);
			}

			if (strpos($text, 'announcementmessage#') === 0) {
				return $announcement['message'];
			}

			$params[] = '<parameter>' . $announcement['subject'] . '</parameter>';

			try {
				if ($announcement['author'] === $this->activityManager->getCurrentUserId()) {
					array_shift($params);
					return (string) $l->t('You announced %s', $params);
				}
			} catch (\UnexpectedValueException $e) {
				// FIXME this is awkward, but we have no access to the current user in emails
			}
			return (string) $l->t('%s announced %s', $params);
		}

		return false;
	}

	/**
	 * The extension can define the type of parameters for translation
	 *
	 * Currently known types are:
	 * * file		=> will strip away the path of the file and add a tooltip with it
	 * * username	=> will add the avatar of the user
	 *
	 * @param string $app
	 * @param string $text
	 * @return array|false
	 */
	public function getSpecialParameterList($app, $text) {
		if ($app === 'announcementcenter'&& strpos($text, 'announcementsubject#') === 0) {
			return [
				0 => 'username',
			];
		}
		return false;
	}

	/**
	 * The extension can define the parameter grouping by returning the index as integer.
	 * In case no grouping is required false is to be returned.
	 *
	 * @param array $activity
	 * @return integer|false
	 */
	public function getGroupParameter($activity) {
		return false;
	}

	/**
	 * The extension can define additional navigation entries. The array returned has to contain two keys 'top'
	 * and 'apps' which hold arrays with the relevant entries.
	 * If no further entries are to be added false is no be returned.
	 *
	 * @return array|false
	 */
	public function getNavigation() {
		return false;
	}

	/**
	 * The extension can check if a customer filter (given by a query string like filter=abc) is valid or not.
	 *
	 * @param string $filterValue
	 * @return boolean
	 */
	public function isFilterValid($filterValue) {
		return false;
	}

	/**
	 * The extension can filter the types based on the filter if required.
	 * In case no filter is to be applied false is to be returned unchanged.
	 *
	 * @param array $types
	 * @param string $filter
	 * @return array|false
	 */
	public function filterNotificationTypes($types, $filter) {
		if (in_array($filter, ['all', 'by', 'self'])) {
			$types[] = 'announcementcenter';
			return $types;
		}
		return false;
	}

	/**
	 * For a given filter the extension can specify the sql query conditions including parameters for that query.
	 * In case the extension does not know the filter false is to be returned.
	 * The query condition and the parameters are to be returned as array with two elements.
	 * E.g. return array('`app` = ? and `message` like ?', array('mail', 'ownCloud%'));
	 *
	 * @param string $filter
	 * @return array|false
	 */
	public function getQueryForFilter($filter) {
		return false;
	}
}
