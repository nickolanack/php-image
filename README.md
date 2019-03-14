# php-image
Simple image utilities for php

##Examples



```php

	// thumbnail and convert to jpg
	//
	// thumbnailFit ensures that the scaled image fits 
	// inside a rectangle (in this case a square 128x128)

	(new \nblackwe\Image())
		->fromFile('/some/image.png')
		->thumbnailFit(128/*, optional $clampY*/)
		->toFile('/thumbnails/some_image_thumb.jpg')
		->close();	//release resources 


	// thumbnail and convert to jpg
	//
	// thumbnailFill ensures that the scaled image is just 
	// big enough to contain a rectangle (in this case a 
	// square 128x128)

	(new \nblackwe\Image())
		->fromFile('/some/image.png')
		->thumbnailFill(128/*, optional $clampY*/)
		->toFile('/thumbnails/some_image_thumb.jpg')
		->close();	//release resources 

```
