$(document).ready(function() {
  $('#change-password').click(function() {
    var password = $('#new-password').val();
    if (password == '') {
      $('#error').removeClass('alert-success');
      $('#error').html(Yii.t('js', "Please enter new Password")).addClass('alert-error');
      return false;
    }
    var confirmPassword = $('#confirm-password').val();
    if (confirmPassword == '') {
      $('#error').removeClass('alert-success');
      $('#error').html(Yii.t('js', "Please enter confirm password")).addClass('alert-error');
      return false;
    }
    if (password != confirmPassword) {
      $('#error').removeClass('alert-success');
      $('#error').html(Yii.t('js', "Password does not match")).addClass('alert-error');
      return false;
    }
    $('#reset-password').submit();
  });
});

