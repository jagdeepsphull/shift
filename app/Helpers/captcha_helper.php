<?php

/**
 * Captcha helper.
 *
 * CodeIgniter 4 dropped the CI3 captcha helper, so the one function this
 * application used - `create_captcha()` - is reimplemented here with the same
 * options and return shape:
 *
 *     ['word' => ..., 'time' => ..., 'image' => '<img ... />', 'filename' => ...]
 */

if (! function_exists('create_captcha')) {
    /**
     * @param array $data see the CI3 documentation for the accepted keys
     *
     * @return array|false
     */
    function create_captcha($data = [], $img_path = '', $img_url = '', $font_path = '')
    {
        $defaults = [
            'word'        => '',
            'img_path'    => '',
            'img_url'     => '',
            'img_width'   => 150,
            'img_height'  => 30,
            'font_path'   => '',
            'font_size'   => 16,
            'expiration'  => 7200,
            'word_length' => 8,
            'img_id'      => '',
            'pool'        => '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'colors'      => [
                'background' => [255, 255, 255],
                'border'     => [153, 102, 102],
                'text'       => [204, 153, 153],
                'grid'       => [255, 182, 182],
            ],
        ];

        if (! is_array($data)) {
            $data = [
                'word'      => (string) $data,
                'img_path'  => $img_path,
                'img_url'   => $img_url,
                'font_path' => $font_path,
            ];
        }

        foreach ($defaults as $key => $value) {
            ${$key} = ($data[$key] ?? '') !== '' ? $data[$key] : $value;
        }

        if ($img_path === '' || $img_url === '' || ! is_dir($img_path) || ! is_really_writable($img_path)) {
            return false;
        }

        if (! extension_loaded('gd')) {
            return false;
        }

        // Remove old captcha images.
        $now = time();

        foreach (glob($img_path . '*.jpg') ?: [] as $filename) {
            if ((@filemtime($filename) + $expiration) < $now) {
                @unlink($filename);
            }
        }

        // Build the word.
        if ($word === '') {
            $poolLength = strlen($pool);
            $word       = '';

            for ($i = 0; $i < $word_length; $i++) {
                $word .= $pool[random_int(0, $poolLength - 1)];
            }
        }

        $im = imagecreatetruecolor($img_width, $img_height);

        $colors = [];

        foreach (['background', 'border', 'text', 'grid'] as $name) {
            $rgb           = $data['colors'][$name] ?? $defaults['colors'][$name];
            $colors[$name] = imagecolorallocate($im, (int) $rgb[0], (int) $rgb[1], (int) $rgb[2]);
        }

        imagefilledrectangle($im, 0, 0, $img_width, $img_height, $colors['background']);

        // Grid
        $theta = 1;

        for ($i = -$img_width; $i < $img_width; $i += 10) {
            imageline($im, $i, 0, $i + $img_height * $theta, $img_height, $colors['grid']);
        }

        // Text
        $useTtf = $font_path !== '' && is_file($font_path) && function_exists('imagettftext');

        if ($useTtf) {
            $bbox   = imagettfbbox($font_size, 0, $font_path, $word);
            $textW  = $bbox[2] - $bbox[0];
            $textH  = $bbox[1] - $bbox[7];
            $x      = (int) max(2, ($img_width - $textW) / 2);
            $y      = (int) (($img_height + $textH) / 2);

            imagettftext($im, $font_size, 0, $x, $y, $colors['text'], $font_path, $word);
        } else {
            $x = (int) max(2, ($img_width - (imagefontwidth(5) * strlen($word))) / 2);
            $y = (int) max(2, ($img_height - imagefontheight(5)) / 2);

            imagestring($im, 5, $x, $y, $word, $colors['text']);
        }

        imagerectangle($im, 0, 0, $img_width - 1, $img_height - 1, $colors['border']);

        $filename = $now . '.jpg';

        imagejpeg($im, $img_path . $filename);

        $img = '<img ' . ($img_id === '' ? '' : 'id="' . $img_id . '" ')
            . 'src="' . rtrim($img_url, '/') . '/' . $filename . '" style="width: ' . $img_width
            . 'px; height: ' . $img_height . 'px; border: 0;" alt="Captcha" />';

        imagedestroy($im);

        return [
            'word'     => $word,
            'time'     => $now,
            'image'    => $img,
            'filename' => $filename,
        ];
    }
}
