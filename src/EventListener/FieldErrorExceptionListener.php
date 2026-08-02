<?php

namespace App\EventListener;

use App\Dto\Error\FieldError;
use App\Exception\HasInvalidFieldInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Exception\ValidationFailedException;

#[AsEventListener(event: KernelEvents::EXCEPTION)]
final class FieldErrorExceptionListener
{
    private const string ERROR_MESSAGE = 'Error en alguno de los campos';

    public function __invoke(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();

        if ($throwable->getPrevious() instanceof ValidationFailedException) {
            $statusCode = $throwable instanceof HttpExceptionInterface ? $throwable->getStatusCode() : Response::HTTP_UNPROCESSABLE_ENTITY;

            $this->respondWithErrors($event, $this->errorsFromViolations($throwable->getPrevious()), $statusCode);

            return;
        }

        if ($throwable instanceof HasInvalidFieldInterface) {
            $this->respondWithErrors($event, [new FieldError($throwable->getField(), $throwable->getMessage())], $this->statusCodeFor($throwable));
        }
    }

    /**
     * @return list<FieldError>
     */
    private function errorsFromViolations(ValidationFailedException $exception): array
    {
        $errors = [];

        foreach ($exception->getViolations() as $violation) {
            $errors[] = new FieldError((string) $violation->getPropertyPath(), (string) $violation->getMessage());
        }

        return $errors;
    }

    /**
     * @param list<FieldError> $errors
     */
    private function respondWithErrors(ExceptionEvent $event, array $errors, int $statusCode): void
    {
        $event->setResponse(new JsonResponse(['message' => self::ERROR_MESSAGE, 'errors' => $errors], $statusCode));
    }

    private function statusCodeFor(\Throwable $throwable): int
    {
        $attribute = (new \ReflectionClass($throwable))->getAttributes(WithHttpStatus::class)[0] ?? null;

        return $attribute?->newInstance()->statusCode ?? Response::HTTP_BAD_REQUEST;
    }
}
