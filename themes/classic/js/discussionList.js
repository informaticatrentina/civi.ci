var discussionSortingOrder = [];
$(document).ready(function() {
  getDiscussionSortingOrder();
  
  $('.sort-order').on('change', function() {
    var regexForOnlyNumber = /[^0-9]/g;
    var discussionSlug = $(this).attr('discussion-slug');
    var sortingOrder = $.trim($(this).val());
    sortingOrder = sortingOrder.replace(/^(0)/, '');
    if (sortingOrder == '') {
      return false;
    }
    if (sortingOrder.match(regexForOnlyNumber)) {
      alert(Yii.t('js', 'Only numbers are allowed'));
      $(this).val('0');
      return false;
    }
    if ($.inArray(sortingOrder, discussionSortingOrder) != -1) {
      alert(Yii.t('js', 'Sorting order is already exist. Please add another sorting order'));
      $(this).val('0');
      return false;
    }
    if (sortingOrder != 0) {
      discussionSortingOrder.push(sortingOrder);
    }
    saveDiscussionSortingOrder(discussionSlug, sortingOrder);

  });
});

function getDiscussionSortingOrder() {
  discussionSortingOrder = [];
  $('.sort-order').each(function() {
    var sortingOrder = $.trim($(this).val());
    if (typeof sortingOrder != 'undefined' && sortingOrder != '' && sortingOrder != '0' ) {
      discussionSortingOrder.push(sortingOrder);
    }
  });
}

function saveDiscussionSortingOrder(discussionSlug, sortingOrder) {
  $.ajax({
    type: 'GET',
    url: baseUrl + 'admin/discussion/order',
    dataType: 'json',
    data: {
      discussion_slug: discussionSlug,
      sorting_order: sortingOrder
    },
    success: function(resp) {
      if (resp.success == false) {
        alert(Yii.t('js', 'Unable to save sorting order. Please try again'));
        $(this).val('0');
      } else {
        getDiscussionSortingOrder();
      }
    },
    error: function() {
      alert(Yii.t('js', 'An error occured. Please try again'));
      $(this).val('0');
    }
  });
}