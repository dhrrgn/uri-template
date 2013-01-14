<?php

use UriTemplate\Parser;

class ParserTest extends PHPUnit_Framework_TestCase
{

    public function testNoExpressionsReturnsSameUri()
    {
        $parser = new Parser('http://foo.com/hello/world', array());

        $this->assertEquals('http://foo.com/hello/world', $parser->parse());
    }

    public function testSimpleExpansion()
    {
        $parser = new Parser('http://foo.com/{var}', array('var' => 'Hello'));
        $this->assertEquals('http://foo.com/Hello', $parser->parse());

        $parser = new Parser('http://foo.com/{var}', array('var' => 'Hello World'));
        $this->assertEquals('http://foo.com/Hello%20World', $parser->parse());

        $parser = new Parser('http://foo.com/{non_existent}', array('var' => 'Hello'));
        $this->assertEquals('http://foo.com/', $parser->parse());

        $parser = new Parser('http://foo.com/{var}', array('var' => array(
            1, 2, 3
        )));
        $this->assertEquals('http://foo.com/1,2,3', $parser->parse());

        $parser = new Parser('http://foo.com/{var}', array('var' => array(
            'foo' => 'bar',
            'baz' => 'yay',
        )));
        $this->assertEquals('http://foo.com/foo,bar,baz,yay', $parser->parse());

    }

    public function testSimpleExpansionWithMaxLen()
    {
        $parser = new Parser('http://foo.com/{var:2}', array('var' => 'welcome'));
        $this->assertEquals('http://foo.com/we', $parser->parse());

        $parser = new Parser('http://foo.com/{var:50}', array('var' => 'welcome'));
        $this->assertEquals('http://foo.com/welcome', $parser->parse());
    }

    public function testExplodeExpansion()
    {
        $parser = new Parser('http://foo.com/{var*}', array(
            'var' => array(
                12, 23, 43
            ),
        ));
        $this->assertEquals('http://foo.com/12,23,43', $parser->parse());

        $parser = new Parser('http://foo.com/{var*}', array(
            'var' => array(
                'foo' => 'bar',
                'baz' => 'yay',
            ),
        ));
        $this->assertEquals('http://foo.com/foo=bar,baz=yay', $parser->parse());
    }

    public function testMultipleExpansionsWithNoOperators()
    {
        $parser = new Parser('http://foo.com/{var0}/{var1:2}/{var2*}/{var3*}', array(
            'var0' => 'bob',
            'var1' => 'hello',
            'var2' => array(
                12, 23, 43
            ),
            'var3' => array(
                'foo' => 'bar',
                'baz' => 'yay',
            ),
        ));

        $this->assertEquals('http://foo.com/bob/he/12,23,43/foo=bar,baz=yay', $parser->parse());
    }

    public function testReservedCharsGetEncodedByDefault()
    {
        $parser = new Parser('http://foo.com/{var}', array('var' => 'welcome!'));
        $this->assertEquals('http://foo.com/welcome%21', $parser->parse());
    }

    public function testReservedStringExpansion()
    {
        $parser = new Parser('http://foo.com/{+var}', array('var' => 'welcome!'));
        $this->assertEquals('http://foo.com/welcome!', $parser->parse());
    }

    public function testFragmentExpansion()
    {
        $parser = new Parser('http://foo.com/{#var}', array('var' => 'welcome!'));
        $this->assertEquals('http://foo.com/#welcome!', $parser->parse());
    }

}
