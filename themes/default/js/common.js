var e_date = '';
var s_date = '';
$(document).ready(function() {
  if ($('#startDate').length > 0) { 
    $( "#startDate" ).datepicker({
      changeMonth: true,
      changeYear: true,
      yearRange:"-90:+0",
      onSelect: function( selectedDate ) {
        $( "#endDate" ).datepicker( "option", "minDate", selectedDate );        
      }
    });
    $( "#endDate" ).datepicker({
      changeMonth: true,
      changeYear: true,
      yearRange:"-90:+0",
      onSelect: function( selectedDate ) {
        $( "#startDate" ).datepicker( "option", "maxDate", selectedDate );       
      }
    });
  }
  
  $('.login-link').click(function() {
    var pathname = window.location.pathname;
    $.pageslide({ direction: 'left', href: page.base_url + 'login?back=' + pathname });
    return false; 
  });
  
  $('.register-link').click(function() {
    var pathname = window.location.pathname;
    $.pageslide({ direction: 'left', href: page.base_url + 'register?back=' + pathname});
    return false; 
  });

  if ($('#btns').length > 0) $('#btns').trigger('click', function(){});
  if ($('#btn-show-use-nickname').length > 0) $('#btn-show-use-nickname').trigger('click', function(){});

  $('#check_availability').on('click', function() {
    ajaxNicknameSearch();
  });

  $('#never_add_nickname').on('change', function() {
    if ($(this).is(':checked')) {
      $('#never_add_nickname_popup').show();
    } else {
      $('#never_add_nickname_popup').hide();
    }
  });

  $('#save_nickname').on('click', function() {
    saveNickname();
  });

  $('#never_display_nickname').on('change', function() {
    if ($(this).is(':checked')) {
      $('#never_display_nickname_popup').show();
      $('#btn_never_display_nickname').show();
      $('#use_nickname').addClass('disabled');
      $('#use_nickname').prop("disabled",true);
      $('<input>').attr({type: 'hidden',id: 'btn_use_nickname', name: 'btn_use_nickname', value: '1'}).appendTo('form');
    } else {
      $('#never_display_nickname_popup').hide();
      $('#btn_never_display_nickname').hide();
      $('#use_nickname').removeClass('disabled');
      $('#use_nickname').prop("disabled",false);
      $('#btn_use_nickname').remove();
    }
  });


 /* $('#use_nickname').on('click', function(e) {
    e.preventDefault();
    var never_display_nickname = $('#never_display_nickname').val();

    $.ajax({
      type: 'POST',
      url: baseUrl + 'user/displaynickname',
      dataType: 'json',
      data:  {
        never_display_nickname : nickname
      },
      success: function(resp) {
        if (resp.success) {
         
        } else {
         
        }
      },
      error: function() {
     
      }
    });

  });*/

});

function showLoadingImage(area) {
  $(area).hide();
  $(area).siblings('.loading-image').show();
}
function hideLoadingImage(area) {
  $(area).siblings('.loading-image').hide();
  $(area).show(); 
}

function ajaxNicknameSearch() {
  var nickname = $('#nickname').val();
  if (nickname.length > 0) {
    $('#load_image_nickname').show();
    $.ajax({
      type: 'GET',
      url: baseUrl + 'user/checknickname',
      dataType: 'json',
      data: {
        nickname : nickname
      },
      success: function(resp) {
        if (resp.success) {
          $('.display_message_nickname').removeClass('alert-success');
          $('.display_message_nickname').addClass('alert-danger');
          $('.display_message_nickname').html(resp.msg);
        } else {
          $('.display_message_nickname').removeClass('alert-danger');
          $('.display_message_nickname').addClass('alert-success');
          $('.display_message_nickname').html(resp.msg);
        }
        $('#load_image_nickname').hide();
      },
      error: function() {
        $('#load_image_nickname').hide();
        alert(Yii.t('js', 'Some error occured, please try again'));
      }
    });
  }
  $('.display_message_nickname').html('');
}

function saveNickname() {
  var nickname = $('#nickname').val();
  var nicknameCheck = $('#never_add_nickname').is(':checked');
  if (nickname !== '' || nicknameCheck) {
    var neverAddNickname = '0';
    if (nickname === '') nickname = '';
    if (nicknameCheck) neverAddNickname = '1';
    $('#load_image_nickname').show();
    $.ajax({
      type: 'GET',
      url: baseUrl + 'user/savenickname',
      dataType: 'json',
      data: {
        nickname: nickname,
        neverAddNickname: neverAddNickname
      },
      success: function(resp) {
        if (resp.success) {
          $('.display_message_nickname').
            removeClass('alert-danger').
            addClass('alert-success').
            html(resp.msg);
          $('#myModal').remove();
          $('#btns').remove();
          $('.modal-backdrop').remove();
        } else {
          $('.display_message_nickname').
            removeClass('alert-success').
            addClass('alert-danger').
            html(resp.msg);
        }
        $('#load_image_nickname').hide();
      },
      error: function() {
        $('#load_image_nickname').hide();
        $('.display_message_nickname').
          removeClass('alert-success').
          addClass('alert-danger').
          html(Yii.t('js', 'Some error occured please try again'));
      }
    });
  }
}
