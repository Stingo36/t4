/**
 * @file
 * belgrade theme main JS file.
 *
 */

(function (Drupal, drupalSettings) {
  // Initiate all Toasts on page.
  Drupal.behaviors.belgradeToast = {
    attach(context, settings) {
      once('initToast', '.toast', context).forEach((el) => {
        const toastList = new bootstrap.Toast(el);
        toastList.show();
      });
    },
  };

  // Accordion buttons containing Edit links.
  Drupal.behaviors.accordionButtonLinks = {
    attach(context, settings) {
      once(
        'accordionButttonLinks',
        '.fieldset-legend.accordion-button a',
        context,
      ).forEach((el) => {
        // Prevent accordion collapse when clicking on links
        el.addEventListener('click', function (e) {
          if (e.target.href) {
            const targetUrl = e.target.href;
            window.location.href = targetUrl;
          }
        });
      });
    },
  };

  // Collapse and accordion if a field is required.
  Drupal.behaviors.focusRequired = {
    attach(context, settings) {
      const inputs = document.querySelectorAll('form .accordion input');
      [].forEach.call(inputs, function (input) {
        input.addEventListener('invalid', function (e) {
          const accordion = input.closest('.collapse');
          const collapseAccordion = bootstrap.Collapse.getInstance(accordion);
          if (collapseAccordion) {
            collapseAccordion.show();
          }
        });
      });
    },
  };

  // Collapse certain accordions on mobile
  Drupal.behaviors.collapseAccordionMob = {
    attach(context, settings) {
      const breakPoint =
        drupalSettings.responsive.breakpoints['belgrade.sm-max'];
      const x = window.matchMedia(breakPoint);
      if (x.matches) {
        // If media query matches collapse the bef
        const befAccordions = document.querySelectorAll(
          '.bef-exposed-form .collapse',
        );
        if (befAccordions.length) {
          [].forEach.call(befAccordions, function (bef) {
            const collapseBef = bootstrap.Collapse.getInstance(bef);
            if (collapseBef) {
              collapseBef.hide();
            }
          });
        }
      }
    },
  };


// AUTOMATICALLY REFRESH 



// (function (Drupal, drupalSettings) {
//   Drupal.behaviors.autoPagerLoop = {
//     attach: function (context, settings) {
//       var currentPage = 0;
//       var totalPages = 0;
//       var viewSelector = '.view-all-freezers'; // Class or ID of the view container

//       // Function to check and set total number of pages
//       function updateTotalPages() {
//         var pagerElement = document.querySelector('.pager'); // Selector for the pager element in the view
//         if (pagerElement) {
//           totalPages = pagerElement.querySelectorAll('li.pager-item').length; // Total number of pages
//           console.log("Total pages found:", totalPages);
//         }
//       }

//       // Function to load the next page
//       function goToNextPage() {
//         var nextPageLink = document.querySelector('.pager-next a'); // Selector for the "Next" button/link

//         if (nextPageLink) {
//           // Simulate click on "Next" link to load next page via AJAX
//           console.log("Navigating to next page:", currentPage + 1);
//           nextPageLink.click();
//           currentPage++;
//         } else {
//           console.log("No next page found. Resetting to first page.");
//           loadFirstPage();
//         }
//       }

//       // Function to reset to the first page
//       function loadFirstPage() {
//         var firstPageLink = document.querySelector('.pager-first a'); // Selector for the "First" button/link

//         if (firstPageLink) {
//           console.log("Navigating back to the first page.");
//           firstPageLink.click();
//           currentPage = 0;
//         } else {
//           console.log("First page link not found. Reloading the first page manually.");
//           var viewWrapper = document.querySelector(viewSelector);
//           if (viewWrapper) {
//             // Use AJAX to reset the view to page 0 (first page)
//             var ajaxSettings = {
//               url: window.location.href, // URL of the current page
//               submit: {
//                 view_name: 'all_freezers',
//                 view_display_id: 'page_1',
//                 page: 0 // First page
//               }
//             };

//             // Create a new AJAX request to reload the first page
//             var ajax = new Drupal.Ajax({
//               element: viewWrapper,
//               url: ajaxSettings.url,
//               event: 'click'
//             });

//             ajax.execute();
//             currentPage = 0; // Reset the page counter
//           }
//         }
//       }

//       // Initial setup to update total pages when view is loaded
//       updateTotalPages();

//       // Set interval to automatically trigger pagination every 10 seconds
//       setInterval(function () {
//         if (document.readyState === 'complete') {
//           // If the last page is reached, reset to the first page
//           if (currentPage >= totalPages - 1) {
//             loadFirstPage();
//           } else {
//             goToNextPage();
//           }
//         } else {
//           console.log("DOM not ready, retrying...");
//         }
//       }, 10000); // Adjust time interval as needed (10 seconds in this example)
//     }
//   };
// })(Drupal, drupalSettings);







})(Drupal, drupalSettings);
