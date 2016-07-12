/**
 * AnnouncementCenter
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Joas Schilling <nickvergessen@gmx.de>
 * @copyright Joas Schilling 2015
 */

(function() {
	if (!OCA.AnnouncementCenter) {
		/**
		 * @namespace
		 */
		OCA.AnnouncementCenter = {};
	}

	OCA.AnnouncementCenter.App = {
		ignoreScroll: 0,
		$container: null,
		$content: null,
		lastLoadedAnnouncement: 0,
		sevenDaysMilliseconds: 7 * 24 * 3600 * 1000,

		compiledTemplate: null,
		handlebarTemplate: '<div class="section">' +
				'<h2>{{{subject}}}</h2>' +
				'<em>' +
					'{{time}} {{author}} ' +
					'{{#if announcementId}}' +
						'<span class="visibility has-tooltip" title="{{{visibilityString}}}">' +
							'{{#if visibilityEveryone}}' +
								'<img src="' + OC.imagePath('core', 'places/link') + '">' +
							'{{else}}' +
								'<img src="' + OC.imagePath('core', 'places/contacts-dark') + '">' +
							'{{/if}}' +
						'</span>' +
						'<span class="delete-link">' +
							' — ' +
							'<a href="#" data-announcement-id="{{{announcementId}}}">' +
								t('announcementcenter', 'Delete') +
							'</a>' +
						'</span>' +
					'{{/if}}' +
				'</em>' +
				'{{#if message}}' +
					'<br /><br /><p>{{{message}}}</p>' +
				'{{/if}}' +
			'</div>' +
			'<hr />',

		init: function() {
			this.$container = $('#app-content-wrapper');
			this.$content = $('#app-content');
			this.compiledTemplate = Handlebars.compile(this.handlebarTemplate);

			$('#submit_announcement').on('click', _.bind(this.postAnnouncement, this));
			this.$content.on('scroll', _.bind(this.onScroll, this));

			this.ignoreScroll = 1;
			this.loadAnnouncements();

			$('#groups').each(function (index, element) {
				OC.Settings.setupGroupsSelect($(element));
				$(element).change(function(ev) {
					var groups = ev.val || [];
					groups = JSON.stringify(groups);
					OC.AppConfig.setValue('core', $(this).attr('name'), groups);
				});
			});
		},

		deleteAnnouncement: function() {
			var $element = $(this);
			$.ajax({
				type: 'DELETE',
				url: OC.generateUrl('/apps/announcementcenter/announcement/' + $element.data('announcement-id'))
			}).done(function () {
				var $announcement = $element.parents('.section').first();

				$announcement.slideUp();
				// Remove the hr
				$announcement.next().remove();

				setTimeout(function() {
					$announcement.remove();

					if ($('#app-content-wrapper .section').length == 1) {
						$('#emptycontent').removeClass('hidden');
					}
				}, 750);

			});
		},

		postAnnouncement: function() {
			var self = this;
			OC.msg.startAction('#announcement_submit_msg', t('announcementcenter', 'Announcing…'));

			$.ajax({
				type: 'POST',
				url: OC.generateUrl('/apps/announcementcenter/announcement'),
				data: {
					subject: $('#subject').val(),
					message: $('#message').val(),
					groups: $('#groups').val().split('|')
				}
			}).done(function(announcement) {
				OC.msg.finishedSuccess('#announcement_submit_msg', t('announcementcenter', 'Announced!'));

				var $html = self.announcementToHtml(announcement);
				$('#app-content-wrapper .section:eq(0)').after($html);
				$html.hide();
				setTimeout(function() {
					$html.slideDown();
					$('#emptycontent').addClass('hidden');
				}, 750);

				$('#subject').val('');
				$('#message').val('');
				$('#groups').val('');
			}).fail(function (response) {
				OC.msg.finishedError('#announcement_submit_msg', response.responseJSON.error);
			});
		},

		loadAnnouncements: function() {
			var self = this,
				offset = self.lastLoadedAnnouncement;
			$.ajax({
				type: 'GET',
				url: OC.generateUrl('/apps/announcementcenter/announcement'),
				data: {
					offset: offset
				}
			}).done(function (response) {
				if (response.length > 0) {
					_.each(response, function (announcement) {
						var $html = self.announcementToHtml(announcement);
						$('#app-content-wrapper').append($html);
						if (announcement.id < self.lastLoadedAnnouncement || self.lastLoadedAnnouncement === 0) {
							self.lastLoadedAnnouncement = announcement.id;
						}
					});
					self.ignoreScroll = 0;
				} else if (offset === 0) {
					$('#emptycontent').removeClass('hidden');
				}
			});
		},

		announcementToHtml: function (announcement) {

			var currentTimestamp = new Date().getTime();
			var details = '';
			if (currentTimestamp >= announcement.time * 1000 + this.sevenDaysMilliseconds) {
				// Old announcement show full date
				details += OC.Util.formatDate(announcement.time * 1000)
			} else {
				details += OC.Util.relativeModifiedDate(announcement.time * 1000)
			}

			var object = {
				time: details,
				author: t('announcementcenter', 'by {author}', announcement),
				subject: announcement.subject,
				message: announcement.message,
				visibilityEveryone: null,
				visibilityString: null,
				announcementId: (oc_isadmin) ? announcement.id : 0
			};


			if (oc_isadmin) {
				if (announcement.groups.indexOf('everyone') > -1) {
					object.visibilityEveryone = true;
					object.visibilityString = t('announcementcenter', 'Visible for everyone');
				} else {
					object.visibilityEveryone = false;
					object.visibilityString = t('announcementcenter', 'Visible for groups: {groups}', {
						groups: announcement.groups.join(t('announcementcenter', ', '))
					});
				}
			}

			var $html = $(this.compiledTemplate(object));
			$html.find('span.delete-link a').on('click', this.deleteAnnouncement);
			$html.find('.has-tooltip').tooltip({
				placement: 'bottom'
			});

			return $html;
		},

		onScroll: function () {
			if (this.ignoreScroll <= 0 && this.$content.scrollTop() +
				this.$content.height() > this.$container.height() - 100) {
				this.ignoreScroll = 1;
				this.loadAnnouncements();
			}
		}
	};

})();

$(document).ready(function() {
	OCA.AnnouncementCenter.App.init();
});
