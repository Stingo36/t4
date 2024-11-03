(function ($, Drupal) {
  Drupal.behaviors.csvDatatables = {
    attach: function attach(context, settings) {
      $(context)
        .find("div.csv-table")
        .each(function () {
          var link = this.getElementsByTagName("a")[0];
          if (!link) {
            return;
          }
          var div = this;

          var settings = JSON.parse(div.dataset.settings);
          if (settings.centerContent) {
            div.classList.add("center-content");
          }

          var autolink = false;
          if (settings.autolink && typeof Autolinker !== "undefined") {
            autolink = new Autolinker({
              newWindow: Boolean(settings.autolinkNewWindow),
              stripPrefix: false,
              truncate: 50,
            });
          }

          var urlColumnNumber = settings.urlColumnNumber ? settings.urlColumnNumber - 1 : null;

          Papa.parse(link.href, {
            download: true,
            skipEmptyLines: true,
            transform: function (value, colNumber) {
              if (autolink && colNumber !== urlColumnNumber) {
                value = autolink.link(value);
              }
              return value;
            },
            complete: function (parsed) {
              var table = document.createElement("table");
              div.classList.remove("csv-table");
              if (settings.download) {
                div.insertBefore(table, div.childNodes[0]); // use inserBefore instead of prepend for IE11 compatibility
              } else {
                div.innerHTML = "";
                div.insertBefore(table, div.childNodes[0]);
              }
              div.classList.remove("hidden");
              table.className = div.className;
              // override responsive width settings
              table.setAttribute("style", "width: 100%");

              if (div.classList.contains("dataTable")) {
                settings.columns = [];

                var headerData = parsed.data.shift();
                settings.data = parsed.data;
                if (settings.data.length > 100) {
                  settings.deferRender = true;
                }

                // wrap overflow columns into details rows responsively
                if (typeof settings.responsive != "undefined") {
                  // immediately expand details row
                  if (settings.responsive === "childRowImmediate") {
                    settings.responsive = {
                      details: {
                        display:
                          $.fn.dataTable.Responsive.display.childRowImmediate,
                        type: "none",
                      },
                    };
                  } else {
                    // by default collapse details row
                    settings.columns = [
                      {
                        className: "dtr-control",
                        orderable: false,
                        data: null,
                        title: "",
                        defaultContent: "",
                      },
                    ];
                    settings.data = parsed.data.map(function (row) {
                      var _row = row;
                      _row.unshift("");
                      return _row;
                    });
                  }
                }

                var i;

                for (i = 0; i < headerData.length; i++) {
                  settings.columns.push({ title: headerData[i] });
                }

                if (autolink) {
                  if (settings.urlColumnNumber) {
                    settings.data = settings.data.map(function (row) {
                      if (row[0] === "") {
                        urlColumnNumber = settings.urlColumnNumber ? settings.urlColumnNumber : null;
                      }
                      if (urlColumnNumber && row[urlColumnNumber - 1] && row[urlColumnNumber]) {
                        row[urlColumnNumber] = Autolinker.link(row[urlColumnNumber], {
                          newWindow: Boolean(settings.autolinkNewWindow),
                          replaceFn: function (match) {
                            const tag = match.buildTag();
                            tag.setInnerHtml(row[urlColumnNumber - 1]);
                            return tag;
                          }
                        });
                      }
                      row.splice(urlColumnNumber - 1, 1);
                      return row;
                    });
                    settings.columns[urlColumnNumber].title = settings.columns[urlColumnNumber - 1].title;
                    settings.columns.splice(urlColumnNumber - 1,1);
                  }
                }

                if (typeof settings.hideSearchingData != "undefined" && typeof settings.searching != "undefined") {
                  if (settings.hideSearchingData === 1 && settings.searching === 1) {
                    var dtable = $(table).DataTable({
                      ...settings,
                      search: {
                        return: true
                      },
                      language: {
                        search: settings.searchLabel ? settings.searchLabel + ':' : 'Search:'
                      },
                    });

                    // Clear search input and redraw table
                    dtable.search('').draw();

                    // Initially hide all rows
                    dtable.rows().nodes().to$().hide();
                    $(table).find('thead').hide();
                    $(table).siblings('.dataTables_paginate').hide();
                    $(table).siblings('.dataTables_info').hide();
                    $(table).css('border-bottom', 'unset');
                    var $search = $(table).parent().find('input[type="search"]');
                    var $button = $('<button type="submit" class="dt-search-submit" value="Search">Search</button>').insertAfter($search);

                    // Event listener to show rows only when a search is performed
                    dtable.on('search.dt', function(e) {
                      var searchTerm = dtable.search();
                      if (searchTerm) {
                        dtable.rows().nodes().to$().show();
                        $(table).find('thead').show();
                        $(table).css('border-bottom', '1px solid rgba(0, 0, 0, 0.3)');
                        $(table).siblings('.dataTables_paginate').show();
                        $(table).siblings('.dataTables_info').show();
                      }
                      else {
                        dtable.rows().nodes().to$().hide();
                        $(table).find('thead').hide();
                        $(table).css('border-bottom', 'unset');
                        $(table).siblings('.dataTables_paginate').hide();
                        $(table).siblings('.dataTables_info').hide();
                      }
                    });

                    // Add event listener to the button
                    $button.on('click', function() {
                      var searchTerm = $search.val();
                      dtable.search(searchTerm).draw();
                    });
                  }
                  else {
                    $(table).DataTable(settings);
                  }
                }
                else {
                  $(table).DataTable(settings);
                }
                var className = 'dt-search-right-aligned';
                if (typeof settings.lengthChange != "undefined" && typeof settings.searching != "undefined") {
                  if (settings.lengthChange === 0 && settings.searching === 1) {
                    className = 'dt-search-left-aligned';
                  }
                }
                $(table).parent().addClass(className);
              } else {
                var lines = parsed.data,
                  output = [],
                  i;
                // Add header.
                output.push(
                  "<thead><tr><th>" +
                    lines[0].join("</th><th>") +
                    "</th></tr></thead><tbody>"
                );

                div.classList.remove("csv-table");
                table.classList = div.classList;
                table.innerHTML = output;

                // Add other rows.
                for (i = 1; i < lines.length; i++)
                  output.push(
                    "<tr><td>" + lines[i].join("</td><td>") + "</td></tr>"
                  );

                output = output.join("") + "</tbody>";

                table.innerHTML = output;
              }
            },
          });
        });
    },
  };
})(jQuery, Drupal);
