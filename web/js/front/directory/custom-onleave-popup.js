$(document).ready(function () {
    let initVal = $('#directories_and_blogs').val(),
        newVal,
        unsaved;

    function checkChanges() {
        newVal = $('#directories_and_blogs').val();
        unsaved = JSON.stringify(initVal) !== JSON.stringify(newVal);
    }

    $('.ibox-content').on('change', '#tableAll :checkbox', function () {
        checkChanges()
    });

    $('.ibox-content').on('change', '#directories_and_blogs', function () {
        checkChanges()
    });

    $(document).on('click', 'form[name="admin_directories_list_relation"] button[type="button"]', function () {
        unsaved = false;
        $('form[name="admin_directories_list_relation"]').submit();
    });

    $(window).on('beforeunload', function () {
        if (unsaved) {
            return translations.errorMessage;
        }
    });
});

