<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Validator\Tests;

use Symfony\Component\Validator\Mapping\PropertyMetadata;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ExecutionContext;

class ExecutionContextTest extends \PHPUnit_Framework_TestCase
{
    private $visitor;
    private $violations;
    private $metadata;
    private $metadataFactory;
    private $globalContext;

    /**
     * @var ExecutionContext
     */
    private $context;

    protected function setUp()
    {
        $this->visitor = $this->getMockBuilder('Symfony\Component\Validator\ValidationVisitor')
            ->disableOriginalConstructor()
            ->getMock();
        $this->violations = new ConstraintViolationList();
        $this->metadata = $this->getMock('Symfony\Component\Validator\MetadataInterface');
        $this->metadataFactory = $this->getMock('Symfony\Component\Validator\MetadataFactoryInterface');
        $this->globalContext = $this->getMock('Symfony\Component\Validator\GlobalExecutionContextInterface');
        $this->globalContext->expects($this->any())
            ->method('getRoot')
            ->will($this->returnValue('Root'));
        $this->globalContext->expects($this->any())
            ->method('getViolations')
            ->will($this->returnValue($this->violations));
        $this->globalContext->expects($this->any())
            ->method('getVisitor')
            ->will($this->returnValue($this->visitor));
        $this->globalContext->expects($this->any())
            ->method('getMetadataFactory')
            ->will($this->returnValue($this->metadataFactory));
        $this->context = new ExecutionContext($this->globalContext, $this->metadata, 'currentValue', 'Group', 'foo.bar');
    }

    protected function tearDown()
    {
        $this->globalContext = null;
        $this->context = null;
    }

    public function testInit()
    {
        $this->assertCount(0, $this->context->getViolations());
        $this->assertSame('Root', $this->context->getRoot());
        $this->assertSame('foo.bar', $this->context->getPropertyPath());
        $this->assertSame('Group', $this->context->getGroup());

        $this->visitor->expects($this->once())
            ->method('getGraphWalker')
            ->will($this->returnValue('GRAPHWALKER'));

        // BC
        $this->assertNull($this->context->getCurrentClass());
        $this->assertNull($this->context->getCurrentProperty());
        $this->assertSame('GRAPHWALKER', $this->context->getGraphWalker());
        $this->assertSame($this->metadataFactory, $this->context->getMetadataFactory());
    }

    public function testInitWithClassMetadata()
    {
        // BC
        $this->metadata = new ClassMetadata(__NAMESPACE__ . '\ExecutionContextTest_TestClass');
        $this->context = new ExecutionContext($this->globalContext, $this->metadata, 'currentValue', 'Group', 'foo.bar');

        $this->assertSame(__NAMESPACE__ . '\ExecutionContextTest_TestClass', $this->context->getCurrentClass());
        $this->assertNull($this->context->getCurrentProperty());
    }

    public function testInitWithPropertyMetadata()
    {
        // BC
        $this->metadata = new PropertyMetadata(__NAMESPACE__ . '\ExecutionContextTest_TestClass', 'myProperty');
        $this->context = new ExecutionContext($this->globalContext, $this->metadata, 'currentValue', 'Group', 'foo.bar');

        $this->assertSame(__NAMESPACE__ . '\ExecutionContextTest_TestClass', $this->context->getCurrentClass());
        $this->assertSame('myProperty', $this->context->getCurrentProperty());
    }

    public function testClone()
    {
        $clone = clone $this->context;

        // Cloning the context keeps the reference to the original violation
        // list. This way we can efficiently duplicate context instances during
        // the validation run and only modify the properties that need to be
        // changed.
        $this->assertSame($this->context->getViolations(), $clone->getViolations());
    }

    public function testAddViolation()
    {
        $this->context->addViolation('Error', array('foo' => 'bar'), 'invalid');

        $this->assertEquals(new ConstraintViolationList(array(
            new ConstraintViolation(
                'Error',
                array('foo' => 'bar'),
                'Root',
                'foo.bar',
                'invalid'
            ),
        )), $this->context->getViolations());
    }

    public function testAddViolationUsesPreconfiguredValueIfNotPassed()
    {
        $this->context->addViolation('Error');

        $this->assertEquals(new ConstraintViolationList(array(
            new ConstraintViolation(
                'Error',
                array(),
                'Root',
                'foo.bar',
                'currentValue'
            ),
        )), $this->context->getViolations());
    }

