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


use OCA\AnnouncementCenter\Model\Attachment;
use OCA\AnnouncementCenter\Model\AnnouncementMapper;
use OCA\AnnouncementCenter\Model\GroupMapper;
use OCA\AnnouncementCenter\Model\Share;
use OCA\AnnouncementCenter\NoPermissionException;
use OCA\AnnouncementCenter\Sharing\AnnouncementcenterShareProvider;
use OCA\AnnouncementCenter\StatusException;
use OCA\AnnouncementCenter\Model\ShareMapper;
use OCP\AppFramework\Http\StreamResponse;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\Constants;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IPreview;
use OCP\IRequest;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager;
use OCP\Share\IShare;
use Psr\Log\LoggerInterface;
use OCP\AppFramework\Http\Response;

class FileShareService implements IAttachmentService
{
	private IRequest $request;
	private IRootFolder $rootFolder;
	private AnnouncementcenterShareProvider $shareProvider;
	private IManager $shareManager;
	private ?string $userId;
	private ConfigService $configService;
	private IL10N $l10n;
	private IPreview $preview;
	private IMimeTypeDetector $mimeTypeDetector;
	// private PermissionService $permissionService;
	private AnnouncementMapper $announcementMapper;
	private LoggerInterface $logger;
	private IDBConnection $connection;
	private GroupMapper $groupMapper;
	private ShareMapper $shareMapper;
	public function __construct(
		IRequest $request,
		IL10N $l10n,
		IRootFolder $rootFolder,
		IManager $shareManager,
		ConfigService $configService,
		AnnouncementcenterShareProvider $shareProvider,
		IPreview $preview,
		IMimeTypeDetector $mimeTypeDetector,
		// PermissionService $permissionService,
		AnnouncementMapper  $announcementMapper,
		LoggerInterface $logger,
		IDBConnection $connection,
		?string $userId,
		GroupMapper $groupMapper,
		ShareMapper $shareMapper
	) {
		$this->request = $request;
		$this->l10n = $l10n;
		$this->rootFolder = $rootFolder;
		$this->configService = $configService;
		$this->shareProvider = $shareProvider;
		$this->shareManager = $shareManager;
		$this->userId = $userId;
		$this->preview = $preview;
		$this->mimeTypeDetector = $mimeTypeDetector;
		// $this->permissionService = $permissionService;
		$this->announcementMapper = $announcementMapper;
		$this->logger = $logger;
		$this->connection = $connection;
		$this->groupMapper = $groupMapper;
		$this->shareMapper = $shareMapper;
		// $this->logger->warning('fileapp1');

	}



	public function extendData(Attachment $attachment)
	{
		$userFolder = $this->rootFolder->getUserFolder($this->userId);
		// $share = $this->getShareForAttachment($attachment);
		// $files = $userFolder->getById($share->getNode()->getId());
		$files = $userFolder->getById($attachment->getFileId());
		if (count($files) === 0) {
			return $attachment;
		}
		$file = array_shift($files);

		$attachment->setExtendedData([
			'path' => $userFolder->getRelativePath($file->getPath()),
			'fileid' => $file->getId(),
			'data' => $file->getName(),
			'filesize' => $file->getSize(),
			'mimetype' => $file->getMimeType(),
			'info' => pathinfo($file->getName()),
			'hasPreview' => $this->preview->isAvailable($file),

		]);
		return $attachment;
	}

	public function display(Attachment $attachment): Response
	{
		// // Problem: Folders
		// /** @psalm-suppress InvalidCatch */
		// try {
		// 	$share = $this->getShareForAttachment($attachment);
		// } catch (ShareNotFound $e) {
		// 	throw new NotFoundException('File not found');
		// }
		// $file = $share->getNode();
		// if ($file === null || $share->getSharedWith() !== (string)$attachment->getAnnouncementId()) {
		// 	throw new NotFoundException('File not found');
		// }
		$userFolder = $this->rootFolder->getUserFolder($this->userId);
		$files = $userFolder->getById($attachment->getId());
		$file = array_shift($files);
		$response = new StreamResponse($file->fopen('rb'));
		$response->addHeader('Content-Disposition', 'attachment; filename="' . rawurldecode($file->getName()) . '"');
		$response->addHeader('Content-Type', $this->mimeTypeDetector->getSecureMimeType($file->getMimeType()));
		return $response;
	}

	public function create(Attachment $attachment, int $permission = Constants::PERMISSION_READ)
	{


		$userFolder = $this->rootFolder->getUserFolder($this->userId);
		$this->logger->warning("path:" . $attachment->getData() . "file" . json_encode($userFolder->getDirectoryListing()[0]));
		try {
			/** @var \OC\Files\Node\Node $node */
			$node = $userFolder->get($attachment->getData());
		} catch (NotFoundException $e) {
			throw new OCSNotFoundException($this->l10n->t('Wrong path, file/folder does not exist'));
		}

		$attachment->setFileId($node->getId());
		$attachment->setData($node->getName());
		$groups = $this->groupMapper->getGroupsByAnnouncementId($attachment->getAnnouncementId());
		foreach ($groups as $group) {
			$share = $this->shareManager->newShare();
			$share->setNode($node);
			$share->setShareType(ISHARE::TYPE_GROUP);
			$share->setSharedWith($group);
			$share->setPermissions($permission);
			$share->setSharedBy($this->userId);
			$share = $this->shareManager->createShare($share);
		}
		$this->logger->warning('fileapp:' . json_encode($share));
		return $attachment;
	}

	public function update(Attachment $attachment)
	{
		$userFolder = $this->rootFolder->getUserFolder($this->userId);
		$files = $userFolder->getById($attachment->getId());
		$file = array_shift($files);
		$fileName = $file['name'];
		$attachment->setData($fileName);

		$content = fopen($file['tmp_name'], 'rb');
		if ($content === false) {
			throw new StatusException('Could not read file');
		}
		$file->putContent($content);
		fclose($content);

		$attachment->setLastModified(time());
		return $attachment;
	}

	/**
	 * @throws NoPermissionException
	 * @throws NotFoundException
	 * @throws ShareNotFound
	 */
	public function delete(Attachment $attachment)
	{
		$userFolder = $this->rootFolder->getUserFolder($this->userId);
		$files = $userFolder->getById($attachment->getId());
		$file = array_shift($files);
		$attachment->setData($file->getName());
		$this->shareManager->getSharesBy($this->userId, IShare::TYPE_GROUP, $file);

		$isFileOwner = $file->getOwner() !== null && $file->getOwner()->getUID() === $this->userId;
		if ($isFileOwner) {
			$this->shareManager->deleteShare($share);
			return;
		}

		throw new NoPermissionException('No permission to remove the attachment from the announcement');
	}

	public function allowUndo()
	{
		return false;
	}

	public function markAsDeleted(Attachment $attachment)
	{
		throw new \Exception('Not implemented');
	}
}
