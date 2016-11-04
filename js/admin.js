/**
 * @copyright Copyright (c) 2016, Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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

$(document).ready(function() {
	var $announcementCenter = $('#announcementcenter'),
		$adminGroup = $announcementCenter.find('.admin_groups');

	OC.Settings.setupGroupsSelect($adminGroup);
	$adminGroup.change(function(event) {
		var groups = event.val || ['admin'];
		groups = JSON.stringify(groups);
		OCP.AppConfig.setValue('announcementcenter', 'admin_groups', groups);
	});

	$announcementCenter.find('.checkbox').change(function() {
		OCP.AppConfig.setValue('announcementcenter', $(this).attr('name'), (this.checked ? 'yes' : 'no'));
	});
});
