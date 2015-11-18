'use strict';

(function (require) {
    var angular = require.angular;
    var weaverApp = require.weaverApp;

    weaverApp.directive('parseUrl', function () {
        var urlPattern = /(http|ftp|https):\/\/[\w-]+(\.[\w-]+)+([\w.,@?^=%&amp;:\/~+#-]*[\w@?^=%&amp;\/~+#-])?/gi;
        return {
            restrict: 'A',
            require: 'ngModel',
            replace: true,
            scope: { props: '=parseUrl', ngModel: '=ngModel' },
            link: function compile(scope, element) {
                scope.$watch('ngModel.text', function (text) {
                    angular.forEach(text.match(urlPattern), function (url) {
                        if (scope.ngModel.parsed === undefined) {
                            var value = text.replace(url, '<a target="' + url + '" href=' + url + '>' + url + '</a>');
                            scope.ngModel.originalText = text;
                            scope.ngModel.parsed = true;
                            element.html(value);
                        }
                    });
                });
            }
        };
    });

    weaverApp.directive('repeatDone', function ($timeout) {
        return {
            restrict: 'A',
            link: function ($scope) {
                if ($scope.$last === true) {
                    $timeout(function () {
                        $scope.$emit('ngRepeatDone');
                    });
                }
            }
        };
    });

    weaverApp.directive('fallbackSrc', function () {
        return {
            restrict: 'A',
            link: function (scope, element, attrs) {
                element.bind('error', function() {
                    angular.element(this).attr('src', attrs.fallbackSrc);
                });
            }
        };
    });
})(this);
