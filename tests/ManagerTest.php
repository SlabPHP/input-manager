<?php
/**
 * Tests for SlabPHP Input Manager
 *
 * @package Slab
 * @subpackage Tests
 * @author Eric
 */
namespace Slab\Tests\Input;

class ManagerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test reading parameters
     */
    public function testReadParameters()
    {
        $_GET = [
            'test1' => 'string',
            'test2' => 134,
            'test3' => ['ok','no']
        ];

        $_POST = [
            'test1' => 'string',
            'test2' => 134,
            'test3' => ['ok','no']
        ];

        $_SERVER = [
            'test1' => 'string',
            'test2' => 134,
            'test3' => ['ok','no']
        ];

        $_FILES = [
            'one' => [
                'test1' => 'string',
                'test2' => 134,
                'test3' => ['ok','no']
            ]
        ];

        $_ENV = [
            'test1' => 'string',
            'test2' => 134,
            'test3' => ['ok','no']
        ];

        $input =  new \Slab\Input\Manager();

        $this->assertEquals('string', $input->get('test1'));
        $this->assertEquals(134, $input->get('test2'));
        $this->assertEquals(['ok','no'], $input->get('test3'));

        $this->assertEquals('string', $input->post('test1'));
        $this->assertEquals(134, $input->post('test2'));
        $this->assertEquals(['ok','no'], $input->post('test3'));

        $this->assertEquals('string', $input->request('test1'));
        $this->assertEquals(134, $input->request('test2'));
        $this->assertEquals(['ok','no'], $input->request('test3'));

        $this->assertEquals('string', $input->server('test1'));
        $this->assertEquals(134, $input->server('test2'));
        $this->assertEquals(['ok','no'], $input->server('test3'));

        $file = $input->file('one');
        $this->assertEquals('string', $file['test1']);
        $this->assertEquals(134, $file['test2']);
        $this->assertEquals(['ok','no'], $file['test3']);

        $this->assertEquals('string', $input->env('test1'));
        $this->assertEquals(134, $input->env('test2'));
        $this->assertEquals(['ok','no'], $input->env('test3'));
    }

    /**
     * Test cleaning of the vars
     */
    public function testCleanVars()
    {
        $_GET = [
            "clean1" => '<script type="text/javascript">alert("hi")</script>',
            "clean2" => '<s><</s>script type="text/javascript">asdf',
            "clean3" => ' asdfa sdf  asdf     '
        ];

        $input =  new \Slab\Input\Manager();

        $this->assertEquals('alert("hi")', $input->get('clean1'));
        $this->assertEquals('asdf', $input->get('clean2'));
        $this->assertEquals('asdfa sdf  asdf', $input->get('clean3'));
    }

    /**
     * Test null returns
     */
    public function testNulls()
    {
        $input =  new \Slab\Input\Manager();

        $this->assertNull($input->get('something'));
        $this->assertNull($input->post('something'));
        $this->assertNull($input->cookie('something'));
        $this->assertNull($input->server('something'));
        $this->assertNull($input->env('something'));
        $this->assertNull($input->file('something'));
    }
}