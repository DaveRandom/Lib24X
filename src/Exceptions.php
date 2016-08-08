<?php declare(strict_types = 1);

namespace Lib24X;

abstract class Exception extends \Exception {}

abstract class ServerErrorException extends Exception {}

class InvalidAuthContextException extends Exception {}
class InvalidDestinationException extends Exception {}
class InvalidSenderException extends Exception {}
class InvalidResponseException extends ServerErrorException {}
class ServerErrorResponseException extends ServerErrorException {}
class SoapFaultException extends ServerErrorException {}
class UnexpectedErrorException extends ServerErrorException {}
