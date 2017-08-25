google.load("visualization", "1", {packages: ["corechart"]});
google.setOnLoadCallback(drawChart);
function drawChart() {
  chartDetails = jQuery.parseJSON(chartDetails);
  for (var chartDetail in chartDetails) {
    if (chartDetails.hasOwnProperty(chartDetail)) {
      for (var chart in chartDetails[chartDetail]) {
        if (chartDetails[chartDetail].hasOwnProperty(chart)) {
          data = chartDetails[chartDetail][chart]['data'];
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
            title: chartDetails[chartDetail][chart]['title'],
            is3D: true,
            pieStartAngle: 100
          };
          var charting = new google.visualization.PieChart(
            document.getElementById('line_chart_' + chartDetail + '_' + chart));
          charting.draw(data, options);
        }
      }
    }
  }
}
