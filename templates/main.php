<?php
/**
 * @var array $_
 */
script('announcementcenter', 'script');
style('announcementcenter', 'style');
?>

<div id="app">
	<div id="app-content">
		<?php if ($_['is_admin']) {
			print_unescaped($this->inc('part.add'));
		} ?>

		<div id="app-content-wrapper">
		</div>
	</div>
</div>
