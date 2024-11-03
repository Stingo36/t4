(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.customChartsAndData = {
    attach: function (context, settings) {
      $(document).ready(function () {
        var chartRoot;
        var updateInterval;
        var currentFreezerName;
        var selectedDuration = '24hr'; // Default value

        function clearCache() {
          if (chartRoot) {
            chartRoot.dispose();
            chartRoot = null;
            console.log("Chart root disposed"); // Debugging line
          }
          if (updateInterval) {
            clearInterval(updateInterval);
            updateInterval = null;
            console.log("Update interval cleared"); // Debugging line
          }
        }

        function fetchDataAndUpdateSeries(freezerName) {
          var cacheBuster = new Date().getTime();
          var url = "/Freezers/custom-charts/freezer-data/" + freezerName + "?t=" + cacheBuster + "&duration=" + selectedDuration;

          console.log("Fetching data from URL:", url); // Debugging line

          $.ajax({
            url: url,
            method: "GET",
            dataType: "json",
            success: function (data) {
              console.log("Data fetched successfully:", data); // Debugging line
              if (chartRoot && data.length > 0) {
                var chart = chartRoot.container.children.getIndex(0); // Assumes only one chart
                var series = chart.series.getIndex(0); // Assumes only one series
                series.data.setAll(data);
                console.log("Series data updated"); // Debugging line
              }
            },
            error: function (xhr, status, error) {
              console.error("Error fetching data:", status, error);
              console.error("Response:", xhr.responseText); // Add detailed error response
            },
          });
        }

        function initializeChart(freezerName) {
          var chartDiv = $("#chartdiv");
          var freezerTitleDiv = $("#freezer-title");

          // Hide elements if no freezerName is selected
          if (!freezerName) {
            clearCache();
            chartDiv.hide();
            freezerTitleDiv.hide();
            return;
          }

          // Ensure the chart container exists
          if (chartDiv.length === 0 || freezerTitleDiv.length === 0) {
            console.error("Chart container or title container not found!");
            return;
          }

          clearCache();
          chartDiv.show();
          freezerTitleDiv.show().text(freezerName);
          console.log("Initializing chart for", freezerName); // Debugging line

          var cacheBuster = new Date().getTime();
          var url = "/Freezers/custom-charts/freezer-data/" + freezerName + "?t=" + cacheBuster + "&duration=" + selectedDuration;

          console.log("Fetching data from URL:", url); // Debugging line

          $.ajax({
            url: url,
            method: "GET",
            dataType: "json",
            success: function (data) {
              console.log("Data fetched successfully:", data); // Debugging line

              if (data.length === 0) {
                clearCache();
                chartDiv.hide();
                freezerTitleDiv.hide();
                return;
              }

              try {
                am5.ready(function () {
                  console.log("am5 ready"); // Debugging line

                  // Create root element
                  chartRoot = am5.Root.new("chartdiv");
                  console.log("Root element created"); // Debugging line

                  // Set themes
                  chartRoot.setThemes([am5themes_Animated.new(chartRoot)]);

                  // Adjust height of chartdiv
                  chartDiv.css('height', '400px');
                  console.log("Chart height set"); // Debugging line

                  // Create chart
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
                  console.log("Chart created"); // Debugging line

                  // Create axes
                  var xAxis = chart.xAxes.push(
                    am5xy.DateAxis.new(chartRoot, {
                      groupData: false,
                      baseInterval: { timeUnit: "minute", count: 1 },
                      renderer: am5xy.AxisRendererX.new(chartRoot, {}),
                      maxDeviation: 0,
                    })
                  );

                  if (selectedDuration !== 'All') {
                    // Set the minimum and maximum dates based on the selected duration
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
                  console.log("Axes created"); // Debugging line

                  // Create series
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
                  console.log("Data set in series"); // Debugging line

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
                    var wheelDirection = event.originalEvent.deltaY > 0 ? 1 : -1;
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

                  console.log("Chart initialized for", freezerName); // Debugging line

                  updateInterval = setInterval(function () {
                    fetchDataAndUpdateSeries(freezerName);
                  }, 20000); // 20 seconds
                });
              } catch (e) {
                console.error("Error initializing chart:", e);
              }
            },
            error: function (xhr, status, error) {
              console.error("Error fetching data:", status, error);
              console.error("Response:", xhr.responseText); // Add detailed error response
              clearCache();
              chartDiv.hide();
              freezerTitleDiv.hide();
            },
          });

          currentFreezerName = freezerName;
        }

        function loadFreezerData(freezerName, page) {
          var url = Drupal.url('custom-charts/freezer-data/' + freezerName + "?duration=" + selectedDuration);
          var itemsPerPage = 10;
          page = page || 1;

          $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            success: function (data) {
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
                var ul = $('<ul></ul>').addClass('pagination justify-content-center');
                var prevPage = page > 1 ? page - 1 : 1;
                var nextPage = page < totalPages ? page + 1 : totalPages;

                ul.append('<li class="page-item' + (page === 1 ? ' disabled' : '') + '"><a class="page-link" href="#" data-page="' + prevPage + '">Previous</a></li>');
                for (var i = Math.max(1, page - 1); i <= Math.min(totalPages, page + 1); i++) {
                  ul.append('<li class="page-item' + (i === page ? ' active' : '') + '"><a class="page-link" href="#" data-page="' + i + '">' + i + '</a></li>');
                }
                ul.append('<li class="page-item' + (page === totalPages ? ' disabled' : '') + '"><a class="page-link" href="#" data-page="' + nextPage + '">Next</a></li>');

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
            },
          });
        }

        function loadInitialData() {
          var selectedFreezer = $("#freezer-select").val();
          initializeChart(selectedFreezer);
          loadFreezerData(selectedFreezer);
          $('#showGraphBtn').hide(); // Hide buttons initially
          $('#showDataBtn').hide();
        }

        loadInitialData();

        if (!Drupal.behaviors.customChartsAndData.initialized) {
          $("#freezer-select").off("change").on("change", function (e) {
            var freezerName = $(this).val();
            console.log("Dropdown changed:", freezerName); // Debugging line
            if (freezerName !== currentFreezerName) {
              $('#showGraphBtn').show(); // Show buttons when a freezer is selected
              $('#showDataBtn').show();
              $('#chartdiv').hide(); // Hide chart and data initially
              $('#freezer-data-container').hide();
              loadInitialData();
            }
          });

          $("#location, #dropdown1").off("click").on("click", function () {
            $("#chartdiv").hide();
            $("#freezer-title").hide();
            $("#freezer-select").val('').trigger('click');
          });

          $(document).ready(function() {
            var previousButton = null;

            // Common hover effect for all buttons
            function addHoverEffect(button) {
              $(button).hover(
                function () { // Mouse enter
                  $(this).css({
                    'background-color': '#aaa', // Shade of grey
                    'color': '#fff' // White text color
                  });
                },
                function () { // Mouse leave
                  if (this !== previousButton) {
                    $(this).css({
                      'background-color': '',
                      'color': ''
                    });
                  }
                }
              );
            }

            function attachClickEvent(buttonId, duration) {
              $(buttonId).off("click").on("click", function () {
                selectedDuration = duration;
                console.log('Filter button clicked: ' + selectedDuration);
                loadInitialData();
                resetButton(previousButton);
                applyActiveStyles(this);
                previousButton = this;
              });
              addHoverEffect(buttonId);
            }

            function resetButton(button) {
              if (button) {
                $(button).css({
                  'background-color': '',
                  'color': ''
                });
              }
            }

            function applyActiveStyles(button) {
              $(button).css({
                'background-color': '#333', // Shade of black
                'color': '#fff' // White text color
              });
            }

            attachClickEvent('#filter-button-1hr', '1hr');
            attachClickEvent('#filter-button-3hr', '3hr');
            attachClickEvent('#filter-button-6hr', '6hr');
            attachClickEvent('#filter-button-12hr', '12hr');
            attachClickEvent('#filter-button-24hr', '24hr');
            attachClickEvent('#filter-button-All', 'All');
          });

          var buttonTrigger = $('#buttonTrigger');

          var showGraphBtn = $('<button>', {
            id: 'showGraphBtn',
            text: 'Show Graph',
            class: 'btn',
            style: 'background-color: #4CAF50; color: white; margin-right: 5px; display: none;', // Hide by default
            click: function () {
              $('#chartdiv').show();
              $('#freezer-data-container').hide();
            }
          });

          var showDataBtn = $('<button>', {
            id: 'showDataBtn',
            text: 'Show Data',
            class: 'btn',
            style: 'background-color: #FF5733; color: white; display: none;', // Hide by default
            click: function () {
              $('#chartdiv').hide();
              $('#freezer-data-container').show();
            }
          });

          buttonTrigger.append(showGraphBtn, showDataBtn);

          setInterval(function () {
            var freezerName = $('#freezer-select').val();
            loadFreezerData(freezerName);
          }, 20000);

          Drupal.behaviors.customChartsAndData.initialized = true;
        }
      });
    },
  };
})(jQuery, Drupal, drupalSettings);
