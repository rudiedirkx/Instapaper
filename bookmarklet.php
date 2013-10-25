<?php

require 'inc.bootstrap.php';

header('Content-type: application/javascript');

$loggedIn = is_logged_in(false);
// var_export($loggedIn);

$color = 'red';
$text = 'Saved to instapaper!';

if ( $loggedIn ) {
  if ( isset($_GET['url'], $_GET['title']) ) {
    $method = 'POST';
    $url = 'https://www.instapaper.com/api/1/bookmarks/add';
    $data = array(
      'url' => ($_GET['url']),
      'title' => ($_GET['title']),
      'resolve_final_url' => 0,
    );

// var_dump(TOKEN, SECRET);

    $http = sign_request($method, $url, $data, SECRET);
    $response = $http->request();

    // Great success!
    if ( $response->response && @$response->response->bookmark_id ) {
      $color = 'green';
    }

    // Bad IP response
    else {
      $text = 'Bad IP response (' . $response->code . '): ' . html($response->body);
    }

    // echo '<pre>';
    // print_r($response);
    // print_r($http);

  }

  // Missing parameters
  else {
    $text = "Missing parameters";
  }
}

// Not logged in
else {
  $text = "You're not logged in";
}

?>

(function() {

  var div = document.createElement('div');
  div.textContent = '<?= addslashes($text) ?>';
  div.setAttribute('style', 'position: fixed; left: 20px; top: 20px; border: solid 20px <?= $color ?>; padding: 30px 20px; background: white; font-size: 30px; cursor: pointer; transition: opacity 500ms linear');
  div.onclick = function(e) {
    this.remove();
  };
  document.body.appendChild(div);
  setTimeout(function() {
    try {
      div.style.opacity = 0;
      setTimeout(function() {
        div.remove();
      }, 500);
    } catch (ex) {}
  }, 3000);

})();
