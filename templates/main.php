<?php
/**
 * @var array $_
 * @var \OCP\IL10N $l
 */
script('announcementcenter', [
	'vendor/Caret.js/dist/jquery.caret.min',
	'vendor/At.js/dist/jquery.atwho.min',
	'vendor/Marked.js/dist/marked',
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
		<div class="icon-announcementcenter-dark"></div>
		<h2><?php p($l->t('No Announcements')); ?></h2>
		<p><?php p($l->t('There are currently no announcements…')); ?></p>
	</div>
	
	<div id="lazyload" class="hidden">
		<input type="button" id="lazyload_button" value="<?php p($l->t('Loading More Announcements …')); ?>" name="lazyload" />
	</div>
</div>

<div id="app-sidebar" class="disappear detailsView scroll-container">
	<div id="commentsTabView_header">
		<p><?php p($l->t('Comments')); ?></p>
		<input type="button" id="commentsTabView_close_button" value="X" name="close" />
	</div>
	<div id="commentsTabView" class="tab"></div>
</div>
