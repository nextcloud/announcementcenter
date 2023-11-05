<?php

/**
 * @copyright Copyright (c) 2018 Ryan Fletcher <ryan.fletcher@codepassion.ca>
 *
 * @author Ryan Fletcher <ryan.fletcher@codepassion.ca>
 *
 * @license GNU AGPL version 3 or any later version
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\AnnouncementCenter\Controller;

use OCP\AppFramework\OCSController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCA\AnnouncementCenter\Service\AttachmentService;
use Psr\Log\LoggerInterface;

class AttachmentController extends OCSController
{
	private $attachmentService;
	private LoggerInterface $logger;
	public function __construct($appName, IRequest $request, AttachmentService $attachmentService, LoggerInterface $logger)
	{
		parent::__construct($appName, $request);
		$this->attachmentService = $attachmentService;
		$this->logger = $logger;
	}

	/**
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 *
	 */
	public function getAll($apiVersion)
	{
		
		$attachment = $this->attachmentService->findAll($this->request->getParam('announcementId'), true);
		if ($apiVersion === '1.0') {
			$attachment = array_filter($attachment, function ($attachment) {
				return $attachment->getType() === 'deck_file';
			});
		}
		return new DataResponse($attachment, HTTP::STATUS_OK);
	}

	/**
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 *
	 */
	public function display($announcementId, $attachmentId, $type = 'deck_file')
	{
		return $this->attachmentService->display($announcementId, $attachmentId, $type);
	}

	/**
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 *
	 */
	public function create($announcementId, $type, $data)
	{
		$attachment = $this->attachmentService->create($announcementId, $type, $data);
		return new DataResponse($attachment, HTTP::STATUS_OK);
	}

	/**
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 *
	 */
	public function update($announcementId, $attachmentId, $data, $type = 'deck_file')
	{
		$attachment = $this->attachmentService->update($announcementId, $attachmentId, $data, $type);
		return new DataResponse($attachment, HTTP::STATUS_OK);
	}

	/**
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 *
	 */
	public function delete($announcementId, $attachmentId, $type = 'deck_file')
	{
		$attachment = $this->attachmentService->delete($announcementId, $attachmentId, $type);
		return new DataResponse($attachment, HTTP::STATUS_OK);
	}

	/**
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 *
	 */
	public function restore($announcementId, $attachmentId, $type = 'deck_file')
	{
		$attachment = $this->attachmentService->restore($announcementId, $attachmentId, $type);
		return new DataResponse($attachment, HTTP::STATUS_OK);
	}
}
