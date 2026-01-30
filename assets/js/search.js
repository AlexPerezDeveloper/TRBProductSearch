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

  // Track if user selected an item from dropdown
  var itemSelected = false;

  searchInput.on("input", function () {
    var term = $(this).val();
    itemSelected = false;

    clearTimeout(typingTimer);

    if (term.length >= minChars) {
      typingTimer = setTimeout(function () {
        performSearch(term);
      }, doneTypingInterval);
    } else {
      dropdown.hide().empty();
    }
  });

  // Handle form submission - redirects to WooCommerce search page
  searchForm.on("submit", function (e) {
    var term = searchInput.val().trim();

    // If user selected an item from dropdown, let the link handle it
    if (itemSelected) {
      return true;
    }

    // Validate minimum characters
    if (term.length < minChars) {
      e.preventDefault();
      dropdown
        .show()
        .html(
          '<div class="trb-no-results">' +
            trb_search_vars.strings.min_chars.replace("%d", minChars) +
            "</div>",
        );
      return false;
    }

    // Form will submit to home_url('/?s=term&post_type=product')
    // This redirects to the native WooCommerce search results page
    return true;
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
        dropdown.show().html('<div class="trb-loading">' + trb_search_vars.strings.loading + "</div>");
      },
      success: function (response) {
        if (response.success) {
          var html = response.data.html;

          // Add "View all results" link at the bottom
          var viewAllUrl =
            trb_search_vars.home_url + "?s=" + encodeURIComponent(term) + "&post_type=product";
          var viewAllLink =
            '<li class="trb-view-all"><a href="' +
            viewAllUrl +
            '">' +
            trb_search_vars.strings.view_all +
            "</a></li>";

          // Insert before closing </ul>
          html = html.replace("</ul>", viewAllLink + "</ul>");

          dropdown.html(html);
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
          '<div class="trb-error">' + trb_search_vars.strings.error + "</div>",
        );
      },
    });
  }

  // Handle click on dropdown items
  dropdown.on("click", "a", function () {
    itemSelected = true;
  });

  // Close dropdown when clicking outside
  $(document).on("click", function (e) {
    if (!searchForm.is(e.target) && searchForm.has(e.target).length === 0) {
      dropdown.hide();
    }
  });
});
