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
					document.all[id].style.display = (document.all[id].style.display != 'block') ? 'block'	: 'none';
				} else if (document.getElementById) {
					document.getElementById(id).style.display = (document.getElementById(id).style.display != 'block') ? 'block' : 'none';
				}
			}
			//--><!]]>
		</script>
	</head>
	<body>
		<?php
		$time_start = microtime();
		require('w3g-julas.php');
	
    if(array_key_exists('id', $_GET)) {
		  $id = $_GET['id'];
    }
	
		// path to the replay directory (*.w3g files) - must be ended with /
		if (isset($_GET['w3g_path'])) {
			$w3g_path = $_GET['w3g_path'];
		} else {
			$w3g_path = 'replays/';
		}
		// path to the data files, can be identical as w3g one or not
		$txt_path = 'database/';
		// only for links to webprofiles
		$gateway = 'Kalimdor';
	
		// listing replay files (we need it even when viewing details for
		// prev/next links
		if (false !== ($replays_dir = opendir($w3g_path))) {
			$i = 0;
			while (false !== ($replay_file = readdir($replays_dir))) {
				if ($replay_file != '.' && $replay_file != '..' && false !== ($ext_pos = strpos($replay_file, '.w3g'))) {
					$replay_file = substr($replay_file, 0, $ext_pos);
					// create database file if replay is new
					if (!file_exists($txt_path.$replay_file.'.txt') && $replay_file != 'LastReplay') { // LastReplay additions just for my own purposes
						$replay = new replay($w3g_path.$replay_file.'.w3g');
						$txt_file = fopen($txt_path.$replay_file.'.txt', 'a');
						flock($txt_file, 2);
						fputs($txt_file, serialize($replay));
						flock($txt_file, 3);
						fclose($txt_file);
					}
					$replays[$i] = $replay_file;
					$i++;
				}
			}
			closedir($replays_dir);
			if ($replays) {
				sort($replays);
			} else {
				echo('<p>Replay folder contains no replays!</p>');      
			}
		} else {
			echo('<p>Can\'t read replay folder!</p>');
		}
	
		// listing replays - short info
		if (!isset($id) && !isset($_FILES['replay_file'])) {
			echo('<div id="top"><h1>index of '.$w3g_path.'</h1></div>
			<div id="functions"><b>'.$i.'</b> total</div>
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
			foreach ($replays as $replay_file) {
				if ($replay_file == 'LastReplay') { // LastReplay additions just for my own purposes
					continue;
				}
				echo('<li><a class="title" href="?id='.urlencode($replay_file).'">'.$replay_file.'</a>
				<a class="download" href="'. $w3g_path.$replay_file.'.w3g">&#187; download</a>('.round(filesize($w3g_path.$replay_file.'.w3g')/1024).' KB)<br />');
	
				$txt_file = fopen($txt_path.$replay_file.'.txt', 'r');
				flock($txt_file, 1);
				$replay = unserialize(fgets($txt_file));
				flock($txt_file, 3);
				fclose($txt_file);
				$i = 1;
				foreach ($replay->teams as $team=>$players) {
					if ($team != 12) {
						echo('<b>team '.$i.': </b>');
						foreach ($players as $player) {
							echo(' <img src="img/'.strtolower($replay->header['ident']).'/'.strtolower($player['race']).'.gif" alt="'.$player['race'].'" />');
							if ($player['race'] == 'Random') {
								echo('&#187; <img src="img/'.strtolower($replay->header['ident']).'/'.strtolower($player['race_detected']).'.gif" alt="'.$player['race_detected'].'" />');
							}
							if (!$player['computer']) {
								echo('<a href="http://classic.battle.net/war3/ladder/'.$replay->header['ident'].'-player-profile.aspx?Gateway='.$gateway.'&amp;PlayerName='.$player['name'].'">'.$player['name'].'</a> ('.round($player['apm']).' APM)');
							} else {
								echo('Computer ('.$player['ai_strength'].')');
							}
						}
						echo('<br />');
						$i++;
					}
				}
				$temp = strpos($replay->game['map'], ')')+1;
				$map = substr($replay->game['map'], $temp, strlen($replay->game['map'])-$temp-4);
				$version = sprintf('%02d', $replay->header['major_v']);
				echo($replay->game['type']);
				echo(' with '.$replay->game['observers']);
				echo(' | '.$map.' | '.convert_time($replay->header['length']).' | v1.'.$version.' '.$replay->header['ident'].'</li>');
			}
			echo('</ol></div>');
	
		// details about the replay
		} else {
			$pos = array_search($id, $replays);
	
			echo('
			<h1>'.$id.' details</h1>
			<div id="functions">');
			if ($pos > 0) {
				echo('<a href="?id='.urlencode($replays[$pos-1]).'">&#171; prev</a>');
			}
			echo('<a href="?">index</a>
			<a href="?id='.urlencode($replays[$pos+1]).'">next &#187;</a>
			</div>
			<div id="content">');
	
			if (file_exists($txt_path.$id.'.txt')) {
				$txt_file = fopen($txt_path.$id.'.txt', 'r');
				flock($txt_file, 1);
				$replay = unserialize(fgets($txt_file));
				flock($txt_file, 3);
				fclose($txt_file);
			} elseif ($id) {
				$replay = new replay($w3g_path.$id.'.w3g');
			} elseif (is_uploaded_file($_FILES['replay_file']['tmp_name'])) {
				$replay = new replay($_FILES['replay_file']['tmp_name']);
				$gateway = $_POST['gateway'];
			} else {
				echo('No replay file given!');
				$error = 1;
			}
	
			if (!isset($error)) {
				if ($replay->errors) {
					echo('<p><b>Warning!</b> The script has encountered some errors when parsing the replay. Please report them to the <a class="menuleft" href="mailto:julas&#64;toya.net.pl">author</a>. <a href="javascript:display(\'errors\');">&#187; details</a></p>
					<div id="errors" class="additional">');
					foreach ($replay->errors as $number => $info) {
						echo($info.'<br /><br />');
					}
					echo('</div>');
				}
			
				echo('
				<h2>General information</h2>');
				$temp = strpos($replay->game['map'], ')')+1;
				$map = substr($replay->game['map'], $temp, strlen($replay->game['map'])-$temp-4);
				$version = sprintf('%02d', $replay->header['major_v']);
				echo('
				<img style="float: left; margin-right: 10px;" src="http://classic.battle.net/war3/images/ladder-revise/minimaps/'.$map.'.jpg" alt="Mini Map" />
				<ul class="info">
				<li><b>name:</b> '.$replay->game['name'].'</li>
				<li><b>type:</b> '.$replay->game['type'].'</li>
				<li><b>host:</b> '.$replay->game['creator'].'</li>
				<li><b>saver:</b> '.$replay->game['saver_name'].'</li>
				<li><br /><b>map:</b> '.$map.'</li>
				<li><b>players:</b> '.$replay->game['player_count'].'</li>
				<li><b>length:</b> '.convert_time($replay->header['length']).'</li>
				<li><b>speed:</b> '.$replay->game['speed'].'</li>
				<li><b>version:</b> 1.'.$version.' '.$replay->header['ident'].'</li>');
				if (file_exists($w3g_path.$id.'.w3g')) {
					echo('<li><br /><a class="download" href="'.urlencode($w3g_path.$id).'.w3g">&#187; download</a>('.round(filesize($w3g_path.$id.'.w3g')/1024).' KB)</li>');
				}
				
				echo('</ul><ul class="info">
				<li><b>lock teams:</b> '.convert_yesno($replay->game['lock_teams']).'</li>
				<li><b>teams together:</b> '.convert_yesno($replay->game['teams_together']).'</li>
				<li><b>full shared unit control:</b> '.convert_yesno($replay->game['full_shared_unit_control']).'</li>
				<li><br /><b>random races:</b> '.convert_yesno($replay->game['random_races']).'</li>
				<li><b>random hero:</b> '.convert_yesno($replay->game['random_hero']).'</li>
				<li><br /><b>observers:</b> '.$replay->game['observers'].'</li>
				<li><b>visibility:</b> '.$replay->game['visibility'].'</li>
				</ul>');
	
				echo('<h2>Players</h2>
				<div>');
				$i = 1;
				foreach ($replay->teams as $team=>$players) {
					if ($team != 12) {
						echo('<b>team '.$i.'</b>');
						// "If at least one player gets a draw result the whole game is draw."
						if (!isset($replay->game['winner_team'])) {
							echo(' (unknown)');
						} else if ($replay->game['winner_team'] === 'tie' || $replay->game['loser_team'] === 'tie') {
							echo(' (tie)');
						} elseif ($team === $replay->game['winner_team']) {
							echo(' (winner)');
						} else {
							echo(' (loser)');
						}
						echo('<br />');
						foreach ($players as $player) {          
							echo('
							<div class="section">
							<img src="img/'.strtolower($replay->header['ident']).'/'.strtolower($player['race']).'.gif" alt="'.$player['race'].'" />');
							if ($player['race'] == 'Random') {
								echo('&#187; <img src="img/'.strtolower($replay->header['ident']).'/'.strtolower($player['race_detected']).'.gif" alt="'.$player['race_detected'].'" />');
							}
							if (!$player['computer']) {
								echo('<b><a href="http://classic.battle.net/war3/ladder/'.$replay->header['ident'].'-player-profile.aspx?Gateway='.$gateway.'&amp;PlayerName='.$player['name'].'">'.$player['name'].'</a></b> (');
							} else {
								echo('<b>Computer ('.$player['ai_strength'].')</b> (');
							}
							// remember there's no color in tournament replays from battle.net website
							if ($player['color']) {
								echo('<span class="'.$player['color'].'">'.$player['color'].'</span>');
								// since version 2.0 of the parser there's no players array so
								// we have to gather colors and names earlier as it will be harder later ;)
								$colors[$player['player_id']] = $player['color'];
								$names[$player['player_id']] = $player['name'];
							}
							if (!$player['computer']) {
								echo(' | '.round($player['apm']).' APM | ');
								echo($player['actions'].' actions | ');
								echo(convert_time($player['time']).')<br />
								<div class="details">');
								
								if (isset($player['heroes'])) {
									foreach ($player['heroes'] as $name=>$info) {
										// don't display info for heroes whose summoning was aborted
										if ($name != 'order' && isset($info['level'])) {
											$hero_file = strtolower(str_replace(' ', '', $name));
											echo('<img style="width: 14px; height: 14px;" src="img/heroes/'.$hero_file.'.gif" alt="Hero icon" /> <b>'.$info['level'].'</b> <a href="javascript:display(\''.$hero_file.$player['player_id'].'\');" title="Click to see abilities">'.$name.'</a> <div id="'.$hero_file.$player['player_id'].'" class="additional">');
											foreach ($info['abilities'] as $time=>$abilities) {
												if ($time !== 'order') {
													if ($time) {
														echo('<br /><b>'.convert_time($time).'</b> Retraining<br />');
													}
													foreach ($abilities as $ability=>$info) {
														echo('<img src="img/abilities/'.strtolower(str_replace(' ', '', $ability)).'.gif" alt="Ability icon" /> <b>'.$info.'</b> '.$ability.'<br />');
													}
												}
											}
											echo('</div>');
										}
									}
								}
								
								if (isset($player['actions_details'])) {
									echo('<br />
									<a href="javascript:display(\'actions'.$player['player_id'].'\');">&#187; actions </a>
									<div id="actions'.$player['player_id'].'" class="additional">
									<table>');
									ksort($player['actions_details']);
									foreach ($player['actions_details'] as $name=>$info) {
										echo('<tr><td style="text-align: right;">'.$name.'</td><td style="text-align: right;"><b>'.$info.'</b></td><td><div class="graph" style="width: '.round($info/10).'px;"></div></td></tr>');
									}
									echo('</table>
									<b>'.$player['actions'].'</b> total</div>');
								}
								
								if (isset($player['hotkeys'])) {
									echo('<a href="javascript:display(\'hotkeys'.$player['player_id'].'\');">&#187; hotkeys </a>
									<div id="hotkeys'.$player['player_id'].'" class="additional">
									<table>');
									ksort($player['hotkeys']);
									foreach ($player['hotkeys'] as $name=>$info) {
										echo('<tr><td style="text-align: right;"><b>'.($name+1).'</b></td><td style="text-align: right;">'.$info['assigned'].'</td><td><div class="graph" style="width: '.round($info['assigned']/7).'px;"></div></td><td style="text-align: right;">'.$info['used'].'</td><td><div class="graph" style="width: '.round($info['used']/7).'px;"></div></td></tr>');
									}
									echo('</table>(assigned/used)</div>');
								}
	
								if (isset($player['units'])) {              
									echo('<a href="javascript:display(\'units'.$player['player_id'].'\');">&#187; units </a>
									<div id="units'.$player['player_id'].'" class="additional">
									<table>');
									$ii = 0;
									foreach ($player['units'] as $name=>$info) {
										if ($name != 'order' && $info > 0) { // don't show units which were cancelled and finally never made by player
											echo('<tr><td style="text-align: right;">'.$name.'</td><td style="text-align: right;"><b>'.$info.'</b></td><td><div class="graph" style="width: '.($info*5).'px;"></div></td></tr>');
											$ii += $info;
										}
									}
									echo('</table>
									<b>'.$ii.'</b> total</div>');
								}
	
								if (isset($player['upgrades'])) {
									echo('<a href="javascript:display(\'upgrades'.$player['player_id'].'\');">&#187; upgrades</a>
									<div id="upgrades'.$player['player_id'].'" class="additional">
									<table>');
									$ii = 0;
									foreach ($player['upgrades'] as $name=>$info) {
										if ($name != 'order') {
											echo('<tr><td style="text-align: right;">'.$name.'</td><td style="text-align: right;"><b>'.$info.'</b></td><td><div class="graph" style="width: '.($info*20).'px;"></div></td></tr>');
											$ii += $info;
										}
									}
									echo('</table>
									<b>'.$ii.'</b> total</div>');
								}
	
								if (isset($player['buildings'])) {
									echo('<a href="javascript:display(\'buildings'.$player['player_id'].'\');">&#187; buildings</a>
									<div id="buildings'.$player['player_id'].'" class="additional">
									<table>');
									$ii = 0;
									foreach ($player['buildings'] as $name=>$info) {
										if ($name != 'order') {
											echo('<tr><td style="text-align: right;">'.$name.'</td><td style="text-align: right;"><b>'.$info.'</b></td><td><div class="graph" style="width: '.($info*20).'px;"></div></td></tr>');
											$ii += $info;
										}
									}
									echo('</table>
									<b>'.$ii.'</b> total</div>');
	
									echo('<a href="javascript:display(\'buildorder'.$player['player_id'].'\');">&#187; build order</a>
									<div id="buildorder'.$player['player_id'].'" class="additional">');
									foreach ($player['buildings']['order'] as $time=>$name) {
										echo('<b>'.convert_time($time).'</b> '.$name.'<br />');
									}
									echo('</div>');
								}
	
								if (isset($player['items'])) {
									echo('<a href="javascript:display(\'items'.$player['player_id'].'\');">&#187; items</a>
									<div id="items'.$player['player_id'].'" class="additional">
									<table>');
									$ii = 0;
									foreach ($player['items'] as $name=>$info) {
										if ($name != 'order') {
											echo('<tr><td style="text-align: right;">'.$name.'</td><td style="text-align: right;"><b>'.$info.'</b></td><td><div class="graph" style="width: '.($info*20).'px;"></div></td></tr>');
											$ii += $info;
										}
									}
									echo('</table>
									<b>'.$ii.'</b> total</div>');
								}
								echo('</div>');
							} else {
								echo(')');
							}
							echo('</div>');
						}
						$i++;
					}
				}
				if (isset($replay->teams['12'])) {
					echo('<b>observers</b> ('.$replay->game['observers'].')<br />');
					$comma = 0;
					foreach ($replay->teams['12'] as $player) {
						if ($comma) {
							echo(', ');
						}
						$comma = 1;
						echo('<a href="http://classic.battle.net/war3/ladder/'.$replay->header['ident'].'-player-profile.aspx?Gateway='.$gateway.'&amp;PlayerName='.$player['name'].'">'.$player['name'].'</a>');
					}
					echo('<br /><br />');
				}
				echo('</div>');
				if ($replay->chat) {
					echo('<h2>Chat log</h2>
					<p>');
					
					$prev_time = 0;
					foreach ($replay->chat as $content) {
						if ($content['time'] - $prev_time > 45000) {
							echo('<br />'); // we can easily see when players stopped chatting
						}
						$prev_time = $content['time'];
						echo('('.convert_time($content['time']));
						if (isset($content['mode'])) {
							if (is_int($content['mode'])) {
								echo(' / '.'<span class="'.$colors[$content['mode']].'">'.$names[$content['mode']].'</span>');
							} else {
								echo(' / '.$content['mode']);
							}
						}
						echo(') ');
						if (isset($content['player_id'])) {
							// no color for observers
							if (isset($colors[$content['player_id']])) {
								echo('<span class="'.$colors[$content['player_id']].'">'.$content['player_name'].'</span>: ');
							} else {
								echo('<span class="observer">'.$content['player_name'].'</span>: ');
							}
						}
						echo(htmlspecialchars($content['text'], ENT_COMPAT, 'UTF-8').'<br />');
					}
					echo('</p>');
				}
			}
			echo('</div>');
		}
		$time_end = microtime();
		$temp = explode(' ', $time_start.' '.$time_end);
		$duration=sprintf('%.8f',($temp[2]+$temp[3])-($temp[0]+$temp[1]));
		?>
		<div id="footer">
			<a href="http://w3rep.sourceforge.net/">Warcraft III Replay Parser for PHP</a>. Copyright &copy; 2003-2010 <a href="http://juliuszgonera.com/">Juliusz 'Julas' Gonera</a>.
			All rights reserved.
			<?php
			echo('Generated in '.$duration.' seconds.');
			?>
		</div>
	</body>
</html>
