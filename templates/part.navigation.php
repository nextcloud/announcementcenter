<ul>
	<li>
		<a href="<?php p($_['u_index']); ?>"><?php p($l->t('Announcements')); ?></a>
	</li>
	<?php if ($_['is_admin']): ?>
	<li class="new-announcement">
		<a href="<?php p($_['u_add']); ?>">
			<span><?php p($l->t('Add announcement')); ?></span>
		</a>
	</li>
	<?php endif; ?>
</ul>
