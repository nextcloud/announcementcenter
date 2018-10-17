(function() {
  var template = Handlebars.template, templates = OCA.AnnouncementCenter.Templates = OCA.AnnouncementCenter.Templates || {};
templates['announcement'] = template({"1":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {});

  return "		<span class=\"visibility has-tooltip\" title=\""
    + ((stack1 = ((helper = (helper = helpers.visibilityString || (depth0 != null ? depth0.visibilityString : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(alias1,{"name":"visibilityString","hash":{},"data":data}) : helper))) != null ? stack1 : "")
    + "\">\n"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.visibilityEveryone : depth0),{"name":"if","hash":{},"fn":container.program(2, data, 0),"inverse":container.program(4, data, 0),"data":data})) != null ? stack1 : "")
    + "		</span>\n";
},"2":function(container,depth0,helpers,partials,data) {
    return "				<img src=\" OC.imagePath('core', 'places/link') + '\">\n";
},"4":function(container,depth0,helpers,partials,data) {
    return "				<img src=\" OC.imagePath('core', 'places/contacts') + '\">\n";
},"6":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "		<span class=\"comment-details\" data-count=\""
    + alias4(((helper = (helper = helpers.num_comments || (depth0 != null ? depth0.num_comments : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"num_comments","hash":{},"data":data}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.comments || (depth0 != null ? depth0.comments : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"comments","hash":{},"data":data}) : helper)))
    + "</span>\n";
},"8":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function";

  return "		<span class=\"delete-link has-tooltip\" title=\""
    + container.escapeExpression(((helper = (helper = helpers.deleteTXT || (depth0 != null ? depth0.deleteTXT : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"deleteTXT","hash":{},"data":data}) : helper)))
    + "'\">\n			<a href=\"#\" data-announcement-id=\""
    + ((stack1 = ((helper = (helper = helpers.announcementId || (depth0 != null ? depth0.announcementId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"announcementId","hash":{},"data":data}) : helper))) != null ? stack1 : "")
    + "\">\n				<img class=\"svg\" src=\" OC.imagePath('core', 'actions/delete') + '\" alt=\"\"/>\n			</a>\n		</span>\n"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.hasNotifications : depth0),{"name":"if","hash":{},"fn":container.program(9, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "");
},"9":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function";

  return "			<span class=\"mute-link has-tooltip\" title=\""
    + container.escapeExpression(((helper = (helper = helpers.removeNotificationTXT || (depth0 != null ? depth0.removeNotificationTXT : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"removeNotificationTXT","hash":{},"data":data}) : helper)))
    + "\">\n				<a href=\"#\" data-announcement-id=\""
    + ((stack1 = ((helper = (helper = helpers.announcementId || (depth0 != null ? depth0.announcementId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"announcementId","hash":{},"data":data}) : helper))) != null ? stack1 : "")
    + "\">\n					<img class=\"svg\" src=\" OC.imagePath('announcementcenter', 'notifications-off.svg') + '\" alt=\"\"/>\n				</a>\n			</span>\n";
},"11":function(container,depth0,helpers,partials,data) {
    var stack1, helper;

  return "		<br /><br /><p>"
    + ((stack1 = ((helper = (helper = helpers.message || (depth0 != null ? depth0.message : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"message","hash":{},"data":data}) : helper))) != null ? stack1 : "")
    + "</p>\n";
},"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "<div class=\"section\" data-announcement-id=\""
    + ((stack1 = ((helper = (helper = helpers.announcementId || (depth0 != null ? depth0.announcementId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"announcementId","hash":{},"data":data}) : helper))) != null ? stack1 : "")
    + "\">\n	<h2>"
    + ((stack1 = ((helper = (helper = helpers.subject || (depth0 != null ? depth0.subject : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"subject","hash":{},"data":data}) : helper))) != null ? stack1 : "")
    + "</h2>\n	<span class=\"has-tooltip live-relative-timestamp\" data-timestamp=\""
    + alias4(((helper = (helper = helpers.timestamp || (depth0 != null ? depth0.timestamp : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"timestamp","hash":{},"data":data}) : helper)))
    + "\" title=\""
    + alias4(((helper = (helper = helpers.dateFormat || (depth0 != null ? depth0.dateFormat : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"dateFormat","hash":{},"data":data}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.dateRelative || (depth0 != null ? depth0.dateRelative : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"dateRelative","hash":{},"data":data}) : helper)))
    + "</span>\n	<span class=\"avatar-name-wrapper\" data-user=\""
    + alias4(((helper = (helper = helpers.authorId || (depth0 != null ? depth0.authorId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"authorId","hash":{},"data":data}) : helper)))
    + "\"><div class=\"avatar\" data-user=\""
    + alias4(((helper = (helper = helpers.authorId || (depth0 != null ? depth0.authorId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"authorId","hash":{},"data":data}) : helper)))
    + "\" data-user-display-name=\""
    + alias4(((helper = (helper = helpers.author || (depth0 != null ? depth0.author : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"author","hash":{},"data":data}) : helper)))
    + "\"></div><strong>"
    + alias4(((helper = (helper = helpers.author || (depth0 != null ? depth0.author : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"author","hash":{},"data":data}) : helper)))
    + "</strong></span>\n"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.isAdmin : depth0),{"name":"if","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.comments : depth0),{"name":"if","hash":{},"fn":container.program(6, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.isAdmin : depth0),{"name":"if","hash":{},"fn":container.program(8, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.message : depth0),{"name":"if","hash":{},"fn":container.program(11, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "</div>\n<hr />\n";
},"useData":true});
templates['comment'] = template({"1":function(container,depth0,helpers,partials,data) {
    return " unread";
},"3":function(container,depth0,helpers,partials,data) {
    return " collapsed";
},"5":function(container,depth0,helpers,partials,data) {
    var stack1;

  return "			<div class=\"avatar\" "
    + ((stack1 = helpers["if"].call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? depth0.actorId : depth0),{"name":"if","hash":{},"fn":container.program(6, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "> </div>\n";
},"6":function(container,depth0,helpers,partials,data) {
    var helper;

  return "data-username=\""
    + container.escapeExpression(((helper = (helper = helpers.actorId || (depth0 != null ? depth0.actorId : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"actorId","hash":{},"data":data}) : helper)))
    + "\"";
},"8":function(container,depth0,helpers,partials,data) {
    var helper;

  return "			<a href=\"#\" class=\"action edit icon icon-rename has-tooltip\" title=\""
    + container.escapeExpression(((helper = (helper = helpers.editTooltip || (depth0 != null ? depth0.editTooltip : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"editTooltip","hash":{},"data":data}) : helper)))
    + "\"></a>\n";
},"10":function(container,depth0,helpers,partials,data) {
    return "		<div class=\"message-overlay\"></div>\n";
},"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "<li class=\"comment"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.isUnread : depth0),{"name":"if","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.isLong : depth0),{"name":"if","hash":{},"fn":container.program(3, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "\" data-id=\""
    + alias4(((helper = (helper = helpers.id || (depth0 != null ? depth0.id : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data}) : helper)))
    + "\">\n	<div class=\"authorRow\">\n"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.avatarEnabled : depth0),{"name":"if","hash":{},"fn":container.program(5, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "		<div class=\"author\">"
    + alias4(((helper = (helper = helpers.actorDisplayName || (depth0 != null ? depth0.actorDisplayName : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"actorDisplayName","hash":{},"data":data}) : helper)))
    + "</div>\n"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.isUserAuthor : depth0),{"name":"if","hash":{},"fn":container.program(8, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "		<div class=\"date has-tooltip live-relative-timestamp\" data-timestamp=\""
    + alias4(((helper = (helper = helpers.timestamp || (depth0 != null ? depth0.timestamp : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"timestamp","hash":{},"data":data}) : helper)))
    + "\" title=\""
    + alias4(((helper = (helper = helpers.altDate || (depth0 != null ? depth0.altDate : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"altDate","hash":{},"data":data}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.date || (depth0 != null ? depth0.date : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"date","hash":{},"data":data}) : helper)))
    + "</div>\n	</div>\n	<div class=\"message\">"
    + ((stack1 = ((helper = (helper = helpers.formattedMessage || (depth0 != null ? depth0.formattedMessage : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"formattedMessage","hash":{},"data":data}) : helper))) != null ? stack1 : "")
    + "</div>\n"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.isLong : depth0),{"name":"if","hash":{},"fn":container.program(10, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "</li>\n";
},"useData":true});
templates['commentsview'] = template({"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "<ul class=\"comments\">\n</ul>\n<div class=\"emptycontent hidden\"><div class=\"icon-comment\"></div>\n<p>"
    + alias4(((helper = (helper = helpers.emptyResultLabel || (depth0 != null ? depth0.emptyResultLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"emptyResultLabel","hash":{},"data":data}) : helper)))
    + "</p></div>\n<input type=\"button\" class=\"showMore hidden\" value=\""
    + alias4(((helper = (helper = helpers.moreLabel || (depth0 != null ? depth0.moreLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"moreLabel","hash":{},"data":data}) : helper)))
    + "\" name=\"show-more\" id=\"show-more\" />\n<div class=\"loading hidden\" style=\"height: 50px\"></div>\n";
},"useData":true});
templates['edit_comment'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper;

  return "			<div class=\"avatar\" data-username=\""
    + container.escapeExpression(((helper = (helper = helpers.actorId || (depth0 != null ? depth0.actorId : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"actorId","hash":{},"data":data}) : helper)))
    + "\"></div>\n";
},"3":function(container,depth0,helpers,partials,data) {
    var helper;

  return "			<a href=\"#\" class=\"action delete icon icon-delete has-tooltip\" title=\""
    + container.escapeExpression(((helper = (helper = helpers.deleteTooltip || (depth0 != null ? depth0.deleteTooltip : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"deleteTooltip","hash":{},"data":data}) : helper)))
    + "\"></a>\n";
},"5":function(container,depth0,helpers,partials,data) {
    var helper;

  return "				<input class=\"cancel pull-right\" type=\"button\" value=\""
    + container.escapeExpression(((helper = (helper = helpers.cancelText || (depth0 != null ? depth0.cancelText : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"cancelText","hash":{},"data":data}) : helper)))
    + "\" />\n";
},"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "<div class=\"newCommentRow comment\" data-id=\""
    + alias4(((helper = (helper = helpers.id || (depth0 != null ? depth0.id : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data}) : helper)))
    + "\">\n	<div class=\"authorRow\">\n"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.avatarEnabled : depth0),{"name":"if","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "		<div class=\"author\">"
    + alias4(((helper = (helper = helpers.actorDisplayName || (depth0 != null ? depth0.actorDisplayName : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"actorDisplayName","hash":{},"data":data}) : helper)))
    + "</div>\n"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.isEditMode : depth0),{"name":"if","hash":{},"fn":container.program(3, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "		</div>\n		 <form class=\"newCommentForm\">\n			<textarea rows=\"1\" class=\"message\" placeholder=\""
    + alias4(((helper = (helper = helpers.newMessagePlaceholder || (depth0 != null ? depth0.newMessagePlaceholder : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"newMessagePlaceholder","hash":{},"data":data}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.message || (depth0 != null ? depth0.message : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"message","hash":{},"data":data}) : helper)))
    + "</textarea>\n			<input class=\"submit icon-confirm\" type=\"submit\" value=\"\" />\n"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.isEditMode : depth0),{"name":"if","hash":{},"fn":container.program(5, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "		<div class=\"submitLoading icon-loading-small hidden\"></div>\n	</form>\n</div>\n";
},"useData":true});
})();