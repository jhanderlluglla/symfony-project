function uploadImage(file, callback) {
    var data = new FormData();
    data.append("image", file);
    data.append("type", "file");

    sendToServer(data, callback);
}

function sendToServer(data, callback) {
    $.ajax({
        data: data,
        type: "POST",
        url: Routing.generate('admin_images_upload'),
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