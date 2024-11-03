(function ($, Drupal) {
  Drupal.behaviors.dependentDropdowns = {
    attach: function (context, settings) {
      function updateDropdown2() {
        const dropdown1 = document.getElementById('dropdown1');
        const freezerDropdown = document.getElementById('freezer-select');
        const selectedFloor = dropdown1.value;
        const selectedLocation = document.getElementById('location').value;

        // Clear the freezer dropdown
        freezerDropdown.innerHTML = '';

        if (selectedFloor && selectedLocation) {
          // Fetch the nodes and dynamically load freezer options based on the selected floor and location
          $.ajax({
            url: Drupal.url('freezer_names'),
            type: 'GET',
            data: {
              floor: selectedFloor,
              location: selectedLocation
            },
            dataType: 'json',
            cache: false,
            headers: {
              'Cache-Control': 'no-cache, no-store, must-revalidate',
              'Pragma': 'no-cache',
              'Expires': '0'
            },
            success: function (data) {
              // Remove duplicates
              const uniqueData = [...new Set(data)];

              // Add the default option
              const defaultOption = document.createElement('option');
              defaultOption.value = '';
              defaultOption.textContent = Drupal.t('Select Freezer');
              freezerDropdown.appendChild(defaultOption);

              // Add the options to the freezer dropdown
              uniqueData.forEach(function (value) {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = value;
                freezerDropdown.appendChild(option);
              });
            },
            error: function (jqXHR, textStatus, errorThrown) {
              console.error('Error fetching freezer names:', textStatus, errorThrown);
            }
          });
        } else {
          // Add the default option if no floor or location is selected
          const defaultOption = document.createElement('option');
          defaultOption.value = '';
          defaultOption.textContent = Drupal.t('Select Freezer');
          freezerDropdown.appendChild(defaultOption);
        }

        // Trigger the update of the freezer data container if no freezer is selected
        // if (!freezerDropdown.value) {
        //   $('#freezer-data-container').html('<div class="select-message"><strong>Select Location and Floor to display Freezers</strong></div>');
        // }
      }

      function updateFloors() {
        const location = document.getElementById('location');
        const floorDropdown = document.getElementById('dropdown1');
        const freezerDropdown = document.getElementById('freezer-select');
        const selectedValue = location.value;

        // Clear the floor dropdown
        floorDropdown.innerHTML = '';

        // Clear and reset the freezer dropdown to default option
        freezerDropdown.innerHTML = '<option value="">' + Drupal.t('Select Freezer') + '</option>';

        if (selectedValue) {
          // Fetch the nodes and dynamically load floor options based on the selected location
          $.ajax({
            url: Drupal.url('floor_names'),
            type: 'GET',
            data: {
              location: selectedValue
            },
            dataType: 'json',
            cache: false,
            headers: {
              'Cache-Control': 'no-cache, no-store, must-revalidate',
              'Pragma': 'no-cache',
              'Expires': '0'
            },
            success: function (data1) {
              // Remove duplicates
              const uniqueData1 = [...new Set(data1)];

              // Add the default option
              const defaultOption = document.createElement('option');
              defaultOption.value = '';
              defaultOption.textContent = Drupal.t('Select Floor');
              floorDropdown.appendChild(defaultOption);

              // Add the options to the floor dropdown
              uniqueData1.forEach(function (value) {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = value;
                floorDropdown.appendChild(option);
              });
            },
            error: function (jqXHR, textStatus, errorThrown) {
              console.error('Error fetching floor names:', textStatus, errorThrown);
            }
          });
        } else {
          // Add the default option if no location is selected
          const defaultOption = document.createElement('option');
          defaultOption.value = '';
          defaultOption.textContent = Drupal.t('Select Floor');
          floorDropdown.appendChild(defaultOption);
        }

        // Reset freezer data container
       // $('#freezer-data-container').html('<div class="select-message"><strong>Select Location and Floor to display Freezers</strong></div>');
      }

      $('#dropdown1', context).on('change', updateDropdown2);
      $('#location', context).on('change', updateFloors);

      // Initialize the dropdowns with the default options
      $('#freezer-select').html('<option value="">' + Drupal.t('Select Freezer') + '</option>');
      $('#dropdown1').html('<option value="">' + Drupal.t('Select Floor') + '</option>');
    }
  };
})(jQuery, Drupal);
