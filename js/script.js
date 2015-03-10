/**
 * ownCloud - announcementcenter
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Joas Schilling <nickvergessen@gmx.de>
 * @copyright Joas Schilling 2015
 */

(function ($, OC) {
	$(document).ready(function () {
		$('#submit_announcement').click(function () {
			OC.msg.startAction('#announcement_submit_msg', t('announcementcenter', 'Announcing…'));

			$.ajax({
				type: 'POST',
				url: OC.generateUrl('/apps/announcementcenter/add'),
				data: {
					subject: $('#subject').val(),
					message: $('#message').val()
				}
			}).done(function(){
				OC.msg.finishedSuccess('#announcement_submit_msg', t('announcementcenter', 'Announced!'));
				$('#subject').val('');
				$('#message').val('');
			}).fail(function (response) {
				OC.msg.finishedError('#announcement_submit_msg', response.responseJSON.error);
			});

		});
	});

	$('#app-content').find('.tooltip').tipsy({
		gravity:	's',
		fade:		true
	});
})(jQuery, OC);
