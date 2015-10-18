
function mountDashboard($, routes) {
    var Dashboard = function ($, routes) {
        this.$ = $;
        this.routes = routes;
    };

    Dashboard.prototype.bindSubmitQueryListener = function () {
        var $ = this.$;
        var $queries = $('.query button');
        $queries.unbind('click');

        /**
         * For each perspective, clicking on a button, replace the text area value with a query when
         *  - the query has a comment starting with `# show` or `# count`
         *  - the query contains the `select` clause, the `create table` clause or the `drop table` clause
         */
        $queries.bind('click', function (event) {
            var $button = $(event.currentTarget),
                sql = $button.parent().find('.sql').text(),
                trimmedSql = sql.toLowerCase().trim();

            if (
                (trimmedSql.indexOf('# show') == 0) ||
                (trimmedSql.indexOf('# count') == 0) ||
                (trimmedSql.indexOf('select') == 0) ||
                (trimmedSql.indexOf('create table') == 0) ||
                (trimmedSql.indexOf('drop table') == 0)) {
                $('#sql').val(sql);
                $('.container form').submit();
            }

            event.preventDefault();
        });
    };

    Dashboard.prototype.getQuery = function () {
        return this.$('#sql').val();
    };

    Dashboard.prototype.handleResponse = function (data) {
        var notification = $('#notification');

        notification.text(data.result);
        notification.parent().removeClass('alert-error');
        notification.parent().removeClass('alert-block');
        notification.parent().removeClass('alert-success');
        notification.parent().addClass('alert alert-' + data.type);
    };

    Dashboard.prototype.bindSaveQueryListener = function (button, url) {
        var $ = this.$;
        var self = this;

        button.click(function (event) {
            var query = self.getQuery();

            $.post(url, {query: query}, self.handleResponse);

            event.stopPropagation();
            event.preventDefault();

            return false;
        });
    };

    Dashboard.prototype.bindExportPerspective = function (button, url) {
        var $ = this.$;
        var self = this;

        button.click(function (event) {
            $.get(url, self.handleResponse)
            .fail(function (error) {
                self.handleResponse({
                    result:  JSON.parse(error.responseText).error.exception[0].message,
                    type: 'error'
                });
            });

            event.stopPropagation();
            event.preventDefault();

            return false;
        });
    };

    Dashboard.prototype.bindListeners = function () {
        var self = this;
        var routes = this.routes;

        var saveQueryButton = $('#action-save-query');
        var exportPerspectiveButton = $('.perspective button');

        if (saveQueryButton[0]) {
            this.bindSaveQueryListener(saveQueryButton, routes.saveQuery);
        }

        if (exportPerspectiveButton[0]) {
            exportPerspectiveButton.each(function (buttonIndex, button) {
                var hash = $(button).parent().find('.hash').text();
                self.bindExportPerspective(
                    $(button),
                    routes.exportPerspective(hash)
                );
            });
        }

        this.bindSubmitQueryListener();
    };

    var dashboard = new Dashboard($, routes);
    dashboard.bindListeners();

    return dashboard;
}

if (window.Routing !== undefined) {
    var routing = window.Routing;
    mountDashboard(
        window.jQuery,
        {
            saveQuery: routing.generate('weaving_the_web_dashboard_save_query'),
            exportPerspective: function (hash) {
                return routing.generate('weaving_the_web_dashboard_export_perspective', {hash: hash});
            }
        }
    );
}
