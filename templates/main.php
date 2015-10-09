<?php
/**
 * @var array $_
 * @var \OCP\IL10N $l
 */
script('announcementcenter', 'script');
style('announcementcenter', 'style');
?>

<div id="app" class="announcementcenter">
	<div id="app-content">
		<div id="app-content-wrapper">
			<?php if ($_['is_admin']) {
				print_unescaped($this->inc('part.add'));
			} ?>

			<div id="emptycontent" class="<?php if ($_['is_admin']): ?>emptycontent-admin <?php endif; ?>hidden">
				<h2><?php p($l->t('No Announcements')); ?></h2>
				<p><?php p($l->t('There are currently no announcements…')); ?></p>
			</div>
		</div>
	</div>
</div>
