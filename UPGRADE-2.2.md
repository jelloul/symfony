﻿UPGRADE FROM 2.1 to 2.2
=======================

### HttpFoundation

 * The MongoDbSessionHandler default field names and timestamp type have changed.

   The `sess_` prefix was removed from default field names. The session ID is
   now stored in the `_id` field by default. The session date is now stored as a
   `MongoDate` instead of `MongoTimestamp`, which also makes it possible to use
   TTL collections in MongoDB 2.2+ instead of relying on the `gc()` method.

 * The Stopwatch functionality was moved from HttpKernel\Debug to its own component

#### Deprecations

 * The `Request::splitHttpAcceptHeader()` is deprecated and will be removed in 2.3.

   You should now use the `AcceptHeader` class which give you fluent methods to
   parse request accept-* headers. Some examples:

   ```
   $accept = AcceptHeader::fromString($request->headers->get('Accept'));
   if ($accept->has('text/html') {
       $item = $accept->get('html');
       $charset = $item->getAttribute('charset', 'utf-8');
       $quality = $item->getQuality();
   }

   // accepts items are sorted by descending quality
   $accepts = AcceptHeader::fromString($request->headers->get('Accept'))->all();

   ```

### Form

  * The PasswordType is now not trimmed by default.

### Validator

 * Interfaces were created for the classes `ConstraintViolation`,
   `ConstraintViolationList`, `GlobalExecutionContext` and `ExecutionContext`.
   If you type hinted against any of these classes, you are recommended to
   type hint against their interfaces now.

   Before:

   ```
   use Symfony\Component\Validator\ExecutionContext;

   public function validateCustomLogic(ExecutionContext $context)
   ```

   After:

   ```
   use Symfony\Component\Validator\ExecutionContextInterface;

   public function validateCustomLogic(ExecutionContextInterface $context)
   ```

   For all implementations of `ConstraintValidatorInterface`, this change is
   mandatory for the `initialize` method:

   Before:

   ```
   use Symfony\Component\Validator\ConstraintValidatorInterface;
   use Symfony\Component\Validator\ExecutionContext;

   class MyValidator implements ConstraintValidatorInterface
   {
       public function initialize(ExecutionContext $context)
       {
           // ...
       }
   }
   ```

   After:

   ```
   use Symfony\Component\Validator\ConstraintValidatorInterface;
   use Symfony\Component\Validator\ExecutionContextInterface;

   class MyValidator implements ConstraintValidatorInterface
   {
       public function initialize(ExecutionContextInterface $context)
       {
           // ...
       }
   }
   ```

#### Deprecations

 * The interface `ClassMetadataFactoryInterface` was deprecated and will be
   removed in Symfony 2.3. You should implement `MetadataFactoryInterface`
   instead, which changes the name of the method `getClassMetadata` to
   `getMetadataFor` and accepts arbitrary values (e.g. class names, objects,
   numbers etc.). In your implementation, you should throw a
   `NoSuchMetadataException` if you don't support metadata for the given value.

   Before:

   ```
   use Symfony\Component\Validator\Mapping\ClassMetadataFactoryInterface;

   class MyMetadataFactory implements ClassMetadataFactoryInterface
   {
       public function getClassMetadata($class)
       {
           // ...
       }
   }
   ```

   After:

   ```
   use Symfony\Component\Validator\MetadataFactoryInterface;
   use Symfony\Component\Validator\Exception\NoSuchMetadataException;

   class MyMetadataFactory implements MetadataFactoryInterface
   {
       public function getMetadataFor($value)
       {
           if (is_object($value)) {
               $value = get_class($value);
           }

           if (!is_string($value) || (!class_exists($value) && !interface_exists($value))) {
               throw new NoSuchMetadataException(...);
           }

           // ...
       }
   }
   ```

   The return value of `ValidatorInterface::getMetadataFactory()` was also
   changed to return `MetadataFactoryInterface`. Make sure to replace calls to
   `getClassMetadata` by `getMetadataFor` on the return value of this method.

   Before:

   ```
   $metadataFactory = $validator->getMetadataFactory();
   $metadata = $metadataFactory->getClassMetadata('Vendor\MyClass');
   ```

   After:

   ```
   $metadataFactory = $validator->getMetadataFactory();
   $metadata = $metadataFactory->getMetadataFor('Vendor\MyClass');
   ```

 * The class `GraphWalker` and the accessor `ExecutionContext::getGraphWalker()`
   were deprecated and will be removed in Symfony 2.3. You should use the
   methods `ExecutionContextInterface::validate()` and
   `ExecutionContextInterface::validateValue()` instead.

   Before:

   ```
   use Symfony\Component\Validator\ExecutionContext;

   public function validateCustomLogic(ExecutionContext $context)
   {
       if (/* ... */) {
           $path = $context->getPropertyPath();
           $group = $context->getGroup();

           if (!empty($path)) {
               $path .= '.';
           }

           $context->getGraphWalker()->walkReference($someObject, $group, $path . 'myProperty', false);
       }
   }
   ```

   After:

   ```
   use Symfony\Component\Validator\ExecutionContextInterface;

   public function validateCustomLogic(ExecutionContextInterface $context)
   {
       if (/* ... */) {
           $context->validate($someObject, 'myProperty');
       }
   }
   ```

 * The method `ExecutionContext::addViolationAtSubPath()` was deprecated and
   will be removed in Symfony 2.3. You should use `addViolationAt()` instead.

   Before:

   ```
   use Symfony\Component\Validator\ExecutionContext;

   public function validateCustomLogic(ExecutionContext $context)
   {
       if (/* ... */) {
           $context->addViolationAtSubPath('myProperty', 'This value is invalid');
       }
   }
   ```

   After:

   ```
   use Symfony\Component\Validator\ExecutionContextInterface;

   public function validateCustomLogic(ExecutionContextInterface $context)
   {
       if (/* ... */) {
           $context->addViolationAt('myProperty', 'This value is invalid');
       }
   }
   ```

 * The methods `ExecutionContext::getCurrentClass()`, `ExecutionContext::getCurrentProperty()`
   and `ExecutionContext::getCurrentValue()` were deprecated and will be removed
   in Symfony 2.3. Use the methods `getClassName()`, `getPropertyName()` and
   `getValue()` instead.

   Before:

   ```
   use Symfony\Component\Validator\ExecutionContext;

   public function validateCustomLogic(ExecutionContext $context)
   {
       $class = $context->getCurrentClass();
       $property = $context->getCurrentProperty();
       $value = $context->getCurrentValue();

       // ...
   }
   ```

   After:

   ```
   use Symfony\Component\Validator\ExecutionContextInterface;

   public function validateCustomLogic(ExecutionContextInterface $context)
   {
       $class = $context->getClassName();
       $property = $context->getPropertyName();
       $value = $context->getValue();

       // ...
   }
   ```
