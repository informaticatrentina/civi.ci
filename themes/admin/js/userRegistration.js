$(document).ready(function() {
  $('#register-submit').click(function(){
    if($('#firstname').val() == '') {
         $('#error').html(Yii.t('js',"Please enter First Name")).css('color','red');
        return false;
    }
     if($('#lastname').val() == '') {
         $('#error').html(Yii.t('js',"Please enter Last Name")).css('color','red');
        return false;
    } 
    var emailRegExp = /^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i;
    var email = $('#email').val();
    if (email == '') {
      $('#error').html(Yii.t('js',"Please enter email id")).css('color','red');
      return false;
    } else if (!emailRegExp.test(email)) {
      $('#error').html(Yii.t('js', 'Please enter valid email id')).css('color','red');
      return false;
    }
    var cemail = $('#cemail').val();
    if (cemail == '') {
      $('#error').html(Yii.t('js',"Please enter same email id")).css('color','red');
      return false;
    } else if (email != cemail) {
      $('#error').html(Yii.t('js',"Please enter same email id")).css('color','red');
      return false;
    }
    var password = $('#password').val();
    if (password == '') {
      $('#error').html(Yii.t('js', "Please enter Password")).css('color', 'red');
      return false;
    }
    var confirmPassword = $('#confirm-password').val();
    if (confirmPassword == '') {
      $('#error').html(Yii.t('js', "Please enter confirm password")).css('color', 'red');
      return false;
    }
    if (password != confirmPassword) {
      $('#error').html(Yii.t('js', "Password does not match")).css('color', 'red');
      return false;
    }
    if ($('#register-terms').is(':checked') == false) {
      $('#error').html(Yii.t('js', 'Please accept terms and condition')).css('color','red');
      return false;
    }
    if ($('#register-privacy').is(':checked') == false) {
      $('#error').html(Yii.t('js', 'Please check privacy checkbox')).css('color','red');
      return false;
    }
    $('#error').html();
  }); 
});

