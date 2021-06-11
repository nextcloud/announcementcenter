<?php
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

script('announcementcenter', 'announcementcenter-admin');

/** @var array $_ */
/** @var \OCP\IL10N $l */
?>
<div id="announcementcenter" class="section">
	<h2 class="inlineblock"><?php p($l->t('Announcements')); ?></h2>

	<p>
		<input type="hidden" name="admin_groups" class="admin_groups" value="<?php p($_['adminGroups']) ?>" style="width: 320px;" />
		<br />
		<em><?php p($l->t('These groups will be able to post announcements.')); ?></em>
	</p>
	<br />

	<p>
		<input id="announcementcenter_create_activities" name="create_activities"
			   type="checkbox" class="checkbox" value="1"
			   <?php if ($_['createActivities']) {
	print_unescaped('checked="checked"');
} ?> />
		<label for="announcementcenter_create_activities"><?php p($l->t('Create activities by default'));?></label><br/>
	</p>

	<p>
		<input id="announcementcenter_create_notifications" name="create_notifications"
			   type="checkbox" class="checkbox" value="1"
			   <?php if ($_['createNotifications']) {
	print_unescaped('checked="checked"');
} ?> />
		<label for="announcementcenter_create_notifications"><?php p($l->t('Create notifications by default'));?></label><br/>
	</p>

	<p>
		<input id="announcementcenter_allow_comments" name="allow_comments"
			   type="checkbox" class="checkbox" value="1"
			   <?php if ($_['allowComments']) {
	print_unescaped('checked="checked"');
} ?> />
		<label for="announcementcenter_allow_comments"><?php p($l->t('Allow comments by default'));?></label><br/>
	</p>
</div>
