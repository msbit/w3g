<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
  <head>
    <!-- this charset is the best for replays because chat messages are encoded in it -->
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <link rel="stylesheet" type="text/css" href="style.css" media="screen, projection" />
    <meta name="author" content="Juliusz 'Julas' Gonera" />
    <title>Warcraft III Replay Parser for PHP</title>
    <script type="text/javascript">
      <!--//--><![CDATA[//><!--
      function display(id) {
        if (document.layers) {
          document.layers[id].display = (document.layers[id].display != 'block') ? 'block' : 'none';
        } else if (document.all) {
          document.all[id].style.display = (document.all[id].style.display != 'block') ? 'block'  : 'none';
        } else if (document.getElementById) {
          document.getElementById(id).style.display = (document.getElementById(id).style.display != 'block') ? 'block' : 'none';
        }
      }
      //--><!]]>
    </script>
  </head>
  <body>
    <?php
    $timeStart = microtime();
    require('w3g-julas.php');
  
    $id = $_GET['id'];
  
    // path to the replay directory (*.w3g files) - must be ended with /
    $w3gPath = 'replays/';

    // path to the data files, can be identical as w3g one or not
    $txtPath = 'database/';

    // only for links to webprofiles
    $gateway = 'Kalimdor';
  
    // listing replay files (we need it even when viewing details for
    // prev/next links
    if (false !== ($replaysDir = opendir($w3gPath))) {
      $i = 0;
      while (false !== ($replayFile = readdir($replaysDir))) {
        if ($replayFile != '.' && $replayFile != '..' && false !== ($ext_pos = strpos($replayFile, '.w3g'))) {
          $replayFile = substr($replayFile, 0, $ext_pos);
          // create database file if replay is new
          if (!file_exists($txtPath . $replayFile . '.txt') && $replayFile != 'LastReplay') {
            $replay = new replay($w3gPath . $replayFile . '.w3g');
            $txtFile = fopen($txtPath . $replayFile . '.txt', 'a');
            flock($txtFile, 2);
            fputs($txtFile, serialize($replay));
            flock($txtFile, 3);
            fclose($txtFile);
          }
          $replays[$i] = $replayFile;
          $i++;
        }
      }
      closedir($replaysDir);
      if ($replays) {
        sort($replays);
      } else {
        print('<p>Replay folder contains no replays!</p>');      
      }
    } else {
      print('<p>Can\'t read replay folder!</p>');
    }
  
    // listing replays - short info
    if (!isset($id) && !isset($_FILES['replay_file'])) {
      print('<div id="top"><h1>index of ' . $w3gPath . '</h1></div>
      <div id="functions"><b>' . $i . '</b> total</div>
      <div id="content">');
      ?>
      <h2>Check your own replay!</h2>
      <form enctype="multipart/form-data" action="?" method="post">
        <fieldset>
          <input type="hidden" name="MAX_FILE_SIZE" id="MAX_FILE_SIZE" value="2000000" />
          <label for="replay_file">File: </label><input name="replay_file" id="replay_file" type="file" />
          <label for="gateway">Gateway: </label><select name="gateway" id="gateway">
            <option selected="selected">Lordaeron</option>
            <option>Azeroth</option>
            <option>Northrend</option>
            <option>Kalimdor</option>
          </select>
          <input type="submit" value="Send" />
        </fieldset>
      </form>
      <ol id="replays">
      <?php
      foreach ($replays as $replayFile) {
        if ($replayFile == 'LastReplay') {
          continue;
        }
        print('<li><a class="title" href="?id=' . urlencode($replayFile) . '">' . $replayFile . '</a>
        <a class="download" href="' . $w3gPath . $replayFile . '.w3g">&#187; download</a>(' . round(filesize($w3gPath . $replayFile . '.w3g') / 1024) . ' KB)<br />');
  
        $txtFile = fopen($txtPath . $replayFile . '.txt', 'r');
        flock($txtFile, 1);
        $replay = unserialize(fgets($txtFile));
        flock($txtFile, 3);
        fclose($txtFile);
        $i = 1;
        foreach ($replay->teams as $team=>$players) {
          if ($team != 12) {
            print('<b>team ' . $i . ': </b>');
            foreach ($players as $player) {
              print(' <img src="img/' . strtolower($replay->header['ident']) . '/' . strtolower($player['race']) . '.gif" alt="' . $player['race'] . '" />');
              if ($player['race'] == 'Random') {
                print('&#187; <img src="img/' . strtolower($replay->header['ident']) . '/' . strtolower($player['race_detected']) . '.gif" alt="' . $player['race_detected'] . '" />');
              }
              if (!$player['computer']) {
                print('<a href="http://classic.battle.net/war3/ladder/' . $replay->header['ident'] . '-player-profile.aspx?Gateway=' . $gateway . '&amp;PlayerName=' . $player['name'] . '">' . $player['name'] . '</a> (' . round($player['apm']) . ' APM)');
              } else {
                print('Computer (' . $player['ai_strength'] . ')');
              }
            }
            print('<br />');
            $i++;
          }
        }
        $temp = strpos($replay->game['map'], ')') + 1;
        $map = substr($replay->game['map'], $temp, strlen($replay->game['map']) - $temp - 4);
        $version = sprintf('%02d', $replay->header['major_v']);
        print($replay->game['type']);
        print(' with ' . $replay->game['observers']);
        print(' | ' . $map . ' | ' . convert_time($replay->header['length']) . ' | v1.' . $version . ' ' . $replay->header['ident'] . '</li>');
      }
      print('</ol></div>');
  
    // details about the replay
    } else {
      $pos = array_search($id, $replays);
  
      print('
      <h1>' . $id . ' details</h1>
      <div id="functions">');
      if ($pos > 0) {
        print('<a href="?id=' . urlencode($replays[$pos - 1]) . '">&#171; prev</a>');
      }
      print('<a href="?">index</a>
      <a href="?id=' . urlencode($replays[$pos + 1]) . '">next &#187;</a>
      </div>
      <div id="content">');
  
      if (file_exists($txtPath . $id . '.txt')) {
        $txtFile = fopen($txtPath . $id . '.txt', 'r');
        flock($txtFile, 1);
        $replay = unserialize(fgets($txtFile));
        flock($txtFile, 3);
        fclose($txtFile);
      } elseif ($id) {
        $replay = new replay($w3gPath . $id . '.w3g');
      } elseif (is_uploaded_file($_FILES['replay_file']['tmp_name'])) {
        $replay = new replay($_FILES['replay_file']['tmp_name']);
        $gateway = $_POST['gateway'];
      } else {
        print('No replay file given!');
        $error = 1;
      }
  
      if (!isset($error)) {
        if ($replay->errors) {
          print('<p><b>Warning!</b> The script has encountered some errors when parsing the replay. Please report them to the <a class="menuleft" href="mailto:julas&#64;toya.net.pl">author</a>. <a href="javascript:display(\'errors\');">&#187; details</a></p>
          <div id="errors" class="additional">');
          foreach ($replay->errors as $number => $info) {
            print($info . '<br /><br />');
          }
          print('</div>');
        }
      
        print('
        <h2>General information</h2>');
        $temp = strpos($replay->game['map'], ')') + 1;
        $map = substr($replay->game['map'], $temp, strlen($replay->game['map']) - $temp - 4);
        $version = sprintf('%02d', $replay->header['major_v']);
        print('
        <img style="float: left; margin-right: 10px;" src="http://classic.battle.net/war3/images/ladder-revise/minimaps/' . $map . '.jpg" alt="Mini Map" />
        <ul class="info">
        <li><b>name:</b> ' . $replay->game['name'] . '</li>
        <li><b>type:</b> ' . $replay->game['type'] . '</li>
        <li><b>host:</b> ' . $replay->game['creator'] . '</li>
        <li><b>saver:</b> ' . $replay->game['saver_name'] . '</li>
        <li><br /><b>map:</b> ' . $map . '</li>
        <li><b>players:</b> ' . $replay->game['player_count'] . '</li>
        <li><b>length:</b> ' . convert_time($replay->header['length']) . '</li>
        <li><b>speed:</b> ' . $replay->game['speed'] . '</li>
        <li><b>version:</b> 1.' . $version . ' ' . $replay->header['ident'] . '</li>');
        if (file_exists($w3gPath . $id . '.w3g')) {
          print('<li><br /><a class="download" href="' . urlencode($w3gPath . $id) . '.w3g">&#187; download</a>(' . round(filesize($w3gPath . $id . '.w3g') / 1024) .' KB)</li>');
        }
        
        print('</ul><ul class="info">
        <li><b>lock teams:</b> ' . convert_yesno($replay->game['lock_teams']) . '</li>
        <li><b>teams together:</b> ' . convert_yesno($replay->game['teams_together']) . '</li>
        <li><b>full shared unit control:</b> ' . convert_yesno($replay->game['full_shared_unit_control']) . '</li>
        <li><br /><b>random races:</b> ' . convert_yesno($replay->game['random_races']) . '</li>
        <li><b>random hero:</b> ' . convert_yesno($replay->game['random_hero']) . '</li>
        <li><br /><b>observers:</b> ' . $replay->game['observers'] . '</li>
        <li><b>visibility:</b> ' . $replay->game['visibility'] . '</li>
        </ul>');
  
        print('<h2>Players</h2>
        <div>');
        $i = 1;
        foreach ($replay->teams as $team=>$players) {
          if ($team != 12) {
            print('<b>team ' . $i . '</b>');
            // "If at least one player gets a draw result the whole game is draw."
            if (!isset($replay->game['winner_team'])) {
              print(' (unknown)');
            } else if ($replay->game['winner_team'] === 'tie' || $replay->game['loser_team'] === 'tie') {
              print(' (tie)');
            } elseif ($team === $replay->game['winner_team']) {
              print(' (winner)');
            } else {
              print(' (loser)');
            }
            print('<br />');
            foreach ($players as $player) {          
              print('<pre>');
              //print_r($player);
              print('</pre>');
              print('
              <div class="section">
              <img src="img/' . strtolower($replay->header['ident']) . '/' . strtolower($player['race']) . '.gif" alt="' . $player['race'] . '" />');
              if ($player['race'] == 'Random') {
                print('&#187; <img src="img/' . strtolower($replay->header['ident']) . '/' . strtolower($player['race_detected']) . '.gif" alt="' . $player['race_detected'] . '" />');
              }
              if (!$player['computer']) {
                print('<b><a href="http://classic.battle.net/war3/ladder/' . $replay->header['ident'] . '-player-profile.aspx?Gateway=' . $gateway . '&amp;PlayerName=' . $player['name'] . '">' . $player['name'] . '</a></b> (');
              } else {
                print('<b>Computer (' . $player['ai_strength'] . ')</b> (');
              }
              // remember there's no color in tournament replays from battle.net website
              if ($player['color']) {
                print('<span class="' . $player['color'] . '">' . $player['color'] . '</span>');
                // since version 2.0 of the parser there's no players array so
                // we have to gather colors and names earlier as it will be harder later ;)
                $colors[$player['player_id']] = $player['color'];
                $names[$player['player_id']] = $player['name'];
              }
              if (!$player['computer']) {
                print(' | ' . round($player['apm']) . ' APM | ');
                print($player['actions'] . ' actions | ');
                print(convert_time($player['time']) . ')<br />
                <div class="details">');
                
                if (isset($player['heroes'])) {
                  foreach ($player['heroes'] as $name=>$info) {
                    // don't display info for heroes whose summoning was aborted
                    if ($name != 'order' && isset($info['level'])) {
                      $hero_file = strtolower(str_replace(' ', '', $name));
                      print('<img style="width: 14px; height: 14px;" src="img/heroes/' . $hero_file . '.gif" alt="Hero icon" /> <b>' . $info['level'] . '</b> <a href="javascript:display(\'' . $hero_file . $player['player_id'] . '\');" title="Click to see abilities">' . $name . '</a> <div id="' . $hero_file . $player['player_id'] . '" class="additional">');
                      foreach ($info['abilities'] as $time=>$abilities) {
                        if ($time !== 'order') {
                          if ($time) {
                            print('<br /><b>' . convert_time($time) . '</b> Retraining<br />');
                          }
                          foreach ($abilities as $ability=>$info) {
                            print('<img src="img/abilities/' . strtolower(str_replace(' ', '', $ability)) . '.gif" alt="Ability icon" /> <b>' . $info . '</b> ' . $ability . '<br />');
                          }
                        }
                      }
                      print('</div>');
                    }
                  }
                }
                
                if (isset($player['actions_details'])) {
                  print('<br />
                  <a href="javascript:display(\'actions' . $player['player_id'] . '\');">&#187; actions </a>
                  <div id="actions' . $player['player_id'] . '" class="additional">
                  <table>');
                  ksort($player['actions_details']);
                  foreach ($player['actions_details'] as $name=>$info) {
                    print('<tr><td style="text-align: right;">' . $name . '</td><td style="text-align: right;"><b>' . $info . '</b></td><td><div class="graph" style="width: ' . round($info/10) . 'px;"></div></td></tr>');
                  }
                  print('</table>
                  <b>' . $player['actions'] . '</b> total</div>');
                }
                
                if (isset($player['hotkeys'])) {
                  print('<a href="javascript:display(\'hotkeys' . $player['player_id'] . '\');">&#187; hotkeys </a>
                  <div id="hotkeys' . $player['player_id'] . '" class="additional">
                  <table>');
                  ksort($player['hotkeys']);
                  foreach ($player['hotkeys'] as $name=>$info) {
                    print('<tr><td style="text-align: right;"><b>' . ($name + 1) . '</b></td><td style="text-align: right;">' . $info['assigned'] . '</td><td><div class="graph" style="width: ' . round($info['assigned']/7) . 'px;"></div></td><td style="text-align: right;">' . $info['used'] . '</td><td><div class="graph" style="width: ' . round($info['used']/7) . 'px;"></div></td></tr>');
                  }
                  print('</table>(assigned/used)</div>');
                }
  
                if (isset($player['units'])) {              
                  print('<a href="javascript:display(\'units' . $player['player_id'] . '\');">&#187; units </a>
                  <div id="units' . $player['player_id'] . '" class="additional">
                  <table>');
                  $ii = 0;
                  foreach ($player['units'] as $name=>$info) {
                    if ($name != 'order' && $info > 0) { // don't show units which were cancelled and finally never made by player
                      print('<tr><td style="text-align: right;">' . $name . '</td><td style="text-align: right;"><b>' . $info . '</b></td><td><div class="graph" style="width: ' . ($info*5) . 'px;"></div></td></tr>');
                      $ii += $info;
                    }
                  }
                  print('</table>
                  <b>' . $ii . '</b> total</div>');
  
                  print('<a href="javascript:display(\'unitorder' . $player['player_id'] . '\');">&#187; unit order</a>
                  <div id="unitorder' . $player['player_id'] . '" class="additional">');
                  foreach ($player['units']['order'] as $time=>$name) {
                    print('<b>' . convert_time($time) . '</b> ' . $name . '<br />');
                  }
                  print('</div>');
                }
  
                if (isset($player['upgrades'])) {
                  print('<a href="javascript:display(\'upgrades' . $player['player_id'] . '\');">&#187; upgrades</a>
                  <div id="upgrades' . $player['player_id'] . '" class="additional">
                  <table>');
                  $ii = 0;
                  foreach ($player['upgrades'] as $name=>$info) {
                    if ($name != 'order') {
                      print('<tr><td style="text-align: right;">' . $name . '</td><td style="text-align: right;"><b>' . $info . '</b></td><td><div class="graph" style="width: ' . ($info*20) . 'px;"></div></td></tr>');
                      $ii += $info;
                    }
                  }
                  print('</table>
                  <b>' . $ii . '</b> total</div>');
                }
  
                if (isset($player['buildings'])) {
                  print('<a href="javascript:display(\'buildings' . $player['player_id'] . '\');">&#187; buildings</a>
                  <div id="buildings' . $player['player_id'] . '" class="additional">
                  <table>');
                  $ii = 0;
                  foreach ($player['buildings'] as $name=>$info) {
                    if ($name != 'order') {
                      print('<tr><td style="text-align: right;">' . $name . '</td><td style="text-align: right;"><b>' . $info . '</b></td><td><div class="graph" style="width: ' . ($info*20) . 'px;"></div></td></tr>');
                      $ii += $info;
                    }
                  }
                  print('</table>
                  <b>' . $ii . '</b> total</div>');
  
                  print('<a href="javascript:display(\'buildorder' . $player['player_id'] . '\');">&#187; build order</a>
                  <div id="buildorder' . $player['player_id'] . '" class="additional">');
                  foreach ($player['buildings']['order'] as $time=>$name) {
                    print('<b>' . convert_time($time) . '</b> ' . $name . '<br />');
                  }
                  print('</div>');
                }
  
                if (isset($player['items'])) {
                  print('<a href="javascript:display(\'items' . $player['player_id'] . '\');">&#187; items</a>
                  <div id="items' . $player['player_id'] . '" class="additional">
                  <table>');
                  $ii = 0;
                  foreach ($player['items'] as $name=>$info) {
                    if ($name != 'order') {
                      print('<tr><td style="text-align: right;">' . $name . '</td><td style="text-align: right;"><b>' . $info . '</b></td><td><div class="graph" style="width: ' . ($info*20) . 'px;"></div></td></tr>');
                      $ii += $info;
                    }
                  }
                  print('</table>
                  <b>' . $ii . '</b> total</div>');
                }
                print('</div>');
              } else {
                print(')');
              }
              print('</div>');
            }
            $i++;
          }
        }
        if (isset($replay->teams['12'])) {
          print('<b>observers</b> (' . $replay->game['observers'] . ')<br />');
          $comma = 0;
          foreach ($replay->teams['12'] as $player) {
            if ($comma) {
              print(', ');
            }
            $comma = 1;
            print('<a href="http://classic.battle.net/war3/ladder/' . $replay->header['ident'] . '-player-profile.aspx?Gateway=' . $gateway . '&amp;PlayerName=' . $player['name'] . '">' . $player['name'] . '</a>');
          }
          print('<br /><br />');
        }
        print('</div>');
        if ($replay->chat) {
          print('<h2>Chat log</h2>
          <p>');
          
          $prev_time = 0;
          foreach ($replay->chat as $content) {
            if ($content['time'] - $prev_time > 45000) {
              print('<br />'); // we can easily see when players stopped chatting
            }
            $prev_time = $content['time'];
            print('(' . convert_time($content['time']));
            if (isset($content['mode'])) {
              if (is_int($content['mode'])) {
                print(' / ' . '<span class="' . $colors[$content['mode']] . '">' . $names[$content['mode']] . '</span>');
              } else {
                print(' / ' . $content['mode']);
              }
            }
            print(') ');
            if (isset($content['player_id'])) {
              // no color for observers
              if (isset($colors[$content['player_id']])) {
                print('<span class="' . $colors[$content['player_id']] . '">' . $content['player_name'] . '</span>: ');
              } else {
                print('<span class="observer">' . $content['player_name'] . '</span>: ');
              }
            }
            print(htmlspecialchars($content['text'], ENT_COMPAT, 'UTF-8') . '<br />');
          }
          print('</p>');
        }
      }
      print('</div>');
    }
    $timeEnd = microtime();
    $temp = explode(' ', $timeStart . ' ' . $timeEnd);
    $duration=sprintf('%.8f',($temp[2] + $temp[3]) - ($temp[0] + $temp[1]));
    ?>
    <div id="footer">
      <a href="http://w3rep.sourceforge.net/">Warcraft III Replay Parser for PHP</a>. Copyright &copy; 2003-2010 <a href="http://juliuszgonera.com/">Juliusz 'Julas' Gonera</a>.
      All rights reserved.
      <?php
      print('Generated in ' . $duration . ' seconds.');
      ?>
    </div>
  </body>
</html>
