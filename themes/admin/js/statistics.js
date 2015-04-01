google.load("visualization", "1", {packages: ["corechart"]});
google.setOnLoadCallback(drawChart);
function drawChart() {
  chartDetails = jQuery.parseJSON(chartDetails);
  for (var chart in chartDetails) {
    if (chartDetails.hasOwnProperty(chart)) {
      data = chartDetails[chart]['data'];
      if (data != '') {
        var dataArray = $.map(data, function(value, index) {
          return [index, value];
        });
        var finalData = [];
        finalData[0] = ["", ""];
        for (var i = 0; i < dataArray.length; i++) {
          finalData[i + 1] = [dataArray[i], dataArray[i + 1]];
          ++i;
        }
        finalData = finalData.filter(function(item) {
          return item !== undefined;
        });
        var data = google.visualization.arrayToDataTable(finalData);
        var options = {
          title: chartDetails[chart]['title'],
          is3D: false,
          pieStartAngle: 100,
          legend: {position: 'top',  maxLines: 5, textStyle: {fontSize: 10}},
          chartArea: { width: '90%', height: '80%', top: '20%'}
        };
        var charting = new google.visualization.PieChart(document.getElementById('line-chart-' + chart));
        charting.draw(data, options);
      } else {
        $('#line-chart-' + chart).html(Yii.t('js', 'No data to show')).addClass('text-error');
      }
    }
  }
}
