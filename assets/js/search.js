jQuery(document).ready(function ($) {
  var searchInput = $(".trb-product-search-form .search-field");
  var searchForm = $(".trb-product-search-form");
  var typingTimer;
  var doneTypingInterval = 500; // 0.5 seconds
  var minChars = 3;

  // Create dropdown container if not exists
  if ($(".trb-search-dropdown").length === 0) {
    searchForm.append('<div class="trb-search-dropdown"></div>');
  }
  var dropdown = $(".trb-search-dropdown");

  searchInput.on("input", function () {
    var term = $(this).val();

    clearTimeout(typingTimer);

    if (term.length >= minChars) {
      typingTimer = setTimeout(function () {
        performSearch(term);
      }, doneTypingInterval);
    } else {
      dropdown.hide().empty();
    }
  });

  function performSearch(term) {
    $.ajax({
      url: trb_search_vars.ajax_url,
      type: "GET",
      data: {
        action: "trb_search",
        term: term,
        security: trb_search_vars.nonce,
      },
      beforeSend: function () {
        dropdown.show().html('<div class="trb-loading">Loading...</div>');
      },
      success: function (response) {
        if (response.success) {
          dropdown.html(response.data.html);
        } else {
          dropdown.html(
            '<div class="trb-no-results">' + response.data.message + "</div>",
          );
        }
      },
      error: function (xhr, status, error) {
        // More detailed error logging
        console.error("AJAX Error:", {
          status: status,
          error: error,
          responseText: xhr.responseText,
          statusCode: xhr.status,
        });
        dropdown.html(
          '<div class="trb-error">Error fetching results. Please try again.</div>',
        );
      },
    });
  }

  // Close dropdown when clicking outside
  $(document).on("click", function (e) {
    if (!searchForm.is(e.target) && searchForm.has(e.target).length === 0) {
      dropdown.hide();
    }
  });
});
