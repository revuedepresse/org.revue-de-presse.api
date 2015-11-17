
function mountDashboard(reqs) {
    var Dashboard = function (reqs) {
        this.$ = reqs.$;
        this.crypto = reqs.crypto;
        this.routes = reqs.routes;
        this.fileSaver = reqs.fileSaver;
        this.notificationCenter = reqs.notificationCenter;
    };

    Dashboard.prototype.bindSubmitQueryListener = function () {
        var self = this;
        var $ = self.$;
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
                $('.container form#edit-query').submit();
            } else {
                self.notificationCenter.showNotification({
                    result: 'Sorry, your query can not be executed as it has been considered to be unsafe.',
                    type: 'danger'
                });
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
        if (data.padding === undefined) {
            data.type = 'error';
            errors.push('Missing padding.');
        }

        return errors;
    };

    Dashboard.prototype.suggestDownload = function (content, contentType, filename) {
        this.fileSaver(
            new Blob([content], {type: 'application/zip'}),
            filename
        );
    };

    Dashboard.prototype.prepareSuccessNotification = function (data) {
        var successMessage = 'This perspective ("{}") has been successfully exported.';
        data.result = successMessage.replace('{}', data.shouldSaveAs);
    };

    Dashboard.prototype.decryptMessage = function (crypto, data, success) {
        var worker = new Worker('js/compiled/deciphering-worker.js');

        worker.addEventListener('message', function(e) {
            var decryptedMesage = e.data;
            var unpaddedData = decryptedMesage.substring(data.padding, decryptedMesage.length);
            success(unpaddedData);
        }, false);

        worker.postMessage(data);
    };

    Dashboard.prototype.decryptNativelyMessage = function (data, errors, success) {
        var worker = new Worker('js/compiled/natively-deciphering-worker.js');

        worker.addEventListener('message', function(e) {
            if (e.data.error) {
                errors.push(e.data.error);
            } else {
                success(e.data);
            }
        }, false);

        worker.postMessage(data);
    };

    Dashboard.prototype.handleResponse = function (data) {
        var crypto = this.crypto;
        var self = this;

        if (data.shouldSaveAs) {
            var errors = [];

            if (data.shouldDecrypt) {
                this.validateDecryptionContext(data, errors);
                if (errors.length === 0) {
                    var success = function (jsonEncodedResponse) {
                        self.suggestDownload(jsonEncodedResponse,
                            'application/json; charset=utf-8', 'perspective.json'
                        );
                        self.prepareSuccessNotification(data);
                        self.notificationCenter.showNotification(data);
                    };

                    if (data.shouldDecryptNatively) {
                        this.decryptNativelyMessage(data, errors, success);
                    } else {
                        this.decryptMessage(crypto, data, success);
                    }
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
                    this.prepareSuccessNotification(data);
                }
            }

            if (errors.length > 0) {
                var errorMessage = 'This perspective ("{}") could not have been exported succesfully.';
                errors.unshift(errorMessage.replace('{}', data.shouldSaveAs));
                data.type = 'danger';
                data.result = errors.join('<br />');
            }
        }

        if (!data.shouldSaveAs || !data.shouldDecrypt || errors.length > 0) {
            self.notificationCenter.showNotification(data);
        }
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

    Dashboard.prototype.bindExportPerspectiveListeners = function (button, url, hash) {
        var $ = this.$;
        var self = this;

        button.click(function (event) {
            $.get(url, function (data) {
                data.shouldSaveAs = hash;
                data.shouldDecrypt = true;
                data.shouldDecryptNatively = true;
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

    Dashboard.prototype.getNavigationFormId = function () {
        return 'navigation-form';
    };

    Dashboard.prototype.getJsonPerspectiveSelector = function () {
        return '.perspective-json';
    };

    Dashboard.prototype.getHashSelector = function () {
        return '.hash';
    };

    Dashboard.prototype.getNavigationForm = function () {
        var $ = this.$;
        var navigationFormSelector = '#' + this.getNavigationFormId();
        var navigationForm;
        if ($(navigationFormSelector).length === 0) {
            navigationForm = $('<form />', {
                id: this.getNavigationFormId(),
                method: 'GET',
                'class': 'hide'
            });
        } else {
            navigationForm = $(navigationFormSelector);
        }

        return navigationForm;
    };

    Dashboard.prototype.bindGoToJsonPerspectiveListeners = function () {
        var self = this;
        var $ = self.$;

        var navigationForm = self.getNavigationForm();
        $('body').append(navigationForm);

        var jsonPerspectiveSelector = self.getJsonPerspectiveSelector();
        $(jsonPerspectiveSelector + ' button').each(function (index, element) {
            var hashSelector = self.getHashSelector();
            var button = $(element);
            var contentPlaceholder = button.parent().find(hashSelector);
            var hash = contentPlaceholder.text();

            button.click(function (e) {
                e.preventDefault();
                navigationForm.attr('action', self.routes.showPerspective(hash));
                navigationForm.submit();

                return false;
            });
        });
    };

    Dashboard.prototype.bindListeners = function () {
        var self = this;
        var routes = self.routes;
        var $ = self.$;

        self.bindSubmitQueryListener();

        var saveQueryButton = $('#action-save-query');
        if (saveQueryButton[0]) {
            self.bindSaveQueryListener(saveQueryButton, routes.saveQuery);
        }

        var exportPerspectiveButton = $('.perspective button');
        if (exportPerspectiveButton[0]) {
            exportPerspectiveButton.each(function (buttonIndex, button) {
                var hash = $(button).parent().find('.hash').text();
                self.bindExportPerspectiveListeners(
                    $(button),
                    routes.exportPerspective(hash),
                    hash
                );
            });
        }

        self.bindGoToJsonPerspectiveListeners();
    };

    var dashboard = new Dashboard(reqs);
    dashboard.bindListeners();

    return dashboard;
}

if (window.Routing !== undefined) {
    var routing = window.Routing;
    var notificationCenter;

    if (window.getNotificationCenter) {
        notificationCenter = window.getNotificationCenter('notification', window.jQuery);
    }

    mountDashboard({
        $: window.jQuery,
        crypto: window.CryptoJS,
        fileSaver: window.saveAs,
        notificationCenter: notificationCenter,
        routes: {
            saveQuery: routing.generate('weaving_the_web_dashboard_save_query'),
            showPerspective: function (hash) {
                return routing.generate('weaving_the_web_dashboard_show_perspective', {hash: hash});
            },
            exportPerspective: function (hash) {
                return routing.generate('weaving_the_web_dashboard_export_perspective', {hash: hash});
            }
        }
    });
}
