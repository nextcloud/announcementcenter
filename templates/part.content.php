<?php
if (empty($_['announcements'])) {
	?>
<div id="no-announcements">
	<p><?php p($l->t('No announcements posted')); ?></p>
</div>
<?php
}
foreach ($_['announcements'] as $announcement) {
?>
	<div class="section">
		<h2><?php p($announcement['subject']); ?></h2>
		<em><?php p($announcement['author']); ?> — <?php p(\OCP\Template::relative_modified_date($announcement['time'])); ?></em>

	<?php
	if ($announcement['message'] !== '') {
	?>
		<br />
		<br />
		<p><?php print_unescaped($announcement['message']); ?></p>
	<?php
	}
	?>
	</div>
<?php
}
