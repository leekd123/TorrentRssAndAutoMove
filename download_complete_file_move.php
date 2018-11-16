<?

include_once ($_SERVER['DOCUMENT_ROOT']."/settings.php");

FileMoveTask($settings);

function FileMoveTask($settings) {

	$chk_file = $settings->path->source."/.chk";
    if(file_exists($chk_file)){
		return;
	}
	exec("touch $chk_file");

	$files = filesInDir($settings->path->source);
	$url_sms = $settings->url->sms;
	createDirectory($settings->path->complete);

	foreach($files as $file) {
		$isTarget = false;
	
		$file_path_src = "";
		$file_path_dest = "";

		foreach($settings->target as $target) {
			if($target->name == "") continue;
			if(!$target->is_use) continue;
			//if($target->is_hold) continue;

			$is_complete = false;
			$is_fake = false;
			$is_overlap = false;
			$is_normal = false;
			$is_hold = false;
			
			$tmp_file_src = str_replace(" ", "", $file["name"]);
			$tmp_file_target = str_replace(" ", "", $target->name);
			
			$file_path_src = $file['dir']."/".$file['name'];
			
			if(preg_match("/$tmp_file_target/", $tmp_file_src)) {
				if($file["type"] == "F") {
				    $is_fake = isFake($file_path_src, $target->is_hold);
				    if($is_fake) {
				        createDirectory($settings->path->fake);
				        $file_path_dest = str_replace($settings->path->source, $settings->path->fake, $file_path_src);
				    } else {
    					$file_dest = getFileDest($file['name'], $target->name, $target->new_name, $target->season, $target->ep_calc, $target->is_bind);
    					$name = $target->name;
	        			$path_dest = ($target->is_hold ? $settings->path->hold : $settings->path->destination)."/$target->category/$target->new_name".($target->season_folder == "" ? "" : "/$target->season_folder");
    					createDirectory($path_dest);
    		
    					$file_path_dest = $path_dest."/".$file_dest;
    					if(file_exists($file_path_dest)){
							$is_overlap = true;
    					    createDirectory($settings->path->overlap);
    					    $file_path_dest = $settings->path->overlap."/".$file_dest;
    					}
    				}
					$is_complete = true;
					$is_hold = $target->is_hold;
				}
				$isTarget = true;
				break;
			}
		}
		
		if(!$isTarget && $settings->path->source==$file["dir"]) {
			$file_path_dest = $settings->path->complete."/".$file['name'];
			$is_normal = true;
			$is_complete = true;
		}

		if($is_complete) {
			$is_send = true;
			if(fileMove($file_path_src, $file_path_dest)) {
				$pre_message = "";
				if($is_fake) {
					$pre_message = "F ";
				}
				elseif ($is_overlap) {
					$pre_message = "O ";
				}
				elseif ($is_hold) {
					$pre_message = "H ";
					$is_send = false;
				}
				elseif ($is_normal) {
					$pre_message = "C ";
				}
				$message = $pre_message.$file["name"];
				if($is_send && !$url_sms) {
					sendMessage($message, $url_sms);
				}
				put_log($message, $settings->path->log);
			}
		} 
	}
	deleteEmptyDir($settings->path->source, $settings->path->log);

	unlink($chk_file);

}

function isFake($path, $is_hold) {
    $fakecheck = $_GET["fakecheck"];
    if($fakecheck == "N" || $is_hold) {
        return false;
    }
    else {
        $info_str = shell_exec('/var/packages/ffmpeg/target/bin/ffprobe -v quiet -print_format json -show_format "'.$path.'"');
        $info = json_decode($info_str);
        $encoder = $info->format->tags->encoder;
        if($encoder == "MH ENCODER")
            return false;
        return true;
    }
}

function put_log($message, $log_path) {
	$log_file = fopen($log_path, "a") or die("Unable to open file!");

	date_default_timezone_set('Asia/Seoul');
	
	$log = "[".date('Y-m-d H:i:s', time())."] ".$message."\n";
	$log = iconv('UTF-8', 'EUC-KR', $log);
	fwrite($log_file, $log);
	fclose($log_file);
}

