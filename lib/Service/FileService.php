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
use OCA\AnnouncementCenter\Model\AttachmentMapper;
use OCA\AnnouncementCenter\StatusException;
use OCA\AnnouncementCenter\Exceptions\ConflictException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Http\StreamResponse;
use OCP\DB\Exception;
use OCP\Files\Folder;
use OCP\Files\IAppData;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\Util;
use Psr\Log\LoggerInterface;

class FileService implements IAttachmentService {
	private IL10N $l10n;
	private IAppData $appData;
	private IRequest $request;
	private LoggerInterface $logger;
	private IRootFolder $rootFolder;
	private IConfig $config;
	private AttachmentMapper $attachmentMapper;
	private IMimeTypeDetector $mimeTypeDetector;

	public function __construct(
		IL10N $l10n,
		IAppData $appData,
		IRequest $request,
		LoggerInterface $logger,
		IRootFolder $rootFolder,
		IConfig $config,
		AttachmentMapper $attachmentMapper,
		IMimeTypeDetector $mimeTypeDetector
	) {
		$this->l10n = $l10n;
		$this->appData = $appData;
		$this->request = $request;
		$this->logger = $logger;
		$this->rootFolder = $rootFolder;
		$this->config = $config;
		$this->attachmentMapper = $attachmentMapper;
		$this->mimeTypeDetector = $mimeTypeDetector;
	}

	/**
	 * @param Attachment $attachment
	 * @return ISimpleFile
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 */
	private function getFileForAttachment(Attachment $attachment): ISimpleFile
    {
		return $this->getFolder($attachment)
			->getFile($attachment->getData());
	}

	/**
	 * @param Attachment $attachment
	 * @return ISimpleFolder
	 * @throws NotPermittedException
	 */
	public function getFolder(Attachment $attachment): ISimpleFolder
    {
		$folderName = 'file-announcement-' . (int)$attachment->getAnnouncementId();
		try {
			$folder = $this->appData->getFolder($folderName);
		} catch (NotFoundException $e) {
			$folder = $this->appData->newFolder($folderName);
		}
		return $folder;
	}

	public function extendData(Attachment $attachment): Attachment
    {
		try {
			$file = $this->getFileForAttachment($attachment);
		} catch (NotFoundException|NotPermittedException $e) {
			$this->logger->info('Extending data for file attachment failed');
			return $attachment;
		}
        $attachment->setExtendedData([
			'filesize' => $file->getSize(),
			'mimetype' => $file->getMimeType(),
			'info' => pathinfo($file->getName())
		]);
		return $attachment;
	}

	/**
	 * @return array
	 * @throws StatusException
	 */
	private function getUploadedFile(): array
    {
		$file = $this->request->getUploadedFile('file');
		$error = null;
		$phpFileUploadErrors = [
			UPLOAD_ERR_OK => $this->l10n->t('The file was uploaded'),
			UPLOAD_ERR_INI_SIZE => $this->l10n->t('The uploaded file exceeds the upload_max_filesize directive in php.ini'),
			UPLOAD_ERR_FORM_SIZE => $this->l10n->t('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form'),
			UPLOAD_ERR_PARTIAL => $this->l10n->t('The file was only partially uploaded'),
			UPLOAD_ERR_NO_FILE => $this->l10n->t('No file was uploaded'),
			UPLOAD_ERR_NO_TMP_DIR => $this->l10n->t('Missing a temporary folder'),
			UPLOAD_ERR_CANT_WRITE => $this->l10n->t('Could not write file to disk'),
			UPLOAD_ERR_EXTENSION => $this->l10n->t('A PHP extension stopped the file upload'),
		];

		if (empty($file)) {
			$error = $this->l10n->t('No file uploaded or file size exceeds maximum of %s', [Util::humanFileSize(Util::uploadLimit())]);
		}
		if (!empty($file) && array_key_exists('error', $file) && $file['error'] !== UPLOAD_ERR_OK) {
			$error = $phpFileUploadErrors[$file['error']];
		}
		if ($error !== null) {
			throw new StatusException($error);
		}
		return $file;
	}

    /**
     * @param Attachment $attachment
     * @throws ConflictException
     * @throws NotFoundException
     * @throws NotPermittedException
     * @throws StatusException
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     * @throws Exception
     */
	public function create(Attachment $attachment): void
    {
		$file = $this->getUploadedFile();
		$folder = $this->getFolder($attachment);
		$fileName = $file['name'];
		if ($folder->fileExists($fileName)) {
			$attachment = $this->attachmentMapper->findByData($attachment->getAnnouncementId(), $fileName);
			throw new ConflictException('File already exists.', $attachment);
		}

		$target = $folder->newFile($fileName);
		$content = fopen($file['tmp_name'], 'rb');
		if ($content === false) {
			throw new StatusException('Could not read file');
		}
		$target->putContent($content);
		if (is_resource($content)) {
			fclose($content);
		}

		$attachment->setData($fileName);
	}

	/**
	 * This method requires to be used with POST so we can properly get the form data
	 *
	 * @throws \Exception
	 */
	public function update(Attachment $attachment): void
    {
		$file = $this->getUploadedFile();
		$fileName = $file['name'];
		$attachment->setData($fileName);

		$target = $this->getFileForAttachment($attachment);
		$content = fopen($file['tmp_name'], 'rb');
		if ($content === false) {
			throw new StatusException('Could not read file');
		}
		$target->putContent($content);
		if (is_resource($content)) {
			fclose($content);
		}

		$attachment->setLastModified(time());
	}

	/**
	 * @param Attachment $attachment
	 * @throws NotPermittedException
	 */
	public function delete(Attachment $attachment): void
    {
		try {
			$file = $this->getFileForAttachment($attachment);
			$file->delete();
		} catch (NotFoundException $e) {
		}
	}

	/**
	 * Workaround until ISimpleFile can be fetched as a resource
	 *
	 * @throws \Exception
	 */
	private function getFileFromRootFolder(Attachment $attachment): \OCP\Files\Node
    {
		$folderName = 'file-announcement-' . (int)$attachment->getAnnouncementId();
		$instanceId = $this->config->getSystemValue('instanceid', null);
		if ($instanceId === null) {
			throw new \Exception('no instance id!');
		}
		$name = 'appdata_' . $instanceId;
		/** @var Folder $appDataFolder */
		$appDataFolder = $this->rootFolder->get($name);
		/** @var Folder $appDataFolder */
		$appDataFolder = $appDataFolder->get('announcementcenter');
		/** @var Folder $announcementFolder */
		$announcementFolder = $appDataFolder->get($folderName);
		return $announcementFolder->get($attachment->getData());
	}

	/**
	 * @param Attachment $attachment
	 * @return StreamResponse
	 * @throws \Exception
	 */
	public function display(Attachment $attachment): StreamResponse
    {
		$file = $this->getFileFromRootFolder($attachment);
		$response = new StreamResponse($file->fopen('rb'));
		$response->addHeader('Content-Disposition', 'attachment; filename="' . rawurldecode($file->getName()) . '"');
		$response->addHeader('Content-Type', $this->mimeTypeDetector->getSecureMimeType($file->getMimeType()));
		return $response;
	}

	/**
	 * Should undo be allowed and the delete action be done by a background job
	 *
	 * @return bool
	 */
	public function allowUndo(): bool
    {
		return true;
	}

	/**
	 * Mark an attachment as deleted
	 *
	 * @param Attachment $attachment
	 */
	public function markAsDeleted(Attachment $attachment): void
    {
		$attachment->setDeletedAt(time());
	}
}
