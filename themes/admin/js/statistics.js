google.load("visualization", "1", {packages: ["corechart"]});
$(document).ready(function() {
  $('.data-chart-type').click(function() {
    $('#statistics-error-block').hide();
    $('#line-chart').show();
    var chartData = $(this).val();
    if (chartData == '') {
      $('#statistics-error-block').show();
      $('#line-chart').hide();
      $('#statistics-error-msg').html(Yii.t('js','We are getting some error'));
      return false;
    }   
    $.ajax({
      type: 'GET',
      url: statistic_page,
      dataType: 'json',
      data: {
        chart_data: chartData
      },  
      success: function(resp) {
        if (resp.success) {
          if(resp.data != '') {
            drawChart(resp.data);
          } else {
            $('#statistics-error-msg').html(Yii.t('js', 'No data to show'));
            $('#statistics-error-block').show();
            $('#line-chart').hide();
          }
        } else {
          $('#statistics-error-msg').html(Yii.t('js', 'We are getting some error'));
          $('#statistics-error-block').show();
          $('#line-chart').hide();
        }   
      },  
      error: function(resp) {
        $('#statistics-error-msg').html(Yii.t('js','We are getting some error'));
        $('#statistics-error-block').show();
        $('#line-chart').hide();
      }   
    }); 
  }); 
});

function drawChart(respData) {
  data = respData.statistic_data;
  var data = google.visualization.arrayToDataTable(data);
  var options = { 
     title: respData.title,
     is3D: true,
     pieStartAngle: 100
  };  
  var chart = new google.visualization.PieChart(document.getElementById('line-chart'));
  chart.draw(data, options);
}