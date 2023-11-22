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

use OCP\AppFramework\Db\Entity;

/**
 * @method void setAnnouncementId(int $announcementId)
 * @method int getAnnouncementId()
 * @method void setType(string $type)
 * @method string getType()
 * @method void setLastModified(int $lastModified)
 * @method string getLastModified()
 * @method void setCreatedAt(int $createdAt)
 * @method string getCreatedAt()
 * @method void setDeletedAt(string $deletedAt)
 * @method string getDeletedAt()
 * @method void setFileId(int $fileId)
 * @method string getFileId()
 */
class Attachment extends RelationalEntity
{
    protected int $announcementId = -1;
    protected $fileId = -1;
    protected $type;
    protected $data;
    protected int $lastModified = 0;
    protected int $createdAt = 0;
    protected mixed $createdBy = '';
    protected int $deletedAt = 0;
    protected array $extendedData = [];


    public function __construct()
    {
        $this->addType('id', 'integer');
        $this->addType('announcementId', 'integer');
        $this->addType('lastModified', 'integer');
        $this->addType('createdAt', 'integer');
        $this->addType('deletedAt', 'integer');
        $this->addType('fileId', 'integer');
        // $this->addResolvable('createdBy');
        // $this->addRelation('extendedData');
    }
}
