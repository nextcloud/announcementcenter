<?php
/**
 * @var array $_
 * @var \OCP\IL10N $l
 */
\OCP\Util::addScript('oc-backbone-webdav');
vendor_script('core', ['marked/marked.min']);
script('announcementcenter', [
	'vendor/Caret.js/dist/jquery.caret.min',
	'vendor/At.js/dist/js/jquery.atwho.min',
	'script',
	'templates',
	'commentmodel',
	'commentsmodifymenu',
	'commentcollection',
	'commentsummarymodel',
	'commentstabview',
]);
style('announcementcenter', [
	'style',
	'comments',
	'autocomplete',
]);
?>

<div id="app-content" data-is-admin="<?php p(!empty($_['isAdmin']) ? 1 : 0); ?>">
	<?php if ($_['isAdmin']) {
		print_unescaped($this->inc('part.add'));
	} ?>

	<div id="emptycontent" class="<?php if ($_['isAdmin']): ?>emptycontent-admin <?php endif; ?>hidden">
		<div class="icon-announcement"></div>
		<h2><?php p($l->t('No Announcements')); ?></h2>
		<p><?php p($l->t('There are currently no announcements…')); ?></p>
	</div>
</div>

<div id="app-sidebar" class="disappear detailsView scroll-container">
	<div id="commentsTabView" class="tab"></div>
</div>
