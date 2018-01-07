<?php

use Folklore\Image\ImageHandler;
use Folklore\Image\Filters\Rotate as RotateFilter;
use Folklore\Image\Filters\Resize as ResizeFilter;
use Imagine\Image\ImageInterface;

/**
 * @coversDefaultClass Folklore\Image\ImageHandler
 */
class ImageHandlerTest extends TestCase
{
    protected $handler;

    public function setUp()
    {
        parent::setUp();

        $this->handler = new ImageHandler(app('image'));
    }

    public function tearDown()
    {
        if (file_exists(public_path('image-test.jpg'))) {
            unlink(public_path('image-test.jpg'));
        }

        parent::tearDown();
    }

    /**
     * Test set and get source
     * @test
     * @covers ::setSource
     * @covers ::getSource
     */
    public function testGetSource()
    {
        $source = app('image.source')->driver('local');
        $this->handler->setSource($source);
        $this->assertEquals($source, $this->handler->getSource());
    }

    /**
     * Test open image
     * @test
     * @covers ::open
     */
    public function testOpen()
    {
        $this->handler->setSource(app('image.source')->driver('local'));

        $image = $this->handler->open('image.jpg');

        $this->assertInstanceOf(ImageInterface::class, $image);
        $this->assertEquals(public_path('image.jpg'), $image->metadata()->get('filepath'));
        $this->assertEquals(300, $image->getSize()->getWidth());
        $this->assertEquals(300, $image->getSize()->getHeight());
    }

    /**
     * Test getting the format of an image
     * @test
     * @covers ::format
     */
    public function testFormat()
    {
        $this->handler->setSource(app('image.source')->driver('local'));

        $format = $this->handler->format('image.jpg');
        $this->assertEquals('jpeg', $format);

        $format = $this->handler->format('image.png');
        $this->assertEquals('png', $format);
    }

    /**
     * Test making an image
     * @test
     * @covers ::make
     * @covers ::applyFilter
     */
    public function testMake()
    {
        $this->handler->setSource(app('image.source')->driver('local'));

        $returnImage = $this->handler->open('image.jpg');
        $returnImage = with(new ResizeFilter())->apply($returnImage, [
            'width' => 100,
            'height' => 90,
            'crop' => false
        ]);
        $returnImage = with(new RotateFilter())->apply($returnImage, 90);

        $rotateFilterMock = $this->getMockBuilder(RotateFilter::class)
            ->setMethods(['apply'])
            ->getMock();

        $rotateFilterMock->expects($this->once())
            ->method('apply')
            ->willReturn($returnImage);

        $resizeFilterMock = $this->getMockBuilder(ResizeFilter::class)
            ->setMethods(['apply'])
            ->getMock();

        $resizeFilterMock->expects($this->once())
            ->method('apply')
            ->willReturn($returnImage);

        app('image')->filter('rotate', $rotateFilterMock);
        app('image')->filter('resize', $resizeFilterMock);

        $this->handler->setSource(app('image.source')->driver('local'));

        $image = $this->handler->make('image.jpg', [
            'width' => 100,
            'height' => 90,
            'crop' => true,
            'rotate' => 90
        ]);

        $this->assertInstanceOf(ImageInterface::class, $image);
        $this->assertEquals(public_path('image.jpg'), $image->metadata()->get('filepath'));
        $this->assertEquals($returnImage->getSize()->getWidth(), $image->getSize()->getWidth());
        $this->assertEquals($returnImage->getSize()->getHeight(), $image->getSize()->getHeight());
    }

    /**
     * Test making an image with wrong path
     * @test
     * @expectedException \Folklore\Image\Exception\FileMissingException
     * @covers ::make
     */
    public function testMakeWithWrongPath()
    {
        $this->handler->setSource(app('image.source')->driver('local'));

        $image = $this->handler->make('doesnt-exists.jpg');
    }

    /**
     * Test making an image with wrong format
     * @test
     * @expectedException \Folklore\Image\Exception\FormatException
     * @covers ::make
     */
    public function testMakeWithWrongFormat()
    {
        $this->handler->setSource(app('image.source')->driver('local'));

        $image = $this->handler->make('wrong.jpg');
    }

    /**
     * Test making an image with wrong format
     * @test
     * @expectedException \Folklore\Image\Exception\FilterMissingException
     * @covers ::make
     */
    public function testMakeWithWrongFilter()
    {
        $this->handler->setSource(app('image.source')->driver('local'));

        $image = $this->handler->make('image.jpg', [
            'wrong' => true
        ]);
    }

    /**
     * Test saving an image
     * @test
     * @covers ::save
     */
    public function testSave()
    {
        $this->handler->setSource(app('image.source')->driver('local'));

        $image = $this->handler->open('image.jpg');
        $this->handler->save($image, 'image-test.jpg');

        $this->assertTrue(file_exists(public_path('image-test.jpg')));
        $imageTest = $this->handler->open('image-test.jpg');
        $this->assertEquals($imageTest->getSize()->getWidth(), $image->getSize()->getWidth());
        $this->assertEquals($imageTest->getSize()->getHeight(), $image->getSize()->getHeight());
    }
}