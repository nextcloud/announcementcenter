<ul>
	<li id="announcements-list"><a href="#"><?php p($l->t('Announcements')); ?></a></li>
	<?php if ($_['is_admin']): ?>
	<li id="new-announcement" class="new-announcement"><a href="#"><span><?php p($l->t('Add announcement')); ?></span></a></li>
	<?php endif; ?>
</ul>
