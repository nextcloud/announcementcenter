<div class="section">
	<h2><?php p($l->t('Add announcement')); ?></h2>

	<form>
		<input type="text" name="announcement-title" id="announcement-title" placeholder="<?php p($l->t('Title…')); ?>" />
		<br />
		<textarea name="announcement-text" id="announcement-text" placeholder="<?php p($l->t('Your announcement…')); ?>"></textarea>
		<br />
		<button><?php p($l->t('Announce')); ?></button>
	</form>
</div>
