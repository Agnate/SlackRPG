$(document).ready(function () {

  var $output = $('#output');
  var $command = $('#text');

  // Intercept the form being submitted.
  $("#interface-form").submit(function (e) {
    var postData = $(this).serializeArray();
    var formURL = $(this).attr("action");

    $.ajax({
      url : formURL,
      type: "POST",
      data : postData,
      success: function(data, textStatus, jqXHR) {
        // Get the scroll height before adding content.
        var scrollTop = $output.offset().top + $output.height();
        // Append the data returned into the main content.
        $output.append('<hr/>');
        $output.append(data);
        // Scroll to the top of the new data.
        $('html, body').animate({scrollTop: scrollTop});
      },
      error: function(jqXHR, textStatus, errorThrown) {
        console.log(textStatus);
        console.log(errorThrown);
      }
    });

    // Reselect the text field.
    $command.select();

    e.preventDefault();
    // e.unbind();
  });

});