<?php


namespace Test\Prometheus;

use PHPUnit_Framework_TestCase;
use Prometheus\Gauge;
use Prometheus\MetricFamilySamples;
use Prometheus\Sample;
use Prometheus\Storage\InMemory;
use Prometheus\Storage\Redis;

/**
 * See https://prometheus.io/docs/instrumenting/exposition_formats/
 */
class GaugeTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Redis
     */
    private $storage;

    public function setUp()
    {
        $this->storage = new Redis(array('host' => REDIS_HOST));
        $this->storage->flushRedis();
    }

    /**
     * @test
     */
    public function itShouldAllowSetWithLabels()
    {
        $gauge = new Gauge($this->storage, 'test', 'some_metric', 'this is for testing', array('foo', 'bar'));
        $gauge->set(123, array('lalal', 'lululu'));
        $this->assertThat(
            $this->storage->collect(),
            $this->equalTo(
                array(
                    new MetricFamilySamples(
                        array(
                            'name' => 'test_some_metric',
                            'help' => 'this is for testing',
                            'type' => Gauge::TYPE,
                            'labelNames' => array('foo', 'bar'),
                            'samples' => array(
                                array(
                                    'name' => 'test_some_metric',
                                    'labelNames' => array(),
                                    'labelValues' => array('lalal', 'lululu'),
                                    'value' => 123,
                                )
                            )
                        )
                    )
                )
            )
        );
        $this->assertThat($gauge->getHelp(), $this->equalTo('this is for testing'));
        $this->assertThat($gauge->getType(), $this->equalTo(Gauge::TYPE));
    }

    /**
     * @test
     */
    public function itShouldAllowSetWithoutLabelWhenNoLabelsAreDefined()
    {
        $gauge = new Gauge($this->storage, 'test', 'some_metric', 'this is for testing');
        $gauge->set(123);
        $this->assertThat(
            $this->storage->collect(),
            $this->equalTo(
                array(
                    new MetricFamilySamples(
                        array(
                            'name' => 'test_some_metric',
                            'help' => 'this is for testing',
                            'type' => Gauge::TYPE,
                            'labelNames' => array(),
                            'samples' => array(
                                array(
                                    'name' => 'test_some_metric',
                                    'labelNames' => array(),
                                    'labelValues' => array(),
                                    'value' => 123,
                                )
                            )
                        )
                    )
                )
            )
        );
        $this->assertThat($gauge->getHelp(), $this->equalTo('this is for testing'));
        $this->assertThat($gauge->getType(), $this->equalTo(Gauge::TYPE));
    }

    /**
     * @test
     */
    public function itShouldIncrementAValue()
    {
        $gauge = new Gauge($this->storage, 'test', 'some_metric', 'this is for testing', array('foo', 'bar'));
        $gauge->inc(array('lalal', 'lululu'));
        $gauge->incBy(123, array('lalal', 'lululu'));
        $this->assertThat(
            $this->storage->collect(),
            $this->equalTo(
                array(
                    new MetricFamilySamples(
                        array(
                            'name' => 'test_some_metric',
                            'help' => 'this is for testing',
                            'type' => Gauge::TYPE,
                            'labelNames' => array('foo', 'bar'),
                            'samples' => array(
                                array(
                                    'name' => 'test_some_metric',
                                    'labelNames' => array(),
                                    'labelValues' => array('lalal', 'lululu'),
                                    'value' => 124,
                                )
                            )
                        )
                    )
                )
            )
        );
    }

    /**
     * @test
     */
    public function itShouldDecrementAValue()
    {
        $gauge = new Gauge($this->storage, 'test', 'some_metric', 'this is for testing', array('foo', 'bar'));
        $gauge->dec(array('lalal', 'lululu'));
        $gauge->decBy(123, array('lalal', 'lululu'));
        $this->assertThat(
            $this->storage->collect(),
            $this->equalTo(
                array(
                    new MetricFamilySamples(
                        array(
                            'name' => 'test_some_metric',
                            'help' => 'this is for testing',
                            'type' => Gauge::TYPE,
                            'labelNames' => array('foo', 'bar'),
                            'samples' => array(
                                array(
                                    'name' => 'test_some_metric',
                                    'labelNames' => array(),
                                    'labelValues' => array('lalal', 'lululu'),
                                    'value' => -124,
                                )
                            )
                        )
                    )
                )
            )
        );
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function itShouldRejectInvalidMetricsNames()
    {
        new Gauge($this->storage, 'test', 'some metric invalid metric', 'help');
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function itShouldRejectInvalidLabelNames()
    {
        new Gauge($this->storage, 'test', 'some_metric', 'help', array('invalid label'));
    }
}
