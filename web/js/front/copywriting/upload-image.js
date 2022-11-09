function uploadImage(file, callback) {
    var data = new FormData();
    data.append("image", file);
    data.append("type", "file");

    sendToServer(data, callback);
}

function uploadByUrl(url, callback) {
    var data = new FormData();
    data.append("url", url);
    data.append("type", "url");

    sendToServer(data, callback);
}

function sendToServer(data, callback) {
    $.ajax({
        data: data,
        type: "POST",
        url: Routing.generate('copywriting_upload_image'),
        cache: false,
        contentType: false,
        processData: false,
        success: function success(response) {
            if (response.status === "success") {
                callback(response);
            } else {
                toastr.error(response.message);
            }
        }
    });
}