
function mountDashboard(reqs) {
    var Dashboard = function (reqs) {
        this.$ = reqs.$;
        this.crypto = reqs.crypto;
        this.routes = reqs.routes;
        this.fileSaver = reqs.fileSaver;
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

    Dashboard.prototype.validateDecryptionContext = function (data, errors) {
        if (!data.key) {
            data.type = 'error';
            errors.push('Missing key.');
        }
        if (!data.iv) {
            data.type = 'error';
            errors.push('Missing initialiation vector.');
        }
        if (!data.result) {
            data.type = 'error';
            errors.push('Missing data.');
        }
        if (!data.padding) {
            data.type = 'error';
            errors.push('Missing padding.');
        }

        return errors;
    };

    Dashboard.prototype.decryptMessage = function (crypto, data) {
        var key = crypto.enc.Hex.parse(data.key);
        var iv = crypto.enc.Hex.parse(data.iv);
        var encryptedContent = {
            ciphertext: crypto.enc.Base64.parse(data.result)
        };
        var decryptionOptions = {iv: iv, padding: crypto.pad.NoPadding};
        var decryptedResponse = crypto.AES.decrypt(encryptedContent, key, decryptionOptions)
            .toString(crypto.enc.Latin1);

        return decryptedResponse.substring(data.padding, decryptedResponse.length);
    };

    Dashboard.prototype.suggestDownload = function (content, contentType, filename) {
        this.fileSaver(
            new Blob([content], {type: contentType}),
            filename
        );
    };

    Dashboard.prototype.handleResponse = function (data) {
        var $ = this.$;
        var notification = $('#notification');
        var crypto = this.crypto;

        if (data.shouldSaveAs) {
            var errors = [];

            if (data.shouldDecrypt) {
                this.validateDecryptionContext(data, errors);
                if (errors.length === 0) {
                    var jsonEncodedResponse = this.decryptMessage(crypto, data);
                    this.suggestDownload(jsonEncodedResponse, 'application/json; charset=utf-8', 'perspective.json');
                } else {
                    data.type = 'danger';
                    data.result = errors.join('<br />');
                }
            } else {
                if (data.type === 'error') {
                    data.type = 'danger';
                    errors.push(data.result);
                } else {
                    this.suggestDownload(data.result, 'application/octet-stream; charset=utf-8', 'perspective.bin');
                }
            }

            if (errors.length === 0) {
                var successMessage = 'This perspective ("{}") has been successfully exported.';
                data.result = successMessage.replace('{}', data.shouldSaveAs);
            } else {
                var errorMessage = 'This perspective ("{}") could not have been exported succesfully.';
                errors.unshift(errorMessage.replace('{}', data.shouldSaveAs));
                data.type = 'danger';
                data.result = errors.join('<br />');
            }
        }

        if (data.type !== 'danger') {
            notification.text(data.result);
        } else {
            notification.html(data.result);
        }

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

            $.post(url, {query: query}, self.handleResponse.bind(self));

            event.stopPropagation();
            event.preventDefault();

            return false;
        });
    };

    Dashboard.prototype.bindExportPerspective = function (button, url, hash) {
        var $ = this.$;
        var self = this;

        button.click(function (event) {
            $.get(url, function (data) {
                data.shouldSaveAs = hash;
                data.shouldDecrypt = true;
                self.handleResponse(data);
            })
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
        var routes = self.routes;
        var $ = self.$;

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
                    routes.exportPerspective(hash),
                    hash
                );
            });
        }

        this.bindSubmitQueryListener();
    };

    var dashboard = new Dashboard(reqs);
    dashboard.bindListeners();

    return dashboard;
}

if (window.Routing !== undefined) {
    var routing = window.Routing;
    mountDashboard({
        $: window.jQuery,
        crypto: window.CryptoJS,
        fileSaver: window.saveAs,
        routes: {
            saveQuery: routing.generate('weaving_the_web_dashboard_save_query'),
            exportPerspective: function (hash) {
                return routing.generate('weaving_the_web_dashboard_export_perspective', {hash: hash});
            }
        }
    });
}
