<?php

use UriTemplate\Processor;

class ProcessorTest extends PHPUnit_Framework_TestCase
{

    public function testNoExpressionsReturnsSameUri()
    {
        $processor = new Processor('http://foo.com/hello/world', array());

        $this->assertEquals('http://foo.com/hello/world', $processor->process());
    }

    public function testSimpleExpansion()
    {
        $processor = new Processor('http://foo.com/{var}', array('var' => 'Hello'));
        $this->assertEquals('http://foo.com/Hello', $processor->process());

        $processor = new Processor('http://foo.com/{var}', array('var' => 'Hello World'));
        $this->assertEquals('http://foo.com/Hello%20World', $processor->process());

        $processor = new Processor('http://foo.com/{non_existent}', array('var' => 'Hello'));
        $this->assertEquals('http://foo.com/', $processor->process());

        $processor = new Processor('http://foo.com/{var}', array('var' => array(
            1, 2, 3
        )));
        $this->assertEquals('http://foo.com/1,2,3', $processor->process());

        $processor = new Processor('http://foo.com/{var}', array('var' => array(
            'foo' => 'bar',
            'baz' => 'yay',
        )));
        $this->assertEquals('http://foo.com/foo,bar,baz,yay', $processor->process());

    }

    public function testSimpleExpansionWithMaxLen()
    {
        $processor = new Processor('http://foo.com/{var:2}', array('var' => 'welcome'));
        $this->assertEquals('http://foo.com/we', $processor->process());

        $processor = new Processor('http://foo.com/{var:50}', array('var' => 'welcome'));
        $this->assertEquals('http://foo.com/welcome', $processor->process());
    }

    public function testExplodeExpansion()
    {
        $processor = new Processor('http://foo.com/{var*}', array(
            'var' => array(
                12, 23, 43
            ),
        ));
        $this->assertEquals('http://foo.com/12,23,43', $processor->process());

        $processor = new Processor('http://foo.com/{var*}', array(
            'var' => array(
                'foo' => 'bar',
                'baz' => 'yay',
            ),
        ));
        $this->assertEquals('http://foo.com/foo=bar,baz=yay', $processor->process());
    }

    public function testMultipleExpansionsWithNoOperators()
    {
        $processor = new Processor('http://foo.com/{var0}/{var1:2}/{var2*}/{var3*}', array(
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

        $this->assertEquals('http://foo.com/bob/he/12,23,43/foo=bar,baz=yay', $processor->process());
    }

    public function testReservedCharsGetEncodedByDefault()
    {
        $processor = new Processor('http://foo.com/{var}', array('var' => 'welcome!'));
        $this->assertEquals('http://foo.com/welcome%21', $processor->process());
    }

    public function testReservedStringExpansion()
    {
        $processor = new Processor('http://foo.com/{+var}', array('var' => 'welcome!'));
        $this->assertEquals('http://foo.com/welcome!', $processor->process());
    }

    public function testFragmentExpansion()
    {
        $processor = new Processor('http://foo.com/{#var}', array('var' => 'welcome!'));
        $this->assertEquals('http://foo.com/#welcome!', $processor->process());
    }

    public function testDotPrefixedExpansion()
    {
        $processor = new Processor('http://foo.com/{.var1,var2}', array(
            'var1' => 'foo',
            'var2' => 'bar'
        ));
        $this->assertEquals('http://foo.com/.foo.bar', $processor->process());
    }

    public function testPathSegmentsExpansion()
    {
        $processor = new Processor('http://foo.com{/var1,var2}', array(
            'var1' => 'foo',
            'var2' => 'bar'
        ));
        $this->assertEquals('http://foo.com/foo/bar', $processor->process());
    }

    public function testSemiColonPathExpansion()
    {
        $processor = new Processor('http://foo.com/{;var1,var2}', array(
            'var1' => 'foo',
            'var2' => 'bar'
        ));
        $this->assertEquals('http://foo.com/;var1=foo;var2=bar', $processor->process());
    }

    public function testFormQueryWithQuestionMarkExpansion()
    {
        $processor = new Processor('http://foo.com/{?var1,var2}', array(
            'var1' => 'foo',
            'var2' => 'bar'
        ));
        $this->assertEquals('http://foo.com/?var1=foo&var2=bar', $processor->process());
    }

    public function testFormQueryWithAmpersandExpansion()
    {
        $processor = new Processor('http://foo.com/?a=b{&var1,var2}', array(
            'var1' => 'foo',
            'var2' => 'bar'
        ));
        $this->assertEquals('http://foo.com/?a=b&var1=foo&var2=bar', $processor->process());
    }

    public function testUriCreation()
    {
        $processor = new Processor('{schema}://{domain}{.tld}{/resource}{.format}{?query*}');
        $processor->setContext(array(
            'schema' => 'http',
            'domain' => 'api.foo',
            'tld' => array('co', 'uk'),
            'resource' => array('search', 'entries'),
            'format' => 'json',
            'query' => array('q' => 'bar', 'sort_by' => 'recent')
        ));

        $this->assertEquals('http://api.foo.co.uk/search/entries.json?q=bar&sort_by=recent', $processor->process());
    }

}
