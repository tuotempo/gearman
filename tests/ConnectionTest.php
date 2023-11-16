<?php
namespace MHlavac\Gearman\tests;

use MHlavac\Gearman\Connection;

/**
 * @category   Testing
 *
 * @author     Till Klampaeckel <till@php.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php New BSD License
 *
 * @version    CVS: $Id$
 *
 * @link       http://pear.php.net/package/Net_Gearman
 * @since      0.2.4
 */
class ConnectionTest extends \PHPUnit\Framework\TestCase
{
    public const TEST_SERVER = 'host.docker.internal';

    /**
     * When no server is supplied, it should connect to localhost:4730.
     */
    public function testDefaultConnect()
    {
        try {
            $connection = Connection::connect(self::TEST_SERVER);
        } catch (\MHlavac\Gearman\Exception $exception) {

            return $this->markTestSkipped('Skipped. You can try this test on your machine with gearman running.' . $exception->getMessage());
        }

        $phpVer = substr(phpversion(),0,1);
        switch ($phpVer) {
            case '7':
                $this->assertInternalType('resource', $connection);
                $this->assertEquals('socket', strtolower(get_resource_type($connection)));
                break;
        }


        $this->assertTrue(Connection::isConnected($connection));

        Connection::close($connection);
    }

    public function testSend()
    {
        try {
            $connection = Connection::connect(self::TEST_SERVER);
        } catch (\MHlavac\Gearman\Exception $exception) {
            return $this->markTestSkipped('Skipped. You can try this test on your machine with gearman running.');
        }

        Connection::send($connection, 'echo_req', array('text' => 'foobar'));

        do {
            $ret = Connection::read($connection);
        } while (is_array($ret) && !count($ret));

        Connection::close($connection);

        $this->assertInternalType('array', $ret);
        $this->assertEquals('echo_res', $ret['function']);
        $this->assertEquals(17, $ret['type']);

        $this->assertInternalType('array', $ret['data']);
        $this->assertEquals('foobar', $ret['data']['text']);
    }
}
