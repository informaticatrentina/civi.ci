$(document).ready(function() {
  $('#forgot-password-on-forgot-page').click(function() {
    var emailRegExp = /^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i;
    var email = $('#email').val();
    if (email == '') {
      $('#error').html(Yii.t('js', "Please enter email id")).css('color', 'red');
      return false;
    } else if (!emailRegExp.test(email)) {
      $('#error').html(Yii.t('js', 'Please enter valid email id')).css('color', 'red');
      return false;
    } else {
      $('#error').html();
    }
  });

  $('#login-on-forgot-page').click(function() {
    window.location.replace(loginPageUrl);
    return false;
  });
});