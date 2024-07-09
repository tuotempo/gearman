<?php
namespace MHlavac\Gearman\Tests;

use MHlavac\Gearman\Client;
use MHlavac\Gearman\Connection;
use MHlavac\Gearman\Exception;
use MHlavac\Gearman\Exception\CouldNotConnectException;
use MHlavac\Gearman\Task;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class ClientTest extends TestCaseBase
{
    /**
     * @var Client
     */
    private $client;

    public function setUp() :void
    {
        $this->client = new Client();
        $this->client->addServer('host.docker.internal');
    }

    public function testClient()
    {
        $process = new Process("gearman -w -f replace -- sed 's/__replace__/the best/g'");
        $process->start();
        try {
            $this->assertEquals('php is the best', $this->client->doNormal('replace', 'php is __replace__'));
            $this->assertEquals('php is the best', $this->client->doLow('replace', 'php is __replace__'));
            $this->assertEquals('php is the best', $this->client->doHigh('replace', 'php is __replace__'));
            $this->client->doBackground('replace', 'php is __replace__');
            $this->client->doHighBackground('replace', 'php is __replace__');
            $this->client->doLowBackground('replace', 'php is __replace__');
        } catch (CouldNotConnectException $e) {
            $this->markTestSkipped('Skipped, please start Gearman on port ' . Connection::DEFAULT_PORT . ' to be able to run this test');
        }

        $process->stop();
    }

    public function testTimeout()
    {
        $client = new Client();
        $client->addServer(self::SERVER_ADDRESS);
        $task = $client->doBackground('test', '');
        self::assertInstanceOf(Task::class, $task);

        //-------------------------------------------------------//

        sleep(Connection::CONNECTION_TIMEOUT_SEC + 1);
        $task = $client->doBackground('test', '');
        self::assertInstanceOf(Task::class, $task);

        //-------------------------------------------------------//
        // Invalid address
        $start_time = time();
        try {
            $client = new Client();
            $client->addServer('10.10.10.10');
            $task =  $client->doBackground('test', '');
            self::fail("Timeout not fired");
        } catch (\Exception $ex) {
            echo get_class($ex);
            self::assertInstanceOf(CouldNotConnectException::class, $ex, $ex->getMessage());
            echo $ex->getMessage();
            $elapsed_time = time() - $start_time;
            self::assertLessThan(5, $elapsed_time);
        }

    }
}
