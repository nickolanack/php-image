# php-image
Simple image utilities for php

##Examples



```php

	//thumbnail and convert to jpg

	(new \nblackwe\Image())
		->fromFile('/some/image.png')
		->thumbnailFit(128)
		->toFile('/thumbnails/some_image_thumb.jpg')
		->close();


```
