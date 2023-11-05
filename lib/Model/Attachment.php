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

namespace OCA\AnnouncementCenter\Model;

class Attachment extends RelationalEntity
{
    /**
     * @var int
     */
    protected int $announcementId;
	protected $type;
    protected $data;
	protected int $lastModified = 0;
	protected int $createdAt = 0;
	protected mixed $createdBy;
	protected int $deletedAt = 0;
	protected array $extendedData = [];

	public function __construct()
	{
		$this->addType('id', 'integer');
		$this->addType('announcementId', 'integer');
		$this->addType('lastModified', 'integer');
		$this->addType('createdAt', 'integer');
		$this->addType('deletedAt', 'integer');
		$this->addResolvable('createdBy');
		$this->addRelation('extendedData');
	}

    public function setExtendedData(array $array): void
    {
        $this->extendedData=$array;
    }



    public function getData()
    {
        return $this->data;
    }

    public function setData(mixed $fileName): void
    {
        $this->data=$fileName;
    }

    /**
     * @return int
     */
    public function getAnnouncementId(): int
    {
        return $this->announcementId;
    }

    /**
     * @param int $announcementId
     */
    public function setAnnouncementId(int $announcementId): void
    {
        $this->announcementId = $announcementId;
    }

    public function getExtendedData(): array
    {
        return $this->extendedData;
    }

    public function getDeletedAt(): int
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(int $deletedAt): void
    {
        $this->deletedAt = $deletedAt;
    }

    /**
     * @return mixed
     */
    public function getCreatedBy(): mixed
    {
        return $this->createdBy;
    }

    /**
     * @param mixed $createdBy
     */
    public function setCreatedBy(mixed $createdBy): void
    {
        $this->createdBy = $createdBy;
    }

    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    public function setCreatedAt(int $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getLastModified(): int
    {
        return $this->lastModified;
    }

    public function setLastModified(int $lastModified): void
    {
        $this->lastModified = $lastModified;
    }
}
