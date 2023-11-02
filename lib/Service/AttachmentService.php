<?php

/**
 * @copyright Copyright (c) 2018 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\AnnouncementCenter\Service;

// use OCA\Announcementcenter\Activity\ActivityManager;
use OCA\AnnouncementCenter\AppInfo\Application;
use OCA\AnnouncementCenter\BadRequestException;
use OCA\AnnouncementCenter\Model\Attachment;
use OCA\AnnouncementCenter\Model\AttachmentMapper;
// use OCA\Announcementcenter\Model\AnnouncementMapper;
// use OCA\Announcementcenter\Model\ChangeHelper;
use OCA\AnnouncementCenter\InvalidAttachmentType;
use OCA\AnnouncementCenter\NoPermissionException;
use OCA\AnnouncementCenter\NotFoundException;
use OCA\AnnouncementCenter\Cache\AttachmentCacheHelper;
use OCA\AnnouncementCenter\StatusException;
use OCA\AnnouncementCenter\Validators\AttachmentServiceValidator;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\IMapperException;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\QueryException;
use OCP\DB\Exception;
use OCP\IL10N;
use OCP\IUserManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use ReflectionException;

class AttachmentService
{
	private AttachmentMapper $attachmentMapper;
	private $userId;

	/** @var IAttachmentService[] */
	private array $services = [];
	/** @var Application */
	private Application $application;
	/** @var AttachmentCacheHelper */
	private AttachmentCacheHelper $attachmentCacheHelper;
	/** @var IL10N */
	private IL10N $l10n;
	// /** @var ActivityManager */
	// private $activityManager;
	private IUserManager $userManager;
	/** @var AttachmentServiceValidator */
	private AttachmentServiceValidator $attachmentServiceValidator;
	private LoggerInterface $logger;

	/**
	 * @param AttachmentMapper $attachmentMapper
	 * @param IUserManager $userManager
	 * @param Application $application
	 * @param AttachmentCacheHelper $attachmentCacheHelper
	 * @param $userId
	 * @param IL10N $l10n
	 * @param AttachmentServiceValidator $attachmentServiceValidator
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 * @throws QueryException
	 */
	public function __construct(
		AttachmentMapper $attachmentMapper,
		IUserManager $userManager,
		// PermissionService $permissionService,
		Application $application,
		AttachmentCacheHelper $attachmentCacheHelper,
		$userId,
		IL10N $l10n,
		// ActivityManager $activityManager,
		AttachmentServiceValidator $attachmentServiceValidator,
		LoggerInterface $logger
	) {
		$this->attachmentMapper = $attachmentMapper;
		// $this->permissionService = $permissionService;
		$this->userId = $userId;
		$this->application = $application;
		$this->attachmentCacheHelper = $attachmentCacheHelper;
		$this->l10n = $l10n;
		// $this->activityManager = $activityManager;
		$this->userManager = $userManager;
		$this->attachmentServiceValidator = $attachmentServiceValidator;
		$this->logger = $logger;
		// Register shipped attachment services
		// TODO: move this to a plugin based approach once we have different types of attachments
		$this->registerAttachmentService('deck_file', FileService::class);
		$this->registerAttachmentService('file', FilesAppService::class);
	}

	/**
	 * @param string $type
	 * @param string $class
	 * @throws QueryException
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function registerAttachmentService($type, $class): void
	{
		$this->services[$type] = $this->application->getContainer()->query($class);
	}

	/**
	 * @param string $type
	 * @return IAttachmentService
	 * @throws InvalidAttachmentType
	 */
	public function getService(string $type): IAttachmentService
	{
		if (isset($this->services[$type])) {
			return $this->services[$type];
		}
		throw new InvalidAttachmentType($type);
	}

	/**
	 * @param $announcementId
	 * @param bool $withDeleted
	 * @return array
	 * @throws BadRequestException
	 * @throws InvalidAttachmentType
	 * @throws Exception
	 * @throws ReflectionException
	 */
	public function findAll($announcementId, bool $withDeleted = false): array
	{
		if (is_numeric($announcementId) === false) {
			throw new BadRequestException('announcement id must be a number');
		}

		// $this->permissionService->checkPermission($this->announcementMapper, $announcementId, Acl::PERMISSION_READ);

		$attachments = $this->attachmentMapper->findAll($announcementId);
		if ($withDeleted) {
			$attachments = array_merge($attachments, $this->attachmentMapper->findToDelete($announcementId, false));
		}

		foreach (array_keys($this->services) as $attachmentType) {
			$service = $this->getService($attachmentType);
			if ($service instanceof ICustomAttachmentService) {
				$attachments = array_merge($attachments, $service->listAttachments((int)$announcementId));
			}
		}

		foreach ($attachments as &$attachment) {
			try {
				$service = $this->getService($attachment->getType());
				$service->extendData($attachment);
				$this->addCreator($attachment);
			} catch (InvalidAttachmentType $e) {
				// Ingore invalid attachment types when extending the data
			}
		}

		return $attachments;
	}

	/**
	 * @param $announcementId
	 * @return int|mixed
	 * @throws BadRequestException
	 * @throws InvalidAttachmentType
	 * @throws Exception
	 */
	public function count($announcementId): mixed
	{
		if (is_numeric($announcementId) === false) {
			throw new BadRequestException('announcement id must be a number');
		}

		$count = $this->attachmentCacheHelper->getAttachmentCount((int)$announcementId);
		if ($count === null) {
			$count = count($this->attachmentMapper->findAll($announcementId));

			foreach (array_keys($this->services) as $attachmentType) {
				$service = $this->getService($attachmentType);
				if ($service instanceof ICustomAttachmentService) {
					$count += $service->getAttachmentCount((int)$announcementId);
				}
			}

			$this->attachmentCacheHelper->setAttachmentCount((int)$announcementId, $count);
		}

		return $count;
	}

	/**
	 * @param $announcementId
	 * @param $type
	 * @param $data
	 * @return Attachment|Entity
	 * @throws NoPermissionException
	 * @throws StatusException
	 * @throws BadRequestException
	 */
	public function create($announcementId, $type, $data)
	{
		$this->attachmentServiceValidator->check(compact('announcementId', 'type'));

		// $this->permissionService->checkPermission($this->announcementMapper, $announcementId, Acl::PERMISSION_EDIT);
		$this->logger->warning('data:' . json_encode($data));
		$this->attachmentCacheHelper->clearAttachmentCount((int)$announcementId);
		$attachment = new Attachment();
		$attachment->setAnnouncementId($announcementId);
		$attachment->setType($type);
		$attachment->setData($data);
		$attachment->setCreatedBy($this->userId);
		$attachment->setLastModified(time());
		$attachment->setCreatedAt(time());

		try {
			$service = $this->getService($attachment->getType());
			$service->create($attachment);

			if (!$service instanceof ICustomAttachmentService) {
				if ($attachment->getData() === null) {
					throw new StatusException($this->l10n->t('No data was provided to create an attachment.'));
				}

				$attachment = $this->attachmentMapper->insert($attachment);
			}

			$service->extendData($attachment);
			$this->addCreator($attachment);
		} catch (InvalidAttachmentType $e) {
			// just store the data
		}

		// $this->changeHelper->announcementChanged($attachment->getAnnouncementId());
		// $this->activityManager->triggerEvent(ActivityManager::DECK_OBJECT_CARD, $attachment, ActivityManager::SUBJECT_ATTACHMENT_CREATE);
		return $attachment;
	}


	/**
	 * Display the attachment
	 *
	 * @param $attachmentId
	 * @return Response
	 * @throws NoPermissionException
	 * @throws NotFoundException
	 */
	public function display($announcementId, $attachmentId, $type = 'deck_file')
	{
		try {
			$service = $this->getService($type);
		} catch (InvalidAttachmentType $e) {
			throw new NotFoundException();
		}

		if (!$service instanceof ICustomAttachmentService) {
			try {
				$attachment = $this->attachmentMapper->find($attachmentId);
			} catch (\Exception $e) {
				throw new NoPermissionException('Permission denied');
			}
			// $this->permissionService->checkPermission($this->announcementMapper, $attachment->getAnnouncementId(), Acl::PERMISSION_READ);

			try {
				$service = $this->getService($attachment->getType());
			} catch (InvalidAttachmentType $e) {
				throw new NotFoundException();
			}
		} else {
			$attachment = new Attachment();
			$attachment->setId($attachmentId);
			$attachment->setType($type);
			$attachment->setAnnouncementId($announcementId);
			// $this->permissionService->checkPermission($this->announcementMapper, $attachment->getAnnouncementId(), Acl::PERMISSION_READ);
		}

		return $service->display($attachment);
	}

	/**
	 * Update an attachment with custom data
	 *
	 * @param $attachmentId
	 * @param $data
	 * @return mixed
	 * @throws BadRequestException
	 * @throws NoPermissionException
	 */
	public function update($announcementId, $attachmentId, $data, $type = 'deck_file')
	{
		$this->attachmentServiceValidator->check(compact('announcementId', 'type', 'data'));
		try {
			$service = $this->getService($type);
		} catch (InvalidAttachmentType $e) {
			throw new NotFoundException();
		}

		if ($service instanceof ICustomAttachmentService) {
			try {
				$attachment = new Attachment();
				$attachment->setId($attachmentId);
				$attachment->setType($type);
				$attachment->setData($data);
				$attachment->setAnnouncementId($announcementId);
				$service->update($attachment);
				// $this->changeHelper->announcementChanged($attachment->getAnnouncementId());
				return $attachment;
			} catch (\Exception $e) {
				throw new NotFoundException();
			}
		}

		try {
			$attachment = $this->attachmentMapper->find($attachmentId);
		} catch (\Exception $e) {
			throw new NoPermissionException('Permission denied');
		}

		// $this->permissionService->checkPermission($this->announcementMapper, $attachment->getAnnouncementId(), Acl::PERMISSION_EDIT);
		$this->attachmentCacheHelper->clearAttachmentCount($announcementId);
		$attachment->setData($data);
		try {
			$service = $this->getService($attachment->getType());
			$service->update($attachment);
		} catch (InvalidAttachmentType $e) {
			// just update without further action
		}
		$attachment->setLastModified(time());
		$this->attachmentMapper->update($attachment);
		// extend data so the frontend can use it properly after creating
		$service->extendData($attachment);
		$this->addCreator($attachment);

		// $this->changeHelper->announcementChanged($attachment->getAnnouncementId());
		// $this->activityManager->triggerEvent(ActivityManager::DECK_OBJECT_CARD, $attachment, ActivityManager::SUBJECT_ATTACHMENT_UPDATE);
		return $attachment;
	}

	/**
	 * Either mark an attachment as deleted for later removal or just remove it depending
	 * on the IAttachmentService implementation
	 *
	 * @throws NoPermissionException
	 * @throws NotFoundException
	 */
	public function delete(int $announcementId, int $attachmentId, string $type = 'deck_file'): Attachment
	{
		try {
			$service = $this->getService($type);
		} catch (InvalidAttachmentType $e) {
			throw new NotFoundException();
		}

		if ($service instanceof ICustomAttachmentService) {
			$attachment = new Attachment();
			$attachment->setId($attachmentId);
			$attachment->setType($type);
			$attachment->setAnnouncementId($announcementId);
			$service->extendData($attachment);
		} else {
			try {
				$attachment = $this->attachmentMapper->find($attachmentId);
			} catch (IMapperException $e) {
				throw new NoPermissionException('Permission denied');
			}
		}
		// $this->permissionService->checkPermission($this->announcementMapper, $attachment->getAnnouncementId(), Acl::PERMISSION_EDIT);

		if ($service->allowUndo()) {
			$service->markAsDeleted($attachment);
			$attachment = $this->attachmentMapper->update($attachment);
		} else {
			$service->delete($attachment);
			if (!$service instanceof ICustomAttachmentService) {
				$attachment = $this->attachmentMapper->delete($attachment);
			}
		}

		$this->attachmentCacheHelper->clearAttachmentCount($announcementId);
		// $this->changeHelper->announcementChanged($attachment->getAnnouncementId());
		// $this->activityManager->triggerEvent(ActivityManager::DECK_OBJECT_CARD, $attachment, ActivityManager::SUBJECT_ATTACHMENT_DELETE);
		return $attachment;
	}

	public function restore(int $announcementId, int $attachmentId, string $type = 'deck_file'): Attachment
	{
		try {
			$attachment = $this->attachmentMapper->find($attachmentId);
		} catch (\Exception $e) {
			throw new NoPermissionException('Permission denied');
		}

		// $this->permissionService->checkPermission($this->announcementMapper, $attachment->getAnnouncementId(), Acl::PERMISSION_EDIT);
		$this->attachmentCacheHelper->clearAttachmentCount($announcementId);

		try {
			$service = $this->getService($attachment->getType());
			if ($service->allowUndo()) {
				$attachment->setDeletedAt(0);
				$attachment = $this->attachmentMapper->update($attachment);
				// $this->changeHelper->announcementChanged($attachment->getAnnouncementId());
				// $this->activityManager->triggerEvent(ActivityManager::DECK_OBJECT_CARD, $attachment, ActivityManager::SUBJECT_ATTACHMENT_RESTORE);
				return $attachment;
			}
		} catch (InvalidAttachmentType $e) {
		}
		throw new NoPermissionException('Restore is not allowed.');
	}

	/**
	 * @param Attachment $attachment
	 * @return Attachment
	 * @throws ReflectionException
	 */
	private function addCreator(Attachment $attachment): Attachment
	{
		$createdBy = $attachment->jsonSerialize()['createdBy'] ?? '';
		$creator = [
			'displayName' => $createdBy,
			'id' => $createdBy,
			'email' => null,
		];
		if ($this->userManager->userExists($createdBy)) {
			$user = $this->userManager->get($createdBy);
			$creator['displayName'] = $user->getDisplayName();
			$creator['email'] = $user->getEMailAddress();
		}
		$extendedData = $attachment->jsonSerialize()['extendedData'] ?? [];
		$extendedData['attachmentCreator'] = $creator;
		$attachment->setExtendedData($extendedData);

		return $attachment;
	}
}
