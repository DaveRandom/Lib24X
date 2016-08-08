<?php declare(strict_types = 1);

namespace Lib24X;

class Service
{
    const DEFAULT_WSDL_URL     = 'http://www.24x.com/WS/SendSMS/service.asmx?WSDL';
    const DEFAULT_SOAP_OPTIONS = ['soap_version' => SOAP_1_2];

    const OP_WSDL_URL          = 'wsdl_url';
    const OP_SOAP_OPTIONS      = 'soap_options';
    const OP_CHECK_CREDENTIALS = 'check_credentials';
    const OP_DEFAULT_SENDER    = 'default_sender';

    private $options = [
        self::OP_WSDL_URL          => self::DEFAULT_WSDL_URL,
        self::OP_SOAP_OPTIONS      => self::DEFAULT_SOAP_OPTIONS,
        self::OP_DEFAULT_SENDER    => 'Lib24X',
        self::OP_CHECK_CREDENTIALS => false,
    ];

    /**
     * @var AuthContext
     */
    private $authContext;

    /**
     * @var \SoapClient
     */
    private $soapClient;

    private function getActiveAuthContext(AuthContext $authContext = null): AuthContext
    {
        $active = $authContext ?? $this->authContext;

        if (!$active) {
            throw new InvalidAuthContextException('No auth context supplied and no default auth context defined');
        }

        return $active;
    }

    /**
     * @param string $method
     * @param AuthContext $authContext
     * @param array $params
     * @return string
     * @throws InvalidResponseException
     * @throws ServerErrorResponseException
     * @throws SoapFaultException
     * @throws UnexpectedErrorException
     */
    private function sendSoapRequest(string $method, AuthContext $authContext, array $params = []): string
    {
        try {
            $result = $this->soapClient->__soapCall($method, [
                $method => array_merge([
                    'UserName' => $authContext->getUsername(),
                    'Password' => $authContext->getPassword(),
                ], $params)
            ]);
        } catch (\SoapFault $e) {
            throw new SoapFaultException('SOAP Fault: ' . $e->getMessage(), $e->getCode(), $e);
        } catch (\Throwable $e) {
            throw new UnexpectedErrorException('Unexpected error: ' . $e->getMessage(), $e->getCode(), $e);
        }

        if (!isset($result->{$method . 'Result'})) {
            throw new InvalidResponseException($method . 'Result property is not present in response');
        }

        $result = (string)$result->{$method . 'Result'};

        if (preg_match('/^0*(\d+)\s+-\s+(.+)$/', $result, $match)) {
            throw new ServerErrorResponseException($match[2], (int)$match[1]);
        }

        return (string)$result;
    }

    private function getSenderString($sender = null): string
    {
        if (empty($sender)) {
            $sender = $this->options[self::OP_DEFAULT_SENDER];
        }

        if (!preg_match('/^([a-z0-9]{1,11}|[0-9]{1,12})$/i', (string)$sender, $match)) {
            throw new InvalidSenderException($sender);
        }

        return $match[1];
    }

    private function normalizePhoneNumber($phoneNumber): string
    {
        $result = preg_replace('/[^0-9+]+/', '', (string)$phoneNumber);

        if (!preg_match('/\+?[0-9]+/', $result)) {
            throw new InvalidDestinationException($phoneNumber);
        }

        return $result;
    }

    private function normalizeDestination($destination): array
    {
        if (!is_array($destination) && !($destination instanceof \Traversable)) {
            return [$this->normalizePhoneNumber($destination)];
        }

        $result = [];

        foreach ($destination as $phoneNumber) {
            $result[] = $this->normalizePhoneNumber($phoneNumber);
        }

        return $result;
    }

    private function validateServerInt(string $value): int
    {
        if (!preg_match('/^\d+$/', $value)) {
            throw new UnexpectedErrorException('Unexpected response string format (expecting integer): ' . $value);
        }

        return (int)$value;
    }

    public function __construct(AuthContext $authContext = null, array $options = [])
    {
        if (isset($options['soap_options'])) {
            $this->options['soap_options'] = array_merge($this->options['soap_options'], $options['soap_options']);
            unset($options['soap_options']);
        }

        $this->options = array_merge($this->options, $options);
        $this->soapClient = new \SoapClient($this->options['wsdl_url'], $this->options['soap_options']);

        if (!$authContext) {
            return;
        }

        $this->authContext = $authContext;

        if ($this->options['check_credentials']) {
            $this->checkCredentials($authContext);
        }
    }

    public function checkCredentials(AuthContext $authContext)
    {
        $result = $this->sendSoapRequest('CheckLoginDetails', $authContext);

        if (!(int)$result) {
            throw new InvalidAuthContextException('Invalid credentials');
        }
    }

    /**
     * @param AuthContext $authContext
     * @return int
     * @throws SoapFaultException
     * @throws InvalidAuthContextException
     * @throws InvalidResponseException
     * @throws ServerErrorResponseException
     * @throws UnexpectedErrorException
     */
    public function getAvailableCredits(AuthContext $authContext = null): int
    {
        $result = $this->sendSoapRequest('CreditsAvailable', $this->getActiveAuthContext($authContext));

        if (!preg_match('/^\d+$/', $result)) {
            throw new UnexpectedErrorException('Unexpected response string format: ' . $result);
        }

        return (int)$result;
    }

    public function sendText(string $text, $destination, AuthContext $authContext = null): int
    {
        $messageId = $this->sendSoapRequest('SendSimpleSMS', $this->getActiveAuthContext($authContext), [
            'Mobiles' => implode(',', $this->normalizeDestination($destination)),
            'MessageFrom' => $this->getSenderString(),
            'MessageToSend' => $text,
        ]);

        return $this->validateServerInt($messageId);
    }

    public function sendSMS(SMS $sms, $destination, AuthContext $authContext = null): int
    {
        $text = $sms->getText();
        $sender = $sms->getSender();
        $dateTime = $sms->getDateTime();
        $replyAddress = $sms->getReplyAddress();
        $userField = $sms->getUserField();

        if (empty($sender) && empty($dateTime) && empty($replyAddress) && empty($userField)) {
            return $this->sendText($text, $destination, $authContext);
        }

        $messageId = $this->sendSoapRequest('SendFullSMS', $this->getActiveAuthContext($authContext), [
            'Mobiles' => implode(',', $this->normalizeDestination($destination)),
            'MessageFrom' => $this->getSenderString($sender),
            'MessageToSend' => $text,
            'DateTimeToSend' => $dateTime->format('Y-m-d\TH:i:s'),
            'EmailAddressToSendReplies' => $replyAddress,
            'UserField' => $userField,
        ]);

        return $this->validateServerInt($messageId);
    }
}
