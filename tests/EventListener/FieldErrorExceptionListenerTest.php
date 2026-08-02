<?php

namespace App\Tests\EventListener;

use App\EventListener\FieldErrorExceptionListener;
use App\Exception\CannotAssignSuperAdminRoleException;
use App\Exception\InvalidDocumentNumberException;
use App\Enum\DocumentType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class FieldErrorExceptionListenerTest extends TestCase
{
    private FieldErrorExceptionListener $listener;

    protected function setUp(): void
    {
        $this->listener = new FieldErrorExceptionListener();
    }

    public function testHandlingExceptionWithInvalidFieldSetsJsonResponseWithFieldAndStatus(): void
    {
        $event = $this->exceptionEvent(new InvalidDocumentNumberException(DocumentType::DNI, '999'));

        ($this->listener)($event);

        $response = $event->getResponse();
        self::assertNotNull($response);
        self::assertSame(400, $response->getStatusCode());
        self::assertSame(
            [
                'message' => 'Error en alguno de los campos',
                'errors' => [['field' => 'documentNumber', 'message' => '"999" no es un número de DNI válido.']],
            ],
            json_decode($response->getContent(), true),
        );
    }

    public function testHandlingExceptionWithInvalidFieldUsesItsOwnHttpStatus(): void
    {
        $event = $this->exceptionEvent(new CannotAssignSuperAdminRoleException());

        ($this->listener)($event);

        $response = $event->getResponse();
        self::assertNotNull($response);
        self::assertSame(400, $response->getStatusCode());
        self::assertSame('role', json_decode($response->getContent(), true)['errors'][0]['field']);
    }

    public function testHandlingValidationFailedExceptionSetsJsonResponseWithOneErrorPerViolation(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation('This value should not be blank.', null, [], null, 'email', ''),
            new ConstraintViolation('This value is too short.', null, [], null, 'name', 'a'),
        ]);
        $validationFailedException = new ValidationFailedException(null, $violations);
        $event = $this->exceptionEvent(new HttpException(422, 'Validation failed.', $validationFailedException));

        ($this->listener)($event);

        $response = $event->getResponse();
        self::assertNotNull($response);
        self::assertSame(422, $response->getStatusCode());
        self::assertSame(
            [
                'message' => 'Error en alguno de los campos',
                'errors' => [
                    ['field' => 'email', 'message' => 'This value should not be blank.'],
                    ['field' => 'name', 'message' => 'This value is too short.'],
                ],
            ],
            json_decode($response->getContent(), true),
        );
    }

    public function testHandlingValidationFailedExceptionUsesTheWrappingExceptionStatusCode(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation('This value should be of type string.', null, [], null, 'name', null),
        ]);
        $validationFailedException = new ValidationFailedException(null, $violations);
        $event = $this->exceptionEvent(new HttpException(404, 'Not found.', $validationFailedException));

        ($this->listener)($event);

        self::assertSame(404, $event->getResponse()?->getStatusCode());
    }

    public function testHandlingUnrelatedExceptionDoesNotSetResponse(): void
    {
        $event = $this->exceptionEvent(new \RuntimeException('Something else went wrong.'));

        ($this->listener)($event);

        self::assertNull($event->getResponse());
    }

    private function exceptionEvent(\Throwable $throwable): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->createStub(HttpKernelInterface::class),
            Request::create('/'),
            HttpKernelInterface::MAIN_REQUEST,
            $throwable,
        );
    }
}
