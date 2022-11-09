var pixabayEndpoint = "https://pixabay.com/api/";
var choiceImagesModal = $('#choiceImages');
var pagination = choiceImagesModal.find('#pagination');
var perPage = 21;
var adminImageContainer = choiceImagesModal.find('#admin-images-container .inner-container');
var totalAdminImages = 0;
var choiceCallback = null;

choiceImagesModal.find("#search_images").submit(function (e) {
    e.preventDefault();
    var query = choiceImagesModal.find('#query').val();

    if(pagination.twbsPagination().html() !== ""){
        pagination.twbsPagination('destroy');
    }

    getAdminImages(query,1,perPage);
    totalAdminImages = 0;
});

function getImages(key, query, page, perPage,totalAdminImages){
    totalAdminImages = totalAdminImages || 0;

    // check "real Pixabay pagination page-offset"
    var pixabayRealPage = page;
    var pixabayRequest = true;
    console.log('Total admin - ',totalAdminImages);
    if (totalAdminImages){
        var offset = Math.floor(totalAdminImages / perPage);
        pixabayRealPage -= offset;
        pixabayRequest = (pixabayRealPage > 0);
    }
    if (!pixabayRequest)
        return;

    console.log('Pixabay Request => ',pixabayRequest);

    var params = {
        key: key,
        q: query,
        lang: userLocale,
        page: pixabayRealPage,
        per_page: perPage,
    };

    var url = pixabayEndpoint + "?" + $.param(params);
    $.get(url, function (response) {
        var imageContainer = choiceImagesModal.find('#pixabay-images-container .inner-container');
        var totalHits = response.totalHits;
        console.log('Total hist',totalHits);
        if(totalHits > 0) {
            // check Pixa-pagination. "Extend base pagination"
            totalHits += totalAdminImages;
            var initPaginator = (page*perPage < totalHits);
            if (initPaginator) {
                pagination.twbsPagination('destroy');
                choiceImagesModal.find('#pagination').twbsPagination({
                    totalPages: Math.ceil(totalHits / perPage),
                    visiblePages: 5,
                    initiateStartPageClick: false,
                    first: null,
                    startPage : page,
                    last: null,
                    onPageClick: function onPageClick(event, page) {
                        var currentPixabayOffset = (page-1)*perPage+1;
                        // check "Admin images visibilty"
                        if ( !(totalAdminImages >= currentPixabayOffset) ){
                           adminImageContainer.parent().hide();
                        }else{
                            adminImageContainer.parent().show();
                            getAdminImages(query,page,perPage,true);
                        }
                        if (pixabayRequest) {
                            getImages(pixabayKey, query, page, perPage, totalAdminImages);
                        }
                    }
                });
            }
            imageContainer.html(wrapImages(response.hits,false)).parent().show();
        }else{
            imageContainer.html("<h3 style='width: 100%; text-align: center'>" + translations.noResults + "</h3>").parent().show();
        }
    });

}

function wrapImages(hits,isAdminImages) {
    var images = [];
    isAdminImages = isAdminImages || false;

    for(var hit in hits){
        var element = hits[hit];

        var wrap = document.createElement('div');
        var img = document.createElement('img');

        if (isAdminImages) {
            img.src = element.url;
            img.dataset.large = element.url;
        }else{
            img.src = element.webformatURL;
            img.dataset.large = element.largeImageURL;
        }

        img.dataset.isAdminImage = isAdminImages;

        wrap.classList.add("img-wrap");
        wrap.appendChild(img);

        images.push(wrap);
    }

    return images;
}

choiceImagesModal.on('click', '.img-wrap', function (e) {
    var target = e.target;

    var imgWrap = $(target).closest('.img-wrap');
    var link = target.dataset.large;
    var isAdminImage = target.dataset.isAdminImage;

    $('.img-wrap').removeClass('active');
    imgWrap.addClass('active');

    if (isAdminImage){
        if (choiceCallback){
            choiceCallback(link);
            choiceImagesModal.modal('hide');
        }
    }else{
        uploadByUrl(link, function (response) {
            if(choiceCallback) {
                choiceCallback(response.url);
                choiceImagesModal.modal('hide');
            }
        });
    }

});

function showPixabayModal(newChoiceCallback) {
    choiceImagesModal.modal('show');

    choiceCallback = newChoiceCallback;
}
