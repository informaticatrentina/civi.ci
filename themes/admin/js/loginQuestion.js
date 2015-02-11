$(document).ready(function() {
  $('#save-additional-info').click(function(e) {
    e.preventDefault();
    if ($('#age-range').val() == '') {
      $('#error-msg').html(Yii.t('js', 'Please select age range'));
      focusOnError();
      return false;
    }
    var age = $('#age').val();
    if (age == '') {
      $('#error-msg').html(Yii.t('js', 'Please select age'));
      focusOnError();
      return false;
    } else if (!(/^[0-9]+$/.test(age))) {
      $('#error-msg').html(Yii.t('js', 'Please add valid age'));
      focusOnError();
      return false;
    }
    if ($('#gender').val() == '') {
      $('#error-msg').html(Yii.t('js', 'Please select gender'));
      focusOnError();
      return false;
    }
    var educationLevel = $('#education-level').val();
    if (educationLevel == '') {
      $('#error-msg').html(Yii.t('js', 'Please select education level'));
      focusOnError();
      return false;
    } else if (educationLevel == 'other') {
      if ($.trim($('#education-level-description').val()) == '') {
        $('#error-msg').html(Yii.t('js', 'Please select education level'));
	focusOnError();
        return false;
      }
    }
    if ($('#citizenship').val() == '') {
      $('#error-msg').html(Yii.t('js', 'Please select citizenship'));
      focusOnError();
      return false;
    }
    if ($('#work').val() == '') {
      $('#error-msg').html(Yii.t('js', 'Please select work'));
      focusOnError();
      return false;
    }
    if ($('#public-authority').val() == '') {
      $('#error-msg').html(Yii.t('js', 'Please select authority'));
      focusOnError();
      return false;
    }
    if ($('#authority-description').val() == '') {
      $('#error-msg').html(Yii.t('js', 'Please add authority description'));
      focusOnError();
      return false;
    }
    var profession = $('#profession').val();
    if (profession == '') {
      $('#error-msg').html(Yii.t('js', 'Please select profession'));
      focusOnError();
      return false;
    }
    var residence = $('#residence').val();
    if (residence == '') {
      $('#error-msg').html(Yii.t('js', 'Please select residence'));
      focusOnError();
      return false;
    }
    var association = $('#association').val();
    if (association == '') {
      $('#error-msg').html(Yii.t('js', 'Please select association'));
      focusOnError();
      return false;
    } else if (association == 'other') {
      if ($.trim($('#association-description').val()) == '') {
        $('#error-msg').html(Yii.t('js', 'Please select association'));
	focusOnError();
        return false;
      }
    }
    $('#additional-info-form').submit();
  });
  
  $('#education-level').on('change', function() {
    var educationLevel = $('#education-level').val();
    if (educationLevel == 'other') {
      $('#education-level-description').show();      
    } else {
      $('#education-level-description').val('');     
      $('#education-level-description').hide();     
    }
  });
  
  $('#public-authority').on('change', function() {
    var authority = $('#public-authority').val();
    if (authority != '') {
      $('#authority-description').show();      
    } else {
      $('#authority-description').val('');     
      $('#authority-description').hide();     
    }
  });
  $('#association').on('change', function() {
    var association = $('#association').val();
    if (association == 'other') {
      $('#association-description').show();
    } else {
      $('#association-description').val('');
      $('#association-description').hide();
    }
  });
});

function focusOnError() {
 $("html, body").animate({ scrollTop: 0 }, 600);
}
