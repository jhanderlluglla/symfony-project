function initFreeze(){
  $('.table-freeze').each(function() {
    if($(this).find('thead').length > 0 && $(this).find('th').length > 0) {
      // Clone <thead>
      var $window	   = $(window),
        $table = $(this),
        $thead = $table.find('thead').clone(),
        $col = $table.find('thead, > tbody').clone();
      // Add class, remove margins, reset width and wrap table
      $table
        .addClass('sticky-enabled')
        .css({
          margin: 0,
          width: '100%'
        }).wrap('<div class="sticky-wrap" />');

      if($table.hasClass('overflow-y')) $table.removeClass('overflow-y').parent().addClass('overflow-y');

      // Create new sticky table head (basic)
      $table.after('<table class="sticky-thead" />');

      // If <tbody> contains <th>, then we create sticky column and intersect (advanced)
      if($table.find('tbody th').length > 0) {
        $table.after('<table class="sticky-col" /><table class="sticky-intersect" />');
      }

      // Create shorthand for things
      var $stickyHead  = $(this).siblings('.sticky-thead'),
        $stickyCol   = $(this).siblings('.sticky-col'),
        $stickyInsct = $(this).siblings('.sticky-intersect'),
        $stickyWrap  = $(this).parent('.sticky-wrap');

      $stickyHead.append($thead);

      $stickyCol
        .append($col)
        .find('thead th:gt(0)').remove()
        .end()
        .find('tbody td').remove();

      $stickyInsct.html('<thead><tr><th>'+$table.find('thead th:first-child').html()+'</th></tr></thead>');

      // Set widths
      var setWidths = function () {
          setTimeout(function () {
            $table
              .find('thead th').each(function (i) {
                $stickyHead.find('th').eq(i).width($(this).width());
              })
              .end()
              .find('tr').each(function (i) {
              $stickyCol.find('tr').eq(i).height($(this).height());
            });

            // Set width of sticky table head
            $stickyHead.width($table.width());

            // Set width of sticky table col
            $stickyCol.find('th').add($stickyInsct.find('th')).width($table.find('thead th').width())
          },200)
        },
        repositionStickyHead = function () {
          // Return value of calculated allowance
          var allowance = calcAllowance();

          // Check if wrapper parent is overflowing along the y-axis
          if($table.height() > $stickyWrap.height()) {
            // If it is overflowing (advanced layout)
            // Position sticky header based on wrapper scrollTop()
            if($stickyWrap.scrollTop() > 0) {
              // When top of wrapping parent is out of view
              $stickyHead.add($stickyInsct).css({
                opacity: 1,
                visibility: 'visible',
                top: $stickyWrap.scrollTop()
              });
            } else {
              // When top of wrapping parent is in view
              $stickyHead.add($stickyInsct).css({
                opacity: 0,
                visibility: 'hidden',
                top: 0
              });
            }
          } else {
            // If it is not overflowing (basic layout)
            // Position sticky header based on viewport scrollTop
            if($window.scrollTop() > $table.offset().top && $window.scrollTop() < $table.offset().top + $table.outerHeight() - allowance) {
              // When top of viewport is in the table itself
              $stickyHead.add($stickyInsct).css({
                opacity: 1,
                visibility: 'visible',
                top: $window.scrollTop() - $table.offset().top
              });
            } else {
              // When top of viewport is above or below table
              $stickyHead.add($stickyInsct).css({
                opacity: 0,
                visibility: 'hidden',
                top: 0
              });
            }
          }

          if($window.scrollTop() > $table.offset().top) {
            $stickyInsct.css({
              opacity: 1,
              visibility: 'visible',
            });
          } else {
            $stickyInsct.css({
              opacity: 0,
              visibility: 'hidden',
            });
          }
        },
        visibilityShadow = function () {
          if ($table.width() - $stickyWrap.width() < $stickyWrap.scrollLeft() + 10 ){
            $('.directories-table-wrap').removeClass('vis-shadow')
          } else {
            $('.directories-table-wrap').addClass('vis-shadow')
          }
        },
        repositionStickyCol = function () {

          visibilityShadow();

          if($stickyWrap.scrollLeft() > 0) {
            // When left of wrapping parent is out of view
            $stickyCol.css({
              visibility: 'visible'
            }).add($stickyInsct).css({
              opacity: 1,
              left: $stickyWrap.scrollLeft()
            });
          } else {
            // When left of wrapping parent is in view
            $stickyCol
              .css({
                opacity: 0,
                visibility: 'hidden'
              })
              .add($stickyInsct).css({ left: 0 });
          }
        },
        calcAllowance = function () {
          var a = 0;
          // Calculate allowance
          $table.find('tbody tr:lt(3)').each(function () {
            a += $(this).height();
          });

          // Set fail safe limit (last three row might be too tall)
          // Set arbitrary limit at 0.25 of viewport height, or you can use an arbitrary pixel value
          if(a > $window.height()*0.25) {
            a = $window.height()*0.25;
          }

          // Add the height of sticky header
          a += $stickyHead.height();
          return a;
        };

      setWidths();
      visibilityShadow();

      setTimeout(function () {
        $(".sticky-wrap").floatingScroll();
      }, 200);

      $table.parent('.sticky-wrap').scroll($.throttle(20, function() {
        repositionStickyHead();
        repositionStickyCol();
      }));

      $window
      // .load(setWidths)
        .resize($.debounce(250, function () {
          setWidths();
          repositionStickyHead();
          repositionStickyCol();
        }))
        .on('scroll', function () {
          repositionStickyHead();
        });
    }
  });
}
