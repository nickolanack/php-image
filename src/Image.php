<?php

namespace nblackwe;

class Image {

	private $resource;

	public function fromFile($path) {
		$p_ex = explode('.', $path);
		$p_po = array_pop($p_ex);

		$ext = strtolower($p_po);

		if (!file_exists($path)) {
			throw new Exception("Image: File not found: " . $path);
		}

		switch ($ext) {
		case 'jpeg':
		case 'jpg':
			$this->resource = imagecreatefromjpeg($path);
			break;
		case 'png':
			$this->resource = imagecreatefrompng($path);
			break;
		case 'gif':
			$this->resource = imagecreatefromgif($path);
			break;
		case 'bmp':

			if(function_exists('imagecreatefrombmp')){
				//(Exists in php 7.2+)
				$this->resource = imagecreatefrombmp($path);
				break;
			}

			$this->resource = $this->createFromBmp($path);
			break;

		default:

			throw new Exception('Image: Invalid Image Type, not one of [jpeg, jpg, png, gif, bmp]: ' . $path);
		}

		return $this;

	}

	public function fromResource($resource) {

		if(!is_resource($resource)){
			throw new \Exception('Expected resource');
		}

		$this->resource=$resource;
		return $this;
	}

	private function createFromBmp($filename) {

		/*
				* this needs to be rewritten
				*/

		$file = fopen($filename, "rb");
		$read = fread($file, 10);
		while (!feof($file) && $read != "") {
			$read .= fread($file, 1024);
		}
		$temp = unpack("H*", $read);
		$hex = $temp[1];
		$header = substr($hex, 0, 104);
		$body = str_split(substr($hex, 108), 6);
		if (substr($header, 0, 4) == "424d") {
			$header = substr($header, 4);
			// Remove some stuff?
			$header = substr($header, 32);
			// Get the width
			$width = hexdec(substr($header, 0, 2));
			// Remove some stuff?
			$header = substr($header, 8);
			// Get the height
			$height = hexdec(substr($header, 0, 2));
			unset($header);
		}
		$x = 0;
		$y = 1;
		$image = imagecreatetruecolor($width, $height);
		foreach ($body as $rgb) {
			$r = hexdec(substr($rgb, 4, 2));
			$g = hexdec(substr($rgb, 2, 2));
			$b = hexdec(substr($rgb, 0, 2));
			$color = imagecolorallocate($image, $r, $g, $b);
			imagesetpixel($image, $x, $height - $y, $color);
			$x++;
			if ($x >= $width) {
				$x = 0;
				$y++;
			}
		}
		return $image;

	}

	public function fromString($str) {

		//error_log(ini_get('memory_limit').' '.strlen($str).' '.memory_get_usage());
		//error_log(($d=debug_backtrace())[0]['file'].':'.$d[0]['line']);
		$mult = array(
			"M" => 1024 * 1024,
			"G" => 1024 * 1024 * 1024,
		);
		$memStr = ini_get('memory_limit');
		$memInt = intval($memStr);
		$m = str_replace($memInt . "", "", $memStr);
		//error_log($m);
		$haveBytes = $memInt * $mult[$m];

		$needBytes = (strlen($str) * 40) + memory_get_usage();

		if ($needBytes > $haveBytes) {
			$mem = ceil($needBytes / ($mult["M"])) . "M";

			ini_set('memory_limit', $mem);
			//error_log('set: memory_limit '.$mem.' strlen'.strlen($str));

		}

		$this->resource= imagecreatefromstring($str);
		return $this;
	}

	private  function parseRgb($rgb) {
		return $rgb;
	}

	/**
	 *
	 * @param array $rgb
	 *            array(0,0,0) = black...
	 * @param number $threshhold
	 * @return boolean if every pixel is aproximately equal to $rgb this method scales the image so
	 *         that pixels may be blended before compared
	 *
	 */
	public  function isAllColor($rgb, $threshhold = 0) {

		$image=$this->resource;

		$simplified = Image::ThumbnailFit($image, 10);
		// imagetruecolortopalette($simplified, false, 5);
		$s = Image::GetSize($simplified);

		$rgb = $this->parseRgb($rgb);

		for ($x = 0; $x < $s['w']; $x++) {
			for ($y = 0; $y < $s['h']; $y++) {
				$rgb = imagecolorat($simplified, $x, $y);
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;

				if (abs($r - $rgb[0]) > $threshhold) {
					return false;
				}

				if (abs($g - $rgb[1]) > $threshhold) {
					return false;
				}

				if (abs($b - $rgb[2]) > $threshhold) {
					return false;
				}

			}
		}
		return true;
	}

