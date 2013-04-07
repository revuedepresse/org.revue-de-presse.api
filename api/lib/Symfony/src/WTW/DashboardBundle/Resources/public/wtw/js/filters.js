'use strict';

angular.module('WTW.filters', []).filter('title', function () {
        return function (input) {
            var capitalizedFirstLetter,
                titleLength,
                title = '';

            if (typeof input !== 'undefined') {
                capitalizedFirstLetter = input.substring(0, 1).toUpperCase();
                titleLength = input.length;
                title = capitalizedFirstLetter +
                    input.substring(1, titleLength);
            }

            return title.replace('_', ' ');
        }
    }).filter('helperAvatar', function () {
        return function (input, $index) {
            $.get(input, $.proxy(function (data) {
                $('#' + this.id).html(
                    '<img ' +
                        'height="80" ' +
                        'width="80" ' +
                        'src="' + input +  '" />'
                )
            }, { id: $index}));

            return '';
        }
    });
