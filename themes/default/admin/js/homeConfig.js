$(document).ready(function() {
  $('.homeConfigUpdate').on('click', function() {
    var layout = $('.home-layout').val();
    if (!(/^[1-9]\d*$/.test(layout))) {
      $('#error-msg').attr('class', 'alert-error');
      $('#error-msg').html(Yii.t('js', 'Please enter valid layout columns.'));
      focusOnError();
      return false;
    }
    $('.homeConfigUpdate').submit();
  });
});

function focusOnError() {
 $("html, body").animate({ scrollTop: 0 }, 600);
}