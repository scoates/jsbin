<?php

function db_connect() {
  // sniff, and if on my mac...
  $link = mysql_connect(JSBIN_DB_HOST, JSBIN_DB_USER, JSBIN_DB_PASSWORD); 
  mysql_select_db(JSBIN_DB_NAME, $link);
}

function db_getMaxRevision($code_id) {
  $sql = sprintf('select max(revision) as rev from sandbox where url="%s"', mysql_real_escape_string($code_id), mysql_real_escape_string($revision));
  $result = mysql_query($sql);
  $row = mysql_fetch_object($result);
  return $row->rev ? $row->rev : 0;
}

function db_getCode($code_id, $revision, $testonly = false) {
  $sql = sprintf('select * from sandbox where url="%s" and revision="%s"', mysql_real_escape_string($code_id), mysql_real_escape_string($revision));
  $result = mysql_query($sql);
  
  if (!mysql_num_rows($result) && $testonly == false) {
    header("HTTP/1.0 404 Not Found");
    $default = defaultCode(true);
    return array(0, $default[0], $default[1]);
  } else if (!mysql_num_rows($result)) {
    return array($revision, null, null);
  } else {
    $row = mysql_fetch_object($result);
    
    // TODO required anymore? used for auto deletion
    $sql = 'update sandbox set last_viewed=now() where id=' . $row->id;
    mysql_query($sql);
    
    $javascript = preg_replace('/\r/', '', $row->javascript);
    $html = preg_replace('/\r/', '', $row->html);
    
    $revision = $row->revision;
    
    // return array(preg_replace('/\r/', '', $html), preg_replace('/\r/', '', $javascript), $row->streaming, $row->active_tab, $row->active_cursor);
    if (get_magic_quotes_gpc()) {
      $html = stripslashes($html);
      $javascript = stripslashes($javascript);
    }
    return array($revision, $html, $javascript, $row->streaming, $row->active_tab, $row->active_cursor);
  }
}

function db_insert($javascript, $html, $code_id, $revision)
{
  $sql = sprintf('insert into sandbox (javascript, html, created, last_viewed, url, revision) values ("%s", "%s", now(), now(), "%s", "%s")', mysql_real_escape_string($javascript), mysql_real_escape_string($html), mysql_real_escape_string($code_id), mysql_real_escape_string($revision));
  mysql_query($sql);
}