	public function isAllOneColor( $threshhold = 0) {

		$image=$this->resource;
		$simplified = Image::ThumbnailFit($image, 10);
		// imagetruecolortopalette($simplified, false, 5);
		$s = Image::GetSize($simplified);
		for ($x = 0; $x < $s['w']; $x++) {
			for ($y = 0; $y < $s['h']; $y++) {

				$rgb = imagecolorat($simplified, $x, $y);
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;
				if (!$rgb) {
					$rgb = array(
						$r,
						$g,
						$b,
					);
				}

				if (abs($r - $rgb[0]) > $threshhold) {
					return false;
				}

				if (abs($g - $rgb[1]) > $threshhold) {
					return false;
				}

				if (abs($b - $rgb[2]) > $threshhold) {
					return false;
				}

			}
		}
		return true;
	}

	public function colorProfile($image) {

		$image=$this->resource;
		$simplified = Image::ThumbnailFit($image, 10);
		// imagetruecolortopalette($simplified, false, 5);
		$s = Image::GetSize($simplified);
		$values = array();
		for ($x = 0; $x < $s['w']; $x++) {
			for ($y = 0; $y < $s['h']; $y++) {
				$rgb = imagecolorat($simplified, $x, $y);
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;
				$values[] = 'rgb(' . $r . ', ' . $g . ', ' . $b . ')';
			}
		}

		/**
		 * TODO: return color information about the image, metadata, with main colors?
		 */
		return array(
			'colors' => $values,
		);
	}


	public function detectBoundary(){
		return array();
	}


	public static function filterGrayScale() {
		$image=$this->resource;
		imagefilter($image, IMG_FILTER_GRAYSCALE);
	}

	public  function filterBrightness($amount) {
		$image=$this->resource;
		imagefilter($image, IMG_FILTER_BRIGHTNESS, $amount);
	}

	/**
	 * TODO: similar to ThumbnailFit, but will crop to size maintaining aspect ratio
	 */
	public  function ThumbnailFill($x, $y = false, $scale = true) {
		throw new Exception('Image: Not implemented: (ThumbnailFill)');
	}

	/**
	 * scales an image, given a image resource $image so that it fits entirely within $x, $y (width, height) and
	 * maintains aspect ratio

	 * @param int $x
	 *            width
	 * @param int $y
	 *            height (or null for $x=$y)
	 * @param boolean $scale
	 *            ignore this arg
	 * @return resource a new image resource. call Image::Close($oldResource) if done with the previous
	 */
	private function thumbnailFitRes($x, $y = false, $scale = true) {

		$image=$this->resource;

		if (!$y) {
			$y = $x;
		}

		$width = imagesx($image);
		$height = imagesy($image);

		$outW = $width;
		$outY = $height;

		if ($scale) {

			if ($x < $outW) {
				$outY = $height * ($x / $width);
				$outW = $x;
			}
			if ($y < $outY) {
				$outW = $width * ($y / $height);
				$outY = $y;
			}
		} else {
			$outW = $x;
			$outY = $y;
		}

		$out = imagecreatetruecolor($outW, $outY);
		imagefill($out, 0, 0, imagecolortransparent($out, imagecolorallocate($out, 0, 0, 0)));
		imagesavealpha($out, true);
		imagealphablending($out, false);
		imagecopyresampled($out, $image, 0, 0, 0, 0, $outW, $outY, $width, $height);

		return $out;
	}

	public function thumbnailFit($x, $y = false, $scale = true) {

		$out=$this->thumbnailFitRes($x, $y, $scale);
		$this->close();
		$this->resource= $out;
		return $this;
	}

	public function thumbnailFitCopy($x, $y = false, $scale = true) {
		return (new \nblackwe\Image())->fromResource($this->thumbnailFitRes($x, $y, $scale));
	}

	public function close() {
	
		imagedestroy($this->resource);
		$this->resource=null;

	}

	/**
	 *
	 * @param unknown $image
	 * @return array with (w,h) keys
	 */
	public function getSize() {
		$image=$this->resource;
		$x = imagesx($image);
		$y = imagesy($image);

		return array(
			'w' => $x,
			'h' => $y,
		);
	}

	/**
	 * overlays two images ($img1 on top of $img2 at [$xOffset, $yOffset])
	 *
	 *
	 * TODO: make use of this method. and test.
	 *
	 * @param resource $img1
	 *            a GD image resource
	 * @param resource $img2
	 *            a GD image resource
	 * @param int $xOffset
	 * @param int $yOffset
	 */
	public function overlay($image, $xOffset, $yOffset = false) {

		$img1=$image;
		$img2=$this->resource;
		if ($yOffset === false) {
			$yOffset = $xOffset;
		}

		imagealphablending($img1, true);
		imagealphablending($img2, true);
		imagecopy($img2, $img1, $xOffset, $yOffset, 0, 0, imagesx($img2), imagesy($img2));
		
		return $this;
	}


