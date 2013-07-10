
$saveQueryButton = $('#action-save-query');
$queries = $('div.query button');

if ($saveQueryButton.length > 0) {
    $saveQueryButton.click(function (event) {
        var saveSqlUrl = Routing.generate('wtw_dashboard_save_sql'),
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

$queries.bind('click', function(event) {
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