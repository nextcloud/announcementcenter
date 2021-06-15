<?php

declare(strict_types=1);
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
use OCA\AnnouncementCenter\Model\Announcement;
use OCA\AnnouncementCenter\Model\AnnouncementDoesNotExistException;
use OCP\Activity\IEvent;
use OCP\Activity\IManager as IActivityManager;
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

	/** @var IActivityManager */
	protected $activityManager;

	/** @var IUserManager */
	protected $userManager;

	/** @var Manager */
	protected $manager;

	/** @var string[] */
	protected $displayNames = [];

	public function __construct(
		IFactory $languageFactory,
		IURLGenerator $url,
		IActivityManager $activityManager,
		IUserManager $userManager,
		Manager $manager
	) {
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
	public function parse($language, IEvent $event, IEvent $previousEvent = null): IEvent {
		if ($event->getApp() !== 'announcementcenter' || (
			$event->getSubject() !== 'announcementsubject' && // 3.1 and later
			strpos($event->getSubject(), 'announcementsubject#') !== 0) // 3.0 and before
		) {
			throw new \InvalidArgumentException('Unknown subject');
		}

		$l = $this->languageFactory->get('announcementcenter', $language);

		if (method_exists($this->activityManager, 'getRequirePNG') && $this->activityManager->getRequirePNG()) {
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('announcementcenter', 'announcementcenter-dark.png')));
		} else {
			$event->setIcon($this->url->getAbsoluteURL($this->url->imagePath('announcementcenter', 'announcementcenter-dark.svg')));
		}

		$parameters = $this->getParameters($event);

		try {
			$announcement = $this->manager->getAnnouncement($parameters['announcement']);

			$parsedParameters = $this->getParsedParameters($parameters, $announcement);
			if ($parsedParameters['actor']['id'] === $this->activityManager->getCurrentUserId()) {
				$subject = $l->t('You announced “{announcement}”');
				unset($parsedParameters['actor']);
			} else {
				$subject = $l->t('{actor} announced “{announcement}”');
			}
			$event->setParsedMessage($announcement->getMessage());
		} catch (AnnouncementDoesNotExistException $e) {
			$parsedParameters = $this->getParsedParameters($parameters);
			if ($parsedParameters['actor']['id'] === $this->activityManager->getCurrentUserId()) {
				$subject = $l->t('You posted an announcement');
				unset($parsedParameters['actor']);
			} else {
				$subject = $l->t('{actor} posted an announcement');
			}

			$event->setParsedMessage($l->t('The announcement does not exist anymore'));
		}


		$this->setSubjects($event, $subject, $parsedParameters);

		return $event;
	}

	protected function getParameters(IEvent $event): array {
		$parameters = $event->getSubjectParameters();
		if (isset($parameters['announcement'])) {
			return $parameters;
		}

		// Legacy fallback from before 3.4.0
		return [
			'author' => $parameters[0] ?? '',
			'announcement' => $event->getObjectId(),
		];
	}

	protected function setSubjects(IEvent $event, string $subject, array $parameters) {
		$placeholders = $replacements = [];
		foreach ($parameters as $placeholder => $parameter) {
			$placeholders[] = '{' . $placeholder . '}';
			$replacements[] = $parameter['name'];
		}

		$event->setParsedSubject(str_replace($placeholders, $replacements, $subject))
			->setRichSubject($subject, $parameters);
	}

	protected function getParsedParameters(array $parameters, Announcement $announcement = null): array {
		if ($announcement !== null) {
			return [
				'actor' => $this->generateUserParameter($parameters['author']),
				'announcement' => $this->generateAnnouncementParameter($announcement),
			];
		}

		return [
			'actor' => $this->generateUserParameter($parameters['author']),
		];
	}

	protected function generateAnnouncementParameter(Announcement $announcement): array {
		return [
			'type' => 'announcement',
			'id' => $announcement->getId(),
			'name' => $announcement->getParsedSubject(),
			'link' => $this->url->linkToRouteAbsolute('announcementcenter.page.index', [
				'announcement' => $announcement->getId(),
			]),
		];
	}

	protected function generateUserParameter(string $uid): array {
		if (!isset($this->displayNames[$uid])) {
			$this->displayNames[$uid] = $this->getDisplayName($uid);
		}

		return [
			'type' => 'user',
			'id' => $uid,
			'name' => $this->displayNames[$uid],
		];
	}

	protected function getDisplayName(string $uid): string {
		$user = $this->userManager->get($uid);
		if ($user instanceof IUser) {
			return $user->getDisplayName();
		}
		return $uid;
	}
}