    public function testAddViolationUsesPassedNullValue()
    {
        // passed null value should override preconfigured value "invalid"
        $this->context->addViolation('Error', array('foo' => 'bar'), null);
        $this->context->addViolation('Error', array('foo' => 'bar'), null, 1);

        $this->assertEquals(new ConstraintViolationList(array(
            new ConstraintViolation(
                'Error',
                array('foo' => 'bar'),
                'Root',
                'foo.bar',
                null
            ),
            new ConstraintViolation(
                'Error',
                array('foo' => 'bar'),
                'Root',
                'foo.bar',
                null,
                1
            ),
        )), $this->context->getViolations());
    }

    public function testAddViolationAtPath()
    {
        // override preconfigured property path
        $this->context->addViolationAtPath('bar.baz', 'Error', array('foo' => 'bar'), 'invalid');

        $this->assertEquals(new ConstraintViolationList(array(
            new ConstraintViolation(
                'Error',
                array('foo' => 'bar'),
                'Root',
                'bar.baz',
                'invalid'
            ),
        )), $this->context->getViolations());
    }

    public function testAddViolationAtPathUsesPreconfiguredValueIfNotPassed()
    {
        $this->context->addViolationAtPath('bar.baz', 'Error');

        $this->assertEquals(new ConstraintViolationList(array(
            new ConstraintViolation(
                'Error',
                array(),
                'Root',
                'bar.baz',
                'currentValue'
            ),
        )), $this->context->getViolations());
    }

    public function testAddViolationAtPathUsesPassedNullValue()
    {
        // passed null value should override preconfigured value "invalid"
        $this->context->addViolationAtPath('bar.baz', 'Error', array('foo' => 'bar'), null);
        $this->context->addViolationAtPath('bar.baz', 'Error', array('foo' => 'bar'), null, 1);

        $this->assertEquals(new ConstraintViolationList(array(
            new ConstraintViolation(
                'Error',
                array('foo' => 'bar'),
                'Root',
                'bar.baz',
                null
            ),
            new ConstraintViolation(
                'Error',
                array('foo' => 'bar'),
                'Root',
                'bar.baz',
                null,
                1
            ),
        )), $this->context->getViolations());
    }

    public function testAddViolationAt()
    {
        // override preconfigured property path
        $this->context->addViolationAt('bam.baz', 'Error', array('foo' => 'bar'), 'invalid');

        $this->assertEquals(new ConstraintViolationList(array(
            new ConstraintViolation(
                'Error',
                array('foo' => 'bar'),
                'Root',
                'foo.bar.bam.baz',
                'invalid'
            ),
        )), $this->context->getViolations());
    }

    public function testAddViolationAtUsesPreconfiguredValueIfNotPassed()
    {
        $this->context->addViolationAt('bam.baz', 'Error');

        $this->assertEquals(new ConstraintViolationList(array(
            new ConstraintViolation(
                'Error',
                array(),
                'Root',
                'foo.bar.bam.baz',
                'currentValue'
            ),
        )), $this->context->getViolations());
    }

    public function testAddViolationAtUsesPassedNullValue()
    {
        // passed null value should override preconfigured value "invalid"
        $this->context->addViolationAt('bam.baz', 'Error', array('foo' => 'bar'), null);
        $this->context->addViolationAt('bam.baz', 'Error', array('foo' => 'bar'), null, 1);

        $this->assertEquals(new ConstraintViolationList(array(
            new ConstraintViolation(
                'Error',
                array('foo' => 'bar'),
                'Root',
                'foo.bar.bam.baz',
                null
            ),
            new ConstraintViolation(
                'Error',
                array('foo' => 'bar'),
                'Root',
                'foo.bar.bam.baz',
                null,
                1
            ),
        )), $this->context->getViolations());
    }

    public function testGetPropertyPath()
    {
        $this->assertEquals('foo.bar', $this->context->getPropertyPath());
    }

    public function testGetPropertyPathWithIndexPath()
    {
        $this->assertEquals('foo.bar[bam]', $this->context->getPropertyPath('[bam]'));
    }

    public function testGetPropertyPathWithEmptyPath()
    {
        $this->assertEquals('foo.bar', $this->context->getPropertyPath(''));
    }

    public function testGetPropertyPathWithEmptyCurrentPropertyPath()
    {
        $this->context = new ExecutionContext($this->globalContext, $this->metadata, 'currentValue', 'Group', '');

        $this->assertEquals('bam.baz', $this->context->getPropertyPath('bam.baz'));
    }
}

class ExecutionContextTest_TestClass
{
    public $myProperty;
}
