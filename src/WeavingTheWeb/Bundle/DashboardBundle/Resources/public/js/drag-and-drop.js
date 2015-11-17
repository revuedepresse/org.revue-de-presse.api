
var selector = '[data-action="drag-and-drop"]';
if ($(selector).length > 0) {
    var dragAndDrop = $(selector);
    (function (Dropzone, targetId) {
        Dropzone.options[targetId] = {
            dictDefaultMessage: dragAndDrop.attr('data-message')
        };
    })(Dropzone, dragAndDrop.attr('id'));
}

