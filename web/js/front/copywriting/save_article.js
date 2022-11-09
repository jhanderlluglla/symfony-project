setInterval(sendArticle, 1000 * 60 * 2); //2 minutes
$("[data-action=\"save\"]").click(function (e) {
    e.preventDefault();
    sendArticle();
});

function sendArticle() {
    var form = $('form[name="copywriting_article"]');
    var data = form.serializeArray();
    var textKey = form.attr('name') + "[text]";

    for(var key in data){
        if(data.hasOwnProperty(key) && data[key]['name'] === textKey) {
            data[key]['value'] = getEditorContent();
        }
    }
    data.push({
        name: form.attr('name') + "[save]",
        value: "",
    });
    $.ajax({
        type: "POST",
        data: data,
        success: function (response) {
            if(response.status === "success"){
                toastr.success(response.message);
            }
        }
    });
}