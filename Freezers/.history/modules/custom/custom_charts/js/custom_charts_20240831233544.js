(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.customChartsAndData = {
    attach: function (context, settings) {
      $(document).ready(function () {
        var chartRoot;
        var updateInterval;
        var currentFreezerName = null;
        var selectedDuration = '24hr'; // Default value

        $('#buttonTrigger').html(`
          <button id="show-graph" class="btn btn-primary">Show Graph</button>
          <button id="show-data" class="btn btn-secondary">Show Data</button>
        `);

        $('#show-graph').on('click', function () {
          $(this).removeClass('btn-secondary').addClass('btn-primary');
          $('#show-data').removeClass('btn-primary').addClass('btn-secondary');
          $('#chartdiv').show();
          $('#freezer-data-container').hide();
          toggleFilterButtons(true); 
        });

        $('#show-data').on('click', function () {
          $(this).removeClass('btn-secondary').addClass('btn-primary');
          $('#show-graph').removeClass('btn-primary').addClass('btn-secondary');
          $('#chartdiv').hide();
          $('#freezer-data-container').show();
          toggleFilterButtons(true); 
        });

        $('#freezer-title, #buttonTrigger, #chartdiv, #freezer-data-container').hide();

        function showStatusMessage(message) {
          $("#Status").html('<h2 class="text-center">' + message + '</h2>');
        }

        function hideStatusMessage() {
          $("#Status").html('');
        }

        function toggleFilterButtons(show) {
          if (show) {
            $('.filter-buttons').removeClass('d-none');
          } else {
            $('.filter-buttons').addClass('d-none');
          }
        }

        function clearCache() {
          if (chartRoot) {
            chartRoot.dispose();
            chartRoot = null;
            console.log("Chart root disposed");
          }
          if (updateInterval) {
            clearInterval(updateInterval);
            updateInterval = null;
            console.log("Update interval cleared");
          }
        }

        function fetchData(url, successCallback) {
          $.ajax({
            url: url,
            method: "GET",
            dataType: "json",
            success: successCallback,
            error: function (xhr, status, error) {
              console.error("Error fetching data:", status, error);
              console.error("Response:", xhr.responseText);
              clearCache();
              $("#chartdiv").hide(); 
              $("#freezer-title").hide(); 
              $('#buttonTrigger, #freezer-data-container').hide(); 
              toggleFilterButtons(false); 
            },
          });
        }

        function generateUrl(freezerName) {
          var cacheBuster = new Date().getTime();
          var url = `/Freezers/custom-charts/freezer-data/${freezerName}?t=${cacheBuster}&duration=${selectedDuration}`;
          console.log("Generated URL:", url); 
          return url;
        }

        function initializeChart(freezerName) {
          console.log("Initializing chart for freezerName:", freezerName);
          if (!freezerName) {
            console.error("No freezer name provided.");
            clearCache();
            return;
          }

          var chartDiv = document.getElementById("chartdiv");
          var freezerTitleDiv = document.getElementById("freezer-title");

          if (!chartDiv || !freezerTitleDiv) {
            console.error("Chart container or title container not found!");
            return;
          }

          clearCache();
          $("#chartdiv").show(); 
          $("#freezer-title").show().text(freezerName); 
          $('#buttonTrigger').show(); 
          $('#freezer-data-container').hide(); 
          toggleFilterButtons(true); 
          hideStatusMessage(); 

          var url = generateUrl(freezerName);

          fetchData(url, function (data) {
            console.log("Data fetched successfully:", data);

            if (data.length === 0) {
              console.error("No data returned from the server.");
              clearCache();
              $("#chartdiv").hide(); 
              $("#freezer-title").hide(); 
              $('#buttonTrigger, #freezer-data-container').hide(); 
              toggleFilterButtons(false); 
              return;
            }

            try {
              am5.ready(function () {
                console.log("am5 ready");

                chartRoot = am5.Root.new("chartdiv");
                console.log("Root element created");

                chartRoot.setThemes([am5themes_Animated.new(chartRoot)]);

                var chartHeight = 400; 
                chartDiv.style.height = chartHeight + "px";
                console.log("Chart height set");

                var chart = chartRoot.container.children.push(
                  am5xy.XYChart.new(chartRoot, {
                    panX: true,
                    panY: true,
                    wheelX: "zoomXY",
                    wheelY: "zoomXY",
                    pinchZoomX: true,
                    pinchZoomY: true,
                  })
                );
                console.log("Chart created");

                var xAxis = chart.xAxes.push(
                  am5xy.DateAxis.new(chartRoot, {
                    groupData: false,
                    baseInterval: { timeUnit: "minute", count: 1 },
                    renderer: am5xy.AxisRendererX.new(chartRoot, {}),
                    maxDeviation: 0,
                  })
                );

                if (selectedDuration !== 'All') {
                  var now = new Date();
                  var startTime = new Date();
                  switch (selectedDuration) {
                    case '1hr':
                      startTime.setHours(now.getHours() - 1);
                      break;
                    case '3hr':
                      startTime.setHours(now.getHours() - 3);
                      break;
                    case '6hr':
                      startTime.setHours(now.getHours() - 6);
                      break;
                    case '12hr':
                      startTime.setHours(now.getHours() - 12);
                      break;
                    case '24hr':
                    default:
                      startTime.setHours(0, 0, 0, 0);
                      now.setHours(24, 0, 0, 0);
                      break;
                  }

                  xAxis.set("min", startTime.getTime());
                  xAxis.set("max", now.getTime());
                } else {
                  var minDate = Math.min(...data.map(d => d.date));
                  var maxDate = Math.max(...data.map(d => d.date));
                  xAxis.set("min", minDate);
                  xAxis.set("max", maxDate);
                }

                var yAxis = chart.yAxes.push(
                  am5xy.ValueAxis.new(chartRoot, {
                    renderer: am5xy.AxisRendererY.new(chartRoot, {}),
                  })
                );
                console.log("Axes created");

                var series = chart.series.push(
                  am5xy.LineSeries.new(chartRoot, {
                    name: "Series 1",
                    xAxis: xAxis,
                    yAxis: yAxis,
                    valueYField: "value",
                    valueXField: "date",
                    stroke: am5.color(0x00ff00),
                  })
                );

                series.data.setAll(data);
                console.log("Data set in series");

                series.set(
                  "tooltip",
                  am5.Tooltip.new(chartRoot, {
                    labelText: "Time: {valueX.formatDate('yyyy-MM-dd HH:mm:ss')}\nValue: {valueY}",
                  })
                );

                var cursor = chart.set(
                  "cursor",
                  am5xy.XYCursor.new(chartRoot, {
                    behavior: "zoomX",
                  })
                );
                cursor.lineY.set("visible", false);

                chartRoot.events.on("wheel", function (event) {
                  var point = chart.plotContainer.toLocal(event.point);
                  var wheelDelta = event.originalEvent.deltaY;
                  var wheelDirection = wheelDelta > 0 ? 1 : -1;

                  xAxis.zoomToValues(
                    xAxis.get("min"),
                    xAxis.get("max") + wheelDirection * 1000 * 60
                  );

                  yAxis.zoomToValues(
                    yAxis.get("min"),
                    yAxis.get("max") + wheelDirection * 10
                  );

                  event.originalEvent.preventDefault();
                });

                console.log("Chart initialized for", freezerName);

                updateInterval = setInterval(function () {
                  var updateUrl = generateUrl(freezerName);
                  fetchData(updateUrl, function (updateData) {
                    if (chartRoot && updateData.length > 0) {
                      var series = chart.series.getIndex(0);
                      series.data.setAll(updateData);
                      console.log("Series data updated");
                    }
                  });
                }, 20000);
              });
            } catch (e) {
              console.error("Error initializing chart:", e);
            }
          });

          currentFreezerName = freezerName;
        }

        function loadFreezerData(freezerName, page) {
          console.log("Loading freezer data for:", freezerName); 
          var url = generateUrl(freezerName);
          var itemsPerPage = 10; 
          page = page || 1;

          fetchData(url, function (data) {
            data.sort(function (a, b) {
              return new Date(b.time) - new Date(a.time);
            });

            var container = $('#freezer-data-container');
            container.empty();

            var table = $('<table></table>').addClass('table table-bordered table-striped').css('margin-top', '20px');
            var thead = $('<thead></thead>');
            var headerRow = $('<tr></tr>');

            headerRow.append('<th>Title</th>');
            headerRow.append('<th>Temperature</th>');
            headerRow.append('<th>Time</th>');

            thead.append(headerRow);
            table.append(thead);

            var tbody = $('<tbody></tbody>');
            var totalItems = data.length;
            var totalPages = Math.ceil(totalItems / itemsPerPage);
            var startIndex = (page - 1) * itemsPerPage;
            var endIndex = Math.min(startIndex + itemsPerPage, totalItems);

            if (totalItems > 0) {
              for (var i = startIndex; i < endIndex; i++) {
                var item = data[i];
                var row = $('<tr></tr>');
                row.append('<td>' + item.title + '</td>');
                row.append('<td>' + item.value + '</td>');
                row.append('<td>' + item.time + '</td>');
                tbody.append(row);
              }
            } else {
              var emptyRow = $('<tr></tr>');
              emptyRow.append('<td colspan="3">No data available</td>');
              tbody.append(emptyRow);
            }

            table.append(tbody);
            container.append(table);

            if (totalPages > 1) {
              var pagination = $('<nav></nav>').addClass('mt-4');
              var ul = $('<ul></ul>').addClass('pagination').css({
                'display': 'flex',
                'justify-content': 'center',
                'list-style-type': 'none',
                'padding': '0',
                'margin': '0'
              });
              var prevPage = page > 1 ? page - 1 : 1;
              var nextPage = page < totalPages ? page + 1 : totalPages;

              var prevLink = $('<li></li>').addClass('page-item' + (page === 1 ? ' disabled' : ''));
              prevLink.append('<a class="page-link" href="#" data-page="' + prevPage + '">Previous</a>');

              var nextLink = $('<li></li>').addClass('page-item' + (page === totalPages ? ' disabled' : ''));
              nextLink.append('<a class="page-link" href="#" data-page="' + nextPage + '">Next</a>');

              var firstPageLink = $('<li></li>').addClass('page-item' + (page === 1 ? ' disabled' : ''));
              firstPageLink.append('<a class="page-link" href="#" data-page="1">First</a>');

              var lastPageLink = $('<li></li>').addClass('page-item' + (page === totalPages ? ' disabled' : ''));
              lastPageLink.append('<a class="page-link" href="#" data-page="' + totalPages + '">Last</a>');

              ul.append(firstPageLink);
              ul.append(prevLink);

              var startPage = Math.max(1, page - 1);
              var endPage = Math.min(totalPages, page + 1);
              for (var i = startPage; i <= endPage; i++) {
                var pageLink = $('<li></li>').addClass('page-item' + (i === page ? ' active' : ''));
                pageLink.append('<a class="page-link" href="#" data-page="' + i + '">' + i + '</a>');
                ul.append(pageLink);
              }

              ul.append(nextLink);
              ul.append(lastPageLink);
              pagination.append(ul);
              container.append(pagination);

              $('.pagination a').on('click', function (e) {
                e.preventDefault();
                var selectedPage = $(this).data('page');
                if (selectedPage !== page) {
                  loadFreezerData(freezerName, selectedPage);
                }
              });
            }
          });
        }

        function loadInitialData() {
          console.log("Loading initial data");
          var selectedFreezer = $("#freezer-select").val();
          if (!selectedFreezer) {
            showStatusMessage("Please select Location and Floor Dropdown Options");
            $("#freezer-title, #buttonTrigger, #chartdiv, #freezer-data-container").hide();
            toggleFilterButtons(false); 
          } else {
            hideStatusMessage();
            initializeChart(selectedFreezer);
            loadFreezerData(selectedFreezer);
          }
        }

        function resetFiltersToDefault() {
          selectedDuration = '24hr'; 
          $('.filter-button').css({
            'background-color': '',
            'color': '',
            'border': '1px solid #000',
          });
        }

        if (!Drupal.behaviors.customChartsAndData.initialized) {
          $("#freezer-select").off("change").on("change", function () {
            var freezerName = $(this).val();
            console.log("Dropdown changed:", freezerName);
            resetFiltersToDefault();
            $("#freezer-title, #buttonTrigger, #chartdiv, #freezer-data-container").hide();
            toggleFilterButtons(false); 
            loadInitialData();
          });

          $("#location, #dropdown1").off("click").on("click", function () {
            $("#chartdiv, #freezer-title, #buttonTrigger, #freezer-data-container").hide();
            $("#freezer-select").val('').trigger('click');
            showStatusMessage("Please select Location and Floor Dropdown Options");
            toggleFilterButtons(false); 
          });

          $(document).ready(function() {
            $('.filter-button').css({
              'border': '1px solid #000',
            });

            function attachClickEvent(buttonId, duration) {
              $(buttonId).off("click").on("click", function () {
                selectedDuration = duration;
                console.log('Filter button clicked: ' + selectedDuration);
                loadInitialData(); 
                if ($('#show-graph').hasClass('btn-primary')) {
                  $('#chartdiv').show();
                  $('#freezer-data-container').hide();
                } else {
                  $('#chartdiv').hide();
                  $('#freezer-data-container').show();
                }
                toggleFilterButtons(true); 
              });
            }

            $('#show-graph').on('click', function() {
              $('#chartdiv').show();
              $('#freezer-data-container').hide();
              toggleFilterButtons(true); 
            });

            $('#show-data').on('click', function() {
              $('#chartdiv').hide();
              $('#freezer-data-container').show();
              toggleFilterButtons(true); 
            });

            attachClickEvent('#filter-button-1hr', '1hr');
            attachClickEvent('#filter-button-3hr', '3hr');
            attachClickEvent('#filter-button-6hr', '6hr');
            attachClickEvent('#filter-button-12hr', '12hr');
            attachClickEvent('#filter-button-24hr', '24hr');
            attachClickEvent('#filter-button-All', 'All');
          });

          setInterval(function() {
            var freezerName = $('#freezer-select').val();
            if (freezerName) {
              loadFreezerData(freezerName);
            }
          }, 20000);

          Drupal.behaviors.customChartsAndData.initialized = true;
        }
      });
    },
  };
})(jQuery, Drupal, drupalSettings);
