<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">    
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="author" content="Juliusz 'Julas' Gonera" />
    <title>Warcraft III Replay Parser (c) 2003 Juliusz 'Julas' Gonera</title>
  </head>
  <body xml:lang="en">
    <pre><?php
      $time_start = microtime();
      $actions = true;
      $chat = true;
      
      include("w3g-julas.php");
      $somerep = new replay('rep.w3g', $actions, $chat);

      print_r($somerep);

      $time_end = microtime();
      $temp = explode(' ', $time_start . ' ' . $time_end);
      $duration=sprintf('%.8f',($temp[2]+$temp[3])-($temp[0]+$temp[1]));
      echo("Generated in $duration seconds.");
    ?></pre>
  </body>
</html>
