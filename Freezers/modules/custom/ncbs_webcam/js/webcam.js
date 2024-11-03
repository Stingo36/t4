(function ($, Drupal) {
  Drupal.behaviors.webcamBehavior = {
    attach: function (context, settings) {
      // Ensure this is only executed once per context load.
      if (typeof Drupal.behaviors.webcamBehavior.initialized === 'undefined') {
        Drupal.behaviors.webcamBehavior.initialized = true;

        $(document).ready(function () {
          // Check if the form is present.
          if ($('#node-page-form', context).length || $('#node-page-edit-form', context).length) {
            console.log('Form detected, initializing webcam...');

            // Check if the browser supports getUserMedia.
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
              console.log('Browser supports getUserMedia.');

              // Set up constraints for the video stream.
              const constraints = {
                video: {
                  width: 320,
                  height: 240
                }
              };

              let stream; // Declare stream variable outside to manage it.

              // Access the user's camera.
              navigator.mediaDevices.getUserMedia(constraints)
                .then(function (mediaStream) {
                  stream = mediaStream; // Assign the stream for later use.

                  // Attach the video stream to the specified element.
                  const videoElement = document.getElementById('my_camera');

                  if (videoElement && videoElement.tagName.toLowerCase() === 'video') {
                    videoElement.srcObject = stream;

                    // Add a check to ensure the play function exists before calling it.
                    if (typeof videoElement.play === 'function') {
                      videoElement.play();
                      console.log('Webcam attached successfully.');
                    } else {
                      console.error('play() is not a function on the video element.');
                    }
                  } else {
                    console.error('Element with ID "my_camera" is not a valid video element.');
                  }

                  // Ensure the capture button click event is bound only once.
                  $('#capture').off('click').on('click', function (event) {
                    event.preventDefault(); // Prevent form submission
                    console.log('Capture button clicked.');

                    if (videoElement) {
                      // Create a canvas to capture the image from the video stream.
                      const canvas = document.createElement('canvas');
                      canvas.width = videoElement.videoWidth;
                      canvas.height = videoElement.videoHeight;
                      const context = canvas.getContext('2d');
                      context.drawImage(videoElement, 0, 0, canvas.width, canvas.height);

                      // Convert the captured image to a data URL.
                      const dataUri = canvas.toDataURL('image/jpeg', 0.9);

                      // Store the captured image data in the hidden field
                      $('#webcam_image_data').val(dataUri);

                      // Send the image data to the Drupal backend to save it temporarily.
                      $.ajax({
                        url: Drupal.url('webcam/save-image'),
                        type: 'POST',
                        data: {
                          imageData: dataUri
                        },
                        success: function (response) {
                          console.log('Response from server:', response);
                          if (response.status === 'success') {
                            $('#result').html('<img src="' + response.file_url + '"/>');
                          } else {
                            console.error('Error saving image:', response.message);
                            alert('Error saving image: ' + response.message);
                          }
                        },
                        error: function (xhr, status, error) {
                          console.error('AJAX request failed:', xhr.responseText, error);
                          alert('Error during AJAX request: ' + xhr.responseText);
                        }
                      });
                    } else {
                      console.error('Cannot capture image, videoElement is null.');
                    }
                  });

                  // Load any previously captured image when the form is reloaded
                  const storedImageData = $('#webcam_image_data').val();
                  if (storedImageData) {
                    $('#result').html('<img src="' + storedImageData + '"/>');
                  }

                  // Cleanup when the page is navigated away or refreshed.
                  $(window).on('beforeunload', function () {
                    if (stream) {
                      stream.getTracks().forEach(function (track) {
                        track.stop();
                      });
                    }
                  });

                })
                .catch(function (error) {
                  if (error.name === 'NotAllowedError') {
                    alert('Access to the camera was denied. Please allow access to continue.');
                  } else if (error.name === 'NotFoundError') {
                    alert('No camera device found. Please connect a camera and try again.');
                  } else if (error.name === 'AbortError') {
                    console.error('The camera access was aborted by the user.');
                  } else {
                    console.error('Error accessing the camera:', error);
                    alert('An error occurred while accessing the camera. Please try again.');
                  }
                });

            } else {
              console.error('getUserMedia is not supported by this browser.');
              alert('Your browser does not fully support webcam functionality. Please use a more modern browser.');
            }
          }
        });
      }
    }
  };
})(jQuery, Drupal);
