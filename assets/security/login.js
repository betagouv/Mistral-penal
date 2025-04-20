$("#login_submit").click(
  function() {
    /** @var {string} username */
    let username = $("#login_username").val();
    /** @var {string} password */
    let password = $("#login_password").val();
    if(username && password) {
      $("#login_username").prop("readOnly", true);
      $("#login_password").prop("readOnly", true);
      $("#login_submit").prop("style", "background-color: #CCCCCC !important;");
      $( "#login_submit" ).fadeOut("slow");
    }
  }
);
