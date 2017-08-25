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
