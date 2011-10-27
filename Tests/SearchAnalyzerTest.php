<?php

namespace RedpillLinpro\SearchAnalyzerBundle\Tests;

use RedpillLinpro\SearchAnalyzerBundle\SearchAnalyzer;

function assertException($sa, $s = null, $a = null) {
    try {
        $result = $sa->convert($s, $a);
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
            $this->assertEquals($expected, $this->sa->convert($query), 'Test :'.$query);
    }

    public function testConvertAliases()
    {
        $tests  = array(
            'l:Bond' => array('familyName' => 'Bond'),
            'd:Ja,l:Bo' => array('displayName' => 'Ja','familyName' => 'Bo'),
            'e:James@mi6.gov' => array('email' => 'James@mi6.gov'),
        );

        foreach ($tests as $query => $expected)
            $this->assertEquals($expected, $this->sa->convert($query), 'Test :'.$query);

    }

    public function testMaintainOrder()
    {

        $tests  = array(
            'd:Ja,l:Bo' => array('displayName' => 'Ja','familyName' => 'Bo'),
            'l:Bo,d:Ja' => array('familyName' => 'Bo','displayName' => 'Ja'),
        );

        foreach ($tests as $query => $expected)
            $this->assertEquals($expected, $this->sa->convert($query), 'Test :'.$query);
    }

    public function testDuplicateField()
    {
        $tests  = array(
            'l:Bond,l:M' => array('familyName' => 'M'),
            'l:Bond,f:Claus' => array('familyName' => 'Claus'),
            'l:Bond,uid:007,f:Claus' => array('userId' => '007', 'familyName' => 'Claus'),
        );

        foreach ($tests as $query => $expected)
            $this->assertEquals($expected, $this->sa->convert($query), 'Test :'.$query);
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
            $this->assertEquals($expected, $this->sa->convert($query), 'Test :'.$query);
    }

    public function testStripInvalidFields()
    {
        $tests  = array(
            'd:Ja,bleh:Bo' => array('displayName' => 'Ja'),
        );

        foreach ($tests as $query => $expected)
            $this->assertEquals($expected, $this->sa->convert($query));

    }

    public function testCleanArray()
    {
        $tests  = array(
            array(
                array('userId' => '007', 'displayName' => 'James', 'familyName' => 'Bond'),
                array('userId' => '007', 'displayName' => 'James', 'familyName' => 'Bond'),
            ),
            array(
                array('uid' => '007', 'd' => 'James', 'l' => 'Bond'),
                array('userId' => '007', 'displayName' => 'James', 'familyName' => 'Bond'),
            ),
            array(
                array('uid' => '007', 'bad' => 'James', 'l' => ''),
                array('userId' => '007'),
            ),
        );
        foreach ($tests as $k => $one) {
            list($array, $expected) = $one;
            $this->assertEquals($expected, $this->sa->convert(null, $array), 'Test #'.($k+1));
        }
    }

    public function testMergeArray()
    {

        $tests  = array(
            array(
                'd:James Bond,uid:007',
                null,
                array('displayName' => 'James Bond', 'userId' => '007'),
            ),
            array(
                '',
                array('userId' => '007', 'displayName' => 'James', 'familyName' => 'Bond'),
                array('userId' => '007', 'displayName' => 'James', 'familyName' => 'Bond'),
            ),
            array(
                'd:James Bond',
                array('userId' => '007'),
                array('userId' => '007', 'displayName' => 'James Bond'),
            ),
            array(
                'd:James,l:Bond',
                array('userId' => '007'),
                array('userId' => '007', 'displayName' => 'James', 'familyName' => 'Bond'),
            ),
            array(
                'uid:009',
                array('userId' => '007', 'displayName' => 'James'),
                array('userId' => '009', 'displayName' => 'James'),
            ),
            array(
                'uid:009,x:male',
                array('userId' => '007', 'displayName' => 'James'),
                array('userId' => '009', 'displayName' => 'James', 'gender' => 'male'),
            ),
        );

        foreach ($tests as $k => $one) {
            list($string, $array, $expected) = $one;
            $this->assertEquals($expected, $this->sa->convert($string, $array), 'Test #'.($k+1));
        }
    }

    public function testString()
    {
        $tests  = array(
            'displayName:Al' => array('displayName' => 'Al'),
            'displayName:Mag' => array('displayName' => 'Mag'),
            'displayName:James,familyName:Bond' => array('displayName' => 'James', 'familyName' => 'Bond'),
            'displayName:James,familyName:Bond,email:james@bond.com' => array('displayName' => 'James', 'familyName' => 'Bond', 'email' => 'james@bond.com'),
        );
        foreach ($tests as $expected => $query)
            $this->assertEquals($expected, $this->sa->string($query));
    }
}
