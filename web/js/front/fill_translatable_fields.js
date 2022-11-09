$('*[data-change-fields="1"]').change(function (e) {
    var target = e.currentTarget;
    var form = $(target).parents("form");

    sendGetRequest(
        Routing.generate('admin_translatable_fields', {'id': form.data('entity-id')}),
        {'language':target.value},
        function (response) {
            for(var attribute in response.attributes){
                var inputName = form.attr('name') + "[" + attribute + "]";
                form.find("[name='" + inputName + "']").val(response.attributes[attribute]);
            }
        }
    );
});