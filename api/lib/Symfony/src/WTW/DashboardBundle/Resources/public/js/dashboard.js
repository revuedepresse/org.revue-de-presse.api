
$saveQueryButton = $('#action-save-query');
$queries = $('pre');

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

$queries.bind('dblclick', function(event) {
    var $query = $(event.currentTarget);

    if (
        ($query.text().toLowerCase().trim().indexOf('select') == 0) ||
        ($query.text().toLowerCase().trim().indexOf('create table') == 0) ||
        ($query.text().toLowerCase().trim().indexOf('drop table') == 0)) {
        $('#sql').val($query.text());
        $('.container form').submit();
    }
});