"use strict";

var modal = $('#submission_modal');
$('.table').on('click', '*[data-action="rejectTask"]', function (e) {
    var jobId = $(e.target).closest('tr').data(('job-id'));
    var tr = $(this).closest('tr');
    var url = Routing.generate('job_reject', {jobId: jobId});
    sendGetRequest(
        url,
        function (response) {
            modal.find('.modal-title').html(response.title);
            modal.find('.modal-body').html(response.body);
            modal.modal('show');
            modal.find('.save').click(function (e) {
                sendPostRequest(
                    url,
                    {
                        'comment': modal.find('#reject_comment').val()
                    },
                    function (data) {
                        tr.fadeOut(500);
                        modal.modal('hide');
                        toastr.success(data.message);
                    }
                );
            });
        });
});