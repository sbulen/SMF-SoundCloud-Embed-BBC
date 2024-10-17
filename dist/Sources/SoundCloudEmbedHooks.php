<?php
/**
 *	Logic for the SoundCloud Embed Widget mod hooks.
 *
 *	Copyright 2022 Shawn Bulen
 *
 *	The SoundCloud Embed Widget mod is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation, either version 3 of the License, or
 *	(at your option) any later version.
 *	
 *	This software is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with this software.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

// If we are outside SMF throw an error.
if (!defined('SMF')) {
    die('Hacking attempt...');
}

/**
 *
 * Hook function - Add the bbc codes.
 *
 * SoundCloud links look like: https://soundcloud.com/artist/track
 * They may have tracking params, e.g., https://soundcloud.com/artist/track?utm_source=clipboard&utm_campaign=social_sharing
 * Tracking params are ignored by this mod.
 *
 * Hook: integrate_bbc_codes
 *
 * @param array $bbc_codes
 * @return null
 *
 */
function soundcloud_embed_bbc_codes(&$bbc_codes)
{
	if (!function_exists('soundcloud_bbc_validate'))
	{
		function soundcloud_bbc_validate(&$tag, &$data)
		{
			if (empty($data))
			{
				$tag['content'] = '';
				return;
			}

			// Need to restore everything if match fails
			$old_data = $data;

			// Trim it...  Even the br tags that may have been added along the way...
			$data = trim($data);
			$data = str_replace('<br>', '', $data);

			// Clean up scheme; always go https
			if (stripos($data, 'http://') === 0)
				$data = 'https://' . substr($data, 7);
			if (stripos($data, 'https://') === false)
				$data = 'https://' . $data;

			// 0 = all; 1 = artist; 2 = type; 3 = track/list
			// Note this discards content after the '?' if there is any
			$pattern = '~^https://soundcloud\.com/([^/]+)(/sets)?/([^?]+)~i';
			if (preg_match($pattern, $data, $parts) == false)
			{
				$tag['content'] = $old_data;
				return;
			}
			$whole_url = $parts[0];
			$artist = $parts[1];
			$height = empty($parts[2]) ? '130' : '350';
			$track = $parts[3];

			$tag['content'] = '<iframe width="100%" height="'. $height . '" scrolling="no" frameborder="no" src="https://w.soundcloud.com/player/?url=' .
				$whole_url .
				'&color=%237c6c64&auto_play=false&sharing=false&download=false&show_playcount=false&show_artwork=true"></iframe>' .
				'<div style="font-size:10px;color:#cccccc;line-break:anywhere;word-break:normal;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;font-family:Interstate,Lucida Grande,Lucida Sans Unicode,Lucida Sans,Garuda,Verdana,Tahoma,sans-serif;font-weight:100;">' .
				'<a href="https://soundcloud.com/' . $artist . '" title="' . $artist . '" target="_blank" style="color:#cccccc;text-decoration:none;">' . $artist . '</a> · ' .
				'<a href="' . $whole_url . '" title="' . $track . '" target="_blank" style="color:#cccccc;text-decoration:none;">' . $track . '</a></div>';
		}
	}

	$bbc_codes[] = array(
		'tag' => 'soundcloud',
		'type' => 'unparsed_content',
		'validate' => 'soundcloud_bbc_validate',
		'block_level' => true
	);
	$bbc_codes[] = array(
		'tag' => 'cloudset',
		'type' => 'unparsed_content',
		'validate' => 'soundcloud_bbc_validate',
		'block_level' => true
	);
}

/**
 *
 * Hook function - Adds SoundCloud Embed button to the editor.
 * If you can find the YouTube button, add it after that.
 * Otherwise, just add it to the end.
 *
 * Hook: integrate_bbc_buttons
 *
 * @param array $bbc_buttons
 * @return null
 *
 */
function soundcloud_embed_bbc_buttons(&$bbc_buttons)
{
	$new_button = array(
		'image' => 'soundcloud',
		'code' => 'soundcloud',
		'description' => 'SoundCloud',
		'before' => '[soundcloud]',
		'after' => '[/soundcloud]'
	);

	// Find YouTube...
	foreach ($bbc_buttons AS $group => $buttons)
	{
		foreach ($buttons AS $ix => $button)
		{
			if (isset($button['code']) && ($button['code'] === 'youtube'))
			{
				$youtube_loc = $ix + 1;
				break 2;
			}
		}
	}

	// Found it, so insert it there...
	if (isset($youtube_loc))
		$bbc_buttons[$group] = array_merge(array_slice($bbc_buttons[$group], 0, $youtube_loc), array($new_button), array_slice($bbc_buttons[$group], $youtube_loc));
	else
		// Didn't find it so just plunk it at the end...
		$bbc_buttons[count($bbc_buttons) - 1][] = $new_button;
}