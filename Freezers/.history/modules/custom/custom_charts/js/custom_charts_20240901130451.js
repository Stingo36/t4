(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.customChartsAndData = {
    attach: function (context, settings) {
      $(document).ready(function () {
        var chartRoot;
        var updateInterval;
        var currentFreezerName = null;
        var selectedDuration = '24hr'; // Default value

        // Add two buttons to the buttonTrigger element
        $('#buttonTrigger').html(`
          <button id="show-graph" class="btn custom-btn">Show Graph</button>
          <button id="show-data" class="btn custom-btn">Show Data</button>
        `);
        
        // Event listeners for graph and data buttons
        $('#show-graph').on('click', function () {
          $(this).css({'background-color': '#333', 'color': 'white', 'border-color': '#333'});
          $('#show-data').css({'background-color': 'white', 'color': 'black', 'border-color': 'black'});
          $('#chartdiv').show();
          $('#freezer-data-container').hide();
          toggleFilterButtons(true); // Show filter buttons
        });
        
        $('#show-data').on('click', function () {
          $(this).css({'background-color': '#333', 'color': 'white', 'border-color': '#333'});
          $('#show-graph').css({'background-color': 'white', 'color': 'black', 'border-color': 'black'});
          $('#chartdiv').hide();
          $('#freezer-data-container').show();
          toggleFilterButtons(true); // Show filter buttons
        });

        // Function to select the "Show Graph" button by default
        function selectDefaultGraphButton() {
          $('#show-graph').css({'background-color': '#333', 'color': 'white', 'border-color': '#333'});
          $('#show-data').css({'background-color': 'white', 'color': 'black', 'border-color': 'black'});
          $('#chartdiv').show();
          $('#freezer-data-container').hide();
          toggleFilterButtons(true); // Show filter buttons
        }

        // Initially hide title, buttons, chart, and data containers
        $('#freezer-title, #buttonTrigger, #chartdiv, #freezer-data-container').hide();

        // Function to display the status message
        function showStatusMessage(message) {
          $("#Status").html('<h2 class="text-center">' + message + '</h2>');
        }

        // Function to hide the status message
        function hideStatusMessage() {
          $("#Status").html('');
        }

        // Function to toggle the visibility of filter buttons
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
            console.log("Chart root disposed"); // Debugging line
          }
          if (updateInterval) {
            clearInterval(updateInterval);
            updateInterval = null;
            console.log("Update interval cleared"); // Debugging line
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
              console.error("Response:", xhr.responseText); // Add detailed error response
              clearCache();
              $("#chartdiv").hide(); // Hide the chart container if there is an error
              $("#freezer-title").hide(); // Hide the title container if there is an error
              $('#buttonTrigger, #freezer-data-container').hide(); // Hide buttons and data container if there is an error
              toggleFilterButtons(false); // Hide filter buttons
            },
          });
        }

        function generateUrl(freezerName) {
          var cacheBuster = new Date().getTime();
          var url = `/Freezers/custom-charts/freezer-data/${freezerName}?t=${cacheBuster}&duration=${selectedDuration}`;
          console.log("Generated URL:", url); // Debugging line
          return url;
        }

        function initializeChart(freezerName) {
          console.log("Initializing chart for freezerName:", freezerName); // Debugging line
          if (!freezerName) {
            console.error("No freezer name provided."); // Debugging line
            clearCache();
            return;
          }

          var chartDiv = document.getElementById("chartdiv");
          var freezerTitleDiv = document.getElementById("freezer-title");

          // Ensure the chart container exists
          if (!chartDiv || !freezerTitleDiv) {
            console.error("Chart container or title container not found!");
            return;
          }

          clearCache();
          $("#chartdiv").show(); // Show the chart container
          $("#freezer-title").show().text(freezerName); // Show the title container and set the text
          $('#buttonTrigger').show(); // Show the buttons container
          $('#freezer-data-container').hide(); // Hide the data container
          selectDefaultGraphButton(); // Select "Show Graph" by default
          hideStatusMessage(); // Hide the status message

          var url = generateUrl(freezerName);

          fetchData(url, function (data) {
            console.log("Data fetched successfully:", data); // Debugging line

            if (data.length === 0) {
              console.error("No data returned from the server."); // Debugging line
              clearCache();
              $("#chartdiv").hide(); // Hide the chart container if no data is fetched
              $("#freezer-title").hide(); // Hide the title container if no data is fetched
              $('#buttonTrigger, #freezer-data-container').hide(); // Hide buttons and data container if no data is fetched
              toggleFilterButtons(false); // Hide filter buttons
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

                updateInterval = setInterval(function () {
                  var updateUrl = generateUrl(freezerName);
                  fetchData(updateUrl, function (updateData) {
                    if (chartRoot && updateData.length > 0) {
                      var series = chart.series.getIndex(0);
                      series.data.setAll(updateData);
                      console.log("Series data updated"); // Debugging line
                    }
                  });
                }, 20000); // 20 seconds
              });
            } catch (e) {
              console.error("Error initializing chart:", e);
            }
          });

          currentFreezerName = freezerName;
        }

        function loadInitialData() {
          console.log("Loading initial data"); // Debugging line
          var selectedFreezer = $("#freezer-select").val();
          if (!selectedFreezer) {
            showStatusMessage("Please select Location and Floor Dropdown Options");
            $("#freezer-title, #buttonTrigger, #chartdiv, #freezer-data-container").hide();
            toggleFilterButtons(false); // Hide filter buttons
          } else {
            hideStatusMessage();
            initializeChart(selectedFreezer);
            loadFreezerData(selectedFreezer);
          }
        }

        function resetFiltersToDefault() {
          selectedDuration = '24hr'; // Reset duration to default
          $('.filter-button').css({
            'background-color': '',
            'color': '',
            'border': '1px solid #000',
          });
        }

        if (!Drupal.behaviors.customChartsAndData.initialized) {
          $("#freezer-select").off("change").on("change", function () {
            var freezerName = $(this).val();
            console.log("Dropdown changed:", freezerName); // Debugging line
            resetFiltersToDefault();
            $("#freezer-title, #buttonTrigger, #chartdiv, #freezer-data-container").hide();
            toggleFilterButtons(false); // Hide filter buttons when freezer changes
            loadInitialData();
          });

          $("#location, #dropdown1").off("click").on("click", function () {
            $("#chartdiv, #freezer-title, #buttonTrigger, #freezer-data-container").hide();
            $("#freezer-select").val('').trigger('click');
            showStatusMessage("Please select Location and Floor Dropdown Options");
            toggleFilterButtons(false); // Hide filter buttons when location or floor changes
          });

          $(document).ready(function() {
            $('.filter-button').css({
              'border': '1px solid #000',
            });

            function attachClickEvent(buttonId, duration) {
              $(buttonId).off("click").on("click", function () {
                selectedDuration = duration;
                console.log('Filter button clicked: ' + selectedDuration);
                var selectedFreezer = $("#freezer-select").val();
                if (selectedFreezer) {
                  hideStatusMessage(); // Hide the status message if a freezer is selected
                }
                loadInitialData();
              });
            }

            $('#show-graph').on('click', function() {
              $('#chartdiv').show();
              $('#freezer-data-container').hide();
              toggleFilterButtons(true); // Show filter buttons
            });

            $('#show-data').on('click', function() {
              $('#chartdiv').hide();
              $('#freezer-data-container').show();
              toggleFilterButtons(true); // Show filter buttons
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
