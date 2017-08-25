var DiscussionSortingOrder = [];
$(document).ready(function() {
  $('.proposalStatus').click(function() {
    var area = $(this);
    var proposalId = $(this).attr('proposal-id');
    var status = $(this).html();
    if (proposalId == '' && status == '') {
      return false;
    }
    showLoadingImage(area);
    $.ajax({
      type: 'GET',
      url: page.proposalStatusUrl,
      dataType: 'json',
      data: {
        proposal_id: proposalId,
        status: status,
      },
      success: function(resp) {
        hideLoadingImage(area);
        if (resp.success) {
          area.html(resp.status);
        } else {
          area.html(status);
        }
      },
      error: function(resp) {
        hideLoadingImage(area);
        area.html(status);
      }
    });
  });
 
  getDiscussionSortingOrder();
  $('.proposal-sorting-order').on('change', function() {
    var regexForOnlyNumber = /[^0-9]/g;
    var proposalId = $(this).attr('proposal-id');
    var sortingOrder = $.trim($(this).val());
    sortingOrder = sortingOrder.replace(/^0+/, '');
    if (sortingOrder == '') {
      return false;
    }
    if (sortingOrder.match(regexForOnlyNumber)) {
      alert(Yii.t('js', 'Only numbers are allowed'));
      $(this).val('0');
      return false;
    }
    if ($.inArray(sortingOrder, DiscussionSortingOrder) != -1) {
      alert(Yii.t('js', 'Sorting order is already exist. Please add another sorting order'));
      $(this).val('0');
      return false;
    }
    if (sortingOrder != 0) {
      DiscussionSortingOrder.push(sortingOrder);
    }
    saveDiscussionSortingOrder(proposalId, sortingOrder);
  });
});

function saveDiscussionSortingOrder(proposalId, sortingOrder) {
  $.ajax({
    type: 'GET',
    url: baseUrl + 'admin/proposal/order',
    dataType: 'json',
    data: {
      proposal_id: proposalId,
      sorting_order: sortingOrder
    },
    success: function(resp) {
      if (resp.success === false) {
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

function getDiscussionSortingOrder() {
  DiscussionSortingOrder = [];
  $('.proposal-sorting-order').each(function() {
    var sortingOrder = $.trim($(this).val());
    sortingOrder = sortingOrder.replace(/^0+/, '');
    if (typeof sortingOrder != 'undefined' && sortingOrder != '' && sortingOrder != '0') {
      DiscussionSortingOrder.push(sortingOrder);
    }
  });
}