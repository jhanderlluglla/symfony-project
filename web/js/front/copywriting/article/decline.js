$('#declineArticle').on('click', function() {
    $('#declineExplanation').removeClass('hidden');
    $('#declineControls').addClass('hidden');
});

$('#cancelDecline').on('click', function() {
    $('#declineExplanation').addClass('hidden');
    $('#declineControls').removeClass('hidden');
});

$('form[name="copywriting_article_decline"]').submit(function(e) {
    e.preventDefault();

    var form = $(this);
    var url = form.attr('action');

    $.ajax({
        type: "POST",
        url: url,
        data: form.serialize(),
        success: function(response) {
            if(response.status === "success"){
                toastr.success(response.message);
                form.closest(".modal").modal("hide");
                window.location.href = response.location;
            }else{
                toastr.error(response.message);
            }
        },
        error: function () {
            toastr.error(translations.modal.error_500);
        }
    });
});
