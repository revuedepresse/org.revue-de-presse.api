
function mountEditableContents(reqs) {
    var $ = reqs.$;
    var routes = reqs.routes;
    var editableContents = $(reqs.selector);
    var notificationCenter = reqs.notificationCenter;

    var handleResponse = function (data) {
        if (data.status >= 400 && data.status < 500) {
            data = data.responseJSON;
            if (data.type === 'error') {
                data.type = 'danger';
            }
        }

        notificationCenter.showNotification(data);
    };

    editableContents.focusout(function (e) {
        var container = $(this);
        var route = routes.saveContent(container.data('tab'), container.data('key'), container.data('col'));
        var content = container.text();

        $.post(
            route, {
                content: content
            },
            handleResponse
        ).fail(handleResponse);

        e.preventDefault();
        e.stopPropagation();

        return false;
    });
}

if (window && window.Routing && window.jQuery) {
    var routing = window.Routing;
    var routes = {
        saveContent: function (table, key, column) {
            return routing.generate('weaving_the_web_dashboard_save_content', {
                table: table,
                key: key,
                column: column
            });
        }
    };
    mountEditableContents({
        selector: '.editable',
        notificationCenter: window.getNotificationCenter('notification', window.jQuery),
        routes: routes,
        $: window.jQuery
    });
}
