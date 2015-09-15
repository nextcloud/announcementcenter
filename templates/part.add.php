<?php
/**
 * @var \OCP\IL10N $l
 */
?>
<form id="announce" class="section">
	<h2><?php p($l->t('Add announcement')); ?></h2>

	<input type="text" name="subject" id="subject" placeholder="<?php p($l->t('Subject…')); ?>" />
	<br />
	<textarea name="message" id="message" placeholder="<?php p($l->t('Your announcement…')); ?>"></textarea>
	<br />
	<input type="button" id="submit_announcement" value="<?php p($l->t('Announce')); ?>" name="submit" />
	<span id="announcement_submit_msg" class="msg"></span>
</form>
