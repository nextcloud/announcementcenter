<?php
/**
 * @var \OCP\IL10N $l
 */
vendor_script('select2/select2');
vendor_style('select2/select2');
script('settings', 'settings');
?>
<form id="announce" class="section">
	<h2><?php p($l->t('Add announcement')); ?></h2>

	<input type="text" name="subject" id="subject" placeholder="<?php p($l->t('Subject…')); ?>" />
	<br />
	<textarea name="message" id="message" placeholder="<?php p($l->t('Your announcement…')); ?>"></textarea>
	<br />
	<input type="hidden" name="groups" id="groups" placeholder="<?php p($l->t('Groups…')); ?>" style="width: 400px;" />
	<br />
	<em><?php p($l->t('These groups will be able to see the announcement. If no group is selected, all users can see it.')); ?></em>
	<br />
	<input type="button" id="submit_announcement" value="<?php p($l->t('Announce')); ?>" name="submit" />
	<span id="announcement_submit_msg" class="msg"></span>
</form>
