<?php

declare(strict_types=1);

namespace SlackApiBundle\Exception;

final class SlackChanelNotFoundException extends \RuntimeException
{
    public function __construct(string $channelName)
    {
        parent::__construct(sprintf('Slack channel with name "%s" not found', $channelName));
    }
}
