"use strict";

paginationInit();
$('#copywriting_site_filter_search_query').on('input', function (key) {
    getCopywritingSites(this.value, 1, function() {
        paginationInit();
        $('.footable').footable(footableConfig);
    });
});

function getCopywritingSites(query, page, callback) {
    page = page === undefined ? 1 : page
    $('#copywriting_sites_collection').load(
        Routing.generate('admin_copywriting_sites'),
        {
            query: query,
            page: page
        }, callback
    );
}

function changePage(page) {
    var query = $('#copywriting_site_filter_search_query').val();
    getCopywritingSites(query, page, function() {
      $('.footable').footable(footableConfig);
    });
}

function paginationInit() {
    var pagerfanta = $('.pagerfanta');
    if(pagerfanta.html()){
        pagerfanta.twbsPagination('destroy');
    }
    if(countResults > 0) {
        var totalPages = Math.ceil(countResults / maxPerPage);
        pagerfanta.twbsPagination({
            totalPages: totalPages,
            visiblePages: 5,
            initiateStartPageClick: false,
            startPage: 1,
            first: null,
            last: null,
            onPageClick: function onPageClick(event, page) {
                changePage(page);
            }
        });
    }
}
