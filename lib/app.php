<?php
// contains DB & important versioning
require dirname(__FILE__) . '/../config/config.php';
require dirname(__FILE__) . '/jsbin.php';

$request = explode('/', preg_replace('/^\//', '', preg_replace('/\/$/', '', preg_replace('/\?.*$/', '', $_SERVER['REQUEST_URI']))));
$action = array_pop($request);
$edit_mode = true; // determines whether we should go ahead and load index.php
$code_id = '';
$ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']);

// doesn't require a connection when we're landing for the first time
if ($action) {
  connect();
}

if (!$action) {
  // do nothing and serve up the page
  $revision = null;
} else if ($action == 'source' || $action == 'js') {
  header('Content-type: text/javascript');
  list($code_id, $revision) = getCodeIdParams($request);
  
  $edit_mode = false;
  
  if ($code_id) {
    list($latest_revision, $html, $javascript) = getCode($code_id, $revision);
  } else {
    list($html, $javascript) = defaultCode();
  }
  
  if ($action == 'js') {
    echo $javascript;
  } else {
		echo 'var template = ' . json_encode(compact('html', 'javascript'));
  }
} else if ($action == 'edit') {
  list($code_id, $revision) = getCodeIdParams($request);
  if ($revision == 'latest') {
    $latest_revision = getMaxRevision($code_id);
    header('Location: /' . $code_id . '/' . $latest_revision . '/edit');
    $edit_mode = false;
    
  }
} else if ($action == 'save') {
  list($code_id, $revision) = getCodeIdParams($request);
  if (!$code_id) {
    $code_id = generateCodeId();
    $revision = 1;
  } else {
    $revision = getMaxRevision($code_id);
    $revision++;
  }
  
  $javascript = isset($_POST['javascript']) ? $_POST['javascript'] : null;
  $html = isset($_POST['html']) ? $_POST['html'] : null;
  
  // $sql = 'select url, revision from sandbox where javascript="' . mysql_real_escape_string($javascript) . '" and html="' . mysql_real_escape_string($html) . '"';
  // $results = mysql_query($sql);

  // if (mysql_num_rows($results)) { // if there's matching code, switch to that. Could this be confusing?
  //   $row = mysql_fetch_object($results);
  //   $code_id = $row->url;
  //   $revision = $row->revision;
  // } else {
  $sql = sprintf('insert into sandbox (javascript, html, created, last_viewed, url, revision) values ("%s", "%s", now(), now(), "%s", "%s")', mysql_real_escape_string($javascript), mysql_real_escape_string($html), mysql_real_escape_string($code_id), mysql_real_escape_string($revision));
  mysql_query($sql);
  // }
  
  if ($ajax) {
    // supports plugins making use of JS Bin via ajax calls and callbacks
    if (isset($_REQUEST['callback']) && $_REQUEST['callback']) {
      echo $_REQUEST['callback'] . '("';
    }
    $url = 'http://jsbin.com/' . $code_id . ($revision == 1 ? '' : '/' . $revision);
    if (isset($_REQUEST['format']) && strtolower($_REQUEST['format']) == 'plain') {
      echo $url;          
    } else {
      echo '{ "url" : "' . $url . '", "edit" : "' . $url . '/edit", "html" : "' . $url . '/edit", "js" : "' . $url . '/edit" }';
    }
    
    if ($_REQUEST['callback']) {
      echo '")';
    }
  } else {
    // code was saved, so lets do a location redirect to the newly saved code
    $edit_mode = false;
    if ($revision == 1) {
      header('Location: /' . $code_id . '/edit');
    } else {
      header('Location: /' . $code_id . '/' . $revision . '/edit');
    }
  }
  
  
} else if ($action) { // this should be an id
  $subaction = array_pop($request);
  
  if ($action == 'latest') {
    // find the latest revision and redirect to that.
    $code_id = $subaction;
    $latest_revision = getMaxRevision($code_id);
    header('Location: /' . $code_id . '/' . $latest_revision);
    $edit_mode = false;
  }
  
  // gist are formed as jsbin.com/gist/1234 - which land on this condition, so we need to jump out, just in case
  else if ($subaction != 'gist') {
    if ($subaction) {
      $code_id = $subaction;
      $revision = $action;
    } else {
      $code_id = $action;
      $revision = 1;
    }
    list($latest_revision, $html, $javascript) = getCode($code_id, $revision);

    if (stripos($html, '%code%') === false) {
      $html = preg_replace('@</body>@', '<script>%code%</script></body>', $html);
    }
    
    // removed the regex completely to try to protect $n variables in JavaScript
    $htmlParts = explode("%code%", $html);
    $html = $htmlParts[0] . $javascript . $htmlParts[1];
    
    $html = preg_replace("/%code%/", $javascript, $html);
    $html = preg_replace('/<\/body>/', jsbin_template('google_analytics.php') . '</body>', $html);
    $html = preg_replace('/<\/body>/', '<script src="/js/render/edit.js"></script>' . "\n</body>", $html);


    if (!$ajax) {
      $html = preg_replace('/<html(.*)/', "<html$1\n\n<!--\n\n  Created using http://jsbin.com\n  Source can be edited via http://jsbin.com/$code_id/edit\n\n-->\n", $html);            
    }

    if (!$html && !$ajax) {
      $javascript = "/*\n  Created using http://jsbin.com\n  Source can be edit via http://jsbin.com/$code_id/edit\n*/\n\n" . $javascript;
    }

    if (!$html) {
      header("Content-type: text/javascript");
    }

    echo $html ? $html : $javascript;
    $edit_mode = false;
  }
}

if (!$edit_mode || $ajax) {
  exit;
}



?>