function getFileDest($filename, $name, $new_name, $season, $ep_num_calc, $is_bind) {
	// 제목
	$t_name = $new_name ? $new_name : $name;

	// 에피소드
	$t_episode1 = null;
	$t_episode2 = null;
	preg_match("/(\d+)([_\-~](\d+))?회\s*합본/", $filename, $matches);
	if(count($matches) == 4) {
		$filename = str_replace($matches[0], "/", $filename);
		$t_episode1 = $matches[1];
		$t_episode2 = $matches[3];
	} else {
		preg_match("/EP?(\d+)([_\-~]EP?(\d+))?(\(합본\))?/", $filename, $matches);
		if(count($matches) == 2) {
			$filename = str_replace($matches[0], "/", $filename);
			$t_episode1 = $matches[1];
		} elseif(count($matches) >= 4) {
			$filename = str_replace($matches[0], "/", $filename);
			$t_episode1 = $matches[1];
			$t_episode2 = $matches[3];
		}
	}
	if($ep_num_calc) {
	    if(strtolower($ep_num_calc) == "x") {
			$t_episode1 = null;
			$t_episode2 = null;
	    }
	    else {
			if($t_episode1) $t_episode1 = $t_episode1 + $ep_num_calc;
			if($t_episode2) $t_episode2 = $t_episode2 + $ep_num_calc;
    	}
	}
	if($is_bind && $t_episode2 == null) {
		$t_episode2 = $t_episode1 * 2;
		$t_episode1 = $t_episode2 - 1;
	}
	
	// 시즌
	$t_season = null;
	if($t_episode1) {
		if($season) {
			$t_season = $season;
		} else {
			$tmp_name = preg_replace("/(\S)/u", "$1\s*", str_replace(" ", "", $name));
			preg_match("/$tmp_name\s*(시즌)?(\d+)/", $filename, $matches);
			if(count($matches) == 3) {
				$filename = str_replace($matches[0], "/", $filename);
				$t_season = $matches[2];
			} else {
				$filename = preg_replace("/$tmp_name/", "/", $filename);
				$t_season = 1;
			}
		}
	}

	// 날짜
	$t_date = null;
	if(preg_match("/\D(\d{2})(\d{2})(\d{2})\D/", $filename, $matches)) {
		$filename = str_replace($matches[0], "/", $filename);
		$t_date = "20$matches[1]-$matches[2]-$matches[3]";
	}

	$is_end = preg_match("/end/i", $filename, $matches);
	$filename = str_replace($matches[0], "", $filename);

	$is_repack = preg_match("/repack/i", $filename, $matches);
	$filename = str_replace($matches[0], "", $filename);
	
	// 나머지
	$t_desc = null;
	if(preg_match("/([^\/]*)$/", $filename, $matches)) {
		$t_desc = $matches[1];
		//$t_desc = preg_replace("/[ \.]+/", ".", $t_desc);
		$t_desc = trim($t_desc, ".");
	}
	
	//파일명 생성
	$t_filename = $t_name;
	if($t_season) $t_filename = $t_filename.sprintf(".S%02d", $t_season);
	if($t_episode1) $t_filename = $t_filename.sprintf(".E%02d", $t_episode1);
	if($t_episode2) $t_filename = $t_filename.sprintf("-%02d", $t_episode2);
	if($is_end) $t_filename = $t_filename.".END";
	if($t_date) $t_filename = $t_filename.sprintf(".%s", $t_date);
	if($is_repack) $t_filename = $t_filename.".repack";
	$t_filename = $t_filename.sprintf(".%s", $t_desc);

	return $t_filename;
}

function filesInDir ($tdir) {
	$time = $_GET["time"];
	if(!$time) $time = 0;

	$current_time = strtotime($time." minutes");
	if($dh = opendir ($tdir)) {
		$files = Array();
		$in_files = Array();
		
		while($a_file = readdir ($dh)) {
			if($a_file[0] == '.') continue;
			if($a_file[0] == '@') continue;

			$type="F";
			$tmp_dir = $tdir."/".$a_file;
			if(is_dir ($tmp_dir)) {
				$in_files = filesInDir ($tmp_dir);
				if(is_array ($in_files)) $files = array_merge ($files , $in_files);
				$type = "D";
			}
			
			$file_time = filemtime($tmp_dir);
			if($file_time < $current_time) {
				array_push ($files , array('dir'=>$tdir, 'type'=>$type, 'name'=>$a_file));
			}
		}
		
		closedir ($dh);
		return $files ;
	}
}

function deleteEmptyDir ($tdir, $log_path) {
	$count = 0;
	if($dh = opendir ($tdir)) {
		while($a_file = readdir ($dh)) {
			if($a_file[0] == '.') continue;

			$tmp_path = $tdir."/".$a_file;
			if(is_dir ($tmp_path)) {
				$countfile = deleteEmptyDir ($tmp_path, $log_path);
				$count += $countfile;
				if($countfile == 0) {
					rmdir ($tmp_path);
				}
			} else {
				if(preg_match("/\/@eaDir/i", $tdir)) {
					unlink($tmp_path);
				} elseif(strrpos($a_file, "최초배포") !== false) {
					$message = "DELETE ".$tmp_path;
					put_log($message, $log_path);
					unlink($tmp_path);
				} else {
					$count++;
				}
			}
		}
		
		closedir ($dh);
		return $count;
	}
}

function createDirectory($path) {
	if(!is_dir($path)) {
		if(@mkdir($path, 0777, true)) {
			if(is_dir($path)) {
				@chmod($path, 0777);
			}
		}
	}
}

function fileMove($src, $dest) {
	$increment = 0;
	$file = $dest;
    while(file_exists($file)) {
        $increment++;
        $file = $dest."~".$increment;
	}

	return rename($src, $file);
}

function sendMessage($message, $url_sms) {
	$url = str_replace("[message]", $message, $url_sms);
	file_get_contents($url);
}

?>