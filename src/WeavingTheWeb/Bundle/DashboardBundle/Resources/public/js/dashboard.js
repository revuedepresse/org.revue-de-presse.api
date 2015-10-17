
function mountDashboard($, routes) {
    var Dashboard = function ($, routes) {
        var self = this;

        self.jQuery = $;
        self.routes = routes;
    };

    Dashboard.prototype.bindSubmitQueryListener = function () {
        var $queries = $('div.query button');
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

    Dashboard.prototype.bindListeners = function () {
        var self = this;
        var $ = self.jQuery;
        var routes = self.routes;

        var $saveQueryButton = $('#action-save-query');
        var $exportQueryExecutionResultsButton = $('#action-export-query-execution-results');

        if ($saveQueryButton.length > 0) {
            $saveQueryButton.click(function (event) {
                var saveSqlUrl = routes.saveSql,
                    sql = $('#sql').val();

                $.post(saveSqlUrl, {
                    sql: sql
                }, function (data) {
                    var notification = $('#notification');

                    notification.text(data.result);
                    notification.parent().removeClass('alert-error');
                    notification.parent().removeClass('alert-block');
                    notification.parent().removeClass('alert-success');
                    notification.parent().addClass('alert alert-' + data.type);
                });

                event.stopPropagation();
                event.preventDefault();

                return false;
            });
        }

        self.bindSubmitQueryListener();
    };

    var dashboard = new Dashboard($, routes);
    dashboard.bindListeners();

    return dashboard;
}

if (window.Routing !== undefined) {
    mountDashboard(
        window.jQuery,
        {
            saveSql: window.Routing.generate('weaving_the_web_dashboard_save_sql')
        }
    );
}
