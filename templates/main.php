<?php
/**
 * @var array $_
 * @var \OCP\IL10N $l
 */
\OCP\Util::addScript('oc-backbone-webdav');
script('announcementcenter', [
	'script',
	'commentmodel',
	'commentcollection',
	'commentsummarymodel',
	'commentstabview',
]);
style('announcementcenter', [
	'style',
	'comments',
]);
?>

<div id="app" class="announcementcenter" data-is-admin="<?php if ($_['is_admin']) { p(1); } else p(0); ?>">
	<div id="app-content">
		<div id="app-sidebar" class="disappear detailsView scroll-container">
			<div id="commentsTabView" class="tab">

			</div>
		</div>

		<div id="app-content-wrapper">
			<?php if ($_['isAdmin']) {
				print_unescaped($this->inc('part.add'));
			} ?>

			<div id="emptycontent" class="<?php if ($_['isAdmin']): ?>emptycontent-admin <?php endif; ?>hidden">
				<h2><?php p($l->t('No Announcements')); ?></h2>
				<p><?php p($l->t('There are currently no announcements…')); ?></p>
			</div>
		</div>
	</div>
</div>
