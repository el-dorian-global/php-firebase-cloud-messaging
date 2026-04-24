<?php
namespace veldor\PhpFirebaseCloudMessaging\Recipient;

class Topic extends Recipient
{
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
}