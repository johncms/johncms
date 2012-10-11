<?php

/**
* @package     JohnCMS
* @link        http://johncms.com
* @copyright   Copyright (C) 2008-2011 JohnCMS Community
* @license     LICENSE.txt (see attached file)
* @version     VERSION.txt (see attached file)
* @author      http://johncms.com/about
*/
$radius = 4;

$file = isset($_GET['file']) ? htmlspecialchars(urldecode($_GET['file'])) : NULL;
if ($file && file_exists('../files/forum/attach/' . $file)) {
    list($width, $height, $type) = getimagesize('../files/forum/attach/' . $file);
	switch ($type) {
		case 1: $att_ext = 'gif' ;break;
		case 2: $att_ext = 'jpeg'; break;
		case 3: $att_ext = 'png'; break;
		case 4: $att_ext = 'jpg'; break;
	}
    if ($att_ext) {
		$razm = 50;
		$x_ratio = $razm / $width;
		$y_ratio = $razm / $height;
		if (($width <= $razm) && ($height <= $razm)) {
			$tn_width = $width;
			$tn_height = $height;
		} else if (($x_ratio * $height) < $razm) {
			$tn_height = ceil($x_ratio * $height);
			$tn_width = $razm;
		} else {
			$tn_width = ceil($y_ratio * $width);
			$tn_height = $razm;
		}
			
		$function = 'imageCreateFrom' . $att_ext;
		$image = $function('../files/forum/attach/' . $file);
		if($att_ext == 'gif') {
			$tmp = imagecreate($tn_width, $tn_height);
			$color = imagecolorallocate($tmp, 0, 0, 0); 
		}
		else
			$tmp = imageCreateTrueColor($tn_width, $tn_height);
		if($att_ext == 'png') {
			imageAlphaBlending($tmp, false);
			imageSaveAlpha($tmp, true);
		} else if($att_ext == 'gif') {
			imagecolortransparent($tmp, $color);
		}
		imageCopyResampled($tmp, $image, 0, 0, 0, 0, $tn_width, $tn_height, $width, $height);
			
		if(($att_ext == 'jpg' || $att_ext == 'jpeg') && $radius > 1 && $radius <= 20)
		{
			$img = $tmp;
			$rate = 3;
			imagealphablending($img, false);
			imagesavealpha($img, true);

			$width = imagesx($img);
			$height = imagesy($img);

			$rs_radius = $radius * $rate;
			$rs_size = $rs_radius * 2;

			$corner = imagecreatetruecolor($rs_size, $rs_size);
			imagealphablending($corner, false);

			$trans = imagecolorallocatealpha($corner, 255, 255, 255, 127);
			imagefill($corner, 0, 0, $trans);

			$positions = array(
			array(0, 0, 0, 0),
				array($rs_radius, 0, $width - $radius, 0),
				array($rs_radius, $rs_radius, $width - $radius, $height - $radius),
				array(0, $rs_radius, 0, $height - $radius),
			);

			foreach ($positions as $pos) {
				imageCopyResampled($corner, $img, $pos[0], $pos[1], $pos[2], $pos[3], $rs_radius, $rs_radius, $radius, $radius);
			}

			$lx = $ly = 0;
			$i = -$rs_radius;
			$y2 = -$i;
			$r_2 = $rs_radius * $rs_radius;

			for (; $i <= $y2; $i++) {
				$y = $i;
				$x = sqrt($r_2 - $y * $y);

				$y += $rs_radius;
				$x += $rs_radius;

				imageline($corner, $x, $y, $rs_size, $y, $trans);
				imageline($corner, 0, $y, $rs_size - $x, $y, $trans);

				$lx = $x;
				$ly = $y;
			}

			foreach ($positions as $i => $pos) {
				imageCopyResampled($img, $corner, $pos[2], $pos[3], $pos[0], $pos[1], $radius, $radius, $rs_radius, $rs_radius);
			}
			ob_start();
			header('Content-Type: image/png');
			imagepng($img);
			ob_end_flush();
		} else {
			ob_start();
			switch($att_ext) {
				case 'jpg':
				case 'jpeg':
					@imagejpeg($tmp, null, 100);
					break;
				case 'gif':
					@imagegif($tmp, null);
					break;
				case 'png':
					@imagepng($tmp, null, 9);
					break;
				default:
					// *** No extension - No save.
				break;
			}
			ImageDestroy($tmp);
			imageDestroy($image);
			Header("Content-Type: image/" . $att_ext);
			Header('Content-Disposition: inline; filename=thumbinal.' . $att_ext);
			Header('Content-Length: ' . ob_get_length());
			ob_end_flush();
		}
    }
}
?>