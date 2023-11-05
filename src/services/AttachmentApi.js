/*
 * @copyright Copyright (c) 2020 Jakob Röhrl <jakob.roehrl@web.de>
 *
 * @author Jakob Röhrl <jakob.roehrl@web.de>
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

import axios from "@nextcloud/axios";
import { generateOcsUrl } from "@nextcloud/router";

export class AttachmentApi {
	url(url) {
		return generateOcsUrl(`apps/announcementcenter/api/v1/${url}`);
	}

	async fetchAttachments(announcementId) {
		const response = await axios({
			method: "GET",
			url: this.url(`announcements/${announcementId}/attachments`),
		});
		console.log(response);
		return response.data.ocs.data;
	}

	async createAttachment({ announcementId, formData, onUploadProgress }) {
		console.log(formData);
		const response = await axios({
			method: "POST",
			url: this.url(`announcements/${announcementId}/attachment`),
			data: formData,
			onUploadProgress,
		});
		console.log(response);
		return response.data;
	}

	async updateAttachment({ announcementId, attachment, formData }) {
		const response = await axios({
			method: "POST",
			url: this.url(
				`announcements/${announcementId}/attachment/${attachment.type}:${attachment.id}`
			),
			data: formData,
		});
		return response.data;
	}

	async deleteAttachment(attachment) {
		await axios({
			method: "DELETE",
			url: this.url(
				`announcements/${attachment.announcementId}/attachment/${attachment.type}:${attachment.id}`
			),
		});
	}

	async restoreAttachment(attachment) {
		const response = await axios({
			method: "GET",
			url: this.url(
				`announcements/${attachment.announcementId}/attachment/${attachment.type}:${attachment.id}/restore`
			),
		});
		return response.data;
	}

	async displayAttachment(attachment) {
		const response = await axios({
			method: "GET",
			url: this.url(
				`announcements/${attachment.announcementId}/attachment/${attachment.type}:${attachment.id}`
			),
		});
		return response.data;
	}
}
