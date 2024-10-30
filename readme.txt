=== Bg RuTube Embed ===

Contributors: VBog
Donate link: http://bogaiskov.ru/about-me/donate/
Tags: video, playlist, channel, rutube, videohosting
Requires PHP: 5.3
Requires at least: 3.0.1
Tested up to: 6.1.1
Stable tag: 1.6.3
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html


The plugin is the easiest way to embed RuTube videos in WordPress.

== Description ==

Plugin allowed to embed [RuTube](https://rutube.ru/) videos in WordPress.  Just specify the **uuid** of the playlist or video in the shortcode. You can also specify a list of **uuid** separated by commas.

`[rutube id="{uuid}" title="" description="" sort="" perpage="" limit="" mode="" ]`

*	`id` - **uuid** of the video or playlist, or list of videos **uuid** separated by commas;
*	`title` - playlist title (for list of **uuid** only);
*	`description` - playlist description (for list of **uuid** only);
*	`sort="on"`- sort playlist by ABC (default: `sort=""` - don't sort);
*	`perpage` - number of items per page (default: *empty* - use plugin's settings);
*	`limit` - playlist size limit (default: *empty* - use plugin's settings);
*	`mode` - mode of start video on page load:
*	 `"preview"` - preview image,
*	 `"load"` - load video into the frame,
*	 `"play"` - load video into the frame and play,
*	 `''` (*empty*) - use plugin's settings (by default);
*	`start` - time in seconds when the video starts.


To embed a single video or playlist, just enter its URL (https://rutube.ru/video/{**uuid**}/ or https://rutube.ru/plst/{**uuid**}/) оn a separate line.

Optional parameter \?t={**time**} in the video URL is time in seconds when the video starts.

You can choose mode of start video on page load in settings and shortcode.

То paginate playlist сhoose number of items per page in the settings or shortcode. 0 (zero) - don't paginate (by default).

If the playlist size is very large, you can limit it with "Max playlist size" option in the settings or shortcode. 0 (zero) - not limited (by default).

== Installation ==

1. Upload 'bg-rutube' directory to the '/wp-content/plugins/' directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

== Frequently Asked Questions ==

Ask me. I'll answer. :)

== Screenshots ==

1. Playlist embedded on the post page.
2. Info about the video on the archive/tag/category page (Enabled "Video only on post pages" option).
3. Plugin settings screen.


== Changelog ==

= 1.6.3 =

* New shortcode parameter `start` - time in seconds when the video starts.

= 1.6.2 =

* New plugin option and shortcode parameter: mode of start video on page load.

= 1.6.1 =

* Fixed SVN loading bug.

= 1.6 =

* New plugin option and shortcode parameter: playlist size limit.

= 1.5.1 =

* Performance has been improved.

= 1.5 =

* Pagination video in playlists.

= 1.4.4 =

* Fixed: don't work Next and Previous buttons on the start.

= 1.4.2-3 =

* Minor fixes.

= 1.4.1 =

* Fixed small bug.

= 1.4 =

* Only the thumbnail is loaded when the page is opened, and the video starts loading only after pressing the "Play" button.

= 1.3.1 =

* Some improvements. Tested for WP 6.0

= 1.3 =

* You can just the URL on its own line to embed playlist.

= 1.2.1 =

* Fixed small bugs.

= 1.2 =

* Added the ability to localize the plugin.
* You can just the URL on its own line to embed single video.
* Fixed some bugs and mistakes.

= 1.1 =

* Added the ability to embed a RuTube playlist or create a playlist from several videos.

= 1.0 =

* Starting version

== License ==

GNU General Public License v2

