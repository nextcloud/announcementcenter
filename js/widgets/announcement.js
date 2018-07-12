/*
 * Nextcloud - Dashboard App
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author regio iT gesellschaft für informationstechnologie mbh
 * @copyright regio iT 2017
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

/** global: OCA */
/** global: net */


(function () {

	/**
	 * @constructs AnnouncementCenter
	 */
	var AnnouncementCenter = function () {

		var announcement = {

			divText: null,
			divInfo: null,
			currAnnoucement: '',

			init: function () {
				announcement.divText = $('#announcement-text');
				announcement.divInfo = $('#announcement-info');

				announcement.divText.on('click', function () {
					window.open(OC.generateUrl('/apps/announcementcenter/?announcement=' +
						announcement.currAnnoucement));
				});
				announcement.divInfo.on('click', function () {
					window.open(OC.generateUrl('/apps/announcementcenter/?announcement=' +
						announcement.currAnnoucement));
				});

				announcement.getLastAnnouncement();
			},


			getLastAnnouncement: function () {
				var request = {
					widget: 'announcement-center',
					request: 'getLastAnnouncement'
				};

				net.requestWidget(request, announcement.displayLastAnnouncement);
			},


			displayLastAnnouncement: function (result) {
				if (result.result === 'fail') {
					return;
				}

				var announce = result.value.lastAnnouncement;
				if (announce.message === undefined) {
					return;
				}

				var comments = n('announcementcenter', '%n comment', '%n comments',
					announce.comments);
				var time = OC.Util.relativeModifiedDate(parseInt(announce.time, 10) * 1000);

				dashboard.setTitle('announcement-center', announce.subject);
				announcement.divText.text(announce.message.replace(/<br \/>/g, '\n\r'));
				announcement.divInfo.text(time + ' - ' + announce.author + ' - ' + comments);

				announcement.currAnnoucement = announce.id;
			},


			push: function (result) {
				console.log('push ' + JSON.stringify(result));
			}
		};

		$.extend(AnnouncementCenter.prototype, announcement);
	};

	OCA.DashBoard.AnnouncementCenter = AnnouncementCenter;
	OCA.DashBoard.announcementCenter = new AnnouncementCenter();

})();


