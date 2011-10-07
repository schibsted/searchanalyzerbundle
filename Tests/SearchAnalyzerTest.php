<?php

namespace RedpillLinpro\SearchAnalyzerBundle\Tests;

use RedpillLinpro\SearchAnalyzerBundle\SearchAnalyzer;

function assertException($sa, $s = null) {
    try {
        $result = $sa->convert($s);
    } catch (\Exception $e) {
        return true;
    }
    return $result;
}

class SearchAnalyzerTest extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        $fields = array(
            'id' => "i",
            'userId' => "uid,#",
            'email' => "@,e",
            'displayName' => "name,n,display,d",
            'familyName' => "last,l,s,sur,f,family",
            'givenName' => "given,g",
            'preferredUsername' => "pre,username,user,u",
            'phoneNumber' => "p,phone,tlf,ph",
            'gender' => "x",
        );
        $sniffers = array(
            'email' => 'email',
            'numeric' => 'userId',
            'default' => 'displayName'
        );
        $this->sa = new SearchAnalyzer($fields, $sniffers);
    }

    public function testSearchAnalyzerConstruct()
    {
        $this->assertInstanceOf('RedpillLinpro\SearchAnalyzerBundle\SearchAnalyzer', $this->sa);
    }

    public function testConvertFullFields()
    {
        $tests  = array(
            'Al' => array('displayName' => 'Al'),
            'displayName:Mag' => array('displayName' => 'Mag'),
            'displayName:James,familyName:Bond' => array('displayName' => 'James', 'familyName' => 'Bond'),
            'displayName:James,familyName:Bond,email:james@bond.com' => array('displayName' => 'James', 'familyName' => 'Bond', 'email' => 'james@bond.com'),
        );

        foreach ($tests as $query => $expected)
            $this->assertEquals($expected, $this->sa->convert($query));
    }

    public function testConvertAliases()
    {
        $tests  = array(
            'l:Bond' => array('familyName' => 'Bond'),
            'd:Ja,l:Bo' => array('displayName' => 'Ja','familyName' => 'Bo'),
            'e:James@mi6.gov' => array('email' => 'James@mi6.gov'),
        );

        foreach ($tests as $query => $expected)
            $this->assertEquals($expected, $this->sa->convert($query));

    }

    public function testDuplicateField()
    {
        $tests  = array(
            'l:Bond,l:M' => array('familyName' => 'M'),
            'l:Bond,f:Claus' => array('familyName' => 'Claus'),
            'l:Bond,uid:007,f:Claus' => array('userId' => '007', 'familyName' => 'Claus'),
        );

        foreach ($tests as $query => $expected)
            $this->assertEquals($expected, $this->sa->convert($query));
    }

    public function testTrim()
    {
        $tests  = array(
            'd:James Bond,uid:007' => array('displayName' => 'James Bond', 'userId' => '007'),
            'd:James Bond ,uid:007' => array('displayName' => 'James Bond', 'userId' => '007'),
            'd:James Bond , uid:007' => array('displayName' => 'James Bond', 'userId' => '007'),
            'd :James Bond , uid:007' => array('displayName' => 'James Bond', 'userId' => '007'),
            'd : James Bond , uid:007' => array('displayName' => 'James Bond', 'userId' => '007'),
            'd : James Bond , uid :007' => array('displayName' => 'James Bond', 'userId' => '007'),
            'd : James Bond , uid : 007' => array('displayName' => 'James Bond', 'userId' => '007'),
            'd : James Bond , uid : 007 ' => array('displayName' => 'James Bond', 'userId' => '007'),
            ' d : James Bond , uid : 007 ' => array('displayName' => 'James Bond', 'userId' => '007'),
            ' d  : James Bond   , uid  : 007    ' => array('displayName' => 'James Bond', 'userId' => '007'),
        );

        foreach ($tests as $query => $expected)
            $this->assertEquals($expected, $this->sa->convert($query));
    }

    public function testStripInvalidFields()
    {
        $tests  = array(
            'd:Ja,bleh:Bo' => array('displayName' => 'Ja'),
        );

        foreach ($tests as $query => $expected)
            $this->assertEquals($expected, $this->sa->convert($query));

    }

    public function testConvertInvalid()
    {
        $this->assertTrue(assertException($this->sa, null), 'Did not throw expected Exception');
        $this->assertTrue(assertException($this->sa, ''), 'Did not throw expected Exception');
        $this->assertTrue(assertException($this->sa, '  '), 'Did not throw expected Exception');
        $this->assertTrue(assertException($this->sa, ':'), 'Did not throw expected Exception');
        $this->assertTrue(assertException($this->sa, ' : '), 'Did not throw expected Exception');
        $this->assertTrue(assertException($this->sa, ':stuff'), 'Did not throw expected Exception');
        $this->assertTrue(assertException($this->sa, 'stuff:'), 'Did not throw expected Exception');
        $this->assertTrue(assertException($this->sa, 'thisdoesntexist:field'), 'Did not throw expected Exception');
    }
}
