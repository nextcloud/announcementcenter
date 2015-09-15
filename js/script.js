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
	var TEMPLATE =
	'<div class="section">' +
	'<h2>{{{subject}}}</h2>' +
	'<em>{{author}} — {{time}}</em>' +
	'{{#if message}}' +
	'<br /><br /><p>{{{message}}}</p>' +
	'{{/if}}' +
	'</div>' +
	'<hr />';

	$(document).ready(function () {
		var compiledTemplate = Handlebars.compile(TEMPLATE);

		$('#submit_announcement').click(function () {
			OC.msg.startAction('#announcement_submit_msg', t('announcementcenter', 'Announcing…'));

			$.ajax({
				type: 'POST',
				url: OC.generateUrl('/apps/announcementcenter/announcement'),
				data: {
					subject: $('#subject').val(),
					message: $('#message').val()
				}
			}).done(function(announcement) {
				OC.msg.finishedSuccess('#announcement_submit_msg', t('announcementcenter', 'Announced!'));

				var $html = $(compiledTemplate({
					time: OC.Util.formatDate(announcement.time * 1000),
					author: OC.currentUser,
					subject: announcement.subject,
					message: announcement.message
				}));

				$('#app-content-wrapper .section:eq(0)').after($html);
				$html.hide();
				setTimeout(function() {
					$html.slideDown();
					$('#emptycontent').addClass('hidden');
				}, 750);

				$('#subject').val('');
				$('#message').val('');
			}).fail(function (response) {
				OC.msg.finishedError('#announcement_submit_msg', response.responseJSON.error);
			});

		});

		$.ajax({
			type: 'GET',
			url: OC.generateUrl('/apps/announcementcenter/announcement'),
			data: {
				page: 1
			}
		}).done(function (response) {
			if (response.length > 0) {
				_.each(response, function (announcement) {
					var $html = $(compiledTemplate({
						time: OC.Util.formatDate(announcement.time * 1000),
						author: announcement.author,
						subject: announcement.subject,
						message: announcement.message
					}));
					$('#app-content-wrapper').append($html);
				});
			} else {
				$('#emptycontent').removeClass('hidden');
			}
		});
	});
})(jQuery, OC);
