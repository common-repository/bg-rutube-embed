jQuery(document).ready(function(){
	
	// Если на странице нет Видео, не выводить
	if (!jQuery('.bg_rutube_playlistContainer')[0]) {
		return false;
	}
	var rutube_url = 'https://rutube.ru/video/embed/';

	jQuery('.bg_rutube_playlistContainer').each(function() {
		var uuid = jQuery(this).attr('data-uuid');
		var movieID = jQuery(this).attr('data-movie');
		var mode = jQuery(this).attr('data-mode');
		var startTime = jQuery(this).attr('data-start');
		if (startTime) movieID += '/?t='+startTime;
		jQuery('div#bg_rutube_videoContainer'+uuid).append('<iframe id="bg_rutube_player'+uuid+'" class="bg_rutube-video" webkitAllowFullScreen mozallowfullscreen allowfullscreen frameborder="0" allow="autoplay" loading="lazy"></iframe>');
		var iframe = jQuery('iframe#bg_rutube_player'+uuid);

		switch (mode) {
		  case 'load':
		// Вставляем первое видео во фрейм 
			iframe.attr('src',rutube_url+movieID);
			
			break;
		  case 'play':
		// Вставляем первое видео во фрейм и воспоизводим его
			iframe.attr('src',rutube_url+movieID);
			bg_rutube_play(uuid);
			break;
		  case 'preview':
		  default:
		// Вставляем первое видео во фрейм после клика на заставку и воспоизводим его
			jQuery('div#bg_rutube_thumbnail'+uuid).on('mousedown, mouseup, click', function(e){
				e.preventDefault();
				iframe.attr('src',rutube_url+movieID);
				bg_rutube_play(uuid);
				jQuery(this).slideUp();
			});
		}

	// Если в списке больше одного видео, показываем список
		if (jQuery('.bg_rutube_showRuTubeVideoLink'+uuid).length > 1) {
		// Следующий фильм
			jQuery('#bg_rutube_next_movie'+uuid).click(function(){
//				var movieID = jQuery('iframe#bg_rutube_player'+uuid).attr('src').replace(rutube_url,'');
				var movieID = jQuery('div#bg_rutube_playlistContainer'+uuid).attr('data-movie');
				var el = bg_rutube_findMovie(movieID, uuid);
				if (el === false) return false;
				el = el.next('.bg_rutube_showRuTubeVideoLink'+uuid);
				if (!el.length) el = jQuery('.bg_rutube_showRuTubeVideoLink'+uuid).first();
				bg_rutube_showMovie(el, uuid);
				return false;
			});
		// Предыдущий фильм
			jQuery('#bg_rutube_prev_movie'+uuid).click(function(){
//				var movieID = jQuery('iframe#bg_rutube_player'+uuid).attr('src').replace(rutube_url,'');
				var movieID = jQuery('div#bg_rutube_playlistContainer'+uuid).attr('data-movie');
				var el = bg_rutube_findMovie(movieID, uuid);
				if (el === false) return false;
				el = el.prev('.bg_rutube_showRuTubeVideoLink'+uuid);
				if (!el.length) el = jQuery('.bg_rutube_showRuTubeVideoLink'+uuid).last();
				bg_rutube_showMovie(el, uuid);
				return false;
			});
		// Выбор видео из плейлиста
			jQuery('.bg_rutube_showRuTubeVideoLink'+uuid).click(function(){
				bg_rutube_showMovie(jQuery(this), uuid);
				return false;
			});
		}

	// Найти элемент по индексу фильма
		function bg_rutube_findMovie(id, uuid) {
			var el = false;
			jQuery('.bg_rutube_showRuTubeVideoLink'+uuid).each(function() {
				this_id = jQuery(this).first().attr('data-movie');
				if (this_id == id) {
					el = jQuery(this);
					return false;
				}
			});
			return el;
		}
	// Показать фильм из списка
		function bg_rutube_showMovie(el, uuid){
			movieID = el.first().attr('data-movie');
			jQuery('iframe#bg_rutube_player'+uuid).attr('src',rutube_url+movieID);
			jQuery('div#bg_rutube_playlistContainer'+uuid).attr('data-movie',movieID);
		// Перемещаемся вверх к фрейму. Фрейм по центру экрана
			var margin = (jQuery(window).height() - jQuery('iframe#bg_rutube_player'+uuid).height())/2;
			var scrollTop = jQuery('#bg_rutube_playlistContainer'+uuid).offset().top - margin;
			if (scrollTop < 0) scrollTop = 0;
			jQuery( 'html, body' ).animate( {scrollTop : scrollTop}, 800 );
			jQuery('div#bg_rutube_thumbnail'+uuid).slideUp();
			bg_rutube_play(uuid);
		}
		
	// Воспроизвести видео
		function bg_rutube_play(uuid){
			setTimeout(function() {
				var player = document.getElementById('bg_rutube_player'+uuid);
				if (player) {
					player.contentWindow.postMessage(JSON.stringify({
						type: 'player:play',
						data: {}
					}), '*');
				}
			}, 800);
		}
	});
});
