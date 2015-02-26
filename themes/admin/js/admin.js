$(document).ready(function() {
  $('#saveProposal').click(function() {
    var msg = Yii.t('js', "Your session has been expired. ");
    msg += " " + Yii.t('js', 'Press Cancel to stay on page. Press Ok to redirect to home page.');
    checkSession(msg);
  });
});

function checkSession(msg) {
  $('#confirm-alert').modal('hide');
  $.ajax({
    type: "POST",
    url: page.base_url + 'admin/existuser',
    dataType: "json",
    success: function(data) {
      if (data.session_exist == false) {        
        var response = confirm(msg);
        if (response) {
          window.location.href = page.base_url;
        } else {
          $('#formModal').modal('show');
        }
      } else {
        $('#confirm-alert').modal('show');
        return true;
      }
    }
  });
}