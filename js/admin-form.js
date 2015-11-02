function formatChange() {
    if (cj('#ticket_attach').val() === '0') {
        cj('span.preview-text').show();
        cj('span.preview-link').hide();
    } else {
        cj('span.preview-text').hide();
        cj('span.preview-link').show();
    }
}

function getQueryStringParam(name) {
    name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
        results = regex.exec(location.search);
    return results == null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
}
