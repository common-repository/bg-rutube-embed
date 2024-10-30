<?php
/* 
    Plugin Name: Bg RuTube Embed
    Plugin URI: http://bogaiskov.ru/plugin-bg-rutube-embed/
    Description: The plugin is the easiest way to embed RuTube videos in WordPress.
    Version: 1.6.3
    Author: VBog
    Author URI: http://bogaiskov.ru 
	License:     GPL2
	Text Domain: bg-rutube-embed
	Domain Path: /languages
*/

/*  Copyright 2022-2023  Vadim Bogaiskov  (email: vadim.bogaiskov@yandex.ru)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*****************************************************************************************

	Блок загрузки плагина
	
******************************************************************************************/

// Запрет прямого запуска скрипта
if ( !defined('ABSPATH') ) {
	die( 'Sorry, you are not allowed to access this page directly.' ); 
}
define('BG_RUTUBE_VERSION', '1.6.3');

// Подключаем CSS и JS 
add_action( 'wp_enqueue_scripts', 'bg_rutube_scripts' );
function bg_rutube_scripts() {
	wp_enqueue_style( 'bg_rutube_styles', plugins_url( '/css/bg_rutube.css', plugin_basename(__FILE__) ), array() , BG_RUTUBE_VERSION );
	wp_enqueue_script( 'bg_rutube_proc', plugins_url( '/js/bg_rutube.js', __FILE__ ), ['jquery'], BG_RUTUBE_VERSION, true );
}

