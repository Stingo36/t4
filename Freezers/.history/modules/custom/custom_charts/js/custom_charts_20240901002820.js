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
          var chartDiv = document.getElementById("chartdiv");
          var freezerTitleDiv = document.getElementById("freezer-title");

          // Check if freezerName is valid
          if (!freezerName) {
            clearCache();
            $("#chartdiv").hide(); // Hide the chart container if no freezerName is provided
            $("#freezer-title").hide(); // Hide the title container if no freezerName is provided
            $("#buttonTrigger").hide(); // Hide buttons if no freezerName is provided
            $("#status").show(); // Show status if no freezerName is selected
            return;
          }

          // Ensure the chart container exists
          if (!chartDiv || !freezerTitleDiv) {
            console.error("Chart container or title container not found!");
            return;
          }

          clearCache();
          $("#chartdiv").show(); // Show the chart container
          $("#freezer-title").show().text(freezerName); // Show the title container and set the text
          $("#buttonTrigger").show(); // Show buttons when a freezerName is selected
          $("#status").hide(); // Hide status when a freezerName is selected

          console.log("Initializing chart for", freezerName); // Debugging line

          // Append a unique query parameter to the URL to bust the cache
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
                $("#chartdiv").hide(); // Hide the chart container if no data is fetched
                $("#freezer-title").hide(); // Hide the title container if no data is fetched
                $("#buttonTrigger").hide(); // Hide buttons if no data is fetched
                $("#status-id").show(); // Show status if no data is fetched
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
                  var chartHeight = 400; // Set the desired height in pixels
                  chartDiv.style.height = chartHeight + "px";
                  console.log("Chart height set"); // Debugging line

                  // Create chart
                  var chart = chartRoot.container.children.push(
                    am5xy.XYChart.new(chartRoot, {
                      panX: true, // Enable panning on X axis
                      panY: true, // Enable panning on Y axis
                      wheelX: "zoomXY", // Enable zooming on both axes
                      wheelY: "zoomXY", // Enable zooming on both axes
                      pinchZoomX: true, // Enable pinch zoom on X axis
                      pinchZoomY: true, // Enable pinch zoom on Y axis
                    })
                  );
                  console.log("Chart created"); // Debugging line

                  // Create axes
                  var xAxis = chart.xAxes.push(
                    am5xy.DateAxis.new(chartRoot, {
                      groupData: false,
                      baseInterval: { timeUnit: "minute", count: 1 }, // Set base interval to 1 minute
                      renderer: am5xy.AxisRendererX.new(chartRoot, {}),
                      maxDeviation: 0, // Ensure the axis doesn't deviate
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
                    // Set the x-axis range based on the data
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
                      stroke: am5.color(0x00ff00), // color
                    })
                  );

                  series.data.setAll(data);
                  console.log("Data set in series"); // Debugging line

                  // Set tooltip on series
                  series.set(
                    "tooltip",
                    am5.Tooltip.new(chartRoot, {
                      labelText: "Time: {valueX.formatDate('yyyy-MM-dd HH:mm:ss')}\nValue: {valueY}",
                    })
                  );

                  // Add cursor
                  var cursor = chart.set(
                    "cursor",
                    am5xy.XYCursor.new(chartRoot, {
                      behavior: "zoomX", // Enable zooming on selection
                    })
                  );
                  cursor.lineY.set("visible", false);

                  // Handle scroll to zoom both axes
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

                  console.log("Chart initialized for", freezerName); // Debugging line

                  // Set up auto-update interval
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
              $("#chartdiv").hide(); // Hide the chart container if there is an error
              $("#freezer-title").hide(); // Hide the title container if there is an error
              $("#buttonTrigger").hide(); // Hide buttons if there is an error
              $("#status-id").show(); // Show status if there is an error
            },
          });

          // Store current freezer name to detect changes
          currentFreezerName = freezerName;
        }

        function loadFreezerData(freezerName, page) {
          var url = Drupal.url('custom-charts/freezer-data/' + freezerName + "?duration=" + selectedDuration);
          var itemsPerPage = 10; // Changed from 2 to 10
          page = page || 1;

          if (!freezerName) {
            $('#freezer-data-container').html('<div class="select-message"><strong>Select Location and Floor to display Freezers</strong></div>');
            return;
          }

          $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            success: function (data) {
              // Sort data in descending order based on the time field
              data.sort(function (a, b) {
                return new Date(b.time) - new Date(a.time);
              });

              var container = $('#freezer-data-container');
              container.empty();

              var table = $('<table></table>').addClass('table table-bordered table-striped').css('margin-top', '20px');
              var thead = $('<thead></thead>');
              var headerRow = $('<tr></tr>');

              // Add headers
              headerRow.append('<th>Title</th>');
              headerRow.append('<th>Temperature</th>');
              headerRow.append('<th>Time</th>');
              // Add other headers as needed

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
                  // Add other fields as needed
                  tbody.append(row);
                }
              } else {
                var emptyRow = $('<tr></tr>');
                emptyRow.append('<td colspan="3">No data available</td>');
                tbody.append(emptyRow);
              }

              table.append(tbody);
              container.append(table);

              // Add pagination controls
              if (totalPages > 1) {
                var pagination = $('<nav></nav>').addClass('mt-4');
                var ul = $('<ul></ul>').addClass('pagination justify-content-center');
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

                // Page numbers
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

                // Page click event
                $('.pagination a').on('click', function (e) {
                  e.preventDefault();
                  var selectedPage = $(this).data('page');
                  if (selectedPage !== page) {
                    loadFreezerData(freezerName, selectedPage);
                  }
                });
              }
            },
            error: function () {
              $('#freezer-data-container').html('<div class="alert alert-danger">An error occurred while fetching the data.</div>');
            }
          });
        }

        // Initial load
        function loadInitialData() {
          var selectedFreezer = $("#freezer-select").val();
          initializeChart(selectedFreezer);
          loadFreezerData(selectedFreezer);
        }

        loadInitialData();

        // Ensure event handlers are attached only once
        if (!Drupal.behaviors.customChartsAndData.initialized) {
          // Event listener for dropdown change
          $("#freezer-select").off("change").on("change", function (e) {
            var freezerName = $(this).val();
            console.log("Dropdown changed:", freezerName); // Debugging line
            if (freezerName !== currentFreezerName) {
              loadInitialData();
            }
          });

          $("#location, #dropdown1").off("click").on("click", function () {
            // Hide chart and title when location or floor changes
            $("#chartdiv").hide();
            $("#freezer-title").hide();
            $("#buttonTrigger").hide(); // Hide buttons as well

            // Clear the freezer select dropdown
            $("#freezer-select").val('').trigger('click');
          });

          $(document).ready(function() {
            var previousButton = null;
        
            // Common hover effect for all buttons
            function addHoverEffect(button) {
                $(button).hover(
                    function() { // Mouse enter
                        $(this).css({
                            'background-color': '#aaa', // Shade of grey
                            'color': '#fff' // White text color
                        });
                    }, 
                    function() { // Mouse leave
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
        
          // Add buttons dynamically
          var buttonTrigger = $('#buttonTrigger');

          var showGraphBtn = $('<button>', {
            id: 'showGraphBtn',
            text: 'Show Graph',
            class: 'btn',
            style: 'background-color: #4CAF50; color: white; margin-right: 5px;',
            click: function () {
              $('#chartdiv').show(); // Show the chart
              $('#freezer-data-container').hide(); // Hide the data table
            }
          });

          var showDataBtn = $('<button>', {
            id: 'showDataBtn',
            text: 'Show Data',
            class: 'btn',
            style: 'background-color: #FF5733; color: white;',
            click: function () {
              $('#chartdiv').hide(); // Hide the chart
              $('#freezer-data-container').show(); // Show the data table
            }
          });

          // Append buttons to the buttonTrigger div
          buttonTrigger.append(showGraphBtn, showDataBtn);

          // Set interval to auto-update the table every 20 seconds
          setInterval(function() {
            var freezerName = $('#freezer-select').val();
            loadFreezerData(freezerName);
          }, 20000);

          // Default display: Show only the graph, hide the data table
          $('#chartdiv').show();
          $('#freezer-data-container').hide();

          Drupal.behaviors.customChartsAndData.initialized = true;
        }
      });
    },
  };
})(jQuery, Drupal, drupalSettings);
