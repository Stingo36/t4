(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.customChartsAndData = {
    attach: function (context, settings) {
      $(document).ready(function () {
        var chartRoot, updateInterval, currentFreezerName;
        var selectedDuration = '24hr'; // Default value
        var previousButton = null;

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

        function fetchDataAndUpdateSeries(freezerName) {
          var cacheBuster = new Date().getTime();
          var url = "/Freezers/custom-charts/freezer-data/" + freezerName + "?t=" + cacheBuster + "&duration=" + selectedDuration;

          console.log("Fetching data from URL:", url);

          $.ajax({
            url: url,
            method: "GET",
            dataType: "json",
            success: function (data) {
              console.log("Data fetched successfully:", data);
              if (chartRoot && data.length > 0) {
                var chart = chartRoot.container.children.getIndex(0);
                var series = chart.series.getIndex(0);
                series.data.setAll(data);
                console.log("Series data updated");
              }
            },
            error: function (xhr, status, error) {
              console.error("Error fetching data:", status, error);
              console.error("Response:", xhr.responseText);
            },
          });
        }

        function initializeChart(freezerName) {
          var chartDiv = document.getElementById("chartdiv");
          var freezerTitleDiv = document.getElementById("freezer-title");

          if (!freezerName) {
            clearCache();
            $("#chartdiv, #freezer-title").hide();
            return;
          }

          if (!chartDiv || !freezerTitleDiv) {
            console.error("Chart container or title container not found!");
            return;
          }

          clearCache();
          $("#chartdiv, #freezer-title").show();
          $("#freezer-title").text(freezerName);
          console.log("Initializing chart for", freezerName);

          fetchDataAndRenderChart(freezerName);
          currentFreezerName = freezerName;
        }

        function fetchDataAndRenderChart(freezerName) {
          var cacheBuster = new Date().getTime();
          var url = "/Freezers/custom-charts/freezer-data/" + freezerName + "?t=" + cacheBuster + "&duration=" + selectedDuration;

          console.log("Fetching data from URL:", url);

          $.ajax({
            url: url,
            method: "GET",
            dataType: "json",
            success: function (data) {
              console.log("Data fetched successfully:", data);

              if (data.length === 0) {
                clearCache();
                $("#chartdiv, #freezer-title").hide();
                return;
              }

              renderChart(data);
              setAutoUpdate(freezerName);
            },
            error: function (xhr, status, error) {
              console.error("Error fetching data:", status, error);
              console.error("Response:", xhr.responseText);
              clearCache();
              $("#chartdiv, #freezer-title").hide();
            },
          });
        }

        function renderChart(data) {
          am5.ready(function () {
            console.log("am5 ready");
            chartRoot = am5.Root.new("chartdiv");
            chartRoot.setThemes([am5themes_Animated.new(chartRoot)]);

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
                groupData: false,
                baseInterval: { timeUnit: "minute", count: 1 },
                renderer: am5xy.AxisRendererX.new(chartRoot, {}),
                maxDeviation: 0,
              })
            );

            var yAxis = chart.yAxes.push(
              am5xy.ValueAxis.new(chartRoot, {
                renderer: am5xy.AxisRendererY.new(chartRoot, {}),
              })
            );

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

            series.set("tooltip", am5.Tooltip.new(chartRoot, {
              labelText: "Time: {valueX.formatDate('yyyy-MM-dd HH:mm:ss')}\nValue: {valueY}",
            }));

            chart.set("cursor", am5xy.XYCursor.new(chartRoot, { behavior: "zoomX" }));
          });
        }

        function setAutoUpdate(freezerName) {
          updateInterval = setInterval(function () {
            fetchDataAndUpdateSeries(freezerName);
          }, 20000); // 20 seconds
        }

        function loadFreezerData(freezerName, page = 1) {
          var url = Drupal.url('custom-charts/freezer-data/' + freezerName + "?duration=" + selectedDuration);
          var itemsPerPage = 10;

          if (!freezerName) {
            $('#freezer-data-container').html('<div class="select-message"><strong>Select Location and Floor to display Freezers</strong></div>');
            return;
          }

          $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            success: function (data) {
              renderTable(data, page, itemsPerPage);
            },
            error: function () {
              $('#freezer-data-container').html('<div class="alert alert-danger">An error occurred while fetching the data.</div>');
            }
          });
        }

        function renderTable(data, page, itemsPerPage) {
          var container = $('#freezer-data-container').empty();
          var table = $('<table></table>').addClass('table table-bordered table-striped').css('margin-top', '20px');
          var thead = $('<thead></thead>');
          var headerRow = $('<tr></tr>');

          headerRow.append('<th>Title</th><th>Temperature</th><th>Time</th>');
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
              var row = $('<tr></tr>').append('<td>' + item.title + '</td><td>' + item.value + '</td><td>' + item.time + '</td>');
              tbody.append(row);
            }
          } else {
            tbody.append('<tr><td colspan="3">No data available</td></tr>');
          }

          table.append(tbody);
          container.append(table);

          if (totalPages > 1) {
            addPagination(container, totalPages, page, freezerName);
          }
        }

        function addPagination(container, totalPages, page, freezerName) {
          var pagination = $('<nav></nav>').addClass('mt-4');
          var ul = $('<ul></ul>').addClass('pagination justify-content-center');

          ul.append(createPageLink("First", 1, page === 1));
          ul.append(createPageLink("Previous", Math.max(1, page - 1), page === 1));

          for (var i = Math.max(1, page - 1); i <= Math.min(totalPages, page + 1); i++) {
            ul.append(createPageLink(i, i, i === page));
          }

          ul.append(createPageLink("Next", Math.min(totalPages, page + 1), page === totalPages));
          ul.append(createPageLink("Last", totalPages, page === totalPages));
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

        function createPageLink(text, page, disabled) {
          return $('<li></li>').addClass('page-item' + (disabled ? ' disabled' : ''))
            .append('<a class="page-link" href="#" data-page="' + page + '">' + text + '</a>');
        }

        function loadInitialData() {
          var selectedFreezer = $("#freezer-select").val();
          initializeChart(selectedFreezer);
          loadFreezerData(selectedFreezer);
        }

        function addHoverEffect(button) {
          $(button).hover(
            function () { $(this).css({ 'background-color': '#aaa', 'color': '#fff' }); },
            function () { if (this !== previousButton) $(this).css({ 'background-color': '', 'color': '' }); }
          );
        }

        function attachClickEvent(buttonId, duration) {
          $(buttonId).off("click").on("click", function () {
            selectedDuration = duration;
            loadInitialData();
            resetButton(previousButton);
            applyActiveStyles(this);
            previousButton = this;
          });
          addHoverEffect(buttonId);
        }

        function resetButton(button) {
          if (button) $(button).css({ 'background-color': '', 'color': '' });
        }

        function applyActiveStyles(button) {
          $(button).css({ 'background-color': '#333', 'color': '#fff' });
        }

        function setupFilterButtons() {
          var buttonContainer = $('#filter_buttons');
          if (buttonContainer.length) {
            attachClickEvent('#filter-button-1hr', '1hr');
            attachClickEvent('#filter-button-3hr', '3hr');
            attachClickEvent('#filter-button-6hr', '6hr');
            attachClickEvent('#filter-button-12hr', '12hr');
            attachClickEvent('#filter-button-24hr', '24hr');
            attachClickEvent('#filter-button-All', 'All');
          } else {
            console.error("Filter button container not found!");
          }
        }

        setupFilterButtons();
        loadInitialData();

        if (!Drupal.behaviors.customChartsAndData.initialized) {
          $("#freezer-select").off("change").on("change", function () {
            var freezerName = $(this).val();
            if (freezerName !== currentFreezerName) {
              loadInitialData();
            }
          });

          $("#location, #dropdown1").off("click").on("click", function () {
            $("#chartdiv, #freezer-title").hide();
            $("#freezer-select").val('').trigger('click');
          });

          var buttonTrigger = $('#buttonTrigger');
          buttonTrigger.append(createShowButton('Show Graph', '#4CAF50', function () {
            $('#chartdiv').show();
            $('#freezer-data-container').hide();
          }));

          buttonTrigger.append(createShowButton('Show Data', '#FF5733', function () {
            $('#chartdiv').hide();
            $('#freezer-data-container').show();
          }));

          setInterval(function () {
            var freezerName = $('#freezer-select').val();
            loadFreezerData(freezerName);
          }, 20000);

          Drupal.behaviors.customChartsAndData.initialized = true;
        }

        function createShowButton(text, color, clickHandler) {
          return $('<button>', {
            text: text,
            class: 'btn',
            style: 'background-color: ' + color + '; color: white; margin-right: 5px;',
            click: clickHandler
          });
        }
      });
    },
  };
})(jQuery, Drupal, drupalSettings);