// Загрузка интернационализации
add_action( 'plugins_loaded', 'bg_rutube_load_textdomain' );
function bg_rutube_load_textdomain() {
	load_plugin_textdomain( 'bg-rutube-embed', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' ); 
}

/*****************************************************************************************

	Регистрируем Embed обработчики
		
******************************************************************************************/
// Вставка видио
add_action( 'init', function () {
	wp_embed_register_handler(
		'bg_rutube_video',
		'~rutube\.ru/video/([a-f0-9]+(\/?\?t=\d+)?)/?~i',
		'bg_rutube_video_callback_oembed_provider'
	);
});
function bg_rutube_video_callback_oembed_provider( $matches ) {
	$playlist = bg_rutube_create_playlist ($matches[1]);
	$embed = bg_rutube_playlist_show ( $playlist );    
	return $embed;
}

// Вставка плейлиста
add_action( 'init', function () {
	wp_embed_register_handler(
		'bg_rutube_plst',
		'~rutube\.ru/plst/([0-9]+)/?~i',
		'bg_rutube_plst_callback_oembed_provider'
	);
});
function bg_rutube_plst_callback_oembed_provider( $matches ) {
	$playlist = bg_rutube_get_playlist ($matches[1]);
	$embed = bg_rutube_playlist_show ( $playlist );    
	return $embed;
}

include_once( 'inc/options.php' );

/*****************************************************************************************

	Регистрируем шорт-код [rutube id="{uuid}" title="" description="" sort=""]
		id - ID плейлиста или фильма, или список ID фильмов через запятую
		title - название плейлиста
		description - описание плейлиста
		sort='on' - сортировать плейлист по алфавиту
		perpage - количество элементов на странице
		limit - max размер плейлиста
		mode - режим запуска видео при загрузке страницы
			'preview' - заставка
			'load' - загрузить видео во фрейм
			'play' - загрузить видео во фрейм и воспроизвести
		
		perpage, limit, mode = '' - в соответствии с настройками плагина
		
******************************************************************************************/
add_shortcode( 'rutube', 'bg_rutube_player_sortcode' );
function bg_rutube_player_sortcode( $atts ) {
	extract( shortcode_atts( array(
		'id' => '',
		'title' => '',
		'description' => '',
		'sort' => '',
		'perpage' => '',
		'limit' => '',
		'mode' => '',
		'start'=> 0
	), $atts ) );

	$id = esc_attr($id);
	$title = esc_html($title);
	$description = esc_html($description);
	if (!in_array ($mode, ['preview', 'load', 'play'])) $mode = '';
	
	// Формируем список фильмов с RuTube из шорткода
	if (empty($id)) return "";														// Фильм не указан
	elseif (strlen($id) < 32) $playlist = bg_rutube_get_playlist ($id, $limit);		// Плейлист RuTube
	else $playlist = bg_rutube_create_playlist ($id, $title, $description, $limit);	// Фильм или список фильмов

	if(!$playlist || empty($playlist)) {
		$quote = "<div class='tube-error'><p class='warning'>".__('Sorry, the video is temporarily unavailable.', 'bg-rutube-embed')."</p></div>";
	} else {
		// Сортировка плейлиста по алфавиту
		if ($sort) {
			usort ($playlist, function($a, $b) {
				 return strcmp($a["title"], $b["title"]);
			});
		}	
		$quote = bg_rutube_playlist_show ( $playlist, $perpage, $mode, $start );    
	}
	return $quote;
}

/*****************************************************************************************

	Получить информацию о плейлисте RuTybe 
		
******************************************************************************************/
function bg_rutube_get_playlist_info ( $playlist_id ) {
	$info = array();
	
	$val = get_option('bg_rutube_options');
	$transient = $val ? $val['transient'] : false;
	
	$key='rutube_playlist_info_'.$playlist_id;				// Проверяем обновления на RuTube раз в час
	if(false===($json=get_transient($key)) || !$transient) {
		$url = 'http://rutube.ru/api/playlist/custom/'.$playlist_id.'/';
		$result = wp_remote_get ($url,
			[
				'timeout' => 5,
				'httpversion' => '1.1',
				'user-agent'  => 'Mozilla/5.0 (compatible; Rigor/1.0.0; http://rigor.com)',
			]
		);
		if( is_wp_error( $result ) ) {
			error_log('Playlist info reading error from '.$playlist_id.' : ('.$result->get_error_code().') '.$result->get_error_message() , 0);
		} elseif( ($errorcode = wp_remote_retrieve_response_code( $result )) === 200 ) {
			$json = wp_remote_retrieve_body($result);
			set_transient( $key, $json, HOUR_IN_SECONDS );
		} else {
			error_log($url.'<br>'.'Playlist info. Error: '.$errorcode.' - '.wp_remote_retrieve_response_message( $result ) , 0);
		}
	}
	$info = json_decode($json, true);
	
	return $info;
}

/*****************************************************************************************

	Получить плейлист с RuTybe 
		
******************************************************************************************/
function bg_rutube_get_playlist ( $playlist_id, $limit='' ) {
	$playlist = array();
	$tracks = array();
	
	$page = 0;			// номер страницы, с которой начинается закачка
	$has_next = true;
	$val = get_option('bg_rutube_options');
	$transient = $val ? $val['transient'] : false;
	$singular = $val ? $val['singular'] : false;
	if (empty($limit) && $limit != '0') $limit = $val ? $val['limit'] : 0;
	
	if (is_singular() || !$singular) {
		while($has_next) {
			$key='rutube_playlist_'.$playlist_id.'_'.$page;	// Проверяем обновления на RuTube раз в час
			if(false===($json=get_transient($key)) || !$transient) {
				$url = 'http://rutube.ru/api/playlist/custom/'.$playlist_id.'/videos/?page='.$page;
				$result = wp_remote_get ($url,
					[
						'timeout' => 5,
						'httpversion' => '1.1',
						'user-agent'  => 'Mozilla/5.0 (compatible; Rigor/1.0.0; http://rigor.com)',
					]
				);
				if( is_wp_error( $result ) ) {
					error_log('Playlist data reading error from '.$playlist_id.' : ('.$result->get_error_code().') '.$result->get_error_message() , 0);
					break;
				} elseif( ($errorcode = wp_remote_retrieve_response_code( $result )) === 200 ) {
					$json = wp_remote_retrieve_body($result);
					set_transient( $key, $json, HOUR_IN_SECONDS );
				} else {
					error_log($url.'<br>'.'Playlist. Error: '.$errorcode.' - '.wp_remote_retrieve_response_message( $result ) , 0);
					break;
				}
			}
			$videos = json_decode($json, true);

			$page = $videos['page'];
			$has_next = $videos['has_next'];
			foreach($videos['results'] as $videoData) {
				$track['uuid'] = $videoData['id'];
				$track['url'] = "https://rutube.ru/video/embed/".$videoData['id']."/";
				$track['length'] = $videoData['duration'];
				$track['artist'] = "";
				$track['title'] = $videoData['title'];
				$track['description'] = $videoData['description'];
				$track['thumbnail'] = $videoData['thumbnail_url'];
				$tracks[] = $track;
			}
			$page++;
			if ($limit && count($tracks) >= $limit) {
				$tracks = array_slice($tracks, 0, $limit);
				break;
			}
		}
		if (!empty($tracks)) {
			$info = bg_rutube_get_playlist_info ( $playlist_id );
			$playlist['title'] = $info['title'];
			$playlist['description'] = $info['description'];
			$playlist['thumbnail'] = $info['thumbnail_url'];
			if ($limit) $playlist['count'] = $limit;
			else $playlist['count'] = $info['videos_count'];
			$playlist['tracks'] = $tracks;
			
			if (!$playlist['description']) $playlist['description'] = $playlist['title'];
		}
	} else {
		$info = bg_rutube_get_playlist_info ( $playlist_id );
		$playlist['title'] = $info['title'];
		$playlist['description'] = $info['description'];
		$playlist['thumbnail'] = $info['thumbnail_url'];
		if ($limit) $playlist['count'] = $limit;
		else $playlist['count'] = $info['videos_count'];
		if (!$playlist['description']) $playlist['description'] = $playlist['title'];			
	}
	return $playlist;
}

/*****************************************************************************************

	Формируем плейлист на основе списка треков RuTube 
	
******************************************************************************************/
function bg_rutube_create_playlist ($ids, $title='', $description='', $limit='') {
	$playlist = array();
	$tracks = array();
	
	$videoList = explode ( ',' , $ids );
	$val = get_option('bg_rutube_options');
	$transient = $val ? $val['transient'] : false;
	$singular = $val ? $val['singular'] : false;
	if (empty($limit) && $limit != '0') $limit = $val ? $val['limit'] : 0;
	
	if (is_singular() || !$singular) {
		foreach ($videoList as $videoID) {
			$videoID = strip_tags($videoID);
			$videoID = trim($videoID);
			$key='rutube_'.$videoID;	// Проверяем обновления на RuTube раз в час
			if(false===($json=get_transient($key)) || !$transient) {
				$url = 'https://rutube.ru/api/video/'.$videoID;
				$result = wp_remote_get ($url,
					[
						'timeout' => 5,
						'httpversion' => '1.1',
						'user-agent'  => 'Mozilla/5.0 (compatible; Rigor/1.0.0; http://rigor.com)',
					]
				);
				$json = '';
				if( is_wp_error( $result ) ) {
					error_log('Metadata reading error from '.$videoID.' : ('.$result->get_error_code().') '.$result->get_error_message() , 0);
				} elseif( ($errorcode = wp_remote_retrieve_response_code( $result )) === 200 ) {
					$json = wp_remote_retrieve_body($result);
					set_transient( $key, $json, HOUR_IN_SECONDS );
				} else {
					error_log($url.'<br>'.'Metadata. Error: '.$errorcode.' - '.wp_remote_retrieve_response_message( $result ) , 0);
				}
			}
			$videoData = json_decode($json, true);
			
			if ($videoData) {
				$track['uuid'] = $videoID;
				$track['url'] = "https://rutube.ru/video/embed/".$videoID."/";
				$track['length'] = $videoData['duration'];
				$track['artist'] = "";
				$track['title'] = $videoData['title'];
				$track['description'] = $videoData['description'];
				$track['thumbnail'] = $videoData['thumbnail_url'];
				$tracks[] = $track;
			} else {
				error_log("Metadata. Empty or error answer from RuTube: ". $json." for ".$videoID, 0); 
			}
			if ($limit && count($tracks) >= $limit) break;
		}
		if (!empty($tracks)) {
			$playlist['thumbnail'] = $tracks[0]['thumbnail'];
			$playlist['tracks'] = $tracks;
			$playlist['count'] = count($tracks);
			
			if ($playlist['count'] == 1) {
				$playlist['title'] = trim($tracks[0]['title']);
				$playlist['description'] = trim($tracks[0]['description']);
				$playlist['count'] = "";
			} else {
				$playlist['title'] = $title;
				$playlist['description'] = $description;
			}
			
			if (!$playlist['description']) $playlist['description'] = $playlist['title'];
		}
	} else {
		$url = 'https://rutube.ru/api/video/'.$videoList[0];
		$result = wp_remote_get ($url,
			[
				'timeout' => 5,
				'httpversion' => '1.1',
				'user-agent'  => 'Mozilla/5.0 (compatible; Rigor/1.0.0; http://rigor.com)',
			]
		);
		$json = '';
		if( is_wp_error( $result ) ) {
			error_log('Metadata reading error from '.$videoList[0].' : ('.$result->get_error_code().') '.$result->get_error_message() , 0);
		} elseif( ($errorcode = wp_remote_retrieve_response_code( $result )) === 200 ) {
			$json = wp_remote_retrieve_body($result);
		} else {
			error_log($url.'<br>'.'Metadata. Error: '.$errorcode.' - '.wp_remote_retrieve_response_message( $result ) , 0);
		}
		$videoData = json_decode($json, true);
		if ($videoData) {
			$playlist['thumbnail'] = $videoData['thumbnail_url'];
			$playlist['count'] = count($videoList);
			if ($playlist['count'] == 1) {
				$playlist['title'] = trim($videoData['title']);
				$playlist['description'] = trim($videoData['description']);
				$playlist['count'] = "";
			} else {
				$playlist['title'] = $title;
				$playlist['description'] = $description;
			}
			if (!$playlist['description']) $playlist['description'] = $playlist['title'];			
		} else {
			error_log("Metadata. Empty or error answer from RuTube: ". $json." for ".$videoList[0], 0); 
		}
		
	}
	return $playlist;
}

/*****************************************************************************************

	Отображение плейлиста, используя RuTube Embed API
		
******************************************************************************************/
function bg_rutube_playlist_show ( $playlist, $perpage = '', $mode = '', $startTime = 0) {
	static $id=999;

	// Выводим на экран
	if (empty($playlist)) return "";
	$val = get_option('bg_rutube_options');
	$singular = $val ? $val['singular'] : false;
	if (empty($perpage) && $perpage != '0')	$perpage = ($val && !empty($val['perpage'])) ? $val['perpage'] : 0;
	if ($mode) $startmode = $mode;
	else $startmode = ($val && $val['startmode']) ? $val['startmode'] : 'preview';

	ob_start();	
	if (is_singular() || !$singular) {
		$id++;
		$uuid = '_'.$id;
		
		if ($perpage > 0) {
			if (isset($_GET['v'.$uuid])) $current_page = $_GET['v'.$uuid];
			else $current_page = 1;
			$track_no = ($current_page-1)*$perpage;
			$last_no = $current_page*$perpage;
			if ($last_no > $playlist['count']) $last_no = $playlist['count'];
			$total =(int) ceil((int) $playlist['count']/$perpage);
			
			$base_url = strtok(get_permalink(), '?');
			$args = [
				'base'         => $base_url.'%_%',
				'format'       => '?v'.$uuid.'=%#%#bg_rutube_playlistContainer'.$uuid,
				'total'        => $total,
				'current'      => $current_page,
				'prev_next'    => false,
				'type'         => 'list',
			];
		} else {
			$track_no = 0;
			$last_no = $playlist['count'];
		}	
			
		$track = $playlist['tracks'][$track_no];
		$thumbnail = $track['thumbnail'];
	
?>
<div id="bg_rutube_playlistContainer<?php echo esc_attr($uuid); ?>" class="bg_rutube_playlistContainer" data-uuid="<?php echo esc_attr($uuid); ?>" data-movie="<?php echo esc_attr($playlist['tracks'][$track_no]['uuid']); ?>" data-mode="<?php echo esc_attr($startmode); ?>" data-start="<?php echo esc_attr($startTime); ?>">
	<div class="bg_rutube_centerVideo">
		<div id="bg_rutube_videoContainer<?php echo esc_attr($uuid); ?>" class="bg_rutube_videoContainer");"></div>
		<?php if ($startmode == 'preview') { ?>	
		<div id="bg_rutube_thumbnail<?php echo esc_attr($uuid); ?>" class="bg_rutube_videoContainer" style="background-image: url('<?php echo esc_url($track['thumbnail']); ?>');">
			<div class="bg_rutube_buttonPlay"></div>
		</div>
		<?php } ?>
	</div>
	
<?php if ($playlist['count'] > 1) : ?>
	
	<table id="bg_rutube_nav<?php echo esc_attr($uuid); ?>" class="bg_rutube_nav_movies">
		<tr>
			<td id="bg_rutube_prev_movie<?php echo esc_attr($uuid); ?>" class="bg_rutube_navLeft">
				<span class="bg_rutube_navButton">&#9204; <?php _e( 'Previous', 'bg-rutube-embed'); ?><span>
			</td>
			<td id="bg_rutube_next_movie<?php echo esc_attr($uuid); ?>" class="bg_rutube_navRight">
				<span class="bg_rutube_navButton"><?php _e( 'Next', 'bg-rutube-embed'); ?> &#9205;</span>
			</td>
		</tr>
	</table>

	<div class="bg_rutube_videoPlayList">
	<table class="bg_rutube_videoPlayListTable">
	<?php 
	for (; $track_no < $last_no && $track_no < $playlist['count']; $track_no++) :
		$track = $playlist['tracks'][$track_no];
	?>
	
		<tr class="bg_rutube_showRuTubeVideoLink<?php echo esc_attr($uuid); ?>" title="<?php _e( 'Play', 'bg-rutube-embed'); ?>: <?php echo esc_html($track['title']);?>" data-movie="<?php echo esc_attr($track['uuid']); ?>">
			<td style="background-image: url('<?php echo esc_url($track['thumbnail']); ?>');">
			</td>
			<td>
				<span class='bg_rutube_trackTtle'><?php echo esc_html($track['title']); ?></span> 
			</td>
			<td align="right">
				<span class='bg_rutube_trackLength'><?php echo esc_html(bg_rutube_videolist_sectotime ($track['length'])); ?></span> 
			</td>
		</tr>
		
	<?php endfor; ?>
	</table>
	<?php if ($perpage > 0) echo '<div class="bg_rutube_paginate">'.  paginate_links( $args ) .'</div>'; ?>
	</div>
<?php endif; ?>
	
</div>
<?php
		
	} else {
?>
	<table class="bg_rutube_videoPlayListInfo">
		<tr class="showRuTubeVideoInfo" title="<?php echo esc_html($playlist['description']); ?>">
			<td style="background-image: url('<?php echo esc_url($playlist['thumbnail']) ;?>');">
			</td>
			<td>
				<span class='playlist_title'><?php echo esc_html($playlist['title']); ?></span>
			<?php if ($playlist['count']) { ?>
					<span class='playlist_count'> (<?php echo esc_html($playlist['count']); ?>)</span> 
			<?php } ?>
			</td>
		</tr>
	</table>
<?php			
	}

	return ob_get_clean();
}

/*****************************************************************************************

	Переводит секунды в часы, минуты, секунды

******************************************************************************************/
function bg_rutube_videolist_sectotime ($seconds) {
	$seconds = (int)$seconds;
	if ($seconds < 0) return "";
	$minutes = floor($seconds / 60);		// Считаем минуты
	$hours = floor($minutes / 60); 			// Считаем количество полных часов
	$minutes = $minutes - ($hours * 60);	// Считаем количество оставшихся минут
	$seconds = $seconds - ($minutes + ($hours * 60))*60;// Считаем количество оставшихся секунд
	return  ($hours?($hours.":"):"").sprintf("%02d:%02d", $minutes, $seconds);
}


