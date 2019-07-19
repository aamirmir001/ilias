<?php declare(strict_types=1);
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Tests\Refinery\String;

use ILIAS\Refinery\Factory;
use PHPUnit\Framework\TestCase;

/**
 * Class ReadingTimeTest
 * @package ILIAS\Tests\Refinery\String
 */
class ReadingTimeTest extends TestCase
{
    const TEXT = <<<EOT
Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.
EOT;
    const HTML = <<<EOT
<div>Lorem ipsum dolor <span style="color: red;">sit amet</span>, <img src="#" /> consetetur sadipscing elitr, sed diam nonumy eirmod <img src="#" />  tempor invidunt <img src="#" />  ut labore et dolore <img src="#" />  magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor <img src="#" />  sit amet. <img src="#" />  Lorem ipsum dolor <img src="#" />  sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, <img src="#" />  sed diam voluptua. <img src="#" />  At vero eos et accusam et justo duo dolores et ea rebum. Stet <img src="#" />  clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.</div>
EOT;
    
    /** @var Factory */
    private $refinery;
    
    /**
     * @throws \ReflectionException
     */
    protected function setUp() : void
    {
        $this->refinery = new Factory(
            $this->createMock(\ILIAS\Data\Factory::class),
            $this->createMock(\ilLanguage::class)
        );
        
        parent::setUp();
    }

    /**
     * @return array
     */
    public function subjectProvider() : array
    {
        return [
            [5],
            [6.3],
            [[]],
            [new \stdClass()],
            [true],
            [null],
            [function() {}],
        ];
    }

    /**
     * @dataProvider subjectProvider
     * @param mixed $from
     */
    public function testExceptionIsRaisedIfSubjectIsNotAString($from)
    {
        $readingTimeTrafo = $this->refinery->string()->readingTime();
        
        $this->expectException(\InvalidArgumentException::class);
        $readingTimeTrafo->transform($from);
    }

    /**
     * 
     */
    public function testReadingTimeForPlainText()
    {
        $readingTimeTrafo = $this->refinery->string()->readingTime();
        $this->assertEquals(
            1,
            $readingTimeTrafo->transform(self::TEXT)
        );
    }

    /**
     *
     */
    public function testReadingTimeForHtmlFragment()
    {
        $text = self::HTML;

        $readingTimeTrafo = $this->refinery->string()->readingTime();
        $this->assertEquals(
            2,
            $readingTimeTrafo->transform($text)
        );
    }
}