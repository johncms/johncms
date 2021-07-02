<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Tests\Unit\Http;

use Johncms\Http\Session;
use PHPUnit\Framework\TestCase;

class SessionTest extends TestCase
{
    protected Session $session;

    protected function setUp(): void
    {
        parent::setUp();
        $this->session = (new Session())();
    }

    /**
     * @covers \Johncms\Http\Session::has
     * @covers \Johncms\Http\Session::remove
     */
    public function testHas(): void
    {
        $this->session->set('has_test', 'value');
        $this->assertTrue($this->session->has('has_test'));
        $this->session->remove('has_test');
        $this->assertFalse($this->session->has('has_test'));
    }

    /**
     * @covers \Johncms\Http\Session::flash
     * @covers \Johncms\Http\Session::getFlash
     */
    public function testFlash(): void
    {
        $this->session->flash('flash_message', 'test_message');
        $this->assertEquals('test_message', $this->session->getFlash('flash_message'));
        $this->assertNull($this->session->getFlash('flash_message'));
    }

    /**
     * @covers \Johncms\Http\Session::get
     * @covers \Johncms\Http\Session::set
     */
    public function testSet(): void
    {
        $this->session->set('test_key', 'test_value');
        $this->assertEquals('test_value', $this->session->get('test_key'));
    }
}
