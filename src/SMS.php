<?php declare(strict_types = 1);

namespace Lib24X;

class SMS
{
    private $text;
    private $sender;
    private $dateTime;
    private $replyAddress;
    private $userField;

    public function __construct(
        string $text,
        string $sender = null,
        string $replyAddress = null,
        \DateTime $dateTime = null,
        string $userField = null
    ) {
        $this->setText($text);
        $this->setSender($sender ?? '');
        $this->setReplyAddress($replyAddress ?? '');
        $this->setDateTime($dateTime ?? new \DateTime);
        $this->setUserField($userField ?? '');
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text)
    {
        $this->text = $text;
    }

    public function getSender(): string
    {
        return $this->sender;
    }

    public function setSender(string $sender)
    {
        $this->sender = $sender;
    }

    public function getDateTime(): \DateTime
    {
        return $this->dateTime;
    }

    public function setDateTime(\DateTime $dateTime)
    {
        $this->dateTime = $dateTime;
    }

    public function getReplyAddress(): string
    {
        return $this->replyAddress;
    }

    public function setReplyAddress(string $replyAddress)
    {
        if ($replyAddress !== '' && !filter_var($replyAddress, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email address');
        }

        $this->replyAddress = $replyAddress;
    }

    public function getUserField(): string
    {
        return $this->userField;
    }

    public function setUserField(string $userField)
    {
        if (strlen($userField) > 50) {
            throw new \InvalidArgumentException('User field cannot be more than 50 characters');
        }

        $this->userField = $userField;
    }

    public function __toString()
    {
        return $this->text;
    }

    public function getMessageCount(): int
    {
        $length = strlen($this->text);

        return $length > 160
            ? (int)(ceil($length) / 153)
            : 1;
    }
}
