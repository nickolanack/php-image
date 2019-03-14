<?php

class ImageTest extends PHPUnit_Framework_TestCase {



	/**
     * @runInSeparateProcess
     */
    public function testEnvironment() {

    	$this->assertTrue(function_exists('gd_info'));

    	//$this->fail(print_r(gd_info(),true));
	}


    /**
     * @runInSeparateProcess
     */
    public function testReadImages() {



    	//$this->fail(print_r(getimagesize(__DIR__.'/[G]_[ImAgE]_WBq_ptd_45j.bmp'),true)); //this does get the right dimensions

    	include_once dirname(__DIR__).'/vendor/autoload.php';
    	
    	$this->assertEquals(array(
    		'w'=>128,
    		'h'=>62
    		), (new nblackwe\Image())->fromFile(__DIR__.'/roof.png')->getSize());

    }


    /**
     * @runInSeparateProcess
     */
    public function _testRotatedImages() {



        //$this->fail(print_r(getimagesize(__DIR__.'/[G]_[ImAgE]_WBq_ptd_45j.bmp'),true)); //this does get the right dimensions

        include_once dirname(__DIR__).'/vendor/autoload.php';
        
       
        (new nblackwe\Image())->fromFile(__DIR__.'/[G]_[ImAgE]_WBq_ptd_45j.bmp')->thumbnailFit(50)->toFile(__DIR__.'/[G]_[ImAgE]_WBq_ptd_45j.thumb.bmp');




    }
}