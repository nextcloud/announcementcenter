<?php
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\AnnouncementCenter\Activity;

use OCA\AnnouncementCenter\Manager;
use OCP\Activity\IEvent;
use OCP\Activity\IManager;
use OCP\Activity\IProvider;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;

class Provider implements IProvider {

	/** @var IFactory */
	protected $languageFactory;

	/** @var IURLGenerator */
	protected $url;

	/** @var IManager */
	protected $activityManager;

	/** @var IUserManager */
	protected $userManager;

	/** @var Manager */
	protected $manager;

	/** @var string[] */
	protected $displayNames = [];

	/**
	 * @param IFactory $languageFactory
	 * @param IURLGenerator $url
	 * @param IManager $activityManager
	 * @param IUserManager $userManager
	 * @param Manager $manager
	 */
	public function __construct(IFactory $languageFactory, IURLGenerator $url, IManager $activityManager, IUserManager $userManager, Manager $manager) {
		$this->languageFactory = $languageFactory;
		$this->url = $url;
		$this->activityManager = $activityManager;
		$this->userManager = $userManager;
		$this->manager = $manager;
	}

	/**
	 * @param string $language
	 * @param IEvent $event
	 * @param IEvent|null $previousEvent
	 * @return IEvent
	 * @throws \InvalidArgumentException
	 * @since 11.0.0
	 */
	public function parse($language, IEvent $event, IEvent $previousEvent = null) {
		if ($event->getApp() !== 'announcementcenter' || (
			$event->getSubject() !== 'announcementsubject' && // 3.1 and later
			strpos($event->getSubject(), 'announcementsubject#') !== 0) // 3.0 and before
		) {
			throw new \InvalidArgumentException();
		}

		$l = $this->languageFactory->get('announcementcenter', $language);

		if (method_exists($this->activityManager, 'getRequirePNG') && $this->activityManager->getRequirePNG()) {
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('announcementcenter', 'announcementcenter-dark.png')));
		} else {
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('announcementcenter', 'announcementcenter-dark.svg')));
		}

		try {
			$announcement = $this->manager->getAnnouncement($event->getObjectId(), true, false, false);

			$parsedParameters = $this->getParameters($event, $announcement);
			if ($parsedParameters['actor']['id'] === $this->activityManager->getCurrentUserId()) {
				$subject = $l->t('You announced {announcement}');
				unset($parsedParameters['actor']);
			} else {
				$subject = $l->t('{actor} announced {announcement}');
			}
			$event->setParsedMessage($announcement['message']);
		} catch (\InvalidArgumentException $e) {
			$parsedParameters = $this->getParameters($event, []);
			if ($parsedParameters['actor']['id'] === $this->activityManager->getCurrentUserId()) {
				$subject = $l->t('You posted an announcement');
				unset($parsedParameters['actor']);
			} else {
				$subject = $l->t('{actor} posted an announcement');
			}

			$event->setParsedMessage($l->t('Announcement does not exist anymore'));
		}


		$this->setSubjects($event, $subject, $parsedParameters);

		return $event;
	}

	/**
	 * @param IEvent $event
	 * @param array $announcement
	 * @return array
	 */
	protected function getParameters(IEvent $event, array $announcement) {
		$parameters = $event->getSubjectParameters();

		if (!empty($announcement)) {
			return [
				'actor' => $this->generateUserParameter($parameters[0]),
				'announcement' => $this->generateAnnouncementParameter($announcement),
			];
		} else {
			return [
				'actor' => $this->generateUserParameter($parameters[0]),
			];
		}
	}

	/**
	 * @param IEvent $event
	 * @param string $subject
	 * @param array $parameters
	 */
	protected function setSubjects(IEvent $event, $subject, array $parameters) {
		$placeholders = $replacements = [];
		foreach ($parameters as $placeholder => $parameter) {
			$placeholders[] = '{' . $placeholder . '}';
			$replacements[] = $parameter['name'];
		}

		$event->setParsedSubject(str_replace($placeholders, $replacements, $subject))
			->setRichSubject($subject, $parameters);
	}

	/**
	 * @param array $announcement
	 * @return array
	 */
	protected function generateAnnouncementParameter(array $announcement) {
		return [
			'type' => 'announcement',
			'id' => $announcement['id'],
			'name' => $announcement['subject'],
			'link' => $this->url->linkToRouteAbsolute('announcementcenter.page.index') . '#' . $announcement['id'],
		];
	}

	/**
	 * @param string $uid
	 * @return array
	 */
	protected function generateUserParameter($uid) {
		if (!isset($this->displayNames[$uid])) {
			$this->displayNames[$uid] = $this->getDisplayName($uid);
		}

		return [
			'type' => 'user',
			'id' => $uid,
			'name' => $this->displayNames[$uid],
		];
	}

	/**
	 * @param string $uid
	 * @return string
	 */
	protected function getDisplayName($uid) {
		$user = $this->userManager->get($uid);
		if ($user instanceof IUser) {
			return $user->getDisplayName();
		} else {
			return $uid;
		}
	}
}
