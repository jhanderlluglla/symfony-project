/* Checking up the deploying to the V2 */
var choiceImagesModal = $('#choiceImages');
var pixabayImageContainer = choiceImagesModal.find('#pixabay-images-container .inner-container');
var maxPerPage = 21;

function getAdminImages(query, page, perPage,isCombinedPage){
    var isCombinedPage = isCombinedPage || false;

    var params = {
        search_query: query,
        lang: userLocale,
        page: page,
        per_page: perPage,
    };

    var url = Routing.generate('admin_images_search') + "?" + $.param(params);
    $.get(url, function (response) {
        response = JSON.parse(response); // "unwrap"
        var imageContainer = choiceImagesModal.find('#admin-images-container .inner-container');
        var totalForCurrentPage = response.collection.length;
        var totalCount = response.totalCount;
        var notEnoughCount = maxPerPage - totalForCurrentPage;
        if(totalCount >= 0) {
            if (response.isPaginated) {
                //
                choiceImagesModal.find('#pagination').twbsPagination({
                    totalPages: Math.ceil(totalCount / perPage),
                    visiblePages: 5,
                    initiateStartPageClick: false,
                    first: null,
                    last: null,
                    onPageClick: function onPageClick(event, page) {
                        // check "Pixabay`s visibilty"
                       getAdminImages(query, page, perPage)
                    }
                });
            }
            imageContainer.html(wrapImages(response.collection,true)).parent().show();
            if(notEnoughCount){
                if (!isCombinedPage)
                    getImages(pixabayKey,query, page, perPage,totalCount);
            }else{
                pixabayImageContainer.html('').parent().hide();
            }
        }
        if (totalCount == 0){
            imageContainer.html("<h3 style='width: 100%; text-align: center'>" + translations.noResults + "</h3>").parent().show();
        }
    });
}