	public function getResource(){
		return $this->resource;
	}

	/**
	 * writes image resource to file.
	 *
	 * @param resource $image
	 * @param string $path
	 *            file type will be detected from file extension
	 * @return boolean true on success.
	 */
	public function toFile($path) {

		$image=$this->resource;
		$ext_ex = explode('.', $path);
		$ext = strtolower(array_pop($ext_ex));
		if (in_array($ext, array(
			'jpg',
			'jpeg',
			'png',
			'gif',
		))) {
			switch ($ext) {
			case 'jpeg':
			case 'jpg':
				imagejpeg($image, $path);
				break;
			case 'png':
				imagepng($image, $path);
				break;
			case 'gif':
				imagegif($image, $path);
				break;
			}
		}
		return $this;
	}

	/**
	 * replaces all colors in an image with $rgb, conserving alpha
	 *
	 * @param resource $image
	 *            image resource eg: Image::Open
	 * @param array $rgb
	 *            [int:red, int:green, int:blue] tint color
	 * @return resource a new resource for the tinted image
	 */
	public function tint($rgb) {

		$image=$this->resource;
		$s = $this->getSize();

		$tinted = imagecreatetruecolor($s['w'], $s['h']);
		$color = imagecolorallocatealpha($tinted, $rgb[0], $rgb[1], $rgb[2], 127);

		imagefill($tinted, 0, 0, $color);

		for ($x = 0; $x < $s['w']; $x++) {
			for ($y = 0; $y < $s['h']; $y++) {

				$a = imagecolorsforindex($image, imagecolorat($image, $x, $y));
				$t = imagecolorsforindex($tinted, imagecolorat($tinted, $x, $y));

				imagesetpixel($tinted, $x, $y,
					imagecolorallocatealpha($tinted, $t['red'], $t['green'], $t['blue'], $a['alpha']));
			}
		}

		// neccessary for transparency
		imagealphablending($tinted, true);
		imagesavealpha($tinted, true);

		$this->close();
		$this->resource=$tinted;

		return $this;
	}

	/**
	 * replaces all colors in an image with an $rgb that slightly transitions, conserving alpha
	 *
	 * @param resource $image
	 *            image resource eg: Image::Open
	 * @param array $rgb
	 *            [int:red, int:green, int:blue] tint color
	 * @return resource a new resource for the tinted image
	 */
	public static function tintFade($rgb) {
		$image=$this->resource;
		$s = $this->getSize();

		$tinted = imagecreatetruecolor($s['w'], $s['h']);

		$span = 0.3;
		$end = 1.15;

		// adjust fade start end colors to within 255 limit
		foreach ($rgb as $c) {
			if ($c * $end > 255) {
				$end = 255.0 / c;
			}
		}

		$start = $end - $span;
		$step = $span / $s['h'];
		// header('Content-Type: text/html;');
		imagealphablending($tinted, false);
		for ($y = 0; $y < $s['h']; $y++) {

			$color = imagecolorallocatealpha($tinted, (int) $rgb[0] * ($start + ($step * $y)),
				(int) $rgb[1] * ($start + ($step * $y)), (int) $rgb[2] * ($start + ($step * $y)), 127);
			if (!imageline($tinted, 0, $y, $s['w'], $y, $color)) {
				// echo 'failed';
			}

			// print_r('(0, '.$y.', '.$s['w'].', '.($y+1).') '.$rgb[0]*($start+($step*$y)).' - '.$rgb[1]*($start+($step*$y)).' - '.$rgb[2]*($start+($step*$y))."<br/>");
		}
		;

		// die();
		for ($x = 0; $x < $s['w']; $x++) {
			for ($y = 0; $y < $s['h']; $y++) {

				$a = imagecolorsforindex($image, imagecolorat($image, $x, $y));
				$t = imagecolorsforindex($tinted, imagecolorat($tinted, $x, $y));

				imagesetpixel($tinted, $x, $y,
					imagecolorallocatealpha($tinted, $t['red'], $t['green'], $t['blue'], $a['alpha']));
			}
		}

		// neccessary for transparency
		// imagealphablending($tinted, true);
		imagesavealpha($tinted, true);

		$this->close();
		$this->resource=$tinted;

		return $this;
	}
}
