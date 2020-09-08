<?php
/**
 * @var \OCP\IL10N $l
 * @var array $_
 */
script('settings', 'settings');
?>
<form id="announce" class="section">
	<h2><?php p($l->t('Add announcement')); ?></h2>

	<input type="text" name="subject" id="subject" placeholder="<?php p($l->t('Subject…')); ?>" />
	<br />
	<textarea name="message" id="message" placeholder="<?php p($l->t('Your announcement…')); ?>"></textarea>
	<br />

	<p>
		<input type="hidden" name="groups" id="groups" placeholder="<?php p($l->t('Groups…')); ?>" style="width: 400px;" />
		<br />
		<em><?php p($l->t('These groups will be able to see the announcement. If no group is selected, all users can see it.')); ?></em>
		<br />
	</p>

	<input type="button" id="submit_announcement" class="primary" value="<?php p($l->t('Announce')); ?>" name="submit" />
	<input type="button" id="announcement_options_button" value="<?php p($l->t('Advanced options')); ?>" name="options" />
	<span id="announcement_submit_msg" class="msg"></span>

	<div id="announcement_options" class="hidden">
		<p>
			<input id="create_activities" name="create_activities"
				   type="checkbox" class="checkbox" value="1"
				<?php if ($_['createActivities']) {
	print_unescaped('checked="checked"');
} ?> />
			<label for="create_activities"><?php p($l->t('Create activities'));?></label><br/>
		</p>

		<p>
			<input id="create_notifications" name="create_notifications"
				   type="checkbox" class="checkbox" value="1"
				<?php if ($_['createNotifications']) {
	print_unescaped('checked="checked"');
} ?> />
			<label for="create_notifications"><?php p($l->t('Create notifications'));?></label><br/>
		</p>

		<p>
			<input id="allow_comments" name="allow_comments"
				   type="checkbox" class="checkbox" value="1"
				<?php if ($_['allowComments']) {
	print_unescaped('checked="checked"');
} ?> />
			<label for="allow_comments"><?php p($l->t('Allow comments'));?></label><br/>
		</p>
	</div>
</form>
