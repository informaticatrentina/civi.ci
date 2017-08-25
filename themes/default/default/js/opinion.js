var ajaxFlag = true;
$(document).ready(function() {
  $('.opinionStatus').click(function() {
    if(ajaxFlag) {
      $('tr').css('backgroundColor', 'white');
      var area = $(this);
      updateOpinionAjax(area);
    }
  });
});
function updateOpinionAjax(area) {
  var currentAuthorId = area.attr('author-id');
  var arrayItems = currentAuthorId.split('-');
  var currentStatus = arrayItems[0];
  var currentId = arrayItems[1];
  if(currentStatus == 'inactive') {
    if($('span[author-id=active-' + currentId + ']').length) {
      alert(t('discussion','Another opinion by same author already active.'));
      $('span[author-id=active-' + currentId + ']').parents('tr').css('backgroundColor', '#FFFF66');
      return false;
    }
  }
  var opinionId = area.attr('opinion-id');
  var tagName = area.attr('tag-name');
  var discussionId = area.attr('discussion-id');
  var status = area.html();
  showLoadingImage(area);
  ajaxFlag = false;
  $.ajax({
    type: 'GET',
    url: page.url,
    dataType: 'json',
    data: {
      opinion_id: opinionId,
      status: status,
      tag_name : tagName,
      discussion_id : discussionId
    },
    success: function(resp) {
      hideLoadingImage(area);
      if(currentStatus == 'active') {
        area.attr('author-id', 'inactive-'+currentId);
      } else {
        area.attr('author-id', 'active-'+currentId);
      }
      if (resp.success) {
        area.html(resp.status);
      } else {
        area.html(status);
        alert(resp.msg);
      }
      ajaxFlag = true;
    },
    error: function(resp){
      hideLoadingImage(area);
      area.html(status);
      ajaxFlag = true;
    }
  });
}