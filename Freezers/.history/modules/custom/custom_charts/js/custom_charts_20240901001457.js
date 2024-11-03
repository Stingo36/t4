(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.customChartsAndData = {
    attach: function (context, settings) {
      $(document).ready(function () {
        var chartRoot, updateInterval, currentFreezerName, selectedDuration = '24hr';

        function clearCache() {
          if (chartRoot) {
            chartRoot.dispose();
            chartRoot = null;
          }
          if (updateInterval) {
            clearInterval(updateInterval);
            updateInterval = null;
          }
        }

        function fetchDataAndUpdateSeries(freezerName) {
          var cacheBuster = new Date().getTime();
          var url = `/Freezers/custom-charts/freezer-data/${freezerName}?t=${cacheBuster}&duration=${selectedDuration}`;

          $.ajax({
            url: url,
            method: "GET",
            dataType: "json",
            success: function (data) {
              if (chartRoot && data.length > 0) {
                var chart = chartRoot.container.children.getIndex(0);
                var series = chart.series.getIndex(0);
                series.data.setAll(data);
              }
            },
            error: function (xhr) {
              console.error("Error fetching data:", xhr.status, xhr.statusText);
              console.error("Response:", xhr.responseText);
            },
          });
        }

        function initializeChart(freezerName) {
          var chartDiv = $("#chartdiv").get(0);
          var freezerTitleDiv = $("#freezer-title");

          if (!freezerName) {
            clearCache();
            $("#chartdiv, #freezer-title").hide();
            return;
          }

          if (!chartDiv) {
            console.error("Chart container not found!");
            return;
          }

          clearCache();
          $("#chartdiv").show();
          freezerTitleDiv.show().text(freezerName);

          var cacheBuster = new Date().getTime();
          var url = `/Freezers/custom-charts/freezer-data/${freezerName}?t=${cacheBuster}&duration=${selectedDuration}`;

          $.ajax({
            url: url,
            method: "GET",
            dataType: "json",
            success: function (data) {
              if (data.length === 0) {
                clearCache();
                $("#chartdiv, #freezer-title").hide();
                return;
              }

              try {
                am5.ready(function () {
                  chartRoot = am5.Root.new("chartdiv");
                  chartRoot.setThemes([am5themes_Animated.new(chartRoot)]);

                  chartDiv.style.height = "400px";

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

                  var xAxis = chart.xAxes.push(
                    am5xy.DateAxis.new(chartRoot, {
                      baseInterval: { timeUnit: "minute", count: 1 },
                      renderer: am5xy.AxisRendererX.new(chartRoot, {}),
                      maxDeviation: 0,
                    })
                  );

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

                  var yAxis = chart.yAxes.push(
                    am5xy.ValueAxis.new(chartRoot, {
                      renderer: am5xy.AxisRendererY.new(chartRoot, {}),
                    })
                  );

                  var series = chart.series.push(
                    am5xy.LineSeries.new(chartRoot, {
                      xAxis: xAxis,
                      yAxis: yAxis,
                      valueYField: "value",
                      valueXField: "date",
                      stroke: am5.color(0x00ff00),
                    })
                  );

                  series.data.setAll(data);

                  series.set(
                    "tooltip",
                    am5.Tooltip.new(chartRoot, {
                      labelText: "Time: {valueX.formatDate('yyyy-MM-dd HH:mm:ss')}\nValue: {valueY}",
                    })
                  );

                  chart.set(
                    "cursor",
                    am5xy.XYCursor.new(chartRoot, {
                      behavior: "zoomX",
                    })
                  );

                  chartRoot.events.on("wheel", function (event) {
                    event.originalEvent.preventDefault();
                  });

                  updateInterval = setInterval(function () {
                    fetchDataAndUpdateSeries(freezerName);
                  }, 20000);
                });
              } catch (e) {
                console.error("Error initializing chart:", e);
              }
            },
            error: function (xhr) {
              console.error("Error fetching data:", xhr.status, xhr.statusText);
              clearCache();
              $("#chartdiv, #freezer-title").hide();
            },
          });

          currentFreezerName = freezerName;
        }

        function loadFreezerData(freezerName) {
          if (!freezerName) {
            $('#freezer-data-container').html('<div class="select-message"><strong>Select Location and Floor to display Freezers</strong></div>');
            return;
          }

          var url = Drupal.url('custom-charts/freezer-data/' + freezerName + "?duration=" + selectedDuration);
          var itemsPerPage = 10;

          $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            success: function (data) {
              // Process and display data
            },
            error: function () {
              $('#freezer-data-container').html('<div class="alert alert-danger">An error occurred while fetching the data.</div>');
            }
          });
        }

        function loadInitialData() {
          var selectedFreezer = $("#freezer-select").val();
          initializeChart(selectedFreezer);
          loadFreezerData(selectedFreezer);
        }

        loadInitialData();

        if (!Drupal.behaviors.customChartsAndData.initialized) {
          $("#freezer-select").on("change", function () {
            var freezerName = $(this).val();
            if (freezerName !== currentFreezerName) {
              loadInitialData();
            }
          });

          $("#location, #dropdown1").on("click", function () {
            $("#chartdiv, #freezer-title").hide();
            $("#freezer-select").val('').trigger('click');
          });

          $(document).ready(function () {
            var previousButton = null;

            function attachClickEvent(buttonId, duration) {
              $(buttonId).on("click", function () {
                selectedDuration = duration;
                loadInitialData();
                if (previousButton) {
                  $(previousButton).css({ 'background-color': '', 'color': '' });
                }
                $(this).css({ 'background-color': '#333', 'color': '#fff' });
                previousButton = this;
              }).hover(
                function () { $(this).css({ 'background-color': '#aaa', 'color': '#fff' }); },
                function () { if (this !== previousButton) $(this).css({ 'background-color': '', 'color': '' }); }
              );
            }

            attachClickEvent('#filter-button-1hr', '1hr');
            attachClickEvent('#filter-button-3hr', '3hr');
            attachClickEvent('#filter-button-6hr', '6hr');
            attachClickEvent('#filter-button-12hr', '12hr');
            attachClickEvent('#filter-button-24hr', '24hr');
            attachClickEvent('#filter-button-All', 'All');
          });

          $('#buttonTrigger').append(
            $('<button>', { id: 'showGraphBtn', text: 'Show Graph', class: 'btn', style: 'background-color: #4CAF50; color: white; margin-right: 5px;', click: function () { $('#chartdiv').show(); $('#freezer-data-container').hide(); } }),
            $('<button>', { id: 'showDataBtn', text: 'Show Data', class: 'btn', style: 'background-color: #FF5733; color: white;', click: function () { $('#chartdiv').hide(); $('#freezer-data-container').show(); } })
          );

          $('#chartdiv').show();
          $('#freezer-data-container').hide();

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
