$(document).ready(function () {
    $('#downloadRenamedPlugin').click(function () {
        var fileName = $('#fileName').val();
        var route;
        if(fileName){
            if (!fileName.match(/^([\w\-_.]+)$/)) {
                toastr.error(translations.fileNameError);
                return false;
            }
            route = Routing.generate('app_download_plugin', {'fileName':fileName});
        }else{
            route = Routing.generate('app_download_plugin');
        }
        window.location = route;
    });
});