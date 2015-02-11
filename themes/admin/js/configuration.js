$(document).ready(function() {
  $("table tr td").on('blur', "input[type='text'], textarea", function(e) {
    if ($(this).siblings('.action').val() == 'additional_information_status') {
      setHtmlForAdditionalUserInformation($(this));
    }
    $.ajax({
      url: document.URL,
      type: 'POST',
      data: {key: $(this).siblings('.action').val(), value: $(this).val()},
    });
    $(this).closest('td').text($(this).val());
  });
  $("table").on('click', 'td', function(e) {
    if ($(this).attr('class') == 'value') {
      if ($(this).find('input').length) {
        return;
      }
      if ($(this).siblings('.name').text() == 'Text to display on home page when submission if Off') {
        var input = $("<textarea></textarea>").val($(this).text());
      } else {
        var input = $("<input type='text' size='5' />").val($(this).text());
      }
      var action = $("<input class='action' type='hidden' value='5'/>").val($(this).siblings('.hidden').text());
      $(this).empty().append(input);
      $(this).append(action);
    }
  });

  $('#save-additional-info-question').click(function() {
    if (!$('.question-list').is(":checked")) {
      $('#error').show();
      $('#error').addClass('alert-error');
      $('#error').removeClass('alert-success');
      $('#error').html(Yii.t('js', 'Please select atleast one checkbox'));
      return false;
    }
    $.ajax({
      url: saveQuestionUrl,
      type: 'POST',
      data: $('#additional-info-question-form').serialize(),
      dataType: 'json',
      success: function(resp) {
        if (resp.status) {
          $('#error').addClass('alert-success');
          $('#error').removeClass('alert-error');
        } else {
          $('#error').addClass('alert-error');
          $('#error').removeClass('alert-success');
        }
        $('#error').show();
        $('#error').html(resp.msg);
      },
      error: function() {
        $('#error').hide();
      }
    });
    return false;
  });

  $('.proposal-sorting-option').click(function() {
    $.ajax({
      url: document.URL,
      type: 'POST',
      data: {key: 'proposal_sorting_base', value: $(this).val()},
    });
  });
});

function setHtmlForAdditionalUserInformation(area) {
  var html = 'Getting additional user information. (Use 0 for OFF and 1 for ON)';
  if ($(area).val() == 1) {
    html += '<br/><button type="button" class="btn btn-primary btn-lg" data-toggle="modal" data-target="#myModal">Show question for addtional information</button>';
  }
  $(area).parent('.value').siblings('.name').html(html);
}